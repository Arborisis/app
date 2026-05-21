<?php

declare(strict_types=1);

namespace App\Jobs\Agent;

use App\Models\User;
use App\Services\Agent\OpenRouterAgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessAgentChatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 360;

    /**
     * @param array<int, array{role: string, content: string}> $history
     * @param array<string, mixed> $page
     * @param array<string, mixed>|null $location
     */
    public function __construct(
        public readonly string $jobId,
        public readonly string $message,
        public readonly array $history,
        public readonly array $page,
        public readonly ?string $conversationId,
        public readonly ?int $userId,
        public readonly ?array $location,
    ) {
    }

    public function handle(OpenRouterAgentService $agent): void
    {
        $user = $this->userId ? User::find($this->userId) : null;

        try {
            $result = $agent->chat(
                message: $this->message,
                history: $this->history,
                page: $this->page,
                conversationId: $this->conversationId,
                user: $user,
                location: $this->location,
            );

            $this->store([
                'status' => 'completed',
                'answer' => $result['answer'] ?? '',
                'sources' => $result['sources'] ?? [],
                'tool_calls' => $result['tool_calls'] ?? [],
                'conversation_id' => $result['conversation_id'] ?? $this->conversationId,
                'model' => $result['model'] ?? null,
            ]);

            if ($user && filled($result['answer'] ?? null)) {
                $conversationId = (string) ($result['conversation_id'] ?? $this->conversationId ?? $this->jobId);
                $debounceKey = "agent:memory:update:{$user->id}:{$conversationId}";
                $debounceToken = $this->jobId.':'.now()->timestamp;

                Redis::setex($debounceKey, 900, $debounceToken);

                UpdateUserAgentMemoryJob::dispatch(
                    userId: $user->id,
                    message: $this->message,
                    answer: (string) $result['answer'],
                    history: $this->history,
                    page: $this->page,
                    debounceKey: $debounceKey,
                    debounceToken: $debounceToken,
                )->delay(now()->addMinutes(2));
            }
        } catch (\Throwable $e) {
            Log::error('Agent chat job failed', [
                'job_id' => $this->jobId,
                'exception' => $e->getMessage(),
            ]);

            $this->store([
                'status' => 'failed',
                'error' => 'L’agent Arborisis a rencontré un problème. Réessaie dans quelques instants.',
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Agent chat job exhausted', [
            'job_id' => $this->jobId,
            'exception' => $e->getMessage(),
        ]);

        $this->store([
            'status' => 'failed',
            'error' => 'L’agent Arborisis a mis trop de temps à répondre. Réessaie avec une demande plus courte.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function store(array $payload): void
    {
        Redis::setex("agent:chat:{$this->jobId}", 600, json_encode($payload));
    }
}
