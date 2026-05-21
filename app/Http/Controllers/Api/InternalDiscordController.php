<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscordSetting;
use App\Models\Sound;
use App\Models\User;
use App\Models\UserDiscordAccount;
use App\Services\Echo\DonationService;
use App\Services\Radio\RadioStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InternalDiscordController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'sounds_count' => DB::table('sounds')->whereNull('deleted_at')->count(),
            'creators_count' => DB::table('users')->whereIn('role', ['creator', 'moderator', 'admin'])->whereNull('deleted_at')->count(),
            'users_count' => DB::table('users')->whereNull('deleted_at')->count(),
            'transactions_24h' => DB::table('echo_transactions')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
        ]);
    }

    public function getUserByDiscordId(string $discordId): JsonResponse
    {
        $link = UserDiscordAccount::where('discord_id', $discordId)->with('user')->first();

        if (! $link) {
            return response()->json(['error' => 'Utilisateur non lié.'], 404);
        }

        return response()->json([
            'user' => [
                'id' => $link->user->id,
                'name' => $link->user->name,
                'slug' => $link->user->slug,
                'role' => $link->user->role,
                'wallet_balance' => $link->user->wallet?->balance,
            ],
            'discord' => [
                'discord_id' => $link->discord_id,
                'discord_username' => $link->discord_username,
                'discord_avatar' => $link->discord_avatar,
            ],
        ]);
    }

    public function searchSounds(Request $request): JsonResponse
    {
        $query = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ])['q'] ?? '';
        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $sounds = Sound::public()
            ->with('user')
            ->when($query !== '', function ($q) use ($query, $likeOperator) {
                $q->where(function ($q) use ($query, $likeOperator) {
                    $q->where('title', $likeOperator, "%{$query}%")
                        ->orWhere('description', $likeOperator, "%{$query}%");
                });
            })
            ->latest()
            ->limit(10)
            ->get(['id', 'title', 'slug', 'duration', 'user_id', 'cover_image']);

        return response()->json($sounds);
    }

    public function getSound(string $id): JsonResponse
    {
        $sound = Sound::public()->with('user', 'category', 'tags')->find($id);

        if (! $sound) {
            return response()->json(['error' => 'Son introuvable.'], 404);
        }

        return response()->json([
            'id' => $sound->id,
            'title' => $sound->title,
            'slug' => $sound->slug,
            'description' => $sound->description,
            'duration' => $sound->duration,
            'license' => $sound->license,
            'cover_image' => $sound->cover_image,
            'user' => [
                'id' => $sound->user->id,
                'name' => $sound->user->name,
                'slug' => $sound->user->slug,
            ],
            'category' => $sound->category?->name,
            'tags' => $sound->tags->pluck('name'),
        ]);
    }

    public function getRadioNowPlaying(RadioStateService $radioState): JsonResponse
    {
        $nowPlaying = $radioState->current(app(\App\Services\Radio\RadioStreamService::class));

        if (! $nowPlaying) {
            return response()->json(['error' => 'Aucune lecture en cours.'], 404);
        }

        return response()->json([
            'title' => $nowPlaying['title'] ?? null,
            'creator' => $nowPlaying['artist'] ?? null,
            'duration' => isset($nowPlaying['duration']) ? gmdate('i:s', (int) $nowPlaying['duration']) : null,
            'sound_id' => $nowPlaying['sound_id'] ?? null,
            'slug' => $nowPlaying['slug'] ?? null,
            'kind' => $nowPlaying['kind'] ?? 'sound',
            'started_at' => $nowPlaying['started_at'] ?? null,
            'cover_url' => $nowPlaying['cover'] ?? null,
            'radio_url' => url('/radio'),
            'url' => ! empty($nowPlaying['slug']) ? url('/sounds/'.$nowPlaying['slug']) : url('/radio'),
            'embed_color' => '#2F7D5C',
        ]);
    }

    public function linkAccount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|size:6',
            'discord_id' => 'required|string',
            'discord_username' => 'required|string',
            'discord_avatar' => 'nullable|string',
        ]);

        $userId = Cache::get("discord_link_code:{$data['code']}");

        if (! $userId) {
            return response()->json(['error' => 'Code invalide ou expiré.'], 400);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json(['error' => 'Utilisateur introuvable.'], 404);
        }

        UserDiscordAccount::updateOrCreate(
            ['user_id' => $user->id],
            [
                'discord_id' => $data['discord_id'],
                'discord_username' => $data['discord_username'],
                'discord_avatar' => $data['discord_avatar'],
                'linked_at' => now(),
            ]
        );

        Cache::forget("discord_link_code:{$data['code']}");

        return response()->json(['success' => true, 'user_name' => $user->name]);
    }
}
