<?php

use App\Models\User;
use App\Models\Sound;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('profile avatar can be uploaded', function () {
    Storage::fake('public');
    config(['filesystems.default' => 'local']);

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post('/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 256, 256),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $avatar = $user->refresh()->profile?->avatar;

    expect($avatar)->toBeString()
        ->and($avatar)->toStartWith("avatars/{$user->id}/");

    Storage::disk('public')->assertExists($avatar);
});

test('creators index exposes avatar url instead of storage path', function () {
    Storage::fake('public');
    config(['filesystems.default' => 'local']);

    $user = User::factory()->create();
    $user->profile()->create(['avatar' => 'avatars/'.$user->id.'/avatar.jpg']);
    Sound::factory()->create(['user_id' => $user->id]);

    Storage::disk('public')->put($user->profile->avatar, 'avatar');

    $response = $this->get('/creators');

    $response
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Creators/Index')
            ->where('creators.data.0.profile.avatarUrl', '/storage/avatars/'.$user->id.'/avatar.jpg')
        );
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNotNull($user->fresh());
    $this->assertTrue($user->fresh()->trashed());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
