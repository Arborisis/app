<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\ChatAgentRequest;
use App\Jobs\Agent\ProcessAgentChatJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ChatAgentController extends Controller
{
    public function __invoke(ChatAgentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $jobId = (string) Str::uuid();

        ProcessAgentChatJob::dispatch(
            jobId: $jobId,
            message: $validated['message'],
            history: $validated['history'] ?? [],
            page: $validated['page'] ?? [],
            conversationId: $validated['conversation_id'] ?? null,
            userId: $request->user()?->id,
            location: $validated['location'] ?? null,
        );

        return response()->json([
            'job_id' => $jobId,
            'status' => 'processing',
        ]);
    }
}
