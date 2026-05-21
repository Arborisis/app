<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\User;

class SoundAnalysisPolicy
{
    public function view(?User $user, Sound $sound): bool
    {
        if ($sound->isPublic()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->id === $sound->user_id || $user->isModerator()) {
            return true;
        }

        return false;
    }

    public function analyze(User $user, Sound $sound): bool
    {
        return $user->id === $sound->user_id || $user->isModerator();
    }

    public function export(User $user, SoundAnalysis $analysis): bool
    {
        $sound = $analysis->sound;

        return $user->id === $sound->user_id || $user->isModerator();
    }

    public function dashboard(User $user, Sound $sound): bool
    {
        return $user->id === $sound->user_id || $user->isModerator();
    }
}
