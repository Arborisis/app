<?php

declare(strict_types=1);

namespace Tests\Feature\Gamification;

use App\Enums\ModerationStatus;
use App\Enums\UserRole;
use App\Models\SoundWalk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SoundWalkPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_moderator_can_delete_sound_walks_from_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $user = User::factory()->create(['role' => UserRole::User]);
        $soundWalk = $this->soundWalkFor($user);

        $this->assertTrue(Gate::forUser($admin)->allows('delete', $soundWalk));
        $this->assertTrue(Gate::forUser($admin)->allows('deleteAny', SoundWalk::class));
        $this->assertTrue(Gate::forUser($admin)->allows('restore', $soundWalk));
        $this->assertTrue(Gate::forUser($admin)->allows('restoreAny', SoundWalk::class));
        $this->assertTrue(Gate::forUser($admin)->allows('forceDelete', $soundWalk));
        $this->assertTrue(Gate::forUser($admin)->allows('forceDeleteAny', SoundWalk::class));

        $this->assertTrue(Gate::forUser($moderator)->allows('delete', $soundWalk));
        $this->assertTrue(Gate::forUser($moderator)->allows('deleteAny', SoundWalk::class));
        $this->assertTrue(Gate::forUser($moderator)->allows('restore', $soundWalk));
        $this->assertTrue(Gate::forUser($moderator)->allows('forceDelete', $soundWalk));
    }

    public function test_regular_user_can_only_delete_their_own_sound_walk_softly(): void
    {
        $owner = User::factory()->create(['role' => UserRole::User]);
        $other = User::factory()->create(['role' => UserRole::User]);
        $soundWalk = $this->soundWalkFor($owner);

        $this->assertTrue(Gate::forUser($owner)->allows('delete', $soundWalk));
        $this->assertFalse(Gate::forUser($owner)->allows('deleteAny', SoundWalk::class));
        $this->assertFalse(Gate::forUser($owner)->allows('restore', $soundWalk));
        $this->assertFalse(Gate::forUser($owner)->allows('forceDelete', $soundWalk));
        $this->assertFalse(Gate::forUser($other)->allows('delete', $soundWalk));
    }

    private function soundWalkFor(User $user): SoundWalk
    {
        return SoundWalk::create([
            'user_id' => $user->id,
            'title' => 'Balade test',
            'slug' => 'balade-test-'.uniqid(),
            'description' => 'Itinéraire de test.',
            'visibility_status' => 'public',
            'moderation_status' => ModerationStatus::Pending,
            'route_geometry' => [
                'type' => 'LineString',
                'source' => 'waypoints',
                'coordinates' => [[4.38, 50.86], [4.39, 50.87]],
            ],
            'start_latitude' => 50.86,
            'start_longitude' => 4.38,
            'approximate_start_latitude' => 50.86,
            'approximate_start_longitude' => 4.38,
            'estimated_duration_minutes' => 60,
            'difficulty_level' => 1,
            'tags' => [],
        ]);
    }
}
