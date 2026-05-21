<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Models\Report;
use App\Services\Report\ReportService;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function store(StoreReportRequest $request): RedirectResponse
    {
        $this->authorize('create', Report::class);

        $validated = $request->validated();

        $this->reportService->create(
            $request->user()->id,
            $validated['reportable_type'],
            $validated['reportable_id'],
            $validated['reason'],
            $validated['description'] ?? null
        );

        return back()->with('success', 'Signalement envoyé. Notre équipe de modération l\'examinera sous peu.');
    }
}
