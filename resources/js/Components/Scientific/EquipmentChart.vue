<script setup>
import { ref, onMounted, watch } from 'vue';
import Plotly from 'plotly.js-dist-min';

const props = defineProps({
    data: { type: Array, default: () => [] },
});

const chartRef = ref(null);

function render() {
    if (!chartRef.value || props.data.length === 0) return;

    const names = props.data.map(d => d.name);
    const counts = props.data.map(d => d.count);

    const data = [{
        y: names.reverse(),
        x: counts.reverse(),
        type: 'bar',
        orientation: 'h',
        marker: {
            color: counts.map((_, i) => {
                const palette = ['#4A6741', '#5a7d4f', '#34D399', '#8FA68E', '#D4A574', '#B87333', '#C9842B', '#2a3142', '#1a1f2e', '#0B1220'];
                return palette[i % palette.length];
            }),
            line: { color: 'rgba(255,255,255,0.08)', width: 1 },
        },
        text: counts.reverse().map(String),
        textposition: 'auto',
        textfont: { color: '#F3F0E7', size: 11 },
    }];

    const layout = {
        paper_bgcolor: 'transparent',
        plot_bgcolor: 'transparent',
        font: { color: '#F3F0E7', family: 'DM Sans, sans-serif', size: 11 },
        margin: { t: 10, r: 30, b: 30, l: 140 },
        xaxis: {
            gridcolor: 'rgba(255,255,255,0.05)',
            zerolinecolor: 'rgba(255,255,255,0.1)',
        },
        yaxis: {
            gridcolor: 'transparent',
            zerolinecolor: 'transparent',
        },
        showlegend: false,
        hoverlabel: {
            bgcolor: '#111827',
            bordercolor: 'rgba(255,255,255,0.1)',
            font: { color: '#F3F0E7' },
        },
    };

    Plotly.newPlot(chartRef.value, data, layout, { responsive: true, displayModeBar: false });
}

onMounted(render);
watch(() => props.data, render, { deep: true });
</script>

<template>
    <div class="glass-card p-6">
        <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Équipements</h3>
        <div ref="chartRef" class="w-full h-80"></div>
    </div>
</template>
