<script setup>
const props = defineProps({
    achievements: {
        type: Array,
        default: () => []
    },
    medals: {
        type: Array,
        default: () => []
    }
});

const formatDate = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);

    if (diff < 60) return 'À l\'instant';
    if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`;
    if (diff < 604800) return `Il y a ${Math.floor(diff / 86400)} j`;

    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
    });
};

const rarityGlow = {
    common: 'border-arbor-mineral/20 text-arbor-sage',
    uncommon: 'border-arbor-firefly/30 text-arbor-firefly',
    rare: 'border-arbor-cyan-trace/30 text-arbor-cyan-trace',
    epic: 'border-arbor-lichen/35 text-arbor-lichen',
    legendary: 'border-arbor-amber/40 text-arbor-amber',
};

const getRarityClass = (rarity) => rarityGlow[rarity] || rarityGlow.common;
</script>

<template>
    <div class="trace-frame p-6">
        <!-- Achievements -->
        <div class="relative z-10 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-arbor-lichen/12 border border-arbor-lichen/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-arbor-lichen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <h3 class="font-display text-lg font-semibold text-arbor-cream">Carnet d'exploration</h3>
                </div>
                <span class="text-arbor-sage text-xs">{{ achievements.length }} récents</span>
            </div>

            <div v-if="achievements.length > 0" class="space-y-3">
                <div
                    v-for="(achievement, index) in achievements"
                    :key="achievement.id"
                    class="group flex items-center gap-3 rounded-lg border border-arbor-mineral/10 bg-arbor-mist/[0.035] p-3 transition-transform duration-300 hover:border-arbor-lichen/30 hover:bg-arbor-mist/[0.06]"
                    :style="`animation: slideInRight 0.4s ease-out forwards; animation-delay: ${index * 0.06}s; opacity: 0;`"
                >
                    <div class="w-10 h-10 rounded-full bg-arbor-lichen/10 border border-arbor-lichen/20 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="text-lg">{{ achievement.icon || '🏆' }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-arbor-cream text-sm font-medium truncate">{{ achievement.title }}</h4>
                        <p class="text-arbor-sage text-xs truncate">{{ achievement.description }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="block text-arbor-lichen text-xs font-medium">+{{ achievement.points }} XP</span>
                        <span class="text-arbor-sage text-[10px]">{{ formatDate(achievement.unlocked_at) }}</span>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-6">
                <p class="text-arbor-sage text-xs">Aucun trophée débloqué pour l'instant.</p>
                <p class="text-arbor-sage/60 text-[10px] mt-1">Participez à la communauté pour en gagner.</p>
            </div>
        </div>

        <!-- Divider -->
        <div class="relative z-10 h-px bg-arbor-glass-border mb-6" />

        <!-- Medals -->
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-arbor-amber/15 border border-arbor-amber/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-arbor-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a4 4 0 00-4-4H5.52a2 2 0 00-1.98 1.73l-.43 3.02a2 2 0 001.65 2.25L8 8m4 0h4m-4 0V6a4 4 0 014-4h2.48a2 2 0 011.98 1.73l.43 3.02a2 2 0 01-1.65 2.25L16 8m-4 0v13m0 0l-3-3m3 3l3-3" />
                        </svg>
                    </div>
                    <h3 class="font-display text-lg font-semibold text-arbor-cream">Médailles</h3>
                </div>
                <span class="text-arbor-sage text-xs">{{ medals.length }} récentes</span>
            </div>

            <div v-if="medals.length > 0" class="grid grid-cols-4 gap-2">
                <div
                    v-for="(medal, index) in medals"
                    :key="medal.id"
                    class="group relative flex flex-col items-center text-center p-2 rounded-lg bg-arbor-mist/[0.035] border hover:border-arbor-amber/30 hover:bg-arbor-mist/[0.06] transition-transform duration-300 cursor-default"
                    :class="getRarityClass(medal.rarity)"
                    :style="`animation: scaleIn 0.4s ease-out forwards; animation-delay: ${index * 0.08}s; opacity: 0;`"
                >
                    <div class="w-10 h-10 rounded-full bg-arbor-night/60 border border-current/20 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform shadow-lichen">
                        <span class="text-lg">{{ medal.icon || '🎖️' }}</span>
                    </div>
                    <span class="text-[10px] font-medium text-arbor-cream leading-tight line-clamp-2">{{ medal.name }}</span>

                    <!-- Tooltip on hover -->
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1.5 bg-arbor-night border border-arbor-fog rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-20 w-32">
                        <p class="text-arbor-cream text-xs font-medium">{{ medal.name }}</p>
                        <p class="text-arbor-sage text-[10px] mt-0.5">{{ medal.description }}</p>
                        <p class="text-arbor-sage/60 text-[10px] mt-1">{{ formatDate(medal.unlocked_at) }}</p>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-6">
                <p class="text-arbor-sage text-xs">Aucune médaille pour l'instant.</p>
                <p class="text-arbor-sage/60 text-[10px] mt-1">Explorez la carte pour en découvrir.</p>
            </div>
        </div>
    </div>
</template>
