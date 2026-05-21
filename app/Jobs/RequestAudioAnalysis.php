<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AnalysisStatus;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RequestAudioAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(
        public int $soundId,
        public string $originalR2Key,
        public bool $force = false,
    ) {}

    public function handle(): void
    {
        $sound = Sound::with('soundFile')->find($this->soundId);

        $analysis = SoundAnalysis::firstOrNew(['sound_id' => $this->soundId]);
        if (! $analysis->exists) {
            $analysis->status = AnalysisStatus::PENDING;
            $analysis->save();
        }

        if (! $sound) {
            $analysis->markFailed('Sound not found during analysis request.');
            Log::warning('RequestAudioAnalysis: sound not found.', [
                'sound_id' => $this->soundId,
            ]);
            $this->fail('Sound not found.');

            return;
        }

        if (! $sound->soundFile) {
            Log::warning('RequestAudioAnalysis: soundFile not found, will retry.', [
                'sound_id' => $this->soundId,
            ]);

            throw new \RuntimeException('Sound file not found.');
        }

        $urls = $this->getAnalyzerUrls();
        $analyzerSecret = config('services.analyzer.secret');

        if (empty($urls) || empty($analyzerSecret)) {
            Log::error('RequestAudioAnalysis: analyzer URLs or secret not configured.');

            return;
        }

        $shuffled = $this->shuffle($urls);
        $lastError = null;

        foreach ($shuffled as $analyzerUrl) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$analyzerSecret,
                    'Content-Type' => 'application/json',
                ])
                    ->connectTimeout(5)
                    ->timeout(45)
                    ->post(rtrim($analyzerUrl, '/').'/analyze', array_filter([
                        'sound_id' => $this->soundId,
                        'original_r2_key' => $this->originalR2Key,
                        'force' => $this->force,
                        'lat' => $sound?->exact_latitude ?? $sound?->approximate_latitude ?? null,
                        'lon' => $sound?->exact_longitude ?? $sound?->approximate_longitude ?? null,
                        'recorded_at' => $sound->recorded_at?->toIso8601String(),
                    ], fn ($v) => $v !== null));

                if ($response->successful()) {
                    $analysis->markQueued();

                    Log::info('RequestAudioAnalysis: analyzer accepted.', [
                        'sound_id' => $this->soundId,
                        'url' => $analyzerUrl,
                    ]);

                    return;
                }

                // 4xx → inutile de réessayer sur un autre worker
                if ($response->status() >= 400 && $response->status() < 500) {
                    Log::error('RequestAudioAnalysis: analyzer returned client error.', [
                        'sound_id' => $this->soundId,
                        'url' => $analyzerUrl,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    $this->fail('Analyzer returned HTTP '.$response->status());

                    return;
                }

                // 5xx → on tente le worker suivant
                Log::warning('RequestAudioAnalysis: analyzer returned server error, trying next.', [
                    'sound_id' => $this->soundId,
                    'url' => $analyzerUrl,
                    'status' => $response->status(),
                ]);

                $lastError = 'Analyzer returned HTTP '.$response->status();
            } catch (\Throwable $e) {
                Log::warning('RequestAudioAnalysis: network error, trying next worker.', [
                    'sound_id' => $this->soundId,
                    'url' => $analyzerUrl,
                    'error' => $e->getMessage(),
                ]);

                $lastError = $e->getMessage();
            }
        }

        Log::error('RequestAudioAnalysis: all analyzer workers failed.', [
            'sound_id' => $this->soundId,
            'last_error' => $lastError,
        ]);

        throw new \RuntimeException($lastError ?? 'All analyzer workers unreachable');
    }

    /**
     * @return list<string>
     */
    private function getAnalyzerUrls(): array
    {
        $urls = config('services.analyzer.urls', []);

        if (! empty($urls)) {
            return $urls;
        }

        $single = config('services.analyzer.url');

        return $single ? [$single] : [];
    }

    /**
     * @template T
     * @param  array<T>  $array
     * @return array<T>
     */
    private function shuffle(array $array): array
    {
        $arr = $array;
        shuffle($arr);

        return $arr;
    }
}
