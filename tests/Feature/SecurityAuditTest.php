<?php

declare(strict_types=1);

use App\Enums\ChatRoomType;
use App\Enums\SoundVisibility;
use App\Enums\UserRole;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Sound;
use App\Models\User;
use App\Services\Audio\AudioDurationService;
use App\Services\Blog\BlogHtmlSanitizer;
use App\Services\Discord\DiscordNotificationService;
use App\Services\Scientific\ListeningPointService;
use App\Services\Sound\SoundUploadService;
use Illuminate\Http\UploadedFile;

it('prevents a standard user from posting in an admin only chat room', function () {
    $user = User::factory()->create(['role' => UserRole::User]);
    $moderator = User::factory()->create(['role' => UserRole::Moderator]);
    $room = ChatRoom::create([
        'name' => 'Moderation',
        'slug' => 'moderation',
        'description' => null,
        'type' => ChatRoomType::AdminOnly,
        'created_by' => $moderator->id,
    ]);

    $this->actingAs($user)
        ->postJson("/chat/rooms/{$room->slug}/messages", ['body' => 'hello'])
        ->assertForbidden();

    expect(ChatMessage::query()->count())->toBe(0);
});

it('prevents a user from posting in a public chat room before joining', function () {
    $user = User::factory()->create(['role' => UserRole::User]);
    $room = ChatRoom::create([
        'name' => 'Public',
        'slug' => 'public',
        'description' => null,
        'type' => ChatRoomType::Public,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->postJson("/chat/rooms/{$room->slug}/messages", ['body' => 'hello'])
        ->assertForbidden();

    expect(ChatMessage::query()->count())->toBe(0);
});

it('only returns public sounds from the internal Discord search endpoint', function () {
    config(['services.discord.internal_api_token' => 'test-token']);

    $public = Sound::factory()->create(['title' => 'Visible dawn chorus']);
    $private = Sound::factory()->private()->create(['title' => 'Visible private field note']);

    $response = $this
        ->withHeader('X-Internal-Token', 'test-token')
        ->getJson('/api/internal/discord/sounds/search?q=Visible');

    $response->assertOk();

    $ids = collect($response->json())->pluck('id');
    expect($ids)->toContain($public->id);
    expect($ids)->not->toContain($private->id);
});

it('does not return a private sound from the internal Discord detail endpoint', function () {
    config(['services.discord.internal_api_token' => 'test-token']);

    $sound = Sound::factory()->create(['visibility' => SoundVisibility::Private]);

    $this
        ->withHeader('X-Internal-Token', 'test-token')
        ->getJson("/api/internal/discord/sounds/{$sound->id}")
        ->assertNotFound();
});

it('removes scripts dangerous attributes and unsafe urls from blog html', function () {
    $dirty = '<article><h2 onclick="alert(1)">Titre</h2><script>alert(1)</script><p><a href="javascript:alert(1)" data-arborisis-type="sound" data-arborisis-id="1" onclick="alert(1)">son</a></p></article>';

    $clean = app(BlogHtmlSanitizer::class)->sanitize($dirty);

    expect($clean)
        ->toContain('<article>')
        ->toContain('data-arborisis-type="sound"')
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:');
});

it('generates uploaded audio names from the detected mime type instead of client extension', function () {
    $service = new SoundUploadService(
        mock(AudioDurationService::class),
        mock(DiscordNotificationService::class),
        mock(ListeningPointService::class),
    );
    $file = UploadedFile::fake()->create('field-recording.php', 32, 'audio/mpeg');

    $method = new ReflectionMethod(SoundUploadService::class, 'generateFileName');
    $method->setAccessible(true);

    $storedName = $method->invoke($service, $file);

    expect($storedName)->toEndWith('.mp3');
});
