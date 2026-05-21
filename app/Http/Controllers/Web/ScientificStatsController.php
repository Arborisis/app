<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Scientific\ScientificStatsService;
use Inertia\Inertia;
use Inertia\Response;

class ScientificStatsController extends Controller
{
    public function __construct(
        private readonly ScientificStatsService $statsService
    ) {}

    public function index(): Response
    {
        return Inertia::render('ScientificStats/Index', [
            'stats' => $this->statsService->getGlobalStats(),
            'categoryDistribution' => $this->statsService->getCategoryDistribution(),
            'environmentDistribution' => $this->statsService->getEnvironmentDistribution(),
            'temporalDistribution' => $this->statsService->getTemporalDistribution(),
            'geoHeatmap' => $this->statsService->getGeoHeatmap(),
            'audioFeatures' => $this->statsService->getAudioFeatureAverages(),
            'audioFeatureDistribution' => $this->statsService->getAudioFeatureDistribution(),
            'topLocations' => $this->statsService->getTopLocations(),
            'equipmentDistribution' => $this->statsService->getEquipmentDistribution(),
            'rawDataSample' => $this->statsService->getRawDataSample(50),
            'listeningPoints' => $this->statsService->getListeningPointsOverview(),
            'speciesDistribution' => $this->statsService->getSpeciesDistribution(),
            'globalMetrics' => $this->statsService->getGlobalMetricsOverview(),
            'qualityOverview' => $this->statsService->getQualityOverview(),
            'datasetCompleteness' => $this->statsService->getDatasetCompleteness(),
            'datasetSchema' => $this->statsService->getDatasetSchema(),
            'environmentalOverview' => $this->statsService->getEnvironmentalOverview(),
        ]);
    }
}
