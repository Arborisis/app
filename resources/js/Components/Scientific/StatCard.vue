<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: Number, required: true },
    suffix: { type: String, default: '' },
    decimals: { type: Number, default: 0 },
    icon: { type: String, default: '' },
});

const displayValue = ref(0);

const iconMap = {
    audio: 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
    time: 'M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z',
    users: 'M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a3 3 0 11-6 0 3 3 0 016 0z',
    location: 'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
    data: 'M4 19V5m0 14h16M8 17V9m4 8V7m4 10v-5',
    score: 'M9 12l2 2 4-4m5.618-4.016A12 12 0 0112 2.944a12 12 0 01-8.618 3.04A12 12 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z',
};

function animateCount(target, duration = 1200) {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        displayValue.value = target;
        return;
    }

    const start = performance.now();
    const from = 0;

    function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        displayValue.value = from + (target - from) * eased;
        if (progress < 1) {
            requestAnimationFrame(step);
        }
    }
    requestAnimationFrame(step);
}

onMounted(() => {
    animateCount(props.value);
});
</script>

<template>
    <div class="field-stat hover-lift transition-transform duration-300">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-arbor-sage text-sm font-medium tracking-wide uppercase">{{ label }}</p>
                <p class="mt-2 text-3xl font-mono font-semibold text-arbor-cream">
                    {{ decimals > 0 ? displayValue.toFixed(decimals) : Math.round(displayValue).toLocaleString('fr-FR') }}
                    <span v-if="suffix" class="text-lg text-arbor-sage ml-1">{{ suffix }}</span>
                </p>
            </div>
            <div v-if="icon" class="grid h-10 w-10 place-items-center rounded-full border border-arbor-lichen/20 bg-arbor-lichen/10 text-arbor-lichen">
                <svg v-if="iconMap[icon]" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="iconMap[icon]" />
                </svg>
                <span v-else class="text-lg">{{ icon }}</span>
            </div>
        </div>
    </div>
</template>
