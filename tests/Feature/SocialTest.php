<?php

use App\Models\Report;
use App\Models\Sound;
use App\Models\User;

it('allows users to like a sound', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create();

    $response = $this->actingAs($user)->post("/sounds/{$sound->id}/likes");

    $response->assertOk()->assertJsonStructure(['liked', 'count']);
    expect($sound->fresh()->isLikedBy($user))->toBeTrue();
});

it('allows users to unlike a sound', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create();

    $this->actingAs($user)->post("/sounds/{$sound->id}/likes");
    $response = $this->actingAs($user)->delete("/sounds/{$sound->id}/likes");

    $response->assertOk()->assertJsonStructure(['liked', 'count']);
    expect($sound->fresh()->isLikedBy($user))->toBeFalse();
});

it('prevents guests from liking', function () {
    $sound = Sound::factory()->create();

    $response = $this->post("/sounds/{$sound->id}/likes");

    $response->assertRedirect('/login');
});

it('allows users to follow a creator', function () {
    $user = User::factory()->create();
    $creator = User::factory()->create();

    $response = $this->actingAs($user)->post("/users/{$creator->id}/follows");

    $response->assertOk()->assertJsonStructure(['following']);
    expect($user->fresh()->isFollowing($creator))->toBeTrue();
});

it('allows users to unfollow a creator', function () {
    $user = User::factory()->create();
    $creator = User::factory()->create();

    $this->actingAs($user)->post("/users/{$creator->id}/follows");
    $response = $this->actingAs($user)->delete("/users/{$creator->id}/follows");

    $response->assertOk()->assertJsonStructure(['following']);
    expect($user->fresh()->isFollowing($creator))->toBeFalse();
});

it('allows users to post comments', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create();

    $response = $this->actingAs($user)
        ->post("/sounds/{$sound->id}/comments", [
            'body' => 'Great sound!',
        ]);

    $response->assertRedirect();
    expect($sound->fresh()->allComments)->toHaveCount(1);
});

it('validates comment body', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create();

    $response = $this->actingAs($user)
        ->post("/sounds/{$sound->id}/comments", [
            'body' => '',
        ]);

    $response->assertSessionHasErrors('body');
});

it('allows users to submit reports', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create();

    $response = $this->actingAs($user)->post('/reports', [
        'reportable_type' => 'sound',
        'reportable_id' => $sound->id,
        'reason' => 'inappropriate_content',
        'description' => 'This sound violates guidelines',
    ]);

    $response->assertRedirect();
    expect(Report::count())->toBe(1);
});
