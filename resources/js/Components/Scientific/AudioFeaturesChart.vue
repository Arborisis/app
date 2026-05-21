<script setup>
import { ref, onMounted, watch } from 'vue';
import Plotly from 'plotly.js-dist-min';

const props = defineProps({
    features: { type: Object, default: () => ({}) },
    distributions: { type: Object, default: () => ({}) },
});

const radarRef = ref(null);
const histRef = ref(null);

const featureLabels = {
    zcr: 'ZCR',
    rms: 'RMS',
    spectral_centroid: 'Centroid',
    spectral_rolloff: 'Rolloff',
    spectral_bandwidth: 'Bandwidth',
    zero_crossing_rate: 'ZCR (alt.)',
};

function renderRadar() {
    if (!radarRef.value) return;
    const entries = Object.entries(props.features);
    if (entries.length === 0) return;

    const labels = entries.map(([k]) => featureLabels[k] ?? k);
    const means = entries.map(([, v]) => v.mean);
    const maxVal = Math.max(...means) || 1;
    const normalized = means.map(m => (m / maxVal) * 100);

    const data = [{
        type: 'scatterpolar',
        r: [...normalized, normalized[0]],
        theta: [...labels, labels[0]],
        fill: 'toself',
        fillcolor: 'rgba(52, 211, 153, 0.15)',
        line: { color: '#34D399', width: 2 },
        marker: { size: 6, color: '#34D399' },
        name: 'Moyenne normalisée',
    }];

    const layout = {
        paper_bgcolor: 'transparent',
        plot_bgcolor: 'transparent',
        font: { color: '#F3F0E7', family: 'DM Sans, sans-serif', size: 11 },
        margin: { t: 40, r: 40, b: 40, l: 40 },
        polar: {
            bgcolor: 'transparent',
            radialaxis: {
                visible: true,
                range: [0, 110],
                gridcolor: 'rgba(255,255,255,0.08)',
                tickfont: { size: 9 },
            },
            angularaxis: {
                gridcolor: 'rgba(255,255,255,0.08)',
                tickfont: { size: 10 },
            },
        },
        showlegend: false,
    };

    Plotly.newPlot(radarRef.value, data, layout, { responsive: true, displayModeBar: false });
}

function renderHistogram() {
    if (!histRef.value) return;
    const entries = Object.entries(props.distributions);
    if (entries.length === 0) return;

    // Show only the first feature with distribution data
    const [key, bins] = entries[0];
    if (!bins || bins.length === 0) return;

    const mids = bins.map(b => b.mid);
    const counts = bins.map(b => b.count);

    const data = [{
        x: mids,
        y: counts,
        type: 'bar',
        name: featureLabels[key] ?? key,
        marker: {
            color: '#D4A574',
            line: { color: 'rgba(255,255,255,0.1)', width: 1 },
        },
        text: counts.map(String),
        textposition: 'auto',
        textfont: { color: '#F3F0E7', size: 10 },
    }];

    const layout = {
        paper_bgcolor: 'transparent',
        plot_bgcolor: 'transparent',
        font: { color: '#F3F0E7', family: 'DM Sans, sans-serif', size: 12 },
        margin: { t: 30, r: 20, b: 50, l: 50 },
        xaxis: {
            title: { text: featureLabels[key] ?? key, font: { size: 11 } },
            gridcolor: 'rgba(255,255,255,0.05)',
            zerolinecolor: 'rgba(255,255,255,0.1)',
        },
        yaxis: {
            title: { text: 'Fréquence', font: { size: 11 } },
            gridcolor: 'rgba(255,255,255,0.05)',
            zerolinecolor: 'rgba(255,255,255,0.1)',
        },
        showlegend: false,
    };

    Plotly.newPlot(histRef.value, data, layout, { responsive: true, displayModeBar: false });
}

onMounted(() => {
    renderRadar();
    renderHistogram();
});
watch(() => props.features, renderRadar, { deep: true });
watch(() => props.distributions, renderHistogram, { deep: true });
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass-card p-6">
            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Profil audio moyen</h3>
            <div ref="radarRef" class="w-full h-80"></div>
            <p class="mt-2 text-xs text-arbor-sage/70">Valeurs normalisées sur l'échelle du dataset.</p>
        </div>
        <div class="glass-card p-6">
            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Distribution — {{ Object.keys(distributions)[0] ? (featureLabels[Object.keys(distributions)[0]] ?? Object.keys(distributions)[0]) : '' }}</h3>
            <div ref="histRef" class="w-full h-80"></div>
            <p class="mt-2 text-xs text-arbor-sage/70">Histogramme de la première feature disponible.</p>
        </div>
    </div>
</template>
