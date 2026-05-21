<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Services\Echo\WalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly DashboardService $dashboardService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $stats = $this->dashboardService->getStats($user);
        $recentSounds = $this->dashboardService->getRecentSounds($user);
        $activities = $this->dashboardService->getActivities($user);
        $echoBalance = $this->walletService->getBalance($user);
        $gamification = $this->dashboardService->getGamificationData($user);
        $dailySoundIdeas = $this->dashboardService->getDailySoundIdeas($user);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentSounds' => $recentSounds,
            'activities' => $activities,
            'echoBalance' => $echoBalance,
            'gamification' => $gamification,
            'dailySoundIdeas' => $dailySoundIdeas,
        ]);
    }
}
