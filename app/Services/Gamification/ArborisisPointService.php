<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Events\Gamification\ArborisisPointSubmitted;
use App\Events\Gamification\PointReported;
use App\Models\ArborisisPoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArborisisPointService
{
    public function createPoint(User $user, array $data): ArborisisPoint
    {
        return DB::transaction(function () use ($user, $data) {
            $sensitivity = NatureSensitivityLevel::tryFrom($data['nature_sensitivity_level'] ?? 'normal')
                ?? NatureSensitivityLevel::Normal;

            $coordinates = ArborisisPoint::publicCoordinates(
                (float) $data['latitude'],
                (float) $data['longitude'],
                $sensitivity,
            );

            $point = ArborisisPoint::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'slug' => $this->generateSlug($data['title']),
                'description' => $data['description'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'approximate_latitude' => $coordinates['approximate_latitude'],
                'approximate_longitude' => $coordinates['approximate_longitude'],
                'visibility_status' => 'public',
                'moderation_status' => ModerationStatus::Pending,
                'category' => $data['category'],
                'tags' => $data['tags'] ?? [],
                'difficulty_level' => $data['difficulty_level'] ?? 1,
                'nature_sensitivity_level' => $sensitivity,
                'recommended_time' => $data['recommended_time'] ?? null,
                'audio_environment_type' => $data['audio_environment_type'] ?? null,
                'cover_image' => $data['cover_image'] ?? null,
            ]);

            ArborisisPointSubmitted::dispatch($point);

            return $point;
        });
    }

    public function updatePoint(ArborisisPoint $point, array $data): ArborisisPoint
    {
        return DB::transaction(function () use ($point, $data) {
            if (isset($data['title']) && $data['title'] !== $point->title) {
                $data['slug'] = $this->generateSlug($data['title']);
            }

            if (isset($data['latitude'], $data['longitude']) || isset($data['nature_sensitivity_level'])) {
                $coordinates = ArborisisPoint::publicCoordinates(
                    (float) ($data['latitude'] ?? $point->latitude),
                    (float) ($data['longitude'] ?? $point->longitude),
                    $data['nature_sensitivity_level'] ?? $point->nature_sensitivity_level,
                );

                $data['approximate_latitude'] = $coordinates['approximate_latitude'];
                $data['approximate_longitude'] = $coordinates['approximate_longitude'];
            }

            $point->update($data);

            return $point->fresh();
        });
    }

    public function reportPoint(User $user, ArborisisPoint $point, array $data): void
    {
        $point->reports()->create([
            'user_id' => $user->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);

        PointReported::dispatch($user, $point);
    }

    public function suggestEdit(User $user, ArborisisPoint $point, array $data): void
    {
        $point->suggestions()->create([
            'user_id' => $user->id,
            'field' => $data['field'],
            'proposed_value' => $data['proposed_value'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);
    }

    private function generateSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $count = 1;

        while (ArborisisPoint::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
