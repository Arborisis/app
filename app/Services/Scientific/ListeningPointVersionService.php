<?php

declare(strict_types=1);

namespace App\Services\Scientific;

use App\Enums\ListeningPointVersionEvent;
use App\Models\ListeningPoint;
use App\Models\ListeningPointVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ListeningPointVersionService
{
    /**
     * Capture une version publique immuable du point d'écoute.
     */
    public function capture(
        ListeningPoint $point,
        ListeningPointVersionEvent $event,
        ?User $actor = null,
        ?string $summary = null,
        string $source = 'system',
    ): ?ListeningPointVersion {
        return DB::transaction(function () use ($point, $event, $actor, $summary, $source): ?ListeningPointVersion {
            $latest = ListeningPointVersion::query()
                ->where('listening_point_id', $point->id)
                ->lockForUpdate()
                ->latest('version_number')
                ->first();

            $payload = $this->publicPayload($point);
            $diff = $this->diff($latest?->public_payload, $payload);

            if ($event !== ListeningPointVersionEvent::Deleted && $latest && $diff === []) {
                return null;
            }

            $versionNumber = ((int) ($latest?->version_number ?? 0)) + 1;
            $capturedAt = now();
            $parentHash = $latest?->version_hash;
            $versionHash = $this->hash([
                'listening_point_id' => $point->id,
                'version_number' => $versionNumber,
                'parent_version_hash' => $parentHash,
                'event' => $event->value,
                'captured_at' => $capturedAt->toIso8601String(),
                'public_payload' => $payload,
            ]);

            return ListeningPointVersion::create([
                'listening_point_id' => $point->id,
                'actor_user_id' => $actor?->id,
                'version_number' => $versionNumber,
                'version_hash' => $versionHash,
                'parent_version_hash' => $parentHash,
                'event' => $event,
                'source' => $source,
                'summary' => $summary ?? $event->label(),
                'public_payload' => $payload,
                'diff' => $diff,
                'captured_at' => $capturedAt,
            ]);
        });
    }

    public function publicPayload(ListeningPoint $point): array
    {
        return [
            'title' => $point->title,
            'slug' => $point->slug,
            'description' => $point->description,
            'public_latitude' => $point->public_latitude === null ? null : (float) $point->public_latitude,
            'public_longitude' => $point->public_longitude === null ? null : (float) $point->public_longitude,
            'public_accuracy_meters' => $point->public_accuracy_meters,
            'environment_id' => $point->environment_id,
            'habitat_type' => $point->habitat_type,
            'country_code' => $point->country_code,
            'admin_level_1' => $point->admin_level_1,
            'elevation_meters' => $point->elevation_meters,
            'moderation_status' => $point->moderation_status?->value,
            'nature_sensitivity_level' => $point->nature_sensitivity_level?->value,
            'recordings_count' => $point->recordings_count,
            'species_detected_count' => $point->species_detected_count,
            'first_recorded_at' => $point->first_recorded_at?->toIso8601String(),
            'last_recorded_at' => $point->last_recorded_at?->toIso8601String(),
            'dominant_tags' => $point->dominant_tags ?? [],
        ];
    }

    private function diff(?array $previous, array $current): array
    {
        if ($previous === null) {
            return collect($current)
                ->map(fn ($value) => ['from' => null, 'to' => $value])
                ->all();
        }

        $diff = [];
        foreach ($current as $key => $value) {
            $old = $previous[$key] ?? null;
            if ($old !== $value) {
                $diff[$key] = ['from' => $old, 'to' => $value];
            }
        }

        return $diff;
    }

    private function hash(array $payload): string
    {
        $this->ksortRecursive($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function ksortRecursive(array &$value): void
    {
        ksort($value);

        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->ksortRecursive($item);
            }
        }
    }
}
