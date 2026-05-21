<script setup>
const props = defineProps({
    analysis: Object,
});

const formatNum = (val, digits = 2) => {
    if (val === null || val === undefined) return '-';
    return Number(val).toFixed(digits);
};

const summary = computed(() => {
    const features = props.analysis?.features ?? {};
    const temporal = features.temporal ?? {};
    const spectral = features.spectral ?? {};

    return {
        duration: temporal.duration_seconds ?? 0,
        rms: temporal.rms?.stats?.mean ?? null,
        zcr: temporal.zcr?.stats?.mean ?? null,
        centroid: spectral.centroid?.stats?.mean ?? null,
    };
});
</script>

<script>
import { computed } from 'vue';
</script>

<template>
    <div v-if="analysis" class="glass-card p-5">
        <h4 class="font-semibold text-arbor-cream text-sm mb-3">Analyse audio</h4>

        <div class="grid grid-cols-2 gap-3">
            <div class="bg-arbor-glass/30 rounded-lg p-3">
                <div class="text-xs text-arbor-sage">Durée</div>
                <div class="text-sm font-mono text-arbor-cream">{{ formatNum(summary.duration) }}s</div>
            </div>
            <div class="bg-arbor-glass/30 rounded-lg p-3">
                <div class="text-xs text-arbor-sage">RMS moyen</div>
                <div class="text-sm font-mono text-arbor-cream">{{ formatNum(summary.rms) }}</div>
            </div>
            <div class="bg-arbor-glass/30 rounded-lg p-3">
                <div class="text-xs text-arbor-sage">ZCR moyen</div>
                <div class="text-sm font-mono text-arbor-cream">{{ formatNum(summary.zcr) }}</div>
            </div>
            <div class="bg-arbor-glass/30 rounded-lg p-3">
                <div class="text-xs text-arbor-sage">Centroïde (Hz)</div>
                <div class="text-sm font-mono text-arbor-cream">{{ formatNum(summary.centroid, 0) }}</div>
            </div>
        </div>

        <div v-if="analysis.visualizations?.length" class="mt-4">
            <img v-for="viz in analysis.visualizations.filter(v => v.type === 'mel_spectrogram')" :key="viz.id"
                :src="viz.url"
                alt="Spectrogramme Mel"
                class="w-full rounded-lg border border-arbor-glass-border"
                loading="lazy"
            >
        </div>
    </div>
</template>
