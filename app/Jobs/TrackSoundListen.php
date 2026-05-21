<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\Gamification\SoundListened;
use App\Models\Sound;
use App\Models\SoundListen;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TrackSoundListen implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly int $soundId,
        private readonly int $listenedSeconds,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        $sound = Sound::find($this->soundId);

        if (! $user || ! $sound) {
            return;
        }

        SoundListen::create([
            'user_id' => $this->userId,
            'sound_id' => $this->soundId,
            'listened_seconds' => $this->listenedSeconds,
        ]);

        SoundListened::dispatch($user, $sound, $this->listenedSeconds);
    }
}
