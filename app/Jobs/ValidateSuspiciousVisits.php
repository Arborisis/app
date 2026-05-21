<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\VisitStatus;
use App\Models\ArborisisVisit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ValidateSuspiciousVisits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $suspiciousVisits = ArborisisVisit::where('status', VisitStatus::Suspicious)
            ->where('created_at', '<=', now()->subMinutes(30))
            ->get();

        foreach ($suspiciousVisits as $visit) {
            // If user has multiple suspicious visits in short time, mark as invalid
            $recentSuspiciousCount = ArborisisVisit::where('user_id', $visit->user_id)
                ->where('status', VisitStatus::Suspicious)
                ->where('created_at', '>=', now()->subHours(24))
                ->count();

            if ($recentSuspiciousCount >= 3) {
                $visit->update([
                    'status' => VisitStatus::Invalid,
                    'anti_cheat_notes' => ($visit->anti_cheat_notes ?? '') . ' | Auto-invalidated: multiple suspicious visits.',
                ]);
            } else {
                $visit->update([
                    'status' => VisitStatus::UnderReview,
                ]);
            }
        }
    }
}
