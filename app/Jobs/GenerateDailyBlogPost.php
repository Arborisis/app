<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BlogPost;
use App\Services\Blog\AiBlogGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDailyBlogPost implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function backoff(): array
    {
        return [60, 300, 600];
    }

    public function handle(): void
    {
        if (! config('blog.ai_enabled', true)) {
            Log::info('GenerateDailyBlogPost: AI blog generation is disabled');

            return;
        }

        $alreadyExists = BlogPost::today()->exists();

        if ($alreadyExists) {
            Log::info('GenerateDailyBlogPost: a post already exists for today');

            return;
        }

        $result = app(AiBlogGeneratorService::class)->generate();

        if ($result === null || empty($result['blog_post'])) {
            Log::error('GenerateDailyBlogPost: generation failed after all attempts');

            return;
        }

        Log::info('GenerateDailyBlogPost: daily blog post created successfully', [
            'id' => $result['blog_post']->id,
            'slug' => $result['blog_post']->slug,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateDailyBlogPost: job failed definitively', [
            'exception' => $exception->getMessage(),
        ]);
    }
}
