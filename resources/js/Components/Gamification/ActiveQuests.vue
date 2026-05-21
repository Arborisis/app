<script setup>
const props = defineProps({
    quests: {
        type: Array,
        default: () => []
    }
});

const statusConfig = {
    in_progress: { label: 'En cours', color: 'text-arbor-firefly bg-arbor-firefly/10 border-arbor-firefly/20' },
    completed: { label: 'Terminée', color: 'text-arbor-amber bg-arbor-amber/10 border-arbor-amber/20' },
    claimed: { label: 'Réclamée', color: 'text-arbor-sage bg-arbor-sage/10 border-arbor-sage/20' },
    available: { label: 'Disponible', color: 'text-sky-400 bg-sky-400/10 border-sky-400/20' },
};

const getStatusConfig = (status) => statusConfig[status] || statusConfig.available;
</script>

<template>
    <div class="trace-frame p-6 lg:p-8">
        <div class="relative z-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <span class="atlas-kicker">Carnet saisonnier</span>
                <h2 class="font-display text-2xl font-semibold text-arbor-cream mt-1">
                    Quêtes actives
                </h2>
            </div>
            <div class="w-10 h-10 rounded-full bg-arbor-lichen/15 border border-arbor-lichen/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-arbor-lichen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            </div>
        </div>

        <div v-if="quests.length > 0" class="space-y-4">
            <div
                v-for="(quest, index) in quests"
                :key="quest.id || quest.title + index"
                class="group relative bg-arbor-mist/[0.035] border border-arbor-mineral/10 rounded-lg p-4 hover:border-arbor-lichen/30 hover:bg-arbor-mist/[0.06] transition-colors duration-300"
                :style="`animation: slideInRight 0.4s ease-out forwards; animation-delay: ${index * 0.1}s; opacity: 0;`"
            >
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-arbor-cream text-sm font-medium truncate group-hover:text-arbor-lichen transition-colors">
                                {{ quest.title }}
                            </h3>
                            <span
                                v-if="quest.is_repeatable"
                                class="shrink-0 text-[10px] px-1.5 py-0.5 rounded bg-arbor-sage/10 text-arbor-sage border border-arbor-sage/20"
                            >
                                Répétable
                            </span>
                        </div>
                        <p class="text-arbor-sage text-xs line-clamp-2 leading-relaxed">
                            {{ quest.description }}
                        </p>
                    </div>
                    <span
                        class="shrink-0 text-[10px] font-medium px-2 py-1 rounded-lg border"
                        :class="getStatusConfig(quest.status).color"
                    >
                        {{ getStatusConfig(quest.status).label }}
                    </span>
                </div>

                <!-- Progress bar -->
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-1.5 bg-arbor-deep rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-[width] duration-700 ease-out"
                            :class="{
                                'bg-arbor-firefly': quest.status === 'in_progress' || quest.status === 'available',
                                'bg-arbor-amber': quest.status === 'completed',
                                'bg-arbor-sage': quest.status === 'claimed',
                            }"
                            :style="{ width: `${quest.progress_percentage}%` }"
                        />
                    </div>
                    <span class="text-arbor-sage text-xs font-mono shrink-0">
                        {{ quest.current_progress }} / {{ quest.target_progress }}
                    </span>
                </div>

                <!-- Reward hint -->
                <div class="mt-2.5 flex items-center gap-1.5">
                    <svg class="w-3 h-3 text-arbor-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="text-arbor-amber text-xs">+{{ quest.reward_xp }} XP</span>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-8">
            <div class="poetic-empty-icon flex items-center justify-center">
                <svg class="w-6 h-6 text-arbor-moss/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <p class="text-arbor-sage text-sm">Aucune quête active pour le moment.</p>
        </div>
        </div>
    </div>
</template>
