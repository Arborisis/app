<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    visualizations: { type: Array, default: () => [] },
});

const activeTab = ref('mel');

const tabs = [
    { key: 'stft', label: 'STFT' },
    { key: 'mel_spectrogram', label: 'Mel' },
    { key: 'mfcc', label: 'MFCC' },
];

const activeViz = computed(() => {
    return props.visualizations.find(v => v.type === activeTab.value);
});
</script>

<template>
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-arbor-cream text-sm">Spectrogrammes</h3>
            <div class="flex gap-1">
                <button v-for="tab in tabs" :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="activeTab === tab.key ? 'bg-arbor-emerald/20 text-arbor-emerald' : 'text-arbor-sage hover:text-arbor-cream'"
                    class="px-3 py-1 rounded-lg text-xs transition-colors">
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden bg-arbor-deep border border-arbor-glass-border">
            <img v-if="activeViz?.url"
                :src="activeViz.url"
                :alt="`Spectrogramme ${activeTab}`"
                class="w-full h-auto object-contain"
                loading="lazy">
            <div v-else class="aspect-video flex items-center justify-center text-arbor-sage text-sm">
                Aucun spectrogramme {{ activeTab }} disponible
            </div>
        </div>
    </div>
</template>
