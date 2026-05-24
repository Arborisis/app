<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LlmModel;
use App\Services\LlmClusterService;
use Inertia\Inertia;

class LlmClusterDashboardController extends Controller
{
    public function __construct(
        private LlmClusterService $llmService
    ) {}

    public function index()
    {
        $stats = $this->llmService->getStats();
        $models = LlmModel::active()-/>get();

        return Inertia::render('AudioWorkers/LlmCluster', [
            'stats' => $stats,
            'models' => $models,
        ]);
    }
}