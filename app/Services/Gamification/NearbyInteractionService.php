<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Events\Gamification\UserNearbyDetected;
use App\Models\NearbyInteraction;
use App\Models\User;
use App\Models\UserPresence;
use Illuminate\Support\Facades\Redis;

class NearbyInteractionService
{
    private const REDIS_KEY_PREFIX = 'nearby_interaction:';

    public function canInteract(User $initiator, User $recipient): bool
    {
        $key = self::REDIS_KEY_PREFIX . "{$initiator->id}:{$recipient->id}";
        $last = Redis::get($key);

        if ($last && (time() - (int) $last) < config('gamification.nearby_interaction_cooldown', 3600)) {
            return false;
        }

        return true;
    }

    public function detectProximity(UserPresence $a, UserPresence $b): ?float
    {
        $distance = $this->haversine(
            (float) $a->approximate_latitude,
            (float) $a->approximate_longitude,
            (float) $b->approximate_latitude,
            (float) $b->approximate_longitude,
        );

        $threshold = config('gamification.nearby_radius', 200);

        return $distance <= $threshold ? $distance : null;
    }

    public function sendGreet(User $initiator, User $recipient, float $distanceMeters): NearbyInteraction
    {
        $interaction = NearbyInteraction::create([
            'initiator_id' => $initiator->id,
            'recipient_id' => $recipient->id,
            'type' => 'greet',
            'metadata' => ['distance_meters' => round($distanceMeters)],
        ]);

        $this->recordCooldown($initiator, $recipient);

        UserNearbyDetected::dispatch($initiator, $recipient, round($distanceMeters), 'greet');

        return $interaction;
    }

    public function sendShareTip(User $initiator, User $recipient, array $payload): NearbyInteraction
    {
        $interaction = NearbyInteraction::create([
            'initiator_id' => $initiator->id,
            'recipient_id' => $recipient->id,
            'type' => 'share_tip',
            'metadata' => $payload,
        ]);

        $this->recordCooldown($initiator, $recipient);

        return $interaction;
    }

    public function historyForUser(User $user, int $limit = 50): array
    {
        return NearbyInteraction::with(['initiator:id,name', 'recipient:id,name'])
            ->where('initiator_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function recordCooldown(User $a, User $b): void
    {
        $key = self::REDIS_KEY_PREFIX . "{$a->id}:{$b->id}";
        Redis::setex($key, config('gamification.nearby_interaction_cooldown', 3600), time());
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) ** 2;

        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
