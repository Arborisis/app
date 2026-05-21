<?php

use App\Models\BirdnetDetection;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\SoundFile;
use App\Models\SoundLocation;
use App\Models\User;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('filesystems.disks.r2.url', 'https://media.arborisis.test');
    Config::set('services.r2.signing_key', 'test-signing-key');
});

it('prepares a xeno-canto submission package with approximate coordinates and species', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create([
        'user_id' => $user->id,
        'title' => 'Chant du matin',
        'description' => 'Lisiere de foret au lever du jour.',
    ]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'disk' => 'r2',
        'path' => "sounds/original/{$sound->id}/morning.wav",
    ]);
    SoundLocation::create([
        'sound_id' => $sound->id,
        'exact_latitude' => 48.856613,
        'exact_longitude' => 2.352222,
        'public_latitude' => 48.86,
        'public_longitude' => 2.35,
        'location_name' => 'Bois de Vincennes',
    ]);
    $analysis = SoundAnalysis::factory()->create([
        'sound_id' => $sound->id,
        'status' => 'completed',
        'quality_label' => 'good',
    ]);
    BirdnetDetection::create([
        'sound_analysis_id' => $analysis->id,
        'sound_id' => $sound->id,
        'scientific_name' => 'Turdus merula',
        'common_name' => 'Common Blackbird',
        'confidence' => 0.91,
        'start_time' => 1.0,
        'end_time' => 4.0,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('sounds.xeno-canto.prepare', $sound));

    $response->assertOk()
        ->assertJsonPath('status', 'prepared')
        ->assertJsonPath('xeno_canto_upload_url', 'https://xeno-canto.org/upload')
        ->assertJsonPath('metadata.species.scientific_name', 'Turdus merula')
        ->assertJsonPath('metadata.location.latitude', '48.86000000')
        ->assertJsonPath('metadata.location.longitude', '2.35000000');

    expect($response->json('audio_download_url'))->toStartWith('https://media.arborisis.test/sounds/original/');
    expect($response->json('metadata.location'))->not->toHaveKey('exact_latitude');
});

it('marks a prepared xeno-canto submission as submitted', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create(['user_id' => $user->id]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'disk' => 'r2',
        'path' => "sounds/original/{$sound->id}/test.wav",
    ]);

    $this->actingAs($user)->postJson(route('sounds.xeno-canto.prepare', $sound))->assertOk();

    $response = $this->actingAs($user)
        ->postJson(route('sounds.xeno-canto.submitted', $sound), [
            'xeno_canto_id' => '123456',
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'submitted')
        ->assertJsonPath('xeno_canto_url', 'https://xeno-canto.org/123456');
});

it('rejects private sounds for xeno-canto preparation', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->private()->create(['user_id' => $user->id]);
    SoundFile::factory()->create(['sound_id' => $sound->id]);

    $this->actingAs($user)
        ->postJson(route('sounds.xeno-canto.prepare', $sound))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sound');
});
