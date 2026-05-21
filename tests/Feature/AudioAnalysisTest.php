<?php

declare(strict_types=1);

use App\Enums\AnalysisStatus;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\SoundFile;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->sound = Sound::factory()->create(['user_id' => $this->user->id]);
    SoundFile::factory()->create(['sound_id' => $this->sound->id]);
});

describe('Audio Analysis Feature Tests', function () {
    it('allows public access to analysis preview for public sounds', function () {
        $this->sound->update(['visibility' => 'public', 'status' => 'published']);
        SoundAnalysis::factory()->create([
            'sound_id' => $this->sound->id,
            'status' => AnalysisStatus::COMPLETED,
        ]);

        $response = $this->getJson(route('api.sounds.analysis.show', $this->sound));

        $response->assertOk()
            ->assertJsonStructure([
                'sound' => ['id', 'slug', 'title', 'duration'],
                'analysis' => ['status', 'duration_seconds', 'sample_rate', 'waveform_url', 'spectrogram_url'],
            ]);
    });

    it('allows owner to trigger analysis', function () {
        Queue::fake();
        $this->sound->update(['visibility' => 'public', 'status' => 'published']);

        $response = $this->actingAs($this->user)
            ->postJson(route('sounds.analysis.store', $this->sound), [
                'n_fft' => 2048,
                'frequency_scale' => 'mel',
            ]);

        $response->assertOk()
            ->assertJson(['status' => 'pending']);
    });

    it('prevents non-owner from triggering analysis', function () {
        $otherUser = User::factory()->create();
        $this->sound->update(['visibility' => 'public', 'status' => 'published']);

        $response = $this->actingAs($otherUser)
            ->postJson(route('sounds.analysis.store', $this->sound));

        $response->assertForbidden();
    });

    it('allows owner to export features as json', function () {
        SoundAnalysis::factory()->create([
            'sound_id' => $this->sound->id,
            'status' => AnalysisStatus::COMPLETED,
            'features_json' => ['temporal' => ['rms' => ['stats' => ['mean' => 0.5]]]],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('sounds.analysis.export', ['sound' => $this->sound, 'format' => 'json']));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json');
    });

    it('allows owner to export features as csv', function () {
        SoundAnalysis::factory()->create([
            'sound_id' => $this->sound->id,
            'status' => AnalysisStatus::COMPLETED,
            'features_json' => ['temporal' => ['rms' => ['stats' => ['mean' => 0.5]]]],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('sounds.analysis.export', ['sound' => $this->sound, 'format' => 'csv']));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    });

    it('dispatches job when triggering analysis', function () {
        Queue::fake();

        $this->actingAs($this->user)
            ->postJson(route('sounds.analysis.store', $this->sound), [
                'n_fft' => 2048,
            ]);

        Queue::assertPushed(\App\Jobs\ProcessAudioAnalysis::class);
    });
});
