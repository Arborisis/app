<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClusterModel;
use App\Services\ClusterIAService;
use Inertia\Inertia;

class ClusterDashboardController extends Controller
{
    public function __construct(
        private ClusterIAService $clusterService
    ) {}

    public function index()
    {
        $stats = $this->clusterService->getClusterStats();
        $models = ClusterModel::active()
            ->withCount(['tasks' => function ($query) {
                $query->where('status', 'processing');
            }])
            ->get();

        return Inertia::render('AudioWorkers/Cluster', [
            'stats' => $stats,
            'models' => $models,
        ]);
    }
}