<?php

declare(strict_types=1);

namespace App\Services\Sound;

use App\Enums\SoundStatus;
use App\Events\SoundPublished;
use App\Jobs\ProcessAudioAnalysis;
use App\Jobs\RequestAudioAnalysis;
use App\Models\Category;
use App\Models\Environment;
use App\Models\Sound;
use App\Services\Audio\AudioDurationService;
use App\Services\Discord\DiscordNotificationService;
use App\Services\Scientific\ListeningPointService;
use App\Enums\Season;
use App\Enums\TimeOfDay;
use App\Models\SoundFile;
use App\Models\SoundLocation;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SoundUploadService
{
    public function __construct(
        private readonly AudioDurationService $durationService,
        private readonly DiscordNotificationService $discordNotification,
        private readonly ListeningPointService $listeningPointService,
    ) {}

    /**
     * @throws \Exception
     */
    public function upload(array $data, int $userId): Sound
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($data, $userId, &$storedPaths) {
                /** @var UploadedFile $audioFile */
                $audioFile = $data['audio_file'];

                $categoryId = $this->resolveCategoryId($data);
                $environmentId = $this->resolveEnvironmentId($data);
                $duration = $this->durationService->getDuration($audioFile->getRealPath());

                // 1. Create sound record
                $sound = Sound::create([
                    'user_id' => $userId,
                    'category_id' => $categoryId,
                    'environment_id' => $environmentId,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'recorded_at' => $this->combineDateTime($data['recorded_at'] ?? null, $data['recorded_time'] ?? null),
                    'duration' => $duration ? (int) round($duration) : null,
                    'equipment' => $data['equipment'] ?? null,
                    'microphone_position' => $data['microphone_position'] ?? null,
                    'license' => $data['license'],
                    'visibility' => $data['visibility'],
                    'status' => SoundStatus::Published,
                ]);

                // 2. Store audio file
                $storedName = $this->generateFileName($audioFile);
                $disk = config('filesystems.default') === 'local' && empty(config('filesystems.disks.s3.key'))
                    ? 'public'
                    : config('filesystems.audio_disk', 'audio');

                if ($disk === 'r2') {
                    $path = "sounds/original/{$sound->id}/{$storedName}";
                } else {
                    $path = "audio/{$userId}/{$storedName}";
                }

                $audioFile->storeAs(
                    dirname($path),
                    basename($path),
                    $disk
                );

                $storedPaths[] = ['disk' => $disk, 'path' => $path];

                SoundFile::create([
                    'sound_id' => $sound->id,
                    'original_name' => $audioFile->getClientOriginalName(),
                    'stored_name' => $storedName,
                    'path' => $path,
                    'mime_type' => $audioFile->getMimeType(),
                    'size_bytes' => $audioFile->getSize(),
                    'disk' => $disk,
                ]);

                // 3. Store location (with privacy)
                $exactLat = (float) $data['latitude'];
                $exactLng = (float) $data['longitude'];
                $isSensitive = $data['is_sensitive_location'] ?? false;

                $publicCoords = SoundLocation::obscure($exactLat, $exactLng);

                $soundLocation = SoundLocation::create([
                    'sound_id' => $sound->id,
                    'exact_latitude' => $exactLat,
                    'exact_longitude' => $exactLng,
                    'public_latitude' => $isSensitive ? $publicCoords['public_latitude'] : $exactLat,
                    'public_longitude' => $isSensitive ? $publicCoords['public_longitude'] : $exactLng,
                    'location_name' => $data['location_name'] ?? null,
                    'is_sensitive' => $isSensitive,
                ]);

                // 3b. Listening point association
                $this->handleListeningPoint($sound, $soundLocation, $data);

                // 4. Store cover image if provided
                if (! empty($data['cover_image'])) {
                    /** @var UploadedFile $coverImage */
                    $coverImage = $data['cover_image'];
                    $coverName = 'cover_'.Str::uuid().'.'.$coverImage->getClientOriginalExtension();
                    $coverPath = "covers/{$userId}/{$coverName}";
                    $coverImage->storeAs(dirname($coverPath), basename($coverPath), $disk);
                    $storedPaths[] = ['disk' => $disk, 'path' => $coverPath];
                    $sound->update(['cover_image' => $coverPath]);
                }

                // 4b. Environmental observation
                if (! empty($data['weather_notes']) || ! empty($data['perceived_activity_level'])) {
                    \App\Models\EnvironmentalObservation::create([
                        'sound_id' => $sound->id,
                        'listening_point_id' => $sound->listening_point_id,
                        'season' => $sound->recorded_at ? Season::fromDate($sound->recorded_at)->value : null,
                        'time_of_day' => $sound->recorded_at ? TimeOfDay::fromHour((int) $sound->recorded_at->format('H'))->value : null,
                        'weather_condition' => $data['weather_notes'] ?? null,
                        'source' => 'user',
                    ]);
                }

                // 5. Attach tags
                if (! empty($data['tags'])) {
                    $tagNames = array_filter(array_map('trim', explode(',', $data['tags'])));
                    $tagIds = [];

                    foreach ($tagNames as $tagName) {
                        $slug = Str::slug($tagName);
                        $tag = Tag::firstOrCreate(
                            ['slug' => $slug],
                            ['name' => $tagName]
                        );
                        $tagIds[] = $tag->id;
                    }

                    $sound->tags()->sync($tagIds);
                }

                // 6. Audio analysis
                $analysisOnUpload = $data['analysis_on_upload'] ?? true;
                if ($analysisOnUpload) {
                    if ($disk === 'r2') {
                        // New pipeline: R2 Event → Queue → Worker → Analyzer
                        \App\Models\SoundAnalysis::firstOrCreate(
                            ['sound_id' => $sound->id],
                            ['status' => \App\Enums\AnalysisStatus::PENDING, 'original_r2_key' => $path]
                        );

                        // Fallback: dispatch directly to analyzer workers
                        // (R2 Event pipeline is unreliable — ensures analysis always starts)
                        RequestAudioAnalysis::dispatch($sound->id, $path);
                    } else {
                        // Legacy pipeline: direct CLI analysis
                        ProcessAudioAnalysis::dispatch($sound->id);
                    }
                }

                // 7. Notify subscribers
                SoundPublished::dispatch($sound);

                // 8. Notify Discord
                $this->discordNotification->notifySoundPublished($sound);

                return $sound->fresh(['soundFile', 'soundLocation', 'tags', 'category', 'user']);
            });
        } catch (\Exception $e) {
            $this->cleanupStoredFiles($storedPaths);
            throw $e;
        }
    }

    /**
     * @param  array<int, array{disk: string, path: string}>  $storedPaths
     */
    private function cleanupStoredFiles(array $storedPaths): void
    {
        foreach ($storedPaths as $file) {
            try {
                Storage::disk($file['disk'])->delete($file['path']);
            } catch (\Exception $e) {
                report($e);
            }
        }
    }

    private function generateFileName(UploadedFile $file): string
    {
        $extension = match ($file->getMimeType()) {
            'audio/mpeg' => 'mp3',
            'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave' => 'wav',
            'audio/flac', 'audio/x-flac' => 'flac',
            'audio/mp4', 'audio/x-m4a', 'audio/m4a' => 'm4a',
            default => $file->extension(),
        };
        $base = Str::uuid();

        return "{$base}.{$extension}";
    }

    private function combineDateTime(?string $date, ?string $time): ?\DateTimeInterface
    {
        if (empty($date)) {
            return null;
        }

        if (empty($time)) {
            return new \DateTimeImmutable($date);
        }

        return new \DateTimeImmutable("{$date} {$time}");
    }

    private function resolveCategoryId(array $data): ?int
    {
        $name = trim((string) ($data['new_category_name'] ?? ''));

        if ($name !== '') {
            $slug = Str::slug($name) ?: 'categorie';
            $category = Category::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => 'Catégorie proposée par la communauté',
                    'color' => '#7BAF6A',
                    'icon' => 'tag',
                    'order' => ((int) Category::max('order')) + 10,
                ],
            );

            return $category->id;
        }

        return ! empty($data['category_id']) ? (int) $data['category_id'] : null;
    }

    private function resolveEnvironmentId(array $data): ?int
    {
        $name = trim((string) ($data['new_environment_name'] ?? ''));

        if ($name !== '') {
            $slug = Str::slug($name) ?: 'environnement';
            $environment = Environment::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => 'Environnement proposé par la communauté',
                    'color' => '#4A6741',
                    'icon' => 'leaf',
                    'order' => ((int) Environment::max('order')) + 10,
                ],
            );

            return $environment->id;
        }

        return ! empty($data['environment_id']) ? (int) $data['environment_id'] : null;
    }

    /**
     * Gère l'association du son à un point d'écoute existant ou nouveau.
     */
    private function handleListeningPoint(Sound $sound, SoundLocation $soundLocation, array $data): void
    {
        // Cas 1 : l'utilisateur a explicitement choisi un point existant
        if (! empty($data['listening_point_id']) && empty($data['create_new_listening_point'])) {
            $point = \App\Models\ListeningPoint::find($data['listening_point_id']);
            if ($point && $point->isApproved()) {
                $this->listeningPointService->attachSoundToPoint($sound, $point);
                return;
            }
        }

        // Cas 2 : l'utilisateur veut forcément créer un nouveau point
        if (! empty($data['create_new_listening_point'])) {
            $point = $this->listeningPointService->createFromSound($sound, [
                'title' => $data['listening_point_title'] ?? ($data['location_name'] ?? null),
                'nature_sensitivity_level' => $data['is_sensitive_location']
                    ? \App\Enums\NatureSensitivityLevel::Fragile
                    : \App\Enums\NatureSensitivityLevel::Normal,
            ]);
            return;
        }

        // Cas 3 : détection automatique de proximité
        $suggestion = $this->listeningPointService->suggestPointForSound($sound);

        if ($suggestion['suggestion_found']) {
            // Par défaut, on attache au point existant le plus proche
            $this->listeningPointService->attachSoundToPoint($sound, $suggestion['point']);
        } else {
            // Aucun point proche : création automatique
            $this->listeningPointService->createFromSound($sound, [
                'title' => $data['location_name'] ?? null,
                'nature_sensitivity_level' => $data['is_sensitive_location']
                    ? \App\Enums\NatureSensitivityLevel::Fragile
                    : \App\Enums\NatureSensitivityLevel::Normal,
            ]);
        }
    }
}
