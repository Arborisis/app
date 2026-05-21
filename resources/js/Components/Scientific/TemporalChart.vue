<script setup>
import { ref, onMounted, watch } from 'vue';
import Plotly from 'plotly.js-dist-min';

const props = defineProps({
    data: { type: Array, default: () => [] },
});

const chartRef = ref(null);

function render() {
    if (!chartRef.value || props.data.length === 0) return;

    const months = props.data.map(d => d.month);
    const counts = props.data.map(d => d.count);
    const avgDurations = props.data.map(d => d.avg_duration);

    const trace1 = {
        x: months,
        y: counts,
        type: 'scatter',
        mode: 'lines+markers',
        name: 'Enregistrements',
        line: { color: '#34D399', width: 2, shape: 'spline' },
        marker: { size: 6, color: '#34D399' },
        fill: 'tozeroy',
        fillcolor: 'rgba(52, 211, 153, 0.08)',
        yaxis: 'y1',
    };

    const trace2 = {
        x: months,
        y: avgDurations,
        type: 'scatter',
        mode: 'lines+markers',
        name: 'Durée moyenne (s)',
        line: { color: '#D4A574', width: 2, dash: 'dot' },
        marker: { size: 5, color: '#D4A574' },
        yaxis: 'y2',
    };

    const layout = {
        paper_bgcolor: 'transparent',
        plot_bgcolor: 'transparent',
        font: { color: '#F3F0E7', family: 'DM Sans, sans-serif', size: 12 },
        margin: { t: 30, r: 60, b: 60, l: 50 },
        xaxis: {
            gridcolor: 'rgba(255,255,255,0.05)',
            zerolinecolor: 'rgba(255,255,255,0.1)',
            tickangle: -45,
        },
        yaxis: {
            title: { text: 'Quantité', font: { color: '#34D399', size: 11 } },
            gridcolor: 'rgba(255,255,255,0.05)',
            zerolinecolor: 'rgba(255,255,255,0.1)',
            side: 'left',
        },
        yaxis2: {
            title: { text: 'Durée moy. (s)', font: { color: '#D4A574', size: 11 } },
            overlaying: 'y',
            side: 'right',
            showgrid: false,
        },
        legend: {
            orientation: 'h',
            y: -0.2,
            x: 0.5,
            xanchor: 'center',
            font: { size: 11 },
        },
        hoverlabel: {
            bgcolor: '#111827',
            bordercolor: 'rgba(255,255,255,0.1)',
            font: { color: '#F3F0E7' },
        },
    };

    Plotly.newPlot(chartRef.value, [trace1, trace2], layout, { responsive: true, displayModeBar: false });
}

onMounted(render);
watch(() => props.data, render, { deep: true });
</script>

<template>
    <div class="glass-card p-6">
        <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Évolution temporelle</h3>
        <div ref="chartRef" class="w-full h-80"></div>
    </div>
</template>
