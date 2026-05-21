<?php

declare(strict_types=1);

namespace App\Services\OpenSearch;

use App\Models\Sound;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SoundSearchRepository
{
    public function __construct(
        private readonly OpenSearchIndexService $openSearch,
    ) {}

    public function search(array $filters): LengthAwarePaginator
    {
        if ($this->openSearch->isAvailable()) {
            try {
                return $this->searchWithOpenSearch($filters);
            } catch (\Exception $e) {
                report($e);
                // Fallback silencieux vers SQL
            }
        }

        return $this->searchWithSql($filters);
    }

    private function searchWithOpenSearch(array $filters): LengthAwarePaginator
    {
        $must = [];
        $filter = [
            ['term' => ['moderation_status' => 'published']],
            ['term' => ['visibility' => 'public']],
        ];

        if (! empty($filters['q'])) {
            $must[] = [
                'multi_match' => [
                    'query' => $filters['q'],
                    'fields' => ['title^3', 'description', 'tags'],
                ],
            ];
        }

        if (! empty($filters['species'])) {
            $filter[] = [
                'nested' => [
                    'path' => 'detected_species',
                    'query' => [
                        'bool' => [
                            'should' => [
                                ['term' => ['detected_species.scientific_name' => $filters['species']]],
                                ['match' => ['detected_species.common_name' => $filters['species']]],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (! empty($filters['tag'])) {
            $filter[] = ['term' => ['tags' => $filters['tag']]];
        }

        if (! empty($filters['habitat'])) {
            $filter[] = ['term' => ['habitat_type' => $filters['habitat']]];
        }

        if (! empty($filters['season'])) {
            $filter[] = ['term' => ['season' => $filters['season']]];
        }

        if (! empty($filters['from'])) {
            $filter[] = ['range' => ['recorded_at' => ['gte' => $filters['from']]]];
        }

        if (! empty($filters['to'])) {
            $filter[] = ['range' => ['recorded_at' => ['lte' => $filters['to']]]];
        }

        if (! empty($filters['min_duration'])) {
            $filter[] = ['range' => ['duration_seconds' => ['gte' => (int) $filters['min_duration']]]];
        }

        if (! empty($filters['max_duration'])) {
            $filter[] = ['range' => ['duration_seconds' => ['lte' => (int) $filters['max_duration']]]];
        }

        if (! empty($filters['lat']) && ! empty($filters['lng']) && ! empty($filters['distance_km'])) {
            $filter[] = [
                'geo_distance' => [
                    'distance' => "{$filters['distance_km']}km",
                    'location' => [
                        'lat' => (float) $filters['lat'],
                        'lon' => (float) $filters['lng'],
                    ],
                ],
            ];
        }

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 24);

        $response = $this->openSearch->getClient()->search([
            'index' => 'arborisis_sounds',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                        'filter' => $filter,
                    ],
                ],
                'sort' => [
                    ['recorded_at' => 'desc'],
                ],
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
            ],
        ]);

        $total = $response['hits']['total']['value'] ?? 0;
        $ids = collect($response['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->all();

        $sounds = Sound::public()
            ->with(['user.profile', 'category', 'tags', 'soundFile', 'soundLocation', 'environment'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($sound) => array_search($sound->id, $ids));

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $sounds->values(),
            $total,
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );
    }

    private function searchWithSql(array $filters): LengthAwarePaginator
    {
        $query = Sound::public()
            ->with(['user.profile', 'category', 'tags', 'soundFile', 'soundLocation', 'environment']);

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sq) use ($q) {
                $sq->where('title', 'ILIKE', "%{$q}%")
                    ->orWhere('description', 'ILIKE', "%{$q}%");
            });
        }

        if (! empty($filters['species'])) {
            $query->whereHas('soundAnalysis.birdnetDetections', function ($dq) use ($filters) {
                $dq->where('scientific_name', $filters['species'])
                    ->orWhere('common_name', 'ILIKE', "%{$filters['species']}%");
            });
        }

        if (! empty($filters['tag'])) {
            $query->whereHas('tags', fn ($tq) => $tq->where('slug', $filters['tag']));
        }

        if (! empty($filters['habitat'])) {
            $query->whereHas('environment', fn ($eq) => $eq->where('slug', $filters['habitat']));
        }

        if (! empty($filters['season'])) {
            $query->whereHas('environmentalObservation', fn ($wq) => $wq->where('season', $filters['season']));
        }

        if (! empty($filters['from'])) {
            $query->whereDate('recorded_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('recorded_at', '<=', $filters['to']);
        }

        if (! empty($filters['min_duration'])) {
            $query->where('duration', '>=', (int) $filters['min_duration']);
        }

        if (! empty($filters['max_duration'])) {
            $query->where('duration', '<=', (int) $filters['max_duration']);
        }

        return $query->latest('recorded_at')->paginate((int) ($filters['per_page'] ?? 24));
    }
}
