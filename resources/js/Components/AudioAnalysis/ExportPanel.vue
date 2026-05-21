<script setup>
const props = defineProps({
    analysisId: { type: Number, required: true },
    visualizations: { type: Array, default: () => [] },
});

const emit = defineEmits(['export']);

const exportFormat = (format) => {
    emit('export', { analysisId: props.analysisId, format });
};
</script>

<template>
    <div class="glass-card p-6">
        <h3 class="font-semibold text-arbor-cream text-sm mb-4">Exporter</h3>

        <div class="grid grid-cols-2 gap-3">
            <button @click="exportFormat('json')"
                class="px-4 py-3 rounded-xl bg-arbor-glass border border-arbor-glass-border text-arbor-sage text-sm hover:text-arbor-cream hover:bg-white/10 transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                JSON
            </button>

            <button @click="exportFormat('csv')"
                class="px-4 py-3 rounded-xl bg-arbor-glass border border-arbor-glass-border text-arbor-sage text-sm hover:text-arbor-cream hover:bg-white/10 transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                CSV
            </button>
        </div>

        <div v-if="visualizations.length" class="mt-4 pt-4 border-t border-arbor-glass-border">
            <h4 class="text-xs text-arbor-sage mb-2">Figures PNG</h4>
            <div class="flex flex-wrap gap-2">
                <a v-for="viz in visualizations" :key="viz.id" :href="viz.url" target="_blank"
                    class="px-3 py-1.5 rounded-lg bg-arbor-glass text-arbor-sage text-xs hover:text-arbor-emerald hover:bg-arbor-emerald/10 transition-colors">
                    {{ viz.type }}
                </a>
            </div>
        </div>
    </div>
</template>
