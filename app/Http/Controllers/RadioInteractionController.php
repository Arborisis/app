<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\RadioReaction;
use App\Models\Sound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RadioInteractionController extends Controller
{
    public function like(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_token' => ['required', 'string', 'max:80'],
            'sound_id' => ['required', 'integer', 'exists:sounds,id'],
        ]);

        $sound = Sound::query()->findOrFail((int) $data['sound_id']);
        $created = false;

        if (! $this->reactionsTableExists()) {
            $created = Cache::add($this->fallbackReactionKey($data['session_token'], $sound->id, 'like'), true, now()->addDays(7));
            if ($created) {
                $sound->increment('like_count');
            }

            return response()->json([
                'liked' => true,
                'created' => $created,
                'like_count' => (int) $sound->fresh()->like_count,
                'summary' => $this->summaryFor($sound),
                'storage' => 'cache',
            ]);
        }

        DB::transaction(function () use ($data, $sound, &$created): void {
            $reaction = RadioReaction::query()->firstOrCreate([
                'session_token' => $data['session_token'],
                'sound_id' => $sound->id,
                'reaction_type' => 'like',
            ]);

            $created = $reaction->wasRecentlyCreated;
            if ($created) {
                $sound->increment('like_count');
            }
        });

        return response()->json([
            'liked' => true,
            'created' => $created,
            'like_count' => (int) $sound->fresh()->like_count,
            'summary' => $this->summaryFor($sound),
        ]);
    }

    public function react(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_token' => ['required', 'string', 'max:80'],
            'sound_id' => ['required', 'integer', 'exists:sounds,id'],
            'reaction_type' => ['required', 'string', Rule::in(['heart', 'leaf', 'star'])],
        ]);

        $sound = Sound::query()->findOrFail((int) $data['sound_id']);

        if (! $this->reactionsTableExists()) {
            Cache::add($this->fallbackReactionKey($data['session_token'], $sound->id, $data['reaction_type']), true, now()->addDays(7));

            return response()->json([
                'summary' => $this->summaryFor($sound),
                'storage' => 'cache',
            ]);
        }

        RadioReaction::query()->firstOrCreate([
            'session_token' => $data['session_token'],
            'sound_id' => $sound->id,
            'reaction_type' => $data['reaction_type'],
        ]);

        return response()->json([
            'summary' => $this->summaryFor($sound),
        ]);
    }

    public function share(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sound_id' => ['required', 'integer', 'exists:sounds,id'],
        ]);

        $sound = Sound::query()->with('user')->findOrFail((int) $data['sound_id']);
        $url = url('/radio?track='.$sound->slug.'&at='.now()->timestamp);

        return response()->json([
            'url' => $url,
            'text' => "J'écoute « {$sound->title} » sur Arborisis Radio.",
            'title' => $sound->title,
            'creator' => $sound->user?->name,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function summaryFor(Sound $sound): array
    {
        if (! $this->reactionsTableExists()) {
            return [
                'like' => (int) ($sound->fresh()->like_count ?? 0),
                'heart' => 0,
                'leaf' => 0,
                'star' => 0,
            ];
        }

        $counts = RadioReaction::query()
            ->where('sound_id', $sound->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->selectRaw('reaction_type, COUNT(*) as aggregate_count')
            ->groupBy('reaction_type')
            ->pluck('aggregate_count', 'reaction_type')
            ->map(fn ($count) => (int) $count)
            ->all();

        return [
            'like' => (int) ($counts['like'] ?? $sound->like_count ?? 0),
            'heart' => (int) ($counts['heart'] ?? 0),
            'leaf' => (int) ($counts['leaf'] ?? 0),
            'star' => (int) ($counts['star'] ?? 0),
        ];
    }

    private function reactionsTableExists(): bool
    {
        return Schema::hasTable('radio_reactions');
    }

    private function fallbackReactionKey(string $sessionToken, int $soundId, string $type): string
    {
        return 'radio:reaction:fallback:'.hash('sha256', "{$sessionToken}|{$soundId}|{$type}");
    }
}
