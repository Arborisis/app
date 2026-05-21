<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

class AgentChatStatusController extends Controller
{
    public function __invoke(string $jobId): JsonResponse
    {
        $cached = Redis::get("agent:chat:{$jobId}");

        if ($cached) {
            $data = json_decode($cached, true);

            return response()->json($data + ['job_id' => $jobId]);
        }

        return response()->json([
            'job_id' => $jobId,
            'status' => 'processing',
        ]);
    }
}
