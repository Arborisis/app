<script setup>
import { computed } from 'vue';

const props = defineProps({
    level: { type: Number, default: 1 },
    xpTotal: { type: Number, default: 0 },
    xpForNextLevel: { type: Number, default: 100 },
    xpProgress: { type: Number, default: 0 },
    xpNeeded: { type: Number, default: 100 },
    progressPercentage: { type: Number, default: 0 },
    currentStreak: { type: Number, default: 0 },
    longestStreak: { type: Number, default: 0 },
    questsCompleted: { type: Number, default: 0 },
    achievementsUnlocked: { type: Number, default: 0 },
    medalsUnlocked: { type: Number, default: 0 },
});

const levelLabel = computed(() => {
    const labels = ['Novice', 'Apprenti', 'Explorateur', 'Aventurier', 'Naturaliste', 'Ranger', 'Druide', 'Gardien', 'Sage', 'Légende'];
    return labels[Math.min(props.level - 1, labels.length - 1)] || 'Novice';
});

const nextMilestone = computed(() => {
    if (props.progressPercentage >= 90) return 'Presque là !';
    if (props.progressPercentage >= 50) return 'À mi-chemin';
    return 'Continuez l\'exploration';
});
</script>

<template>
    <div class="glass-card p-6 lg:p-8 relative overflow-hidden">
        <!-- Ambient glow behind level -->
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-arbor-emerald/10 rounded-full blur-3xl pointer-events-none" />

        <div class="relative z-10">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <span class="text-arbor-sage text-xs font-medium uppercase tracking-wider">Progression</span>
                    <h2 class="font-display text-2xl font-semibold text-arbor-cream mt-1">
                        Niveau {{ level }}
                    </h2>
                    <p class="text-arbor-emerald text-sm font-medium mt-0.5">{{ levelLabel }}</p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-arbor-emerald/10 border border-arbor-emerald/20 flex items-center justify-center">
                    <span class="font-display text-2xl font-bold text-arbor-emerald">{{ level }}</span>
                </div>
            </div>

            <!-- XP Bar -->
            <div class="mb-6">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="text-arbor-sage">{{ xpTotal }} XP</span>
                    <span class="text-arbor-sage">{{ xpForNextLevel }} XP</span>
                </div>
                <div class="h-2.5 bg-arbor-charcoal rounded-full overflow-hidden border border-arbor-fog/50">
                    <div
                        class="h-full bg-gradient-to-r from-arbor-emerald to-arbor-moss rounded-full transition-[width] duration-1000 ease-out relative"
                        :style="{ width: `${progressPercentage}%` }"
                    >
                        <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_ease-in-out_infinite]" />
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-arbor-sage text-xs">{{ xpProgress }} / {{ xpNeeded }} XP</span>
                    <span class="text-arbor-emerald text-xs font-medium">{{ nextMilestone }}</span>
                </div>
            </div>

            <!-- Streaks -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-arbor-charcoal/60 border border-arbor-fog/50 rounded-xl p-3 text-center">
                    <div class="flex items-center justify-center gap-1.5 mb-1">
                        <svg class="w-4 h-4 text-arbor-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                        </svg>
                        <span class="font-display text-xl font-semibold text-arbor-cream">{{ currentStreak }}</span>
                    </div>
                    <span class="text-arbor-sage text-xs">Série actuelle</span>
                </div>
                <div class="bg-arbor-charcoal/60 border border-arbor-fog/50 rounded-xl p-3 text-center">
                    <div class="flex items-center justify-center gap-1.5 mb-1">
                        <svg class="w-4 h-4 text-arbor-moss-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        <span class="font-display text-xl font-semibold text-arbor-cream">{{ longestStreak }}</span>
                    </div>
                    <span class="text-arbor-sage text-xs">Record</span>
                </div>
            </div>

            <!-- Mini stats -->
            <div class="flex items-center justify-between pt-4 border-t border-arbor-glass-border">
                <div class="text-center flex-1">
                    <span class="block font-display text-lg font-semibold text-arbor-cream">{{ questsCompleted }}</span>
                    <span class="text-arbor-sage text-xs">Quêtes</span>
                </div>
                <div class="w-px h-8 bg-arbor-fog/50" />
                <div class="text-center flex-1">
                    <span class="block font-display text-lg font-semibold text-arbor-cream">{{ achievementsUnlocked }}</span>
                    <span class="text-arbor-sage text-xs">Trophées</span>
                </div>
                <div class="w-px h-8 bg-arbor-fog/50" />
                <div class="text-center flex-1">
                    <span class="block font-display text-lg font-semibold text-arbor-cream">{{ medalsUnlocked }}</span>
                    <span class="text-arbor-sage text-xs">Médailles</span>
                </div>
            </div>
        </div>
    </div>
</template>
