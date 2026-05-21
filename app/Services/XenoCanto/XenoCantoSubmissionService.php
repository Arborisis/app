<?php

declare(strict_types=1);

namespace App\Services\XenoCanto;

use App\Enums\SoundStatus;
use App\Enums\SoundVisibility;
use App\Enums\XenoCantoSubmissionStatus;
use App\Models\BirdnetDetection;
use App\Models\Sound;
use App\Models\User;
use App\Models\XenoCantoSubmission;
use App\Services\Storage\SignedUrlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class XenoCantoSubmissionService
{
    private const UPLOAD_URL = 'https://xeno-canto.org/upload';
    private const AUDIO_URL_TTL_MINUTES = 120;

    public function __construct(
        private readonly SignedUrlService $signedUrlService,
    ) {}

    public function prepare(Sound $sound, User $user): XenoCantoSubmission
    {
        $sound->loadMissing([
            'user',
            'category',
            'environment',
            'soundFile',
            'soundLocation',
            'soundAnalysis.birdnetDetections',
        ]);

        $this->validateSoundCanBePrepared($sound);

        return DB::transaction(function () use ($sound, $user) {
            return XenoCantoSubmission::updateOrCreate(
                ['sound_id' => $sound->id],
                [
                    'user_id' => $user->id,
                    'status' => XenoCantoSubmissionStatus::Prepared,
                    'metadata_snapshot' => $this->metadata($sound),
                    'prepared_at' => now(),
                    'submitted_at' => null,
                    'rejected_at' => null,
                ],
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(XenoCantoSubmission $submission): array
    {
        $submission->loadMissing(['sound.soundFile']);
        $sound = $submission->sound;
        $soundFile = $sound->soundFile;

        return [
            'status' => $submission->status->value,
            'xeno_canto_upload_url' => self::UPLOAD_URL,
            'xeno_canto_id' => $submission->xeno_canto_id,
            'xeno_canto_url' => $submission->xeno_canto_url,
            'audio_download_url' => $this->signedUrlService->url(
                $soundFile->disk,
                $soundFile->path,
                self::AUDIO_URL_TTL_MINUTES,
            ),
            'audio_url_expires_at' => now()->addMinutes(self::AUDIO_URL_TTL_MINUTES)->toIso8601String(),
            'metadata' => $submission->metadata_snapshot,
        ];
    }

    public function markSubmitted(XenoCantoSubmission $submission, ?string $xenoCantoId = null): XenoCantoSubmission
    {
        $xenoCantoUrl = $xenoCantoId ? "https://xeno-canto.org/{$xenoCantoId}" : $submission->xeno_canto_url;

        $submission->update([
            'status' => XenoCantoSubmissionStatus::Submitted,
            'xeno_canto_id' => $xenoCantoId,
            'xeno_canto_url' => $xenoCantoUrl,
            'submitted_at' => now(),
            'rejected_at' => null,
        ]);

        return $submission->fresh();
    }

    private function validateSoundCanBePrepared(Sound $sound): void
    {
        if (! $sound->soundFile) {
            throw ValidationException::withMessages([
                'sound' => 'Ce son ne possede pas de fichier audio.',
            ]);
        }

        if ($sound->status !== SoundStatus::Published || $sound->visibility !== SoundVisibility::Public) {
            throw ValidationException::withMessages([
                'sound' => 'Seuls les sons publies et publics peuvent etre prepares pour xeno-canto.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(Sound $sound): array
    {
        $detections = $sound->soundAnalysis?->birdnetDetections
            ?->sortByDesc('confidence')
            ->values();

        $primaryDetection = $detections?->first();
        $backgroundDetections = $detections
            ?->skip(1)
            ->unique(fn (BirdnetDetection $detection) => $detection->scientific_name.'|'.$detection->common_name)
            ->take(8)
            ->map(fn (BirdnetDetection $detection) => [
                'scientific_name' => $detection->scientific_name,
                'common_name' => $detection->common_name,
                'confidence' => $detection->confidence,
            ])
            ->values()
            ->all() ?? [];

        $location = $sound->soundLocation;
        $soundUrl = URL::route('sounds.show', ['slug' => $sound->slug]);

        return [
            'title' => $sound->title,
            'recordist' => $sound->user?->name,
            'recorded_at' => $sound->recorded_at?->toDateString(),
            'recorded_time' => $sound->recorded_at?->format('H:i'),
            'duration_seconds' => $sound->duration,
            'license' => $sound->license->label(),
            'license_warning' => $sound->license->value === 'all_rights_reserved'
                ? 'xeno-canto publie des enregistrements partages avec une licence ouverte. Changer la licence avant soumission.'
                : null,
            'species' => [
                'scientific_name' => $primaryDetection?->scientific_name,
                'common_name' => $primaryDetection?->common_name,
                'confidence' => $primaryDetection?->confidence,
                'needs_manual_confirmation' => $primaryDetection === null || $primaryDetection->confidence < 0.7,
            ],
            'background_species' => $backgroundDetections,
            'location' => [
                'name' => $location?->location_name,
                'latitude' => $location?->public_latitude,
                'longitude' => $location?->public_longitude,
                'coordinates_are_approximate' => true,
                'privacy_note' => 'Arborisis exporte uniquement les coordonnees publiques approximees.',
            ],
            'habitat' => $sound->environment?->name,
            'category' => $sound->category?->name,
            'equipment' => $sound->equipment,
            'remarks' => trim(implode("\n\n", array_filter([
                $sound->description,
                "Source Arborisis: {$soundUrl}",
                $sound->soundAnalysis?->quality_label ? "Qualite d'analyse: {$sound->soundAnalysis->quality_label}" : null,
            ]))),
        ];
    }
}
