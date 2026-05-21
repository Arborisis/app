<?php

use App\Enums\SoundStatus;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('displays the landing page', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('displays the sounds index page', function () {
    Sound::factory()->count(5)->create();

    $response = $this->get('/sounds');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Sounds/Index')
            ->has('sounds.data', 5)
        );
});

it('displays a single sound page', function () {
    $sound = Sound::factory()->create();

    $response = $this->get("/sounds/{$sound->slug}");

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Sounds/Show')
            ->where('sound.id', $sound->id)
        );
});

it('increments play count on sound view', function () {
    $sound = Sound::factory()->create(['play_count' => 10]);

    $this->get("/sounds/{$sound->slug}");

    expect($sound->fresh()->play_count)->toBe(11);
});

it('returns 404 for non-existent sound', function () {
    $response = $this->get('/sounds/non-existent-slug');

    $response->assertStatus(404);
});

it('displays the map page', function () {
    $response = $this->get('/map');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Map/Index'));
});

it('displays the transparency page', function () {
    $response = $this->get('/transparency');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Transparency'));
});

it('does not show unpublished sounds', function () {
    $published = Sound::factory()->create(['status' => SoundStatus::Published]);
    $draft = Sound::factory()->unpublished()->create();

    $response = $this->get('/sounds');

    $response->assertInertia(fn ($page) => $page
        ->has('sounds.data', 1)
        ->where('sounds.data.0.id', $published->id)
    );
});

it('allows authenticated users to access sound creation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/sounds/create');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Sounds/Create'));
});

it('redirects guests from sound creation', function () {
    $response = $this->get('/sounds/create');

    $response->assertRedirect('/login');
});

it('allows authenticated users to upload a sound', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/sounds', [
        'audio_file' => UploadedFile::fake()->create('morning.wav', 128, 'audio/wav'),
        'title' => 'Chant du matin',
        'description' => 'Test upload',
        'latitude' => 48.8566,
        'longitude' => 2.3522,
        'license' => 'cc_by',
        'visibility' => 'public',
    ]);

    $sound = Sound::where('title', 'Chant du matin')->first();

    expect($sound)->not->toBeNull();
    $response->assertRedirect(route('sounds.show', $sound->slug));
});
