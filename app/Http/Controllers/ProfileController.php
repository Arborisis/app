<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\Gamification\ProfileUpdated;
use App\Http\Requests\ProfileAvatarUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\Profile\ProfileAvatarService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileAvatarService $profileAvatarService,
    ) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->load('profile');

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'discord' => $request->user()->discordAccount ? [
                'discord_username' => $request->user()->discordAccount->discord_username,
                'discord_avatar' => $request->user()->discordAccount->discord_avatar,
                'linked_at' => $request->user()->discordAccount->linked_at?->toISOString(),
            ] : null,
            'profile' => $user->profile ? [
                'bio' => $user->profile->bio,
                'location' => $user->profile->location,
                'website' => $user->profile->website,
                'avatar' => $user->profile->avatar,
                'avatarUrl' => $this->profileAvatarService->url($user->profile->avatar),
            ] : null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->authorize('update', $request->user());

        $user = $request->user();
        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Update or create profile
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => $validated['bio'] ?? null,
                'location' => $validated['location'] ?? null,
                'website' => $validated['website'] ?? null,
            ]
        );

        ProfileUpdated::dispatch($user);

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's avatar.
     */
    public function updateAvatar(ProfileAvatarUpdateRequest $request): RedirectResponse
    {
        $this->authorize('update', $request->user());

        $this->profileAvatarService->update($request->user(), $request->file('avatar'));

        ProfileUpdated::dispatch($request->user());

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->authorize('delete', $request->user());

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $this->profileAvatarService->delete($user);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
