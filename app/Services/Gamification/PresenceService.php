<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Enums\PresenceVisibilityMode;
use App\Events\Gamification\UserLeftMap;
use App\Events\Gamification\UserPresenceUpdated;
use App\Models\User;
use App\Models\UserPresence;
use Illuminate\Support\Facades\DB;

class PresenceService
{
    public function updatePresence(
        User $user,
        float $latitude,
        float $longitude,
        PresenceVisibilityMode $mode,
    ): UserPresence {
        return DB::transaction(function () use ($user, $latitude, $longitude, $mode) {
            // Approximate location to ~100m grid
            $approximation = config('gamification.presence_approximation_meters', 100);
            $approxLat = $this->approximateCoordinate($latitude, $approximation);
            $approxLng = $this->approximateCoordinate($longitude, $approximation);

            $presence = UserPresence::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'approximate_latitude' => $approxLat,
                    'approximate_longitude' => $approxLng,
                    'last_seen_at' => now(),
                    'visibility_mode' => $mode,
                    'expires_at' => now()->addMinutes(config('gamification.presence_expiry_minutes', 5)),
                ]
            );

            if ($mode !== PresenceVisibilityMode::Invisible) {
                UserPresenceUpdated::dispatch($presence);
            }

            return $presence;
        });
    }

    public function removePresence(User $user): void
    {
        UserPresence::where('user_id', $user->id)->get()->each(function (UserPresence $presence) {
            UserLeftMap::dispatch($presence->user_id);
            $presence->delete();
        });
    }

    public function getVisiblePresences(float $south, float $north, float $west, float $east, ?User $viewer = null): array
    {
        return UserPresence::visible()
            ->whereBetween('approximate_latitude', [$south, $north])
            ->whereBetween('approximate_longitude', [$west, $east])
            ->with('user:id,name')
            ->get()
            ->filter(function (UserPresence $presence) use ($viewer) {
                if ($presence->visibility_mode !== PresenceVisibilityMode::FriendsOnly) {
                    return true;
                }

                return $viewer !== null && $viewer->isFriend($presence->user);
            })
            ->map(function (UserPresence $presence) {
                return [
                    'user_id' => $presence->user_id,
                    'user_name' => $presence->visibility_mode === PresenceVisibilityMode::PublicZone
                        ? $presence->user?->name
                        : null,
                    'latitude' => (float) $presence->approximate_latitude,
                    'longitude' => (float) $presence->approximate_longitude,
                    'last_seen_at' => $presence->last_seen_at->diffForHumans(),
                    'visibility_mode' => $presence->visibility_mode->value,
                ];
            })
            ->values()
            ->toArray();
    }

    private function approximateCoordinate(float $coordinate, int $meters): float
    {
        // Rough approximation: 1 degree ≈ 111km
        $degrees = $meters / 111000;
        return round($coordinate / $degrees) * $degrees;
    }
}
