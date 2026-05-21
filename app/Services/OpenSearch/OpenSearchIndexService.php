<?php

declare(strict_types=1);

namespace App\Services\OpenSearch;

use App\Enums\Season;
use App\Enums\TimeOfDay;
use App\Models\ListeningPoint;
use App\Models\Sound;
use OpenSearch\Client;
use OpenSearch\Common\Exceptions\Missing404Exception;

class OpenSearchIndexService
{
    private readonly string $prefix;
    private readonly bool $enabled;

    public function __construct(
        private readonly Client $client,
    ) {
        $this->prefix = config('services.opensearch.index_prefix', 'arborisis');
        $this->enabled = config('services.opensearch.enabled', true);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isAvailable(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        try {
            $this->client->cluster()->health();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function indexSound(Sound $sound): void
    {
        if (! $this->enabled) {
            return;
        }

        $doc = $this->buildSoundDocument($sound);

        $this->client->index([
            'index' => "{$this->prefix}_sounds",
            'id' => $sound->id,
            'body' => $doc,
            'refresh' => 'wait_for',
        ]);
    }

    public function deleteSound(int $soundId): void
    {
        if (! $this->enabled) {
            return;
        }

        try {
            $this->client->delete([
                'index' => "{$this->prefix}_sounds",
                'id' => $soundId,
            ]);
        } catch (Missing404Exception) {
            // déjà supprimé
        }
    }

    public function indexListeningPoint(ListeningPoint $point): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->client->index([
            'index' => "{$this->prefix}_listening_points",
            'id' => $point->id,
            'body' => [
                'listening_point_id' => $point->id,
                'title' => $point->title,
                'location' => [
                    'lat' => (float) $point->public_latitude,
                    'lon' => (float) $point->public_longitude,
                ],
                'public_accuracy_meters' => $point->public_accuracy_meters,
                'habitat_type' => $point->habitat_type,
                'environment_id' => $point->environment_id,
                'recordings_count' => $point->recordings_count,
                'species_detected_count' => $point->species_detected_count,
                'first_recorded_at' => $point->first_recorded_at?->toIso8601String(),
                'last_recorded_at' => $point->last_recorded_at?->toIso8601String(),
                'dominant_tags' => $point->dominant_tags ?? [],
                'moderation_status' => $point->moderation_status->value,
            ],
            'refresh' => 'wait_for',
        ]);
    }

    public function createIndices(): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->createSoundsIndex();
        $this->createListeningPointsIndex();
        $this->createSpeciesDetectionsIndex();
    }

    private function createSoundsIndex(): void
    {
        $index = "{$this->prefix}_sounds";

        if ($this->client->indices()->exists(['index' => $index])) {
            return;
        }

        $this->client->indices()->create([
            'index' => $index,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'analyzer' => [
                            'french_sound_analyzer' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase', 'asciifolding'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'properties' => [
                        'sound_id' => ['type' => 'integer'],
                        'listening_point_id' => ['type' => 'integer'],
                        'user_id' => ['type' => 'integer'],
                        'title' => [
                            'type' => 'text',
                            'analyzer' => 'french_sound_analyzer',
                            'fields' => ['keyword' => ['type' => 'keyword']],
                        ],
                        'description' => ['type' => 'text', 'analyzer' => 'french_sound_analyzer'],
                        'tags' => ['type' => 'keyword'],
                        'detected_species' => [
                            'type' => 'nested',
                            'properties' => [
                                'scientific_name' => ['type' => 'keyword'],
                                'common_name' => ['type' => 'text'],
                                'confidence' => ['type' => 'float'],
                                'start_time' => ['type' => 'float'],
                                'end_time' => ['type' => 'float'],
                            ],
                        ],
                        'location' => ['type' => 'geo_point'],
                        'public_accuracy_meters' => ['type' => 'integer'],
                        'recorded_at' => ['type' => 'date'],
                        'uploaded_at' => ['type' => 'date'],
                        'duration_seconds' => ['type' => 'integer'],
                        'season' => ['type' => 'keyword'],
                        'time_of_day' => ['type' => 'keyword'],
                        'habitat_type' => ['type' => 'keyword'],
                        'environment_id' => ['type' => 'integer'],
                        'audio_features' => [
                            'properties' => [
                                'loudness_lufs' => ['type' => 'float'],
                                'spectral_centroid' => ['type' => 'float'],
                                'spectral_rolloff' => ['type' => 'float'],
                                'zero_crossing_rate' => ['type' => 'float'],
                                'acoustic_event_count' => ['type' => 'integer'],
                                'acoustic_diversity_index' => ['type' => 'float'],
                            ],
                        ],
                        'weather' => [
                            'properties' => [
                                'temperature_c' => ['type' => 'float'],
                                'humidity_percent' => ['type' => 'integer'],
                                'is_raining' => ['type' => 'boolean'],
                                'weather_condition' => ['type' => 'keyword'],
                            ],
                        ],
                        'visibility' => ['type' => 'keyword'],
                        'moderation_status' => ['type' => 'keyword'],
                        'license' => ['type' => 'keyword'],
                        'species_count' => ['type' => 'integer'],
                        'biodiversity_score' => ['type' => 'float'],
                    ],
                ],
            ],
        ]);
    }

    private function createListeningPointsIndex(): void
    {
        $index = "{$this->prefix}_listening_points";

        if ($this->client->indices()->exists(['index' => $index])) {
            return;
        }

        $this->client->indices()->create([
            'index' => $index,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'analyzer' => [
                            'french_sound_analyzer' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase', 'asciifolding'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'properties' => [
                        'listening_point_id' => ['type' => 'integer'],
                        'title' => ['type' => 'text', 'analyzer' => 'french_sound_analyzer'],
                        'location' => ['type' => 'geo_point'],
                        'public_accuracy_meters' => ['type' => 'integer'],
                        'habitat_type' => ['type' => 'keyword'],
                        'environment_id' => ['type' => 'integer'],
                        'recordings_count' => ['type' => 'integer'],
                        'species_detected_count' => ['type' => 'integer'],
                        'first_recorded_at' => ['type' => 'date'],
                        'last_recorded_at' => ['type' => 'date'],
                        'dominant_tags' => ['type' => 'keyword'],
                        'moderation_status' => ['type' => 'keyword'],
                        'biodiversity_score' => ['type' => 'float'],
                        'acoustic_activity_score' => ['type' => 'float'],
                    ],
                ],
            ],
        ]);
    }

    private function createSpeciesDetectionsIndex(): void
    {
        $index = "{$this->prefix}_species_detections";

        if ($this->client->indices()->exists(['index' => $index])) {
            return;
        }

        $this->client->indices()->create([
            'index' => $index,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                ],
                'mappings' => [
                    'properties' => [
                        'detection_id' => ['type' => 'integer'],
                        'sound_id' => ['type' => 'integer'],
                        'listening_point_id' => ['type' => 'integer'],
                        'scientific_name' => ['type' => 'keyword'],
                        'common_name' => ['type' => 'text'],
                        'confidence' => ['type' => 'float'],
                        'location' => ['type' => 'geo_point'],
                        'recorded_at' => ['type' => 'date'],
                        'season' => ['type' => 'keyword'],
                        'time_of_day' => ['type' => 'keyword'],
                        'habitat_type' => ['type' => 'keyword'],
                        'is_validated' => ['type' => 'boolean'],
                    ],
                ],
            ],
        ]);
    }

    private function buildSoundDocument(Sound $sound): array
    {
        $location = $sound->soundLocation;
        $analysis = $sound->soundAnalysis;
        $weather = $sound->environmentalObservation;

        return [
            'sound_id' => $sound->id,
            'listening_point_id' => $sound->listening_point_id,
            'user_id' => $sound->user_id,
            'title' => $sound->title,
            'description' => $sound->description,
            'tags' => $sound->tags->pluck('name')->all(),
            'detected_species' => $analysis?->birdnetDetections->map(fn ($d) => [
                'scientific_name' => $d->scientific_name,
                'common_name' => $d->common_name,
                'confidence' => $d->confidence,
                'start_time' => $d->start_time,
                'end_time' => $d->end_time,
            ])->all() ?? [],
            'location' => [
                'lat' => (float) ($location?->public_latitude ?? 0),
                'lon' => (float) ($location?->public_longitude ?? 0),
            ],
            'public_accuracy_meters' => $location?->listeningPoint?->public_accuracy_meters ?? 1000,
            'recorded_at' => $sound->recorded_at?->toIso8601String(),
            'uploaded_at' => $sound->created_at->toIso8601String(),
            'duration_seconds' => $sound->duration,
            'season' => $sound->recorded_at ? Season::fromDate($sound->recorded_at)->value : null,
            'time_of_day' => $sound->recorded_at ? TimeOfDay::fromHour((int) $sound->recorded_at->format('H'))->value : null,
            'habitat_type' => $sound->environment?->slug,
            'environment_id' => $sound->environment_id,
            'audio_features' => [
                'loudness_lufs' => $analysis?->loudness_lufs,
                'spectral_centroid' => $analysis?->spectral_centroid,
                'spectral_rolloff' => $analysis?->spectral_rolloff,
                'zero_crossing_rate' => $analysis?->zero_crossing_rate,
                'acoustic_event_count' => $analysis?->acoustic_event_count,
                'acoustic_diversity_index' => $analysis?->acoustic_diversity_index,
            ],
            'weather' => $weather ? [
                'temperature_c' => $weather->temperature_c,
                'humidity_percent' => $weather->humidity_percent,
                'is_raining' => $weather->is_raining,
                'weather_condition' => $weather->weather_condition,
            ] : null,
            'visibility' => $sound->visibility->value,
            'moderation_status' => $sound->status->value,
            'license' => $sound->license?->value,
            'species_count' => $analysis?->birdnetDetections->count() ?? 0,
        ];
    }
}
