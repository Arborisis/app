<script setup>
import { ref, onMounted, watch } from 'vue';
import Plotly from 'plotly.js-dist-min';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const chartRef = ref(null);

const colors = ['#4A6741', '#34D399', '#8FA68E', '#D4A574', '#B87333', '#2a3142', '#5a7d4f', '#C9842B'];

function render() {
    if (!chartRef.value || props.categories.length === 0) return;

    const names = props.categories.map(c => c.name);
    const counts = props.categories.map(c => c.count);
    const avgDurations = props.categories.map(c => c.avg_duration);

    const data = [
        {
            x: names,
            y: counts,
            type: 'bar',
            name: 'Nombre d\'enregistrements',
            marker: {
                color: names.map((_, i) => colors[i % colors.length]),
                line: { color: 'rgba(255,255,255,0.1)', width: 1 },
            },
            text: counts.map(String),
            textposition: 'auto',
            textfont: { color: '#F3F0E7', size: 12 },
        },
    ];

    const layout = {
        paper_bgcolor: 'transparent',
        plot_bgcolor: 'transparent',
        font: { color: '#F3F0E7', family: 'DM Sans, sans-serif', size: 12 },
        margin: { t: 30, r: 20, b: 60, l: 50 },
        xaxis: {
            tickangle: -30,
            gridcolor: 'rgba(255,255,255,0.05)',
            zerolinecolor: 'rgba(255,255,255,0.1)',
        },
        yaxis: {
            gridcolor: 'rgba(255,255,255,0.05)',
            zerolinecolor: 'rgba(255,255,255,0.1)',
            title: { text: 'Quantité', font: { size: 12 } },
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
watch(() => props.categories, render, { deep: true });
</script>

<template>
    <div class="glass-card p-6">
        <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Répartition par catégorie</h3>
        <div ref="chartRef" class="w-full h-72"></div>
    </div>
</template>
