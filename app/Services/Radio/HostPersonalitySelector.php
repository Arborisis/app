<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Enums\RadioDaypart;
use App\Models\RadioDjAnnouncement;
use App\Models\RadioHostPersonality;
use Illuminate\Support\Carbon;

/**
 * Resolves which RadioHostPersonality should voice the next DJ intro.
 *
 * Selection rules:
 *  1. Only personalities matching the show_type are eligible (filter by show_types JSON).
 *  2. Among those, prefer personalities whose dayparts include the current daypart.
 *  3. Within the eligible set, apply a priority weighting + anti-streak rule
 *     (don't pick the same personality more than `solo_streak_max` times in a row).
 *  4. If no personality fits the daypart, fall back to highest-priority active perso for the show_type.
 *  5. Final fallback: any active personality.
 */
class HostPersonalitySelector
{
    public function __construct(private readonly ?Carbon $now = null) {}

    public function select(
        string $showType,
        ?RadioDaypart $daypart = null,
        ?int $excludePersonalityId = null,
    ): ?RadioHostPersonality {
        $now = $this->now ?? now();
        $daypart ??= RadioDaypart::fromCarbon($now);

        $candidates = RadioHostPersonality::query()
            ->active()
            ->orderByDesc('priority')
            ->get()
            ->filter(fn (RadioHostPersonality $p): bool => $p->supportsShowType($showType));

        if ($excludePersonalityId !== null) {
            $candidates = $candidates->reject(fn (RadioHostPersonality $p) => $p->id === $excludePersonalityId);
        }

        if ($candidates->isEmpty()) {
            return null;
        }

        $daypartMatches = $candidates
            ->filter(fn (RadioHostPersonality $p): bool => $p->supportsDaypart($daypart))
            ->values();

        $primaryPool = $daypartMatches->isNotEmpty() ? $daypartMatches : $candidates;

        $picked = $this->avoidStreak($primaryPool->all(), $showType);

        if ($picked !== null && ! $this->isStreakBlocked($picked)) {
            return $picked;
        }

        // The daypart pool was a single-perso streak — widen to all candidates to break it.
        $widerPool = $candidates->reject(fn (RadioHostPersonality $p) => $picked !== null && $p->id === $picked->id);

        if ($widerPool->isNotEmpty()) {
            return $widerPool->first();
        }

        return $picked;
    }

    private function isStreakBlocked(RadioHostPersonality $candidate): bool
    {
        $streakMax = (int) config('radio.host.dj_solo_streak_max', 2);
        if ($streakMax <= 0) {
            return false;
        }

        $lastSlugs = RadioDjAnnouncement::query()
            ->whereNotNull('personality_slug')
            ->latest('generated_at')
            ->limit($streakMax)
            ->pluck('personality_slug')
            ->all();

        return count($lastSlugs) >= $streakMax
            && count(array_unique($lastSlugs)) === 1
            && $lastSlugs[0] === $candidate->slug;
    }

    /**
     * Returns the recent opening fingerprints to feed the anti-repetition prompt block.
     *
     * @return array<int, string>
     */
    public function recentOpenings(RadioHostPersonality $personality, int $limit = 30): array
    {
        return RadioDjAnnouncement::query()
            ->where('personality_slug', $personality->slug)
            ->whereNotNull('phrase_fingerprint')
            ->latest('generated_at')
            ->limit($limit)
            ->pluck('phrase_fingerprint')
            ->all();
    }

    /**
     * Recent "opening" text (8 first words) for the prompt — only used to feed the LLM
     * with examples to avoid, not to hash-compare.
     *
     * @return array<int, string>
     */
    public function recentOpeningTexts(RadioHostPersonality $personality, int $limit = 6): array
    {
        return RadioDjAnnouncement::query()
            ->where('personality_slug', $personality->slug)
            ->whereNotNull('text')
            ->latest('generated_at')
            ->limit($limit)
            ->pluck('text')
            ->map(fn (?string $text) => $text === null ? '' : $this->openingFromText($text))
            ->filter(fn (string $opening) => $opening !== '')
            ->values()
            ->all();
    }

    public function openingFromText(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/[\.\,\;\:\!\?\…]+/u', '', $normalized) ?? $normalized;
        $words = preg_split('/\s+/u', $normalized) ?: [];
        $first = array_slice($words, 0, 8);

        return implode(' ', $first);
    }

    /**
     * @param  array<int, RadioHostPersonality>  $pool
     */
    private function avoidStreak(array $pool, string $showType): ?RadioHostPersonality
    {
        if ($pool === []) {
            return null;
        }

        $streakMax = (int) config('radio.host.dj_solo_streak_max', 2);

        if ($streakMax <= 0 || count($pool) === 1) {
            return $pool[0];
        }

        $lastSlugs = RadioDjAnnouncement::query()
            ->whereNotNull('personality_slug')
            ->latest('generated_at')
            ->limit($streakMax)
            ->pluck('personality_slug')
            ->all();

        // If the last N announcements were all the same personality, pick another.
        if (count($lastSlugs) >= $streakMax && count(array_unique($lastSlugs)) === 1) {
            $streakSlug = $lastSlugs[0];

            foreach ($pool as $candidate) {
                if ($candidate->slug !== $streakSlug) {
                    return $candidate;
                }
            }
        }

        return $pool[0];
    }
}
