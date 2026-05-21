<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\XenoCanto\MarkXenoCantoSubmittedRequest;
use App\Models\Sound;
use App\Services\XenoCanto\XenoCantoSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class XenoCantoSubmissionController extends Controller
{
    public function __construct(
        private readonly XenoCantoSubmissionService $submissionService,
    ) {}

    public function prepare(Sound $sound): JsonResponse
    {
        Gate::authorize('update', $sound);

        $submission = $this->submissionService->prepare($sound, request()->user());

        return response()->json($this->submissionService->payload($submission));
    }

    public function show(Sound $sound): JsonResponse
    {
        Gate::authorize('update', $sound);

        $submission = $sound->xenoCantoSubmission()->firstOrFail();

        return response()->json($this->submissionService->payload($submission));
    }

    public function markSubmitted(Sound $sound, MarkXenoCantoSubmittedRequest $request): JsonResponse
    {
        Gate::authorize('update', $sound);

        $submission = $sound->xenoCantoSubmission()->firstOrFail();
        $submission = $this->submissionService->markSubmitted(
            $submission,
            $request->validated('xeno_canto_id'),
        );

        return response()->json($this->submissionService->payload($submission));
    }
}
