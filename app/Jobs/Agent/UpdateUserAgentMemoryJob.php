<?php

declare(strict_types=1);

namespace App\Jobs\Agent;

use App\Models\User;
use App\Services\Agent\UserAgentMemoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UpdateUserAgentMemoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    /**
     * @param array<int, array{role?: string, content?: string}> $history
     * @param array<string, mixed> $page
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $message,
        public readonly string $answer,
        public readonly array $history = [],
        public readonly array $page = [],
        public readonly ?string $debounceKey = null,
        public readonly ?string $debounceToken = null,
    ) {
        $this->onQueue('default');
    }

    public function handle(UserAgentMemoryService $memory): void
    {
        if ($this->debounceKey && $this->debounceToken && Redis::get($this->debounceKey) !== $this->debounceToken) {
            return;
        }

        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        try {
            $memory->rememberConversation(
                user: $user,
                message: $this->message,
                answer: $this->answer,
                history: $this->history,
                page: $this->page,
            );
        } catch (\Throwable $e) {
            Log::warning('Sylve conversation memory update failed', [
                'user_id' => $this->userId,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
