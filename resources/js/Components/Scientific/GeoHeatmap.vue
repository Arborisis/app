<script setup>
import { ref, onMounted, watch } from 'vue';
import Plotly from 'plotly.js-dist-min';

const props = defineProps({
    points: { type: Array, default: () => [] },
});

const chartRef = ref(null);

function render() {
    if (!chartRef.value || props.points.length === 0) return;

    const lats = props.points.map(p => p.lat);
    const lngs = props.points.map(p => p.lng);
    const counts = props.points.map(p => p.count);
    const texts = props.points.map(p => `${p.count} enregistrement${p.count > 1 ? 's' : ''}<br>${p.categories ?? ''}`);

    const trace = {
        type: 'scattergeo',
        mode: 'markers',
        lat: lats,
        lon: lngs,
        text: texts,
        marker: {
            size: counts.map(c => Math.max(6, Math.min(30, c * 3))),
            color: counts,
            colorscale: [
                [0, '#8FA68E'],
                [0.3, '#4A6741'],
                [0.6, '#34D399'],
                [1, '#D4A574'],
            ],
            cmin: Math.min(...counts),
            cmax: Math.max(...counts),
            colorbar: {
                title: { text: 'Densité', font: { color: '#F3F0E7', size: 11 } },
                tickfont: { color: '#F3F0E7', size: 10 },
                thickness: 12,
                len: 0.6,
                bgcolor: 'rgba(17,24,39,0.6)',
            },
            line: {
                color: 'rgba(255,255,255,0.15)',
                width: 0.5,
            },
            opacity: 0.85,
        },
        hovertemplate: '<b>%{text}</b><extra></extra>',
    };

    const layout = {
        paper_bgcolor: 'transparent',
        plot_bgcolor: 'transparent',
        font: { color: '#F3F0E7', family: 'DM Sans, sans-serif' },
        margin: { t: 10, r: 10, b: 10, l: 10 },
        geo: {
            bgcolor: 'transparent',
            showland: true,
            landcolor: '#1a1f2e',
            showocean: true,
            oceancolor: '#0B1220',
            showlakes: true,
            lakecolor: '#0B1220',
            showrivers: false,
            showcountries: true,
            countrycolor: 'rgba(255,255,255,0.06)',
            countrywidth: 0.5,
            showcoastlines: true,
            coastlinecolor: 'rgba(255,255,255,0.08)',
            coastlinewidth: 0.5,
            showframe: false,
            projection: {
                type: 'equirectangular',
            },
            center: { lat: 46.6, lon: 1.9 },
            lonaxis: { range: [-10, 20] },
            lataxis: { range: [35, 55] },
        },
        hoverlabel: {
            bgcolor: '#111827',
            bordercolor: 'rgba(255,255,255,0.1)',
            font: { color: '#F3F0E7' },
        },
    };

    Plotly.newPlot(chartRef.value, [trace], layout, { responsive: true, displayModeBar: false });
}

onMounted(render);
watch(() => props.points, render, { deep: true });
</script>

<template>
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display text-xl font-semibold text-arbor-cream">Heatmap géographique</h3>
            <span class="text-xs text-arbor-sage bg-arbor-deep px-2 py-1 rounded border border-arbor-glass-border">
                Agrégation 0.1° — coordonnées approximatives
            </span>
        </div>
        <div ref="chartRef" class="w-full h-[500px]"></div>
        <p class="mt-3 text-xs text-arbor-sage/70">
            Les coordonnées exactes sont volontairement agrégées pour préserver la confidentialité des lieux sensibles.
        </p>
    </div>
</template>
