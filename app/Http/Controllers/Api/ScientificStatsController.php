<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scientific\ScientificStatsRequest;
use App\Services\Scientific\ScientificStatsService;
use Illuminate\Http\JsonResponse;

class ScientificStatsController extends Controller
{
    public function __construct(
        private readonly ScientificStatsService $statsService
    ) {}

    public function globalStats(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse($this->statsService->getGlobalStats($request->filters()), $request);
    }

    public function categories(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse($this->statsService->getCategoryDistribution($request->filters()), $request);
    }

    public function environments(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse($this->statsService->getEnvironmentDistribution($request->filters()), $request);
    }

    public function temporal(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse($this->statsService->getTemporalDistribution($request->filters()), $request);
    }

    public function geoHeatmap(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse($this->statsService->getGeoHeatmap($request->filters()), $request);
    }

    public function audioFeatures(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse([
            'averages' => $this->statsService->getAudioFeatureAverages($request->filters()),
            'distributions' => $this->statsService->getAudioFeatureDistribution($request->filters()),
        ], $request);
    }

    public function topLocations(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse(
            $this->statsService->getTopLocations($request->resultLimit(20, 100), $request->filters()),
            $request
        );
    }

    public function equipment(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse(
            $this->statsService->getEquipmentDistribution($request->resultLimit(15, 100), $request->filters()),
            $request
        );
    }

    public function species(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse(
            $this->statsService->getSpeciesDistribution($request->resultLimit(50, 250), $request->filters()),
            $request
        );
    }

    public function quality(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse($this->statsService->getQualityOverview($request->filters()), $request);
    }

    public function environmental(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse($this->statsService->getEnvironmentalOverview($request->filters()), $request);
    }

    public function modelStats(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse($this->statsService->getGlobalMetricsOverview($request->filters()), $request);
    }

    public function datasetCompleteness(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse($this->statsService->getDatasetCompleteness($request->filters()), $request);
    }

    public function dataset(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse(
            $this->statsService->getResearchDataset(
                $request->resultLimit(100, 1000),
                $request->resultOffset(),
                $request->filters()
            ),
            $request
        );
    }

    public function schema(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse($this->statsService->getDatasetSchema(), $request);
    }

    public function rawData(ScientificStatsRequest $request): JsonResponse
    {
        return $this->scientificResponse(
            $this->statsService->getRawDataSample($request->resultLimit(100, 1000), $request->filters()),
            $request
        );
    }

    /**
     * @param array<string|int, mixed> $data
     */
    private function scientificResponse(array $data, ScientificStatsRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => [
                'schema_version' => 1,
                'generated_at' => now()->toIso8601String(),
                'filters' => $request->filters(),
                'privacy' => [
                    'scope' => 'public_published_sounds_only',
                    'coordinates' => 'public_obfuscated_coordinates_only',
                ],
            ],
        ]);
    }
}
