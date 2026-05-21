<script setup>
const props = defineProps({
    features: { type: Object, default: () => ({}) },
});

const formatNum = (val, digits = 4) => {
    if (val === null || val === undefined) return '-';
    return Number(val).toFixed(digits);
};

const sections = [
    {
        key: 'temporal',
        label: 'Temporelles',
        items: [
            { key: 'zcr', label: 'Zero-Crossing Rate', stat: 'mean' },
            { key: 'rms', label: 'RMS Energy', stat: 'mean' },
        ],
    },
    {
        key: 'spectral',
        label: 'Spectrales',
        items: [
            { key: 'centroid', label: 'Centroid (Hz)', stat: 'mean' },
            { key: 'bandwidth', label: 'Bandwidth (Hz)', stat: 'mean' },
            { key: 'rolloff', label: 'Rolloff (Hz)', stat: 'mean' },
            { key: 'flatness', label: 'Flatness', stat: 'mean' },
        ],
    },
];
</script>

<template>
    <div class="glass-card p-6">
        <h3 class="font-semibold text-arbor-cream text-sm mb-4">Features extraites</h3>

        <div class="space-y-5">
            <div v-for="section in sections" :key="section.key">
                <h4 class="text-xs text-arbor-sage uppercase tracking-wider mb-2">{{ section.label }}</h4>
                <div class="space-y-2">
                    <div v-for="item in section.items" :key="item.key"
                        class="flex items-center justify-between py-2 px-3 rounded-lg bg-arbor-glass/30">
                        <span class="text-sm text-arbor-sage">{{ item.label }}</span>
                        <span class="text-sm font-mono text-arbor-cream">
                            {{ formatNum(features[section.key]?.[item.key]?.stats?.[item.stat]) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
