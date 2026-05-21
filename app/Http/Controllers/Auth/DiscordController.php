<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserDiscordAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class DiscordController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('discord')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $discordUser = Socialite::driver('discord')->user();
        } catch (\Exception $e) {
            return redirect()->route('profile.edit')
                ->with('error', 'La connexion avec Discord a échoué. Veuillez réessayer.');
        }

        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'Votre session a expiré pendant la connexion. Veuillez vous reconnecter et réessayer.');
        }

        UserDiscordAccount::updateOrCreate(
            ['user_id' => $user->id],
            [
                'discord_id' => $discordUser->getId(),
                'discord_username' => $discordUser->getNickname() ?? $discordUser->getName(),
                'discord_avatar' => $discordUser->getAvatar(),
                'access_token' => $discordUser->token,
                'refresh_token' => $discordUser->refreshToken,
                'expires_at' => $discordUser->expiresIn ? now()->addSeconds($discordUser->expiresIn) : null,
                'linked_at' => now(),
            ]
        );

        return redirect()->route('profile.edit')
            ->with('success', 'Compte Discord lié avec succès.');
    }

    public function unlink(): RedirectResponse
    {
        $user = Auth::user();

        if ($user?->discordAccount) {
            $user->discordAccount->delete();
        }

        return redirect()->route('profile.edit')
            ->with('success', 'Compte Discord délié.');
    }
}
