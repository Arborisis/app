<script setup>
const props = defineProps({
    visualizations: { type: Array, default: () => [] },
});

const heatmapViz = computed(() => {
    return props.visualizations.find(v => v.type === 'feature_correlation');
});
</script>

<script>
import { computed } from 'vue';
</script>

<template>
    <div class="glass-card p-6">
        <h3 class="font-semibold text-arbor-cream text-sm mb-4">Corrélations</h3>

        <div class="rounded-xl overflow-hidden bg-arbor-deep border border-arbor-glass-border">
            <img v-if="heatmapViz?.url"
                :src="heatmapViz.url"
                alt="Matrice de corrélation"
                class="w-full h-auto object-contain"
                loading="lazy">
            <div v-else class="aspect-square flex items-center justify-center text-arbor-sage text-sm">
                Heatmap non disponible
            </div>
        </div>
    </div>
</template>
