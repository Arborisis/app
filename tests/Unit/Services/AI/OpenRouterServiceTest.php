<?php

use App\Enums\SoundStatus;
use App\Enums\SoundVisibility;
use App\Models\BirdnetDetection;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Services\AI\OpenRouterService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new OpenRouterService();
    config(['services.openrouter.api_key' => 'test-key']);
});

it('generates structured json script', function () {
    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Forêt Mystique',
                            'description' => 'Une immersion sonore',
                            'segments' => [
                                ['type' => 'intro', 'text' => 'Bienvenue', 'sound_id' => null, 'transition_duration_seconds' => null],
                                ['type' => 'sound_context', 'text' => 'Écoutez ceci', 'sound_id' => 1, 'transition_duration_seconds' => 10],
                                ['type' => 'outro', 'text' => 'Au revoir', 'sound_id' => null, 'transition_duration_seconds' => null],
                            ],
                            'estimated_duration_seconds' => 180,
                        ]),
                    ],
                ],
            ],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 200, 'total_cost' => 0.002],
        ], 200),
    ]);

    $sound = (object) [
        'id' => 1,
        'title' => 'Forest Morning',
        'user' => (object) ['name' => 'Jane'],
        'description' => 'A morning in the forest',
        'duration' => 120,
        'soundLocation' => null,
        'category' => null,
    ];

    $result = $this->service->generatePodcastScript([$sound], ['theme' => 'Test', 'angle' => 'Angle', 'research_summary' => 'Research']);

    expect($result)->toBeArray()
        ->and($result['script']['title'])->toBe('Forêt Mystique')
        ->and($result['cost_cents'])->toBe(0);
});

it('returns null on api failure', function () {
    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([], 500),
    ]);

    $result = $this->service->generatePodcastScript([], ['theme' => 'Test']);

    expect($result)->toBeNull();
});

it('returns null on invalid json schema', function () {
    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode(['invalid' => true]),
                    ],
                ],
            ],
            'usage' => [],
        ], 200),
    ]);

    $result = $this->service->generatePodcastScript([], ['theme' => 'Test']);

    expect($result)->toBeNull();
});

it('returns null when api key is missing', function () {
    config(['services.openrouter.api_key' => null]);

    $result = $this->service->generatePodcastScript([], ['theme' => 'Test']);

    expect($result)->toBeNull();
});

it('includes BirdNET and spectrogram analysis in podcast prompts', function () {
    $sound = Sound::factory()->create([
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
        'title' => 'Aube en lisière',
        'duration' => 120,
    ]);

    $analysis = SoundAnalysis::factory()->create([
        'sound_id' => $sound->id,
        'spectrogram_r2_key' => 'sounds/analysis/'.$sound->id.'/spectrogram.webp',
        'spectral_centroid' => 5400,
        'spectral_rolloff' => 9100,
        'features_json' => [
            'event_density' => 1.4,
            'tempo_bpm' => 0,
            'harmonic_ratio' => 0.72,
        ],
    ]);

    BirdnetDetection::query()->create([
        'sound_analysis_id' => $analysis->id,
        'sound_id' => $sound->id,
        'scientific_name' => 'Erithacus rubecula',
        'common_name' => 'Rougegorge familier',
        'confidence' => 0.91,
        'start_time' => 4.2,
        'end_time' => 8.8,
        'frequency_min' => 2500,
        'frequency_max' => 7600,
    ]);

    Http::fake(function ($request) use ($sound) {
        $body = json_decode($request->body(), true);
        $prompt = $body['messages'][1]['content'] ?? '';

        expect($prompt)->toContain('Rougegorge familier')
            ->and($prompt)->toContain('spectrogram_available')
            ->and($prompt)->toContain('spectral_centroid_hz');

        return Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Lisière chantée',
                            'description' => 'Une écoute guidée.',
                            'segments' => [
                                ['type' => 'intro', 'text' => 'Bienvenue', 'sound_id' => null, 'transition_duration_seconds' => null],
                                ['type' => 'sound_context', 'text' => 'Écoutons ce chant probable.', 'sound_id' => $sound->id, 'transition_duration_seconds' => 10],
                                ['type' => 'outro', 'text' => 'À bientôt', 'sound_id' => null, 'transition_duration_seconds' => null],
                            ],
                            'estimated_duration_seconds' => 180,
                        ]),
                    ],
                ],
            ],
            'usage' => [],
        ], 200);
    });

    $sound = Sound::with(['user', 'category', 'environment', 'soundLocation', 'soundAnalysis.birdnetDetections'])
        ->findOrFail($sound->id);

    $result = $this->service->generatePodcastScript([$sound], [
        'theme' => 'Lisière',
        'angle' => 'Lecture acoustique',
        'research_summary' => 'Contexte',
    ]);

    expect($result)->toBeArray();
});

it('requests a long-form podcast episode instead of a short capsule', function () {
    config([
        'radio.podcast.min_duration' => 600,
        'radio.podcast.max_duration' => 1200,
    ]);

    Http::fake(function ($request) {
        $body = json_decode($request->body(), true);
        $prompt = $body['messages'][1]['content'] ?? '';
        $system = $body['messages'][0]['content'] ?? '';

        expect($system)->toContain('podcasts documentaires')
            ->and($prompt)->toContain('vrai épisode de podcast narratif')
            ->and($prompt)->toContain('600 à 1200 secondes')
            ->and($prompt)->toContain('"type": "chapter"')
            ->and($prompt)->toContain('au moins 2 segments "chapter"')
            ->and($prompt)->not->toContain('capsule radio de 2 à 4 minutes');

        return Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Le grand paysage',
                            'description' => 'Un épisode long.',
                            'segments' => [
                                ['type' => 'intro', 'text' => 'Bienvenue', 'sound_id' => null, 'transition_duration_seconds' => null],
                                ['type' => 'chapter', 'text' => 'Chapitre un', 'sound_id' => null, 'transition_duration_seconds' => null],
                                ['type' => 'sound_context', 'text' => 'Écoutons.', 'sound_id' => 1, 'transition_duration_seconds' => 30],
                                ['type' => 'outro', 'text' => 'À bientôt', 'sound_id' => null, 'transition_duration_seconds' => null],
                            ],
                            'estimated_duration_seconds' => 900,
                        ]),
                    ],
                ],
            ],
            'usage' => [],
        ], 200);
    });

    $sound = (object) [
        'id' => 1,
        'title' => 'Forest Morning',
        'user' => (object) ['name' => 'Jane'],
        'description' => 'A morning in the forest',
        'duration' => 120,
        'soundLocation' => null,
        'category' => null,
        'environment' => null,
    ];

    $result = $this->service->generatePodcastScript([$sound], [
        'theme' => 'Écoute longue',
        'angle' => 'Un vrai épisode',
        'research_summary' => 'Contexte',
    ]);

    expect($result)->toBeArray()
        ->and($result['script']['estimated_duration_seconds'])->toBe(900);
});

it('builds flash prompts with a dynamic editorial brief instead of the static bulletin text', function () {
    Http::fake(function ($request) {
        $body = json_decode($request->body(), true);
        $prompt = $body['messages'][1]['content'] ?? '';

        expect($prompt)->toContain('Ligne éditoriale de cette génération')
            ->and($prompt)->toContain('Snapshot dynamique de la plateforme')
            ->and($prompt)->toContain('Fenêtre de diffusion visée')
            ->and($prompt)->not->toContain('Tu prépares le flash info d\'Arborisis Radio pour les prochaines deux heures')
            ->and($prompt)->not->toContain('"title": "Flash Info Arborisis');

        return Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Lisière du matin',
                            'description' => 'Un flash vivant.',
                            'text' => str_repeat('Une phrase douce sur les sons récents de la plateforme. ', 8),
                            'estimated_duration_seconds' => 120,
                            'word_count' => 100,
                        ]),
                    ],
                ],
            ],
            'usage' => [],
        ], 200);
    });

    $result = $this->service->generateFlashScript([
        'total_sounds' => 2,
        'total_creators' => 1,
        'new_sounds' => [
            ['id' => 77, 'title' => 'Le bois', 'creator' => 'San2Stic'],
        ],
        'new_users' => [],
        'popular_sounds' => [
            ['title' => 'Le bois', 'creator' => 'San2Stic'],
        ],
        'categories' => [],
    ]);

    expect($result)->toBeArray()
        ->and($result['script']['title'])->toBe('Lisière du matin');
});
