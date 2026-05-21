<?php

declare(strict_types=1);

use App\Jobs\Agent\ProcessAgentChatJob;
use App\Jobs\Agent\UpdateUserAgentMemoryJob;
use App\Mail\ContactTicketReceived;
use App\Mail\ContactTicketSubmitted;
use App\Models\BirdnetDetection;
use App\Models\ContactTicket;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\User;
use App\Services\Agent\OpenRouterAgentService;
use App\Services\Agent\UserAgentMemoryService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('services.openrouter.api_key', 'test-openrouter-key');
    config()->set('services.openrouter.model', 'anthropic/claude-sonnet-4.6');
    config()->set('services.openrouter.base_url', 'https://openrouter.test');
});

it('returns a job id immediately', function () {
    Queue::fake();

    $response = $this->postJson('/api/ai-agent/chat', [
        'message' => 'Explique Arborisis',
        'history' => [
            ['role' => 'assistant', 'content' => 'Bonjour.'],
        ],
        'page' => [
            'url' => '/',
            'title' => 'Arborisis',
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', 'processing')
        ->assertJsonStructure(['job_id']);

    Queue::assertPushed(ProcessAgentChatJob::class, function (ProcessAgentChatJob $job) {
        return $job->message === 'Explique Arborisis';
    });
});

it('enforces Sylve daily chat quota before dispatching OpenRouter work', function () {
    Queue::fake();

    config()->set('services.arborisis_agent.rate_limit_per_minute', 10);
    config()->set('services.arborisis_agent.daily_quota', 2);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/ai-agent/chat', ['message' => 'Premier message'])
        ->assertOk();

    $this->actingAs($user)
        ->postJson('/api/ai-agent/chat', ['message' => 'Deuxième message'])
        ->assertOk();

    $this->actingAs($user)
        ->postJson('/api/ai-agent/chat', ['message' => 'Troisième message'])
        ->assertStatus(429)
        ->assertJsonPath('code', 'sylve_daily_quota_exceeded');

    Queue::assertPushed(ProcessAgentChatJob::class, 2);
});

it('enforces Sylve burst rate limit before dispatching OpenRouter work', function () {
    Queue::fake();

    config()->set('services.arborisis_agent.rate_limit_per_minute', 1);
    config()->set('services.arborisis_agent.daily_quota', 10);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/ai-agent/chat', ['message' => 'Premier message'])
        ->assertOk();

    $this->actingAs($user)
        ->postJson('/api/ai-agent/chat', ['message' => 'Deuxième message'])
        ->assertStatus(429)
        ->assertJsonPath('code', 'sylve_rate_limited');

    Queue::assertPushed(ProcessAgentChatJob::class, 1);
});

it('processes the chat job and calls OpenRouter with correct payload', function () {
    Storage::fake('local');

    $user = User::factory()->create([
        'name' => 'Camille Martin',
        'email' => 'camille@example.test',
    ]);

    Http::fake([
        'openrouter.test/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Bonjour Camille, je peux t’aider depuis ton espace Arborisis.',
                    ],
                ],
            ],
        ]),
    ]);

    $job = new ProcessAgentChatJob(
        jobId: 'test-job-1',
        message: 'Tu sais qui je suis ?',
        history: [],
        page: [],
        conversationId: null,
        userId: $user->id,
        location: null,
    );

    $job->handle(app(OpenRouterAgentService::class));

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer test-openrouter-key')
        && $request['model'] === 'anthropic/claude-sonnet-4.6'
        && $request['messages'][0]['role'] === 'system'
        && str_contains($request['messages'][0]['content'], 'Sylve')
        && $request['messages'][count($request['messages']) - 1]['content'] === 'Tu sais qui je suis ?');

    Storage::disk('local')->assertExists("agent-memory/users/{$user->id}/AGENT.md");

    expect(Storage::disk('local')->get("agent-memory/users/{$user->id}/USER.md"))
        ->not->toContain('camille@example.test')
        ->toContain('Prénom: Camille');
});

it('schedules a delayed memory update for authenticated completed chats', function () {
    Queue::fake([UpdateUserAgentMemoryJob::class]);
    Storage::fake('local');

    $user = User::factory()->create([
        'name' => 'Camille Martin',
    ]);

    Http::fake([
        'openrouter.test/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Camille, note ce protocole pour ta prochaine sortie.',
                    ],
                ],
            ],
        ]),
    ]);

    $job = new ProcessAgentChatJob(
        jobId: 'test-job-delayed-memory',
        message: 'Garde en mémoire mon protocole du matin',
        history: [],
        page: ['section' => 'dashboard', 'url' => '/dashboard'],
        conversationId: null,
        userId: $user->id,
        location: null,
    );

    $job->handle(app(OpenRouterAgentService::class));

    Queue::assertPushed(UpdateUserAgentMemoryJob::class, function (UpdateUserAgentMemoryJob $job): bool {
        return $job->message === 'Garde en mémoire mon protocole du matin'
            && $job->page['section'] === 'dashboard'
            && $job->debounceKey !== null
            && $job->debounceToken !== null
            && $job->delay !== null;
    });
});

it('skips stale delayed Sylve memory updates when a newer chat was scheduled', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Redis::setex('agent:memory:update:test', 60, 'newer-token');

    $job = new UpdateUserAgentMemoryJob(
        userId: $user->id,
        message: 'Ancienne demande',
        answer: 'Ancienne réponse',
        history: [],
        page: [],
        debounceKey: 'agent:memory:update:test',
        debounceToken: 'older-token',
    );

    $job->handle(app(UserAgentMemoryService::class));

    Storage::disk('local')->assertMissing("agent-memory/users/{$user->id}/MEMORY.md");
});

it('writes delayed Sylve conversation notes into user and memory files', function () {
    Storage::fake('local');

    $user = User::factory()->create([
        'name' => 'Camille Martin',
        'email' => 'camille@example.test',
    ]);

    $job = new UpdateUserAgentMemoryJob(
        userId: $user->id,
        message: 'Souviens-toi que je veux comparer le même point au lever du jour.',
        answer: 'Camille, la prochaine action est de refaire une prise au même point avec le même protocole.',
        history: [],
        page: ['section' => 'sounds', 'url' => '/sounds/create'],
    );

    $job->handle(app(UserAgentMemoryService::class));

    $memory = Storage::disk('local')->get("agent-memory/users/{$user->id}/MEMORY.md");
    $profile = Storage::disk('local')->get("agent-memory/users/{$user->id}/USER.md");

    expect($memory)
        ->toContain('Mémoire conversationnelle récente')
        ->toContain('comparer le même point au lever du jour')
        ->toContain('refaire une prise au même point')
        ->not->toContain('camille@example.test');

    expect($profile)
        ->toContain('Préférences observées via Sylve')
        ->toContain('Demande récente depuis sounds')
        ->not->toContain('camille@example.test');
});

it('gives Sylve a proactive user memory and field recording tools', function () {
    Storage::fake('local');

    $user = User::factory()->create([
        'name' => 'Camille Martin',
        'email' => 'camille@example.test',
    ]);

    $sound = Sound::factory()->for($user)->create([
        'title' => 'Aube humide au marais',
        'equipment' => null,
        'microphone_position' => null,
        'weather_notes' => null,
    ]);

    $analysis = SoundAnalysis::factory()->create(['sound_id' => $sound->id]);
    BirdnetDetection::factory()->create([
        'sound_analysis_id' => $analysis->id,
        'sound_id' => $sound->id,
        'common_name' => 'Rougegorge familier',
        'scientific_name' => 'Erithacus rubecula',
        'confidence' => 0.91,
    ]);

    Http::fake([
        'openrouter.test/chat/completions' => Http::sequence()
            ->push([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [
                                [
                                    'id' => 'call-brief',
                                    'type' => 'function',
                                    'function' => [
                                        'name' => 'get_user_field_recording_brief',
                                        'arguments' => '{}',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->push([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Camille, complète d’abord le matériel et la météo du marais, puis prépare une reprise comparative à l’aube.',
                        ],
                    ],
                ],
            ]),
    ]);

    $job = new ProcessAgentChatJob(
        jobId: 'test-job-brief',
        message: 'Que me conseilles-tu maintenant ?',
        history: [],
        page: [],
        conversationId: null,
        userId: $user->id,
        location: null,
    );

    $job->handle(app(OpenRouterAgentService::class));

    Http::assertSent(fn ($request): bool => str_contains($request['messages'][0]['content'], 'get_user_field_recording_brief')
        && str_contains($request['messages'][0]['content'], 'Mode assistant field recording')
        && collect($request['tools'])->contains(fn ($tool): bool => ($tool['function']['name'] ?? null) === 'get_field_session_plan'));

    Http::assertSent(fn ($request): bool => collect($request['messages'])->contains(fn ($message): bool => ($message['role'] ?? null) === 'tool'
        && str_contains((string) ($message['content'] ?? ''), 'Rougegorge familier')
        && str_contains((string) ($message['content'] ?? ''), 'metadata_gaps')));

    expect(Storage::disk('local')->get("agent-memory/users/{$user->id}/MEMORY.md"))
        ->toContain('Aube humide au marais')
        ->toContain('Rougegorge familier')
        ->toContain('Lacunes à surveiller');
});

it('forces a final answer when OpenRouter keeps requesting tools', function () {
    config()->set('services.openrouter.max_tool_steps', 2);

    $user = User::factory()->create([
        'name' => 'Camille Martin',
    ]);

    Http::fake([
        'openrouter.test/chat/completions' => Http::sequence()
            ->push([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call-health',
                            'type' => 'function',
                            'function' => [
                                'name' => 'get_api_health',
                                'arguments' => '{}',
                            ],
                        ]],
                    ],
                ]],
            ])
            ->push([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call-brief',
                            'type' => 'function',
                            'function' => [
                                'name' => 'get_user_field_recording_brief',
                                'arguments' => '{}',
                            ],
                        ]],
                    ],
                ]],
            ])
            ->push([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call-loop',
                            'type' => 'function',
                            'function' => [
                                'name' => 'get_latest_blog_posts',
                                'arguments' => '{"limit": 1}',
                            ],
                        ]],
                    ],
                ]],
            ])
            ->push([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Camille, j’ai assez d’éléments pour répondre sans appeler d’autre outil.',
                    ],
                ]],
            ]),
        '*' => Http::response(['ok' => true]),
    ]);

    $job = new ProcessAgentChatJob(
        jobId: 'test-job-tool-limit',
        message: 'Fais une réponse complète avec plusieurs infos',
        history: [],
        page: [],
        conversationId: null,
        userId: $user->id,
        location: null,
    );

    $job->handle(app(OpenRouterAgentService::class));

    Http::assertSentCount(5);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'openrouter.test/chat/completions')
        && collect($request['messages'])->contains(fn ($message): bool => ($message['role'] ?? null) === 'system'
            && str_contains((string) ($message['content'] ?? ''), 'limite d appels outils'))
        && ! array_key_exists('tools', $request->data()));

    $payload = json_decode((string) Redis::get('agent:chat:test-job-tool-limit'), true);

    expect($payload['status'])->toBe('completed')
        ->and($payload['answer'])->toContain('sans appeler d’autre outil')
        ->and($payload['tool_calls'])->toHaveCount(2);
});

it('passes user location to OpenRouter when provided', function () {
    $user = User::factory()->create([
        'name' => 'Léo Dupont',
        'email' => 'leo@example.test',
    ]);

    Http::fake([
        'openrouter.test/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Voici les points proches de toi.',
                    ],
                ],
            ],
        ]),
    ]);

    $job = new ProcessAgentChatJob(
        jobId: 'test-job-2',
        message: 'Qu’est-ce que je peux enregistrer près d’ici ?',
        history: [],
        page: [],
        conversationId: null,
        userId: $user->id,
        location: ['lat' => 48.8566, 'lng' => 2.3522, 'accuracy' => 20],
    );

    $job->handle(app(OpenRouterAgentService::class));

    Http::assertSent(fn ($request): bool => str_contains($request['messages'][0]['content'], 'latitude 48.8566')
        && str_contains($request['messages'][0]['content'], 'longitude 2.3522'));
});

it('rejects invalid location data', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/ai-agent/chat', [
            'message' => 'Test',
            'location' => [
                'lat' => 999,
                'lng' => 2.3522,
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['location.lat']);
});

it('returns chat status from redis when completed', function () {
    $jobId = 'test-status-job';
    Redis::setex("agent:chat:{$jobId}", 60, json_encode([
        'status' => 'completed',
        'answer' => 'Réponse test',
        'sources' => ['Test'],
    ]));

    $this->getJson("/api/ai-agent/status/{$jobId}")
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('answer', 'Réponse test');
});

it('returns processing status when result is not yet in redis', function () {
    $jobId = 'test-pending-job';

    $this->getJson("/api/ai-agent/status/{$jobId}")
        ->assertOk()
        ->assertJsonPath('status', 'processing');
});

it('creates a support ticket via Sylve for authenticated users', function () {
    Storage::fake('local');
    Mail::fake();

    $user = User::factory()->create([
        'name' => 'Camille Martin',
        'email' => 'camille@example.test',
    ]);

    Http::fake([
        'openrouter.test/chat/completions' => Http::sequence()
            ->push([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [
                                [
                                    'id' => 'call-ticket',
                                    'type' => 'function',
                                    'function' => [
                                        'name' => 'create_support_ticket',
                                        'arguments' => json_encode([
                                            'subject' => 'Problème de connexion',
                                            'message' => 'Je ne peux plus me connecter depuis ce matin.',
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->push([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'J\'ai créé ton ticket de support. L\'équipe te répondra bientôt.',
                        ],
                    ],
                ],
            ]),
    ]);

    $job = new ProcessAgentChatJob(
        jobId: 'test-job-ticket',
        message: 'J\'ai un problème de connexion',
        history: [
            ['role' => 'user', 'content' => 'J\'ai un problème de connexion'],
        ],
        page: [],
        conversationId: null,
        userId: $user->id,
        location: null,
    );

    $job->handle(app(OpenRouterAgentService::class));

    $payload = json_decode((string) Redis::get('agent:chat:test-job-ticket'), true);

    expect($payload['status'])->toBe('completed')
        ->and($payload['tool_calls'])->toHaveCount(1)
        ->and($payload['tool_calls'][0]['tool'])->toBe('create_support_ticket')
        ->and($payload['tool_calls'][0]['ok'])->toBeTrue();

    Http::assertSent(fn ($request): bool => collect($request['tools'] ?? [])
        ->contains(fn ($tool): bool => ($tool['function']['name'] ?? null) === 'create_support_ticket'));

    $ticket = ContactTicket::query()
        ->where('email', 'camille@example.test')
        ->where('type', 'support')
        ->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->name)->toBe('Camille Martin')
        ->and($ticket->subject)->toBe('Problème de connexion')
        ->and($ticket->status->value)->toBe('new')
        ->and($ticket->user_id)->toBe($user->id);

    Mail::assertQueued(ContactTicketSubmitted::class);
    Mail::assertQueued(ContactTicketReceived::class);
});

it('requires name and email for guest support tickets via Sylve', function () {
    Storage::fake('local');

    Http::fake([
        'openrouter.test/chat/completions' => Http::sequence()
            ->push([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [
                                [
                                    'id' => 'call-ticket-guest',
                                    'type' => 'function',
                                    'function' => [
                                        'name' => 'create_support_ticket',
                                        'arguments' => json_encode([
                                            'subject' => 'Problème de connexion',
                                            'message' => 'Je ne peux plus me connecter.',
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->push([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'J\'ai besoin de ton nom et de ton email pour créer le ticket.',
                        ],
                    ],
                ],
            ]),
    ]);

    $job = new ProcessAgentChatJob(
        jobId: 'test-job-ticket-guest',
        message: 'J\'ai un problème',
        history: [],
        page: [],
        conversationId: null,
        userId: null,
        location: null,
    );

    $job->handle(app(OpenRouterAgentService::class));

    $payload = json_decode((string) Redis::get('agent:chat:test-job-ticket-guest'), true);

    expect($payload['status'])->toBe('completed')
        ->and($payload['tool_calls'])->toHaveCount(1)
        ->and($payload['tool_calls'][0]['tool'])->toBe('create_support_ticket')
        ->and($payload['tool_calls'][0]['ok'])->toBeFalse()
        ->and($payload['tool_calls'][0]['result']['error'])->toBe('missing_fields');
});
