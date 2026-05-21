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
        values: counts,
        labels: names,
        type: 'pie',
        hole: 0.45,
        marker: {
            colors: ['#4A6741', '#34D399', '#8FA68E', '#D4A574', '#B87333', '#2a3142', '#5a7d4f', '#C9842B', '#1a1f2e', '#0B1220'],
            line: { color: 'rgba(255,255,255,0.08)', width: 1 },
        },
        textinfo: 'label+percent',
        textposition: 'outside',
        textfont: { color: '#F3F0E7', size: 11 },
        hovertemplate: '<b>%{label}</b><br>%{value} enregistrements<br>%{percent}<extra></extra>',
    }];

    const layout = {
        paper_bgcolor: 'transparent',
        plot_bgcolor: 'transparent',
        font: { color: '#F3F0E7', family: 'DM Sans, sans-serif' },
        margin: { t: 20, r: 20, b: 20, l: 20 },
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
        <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Répartition par environnement</h3>
        <div ref="chartRef" class="w-full h-64"></div>
    </div>
</template>
