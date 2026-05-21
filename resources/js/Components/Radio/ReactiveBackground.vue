<script setup>
import { computed } from 'vue';

const props = defineProps({
    bands: {
        type: Object,
        default: () => ({ low: 0, mid: 0, high: 0, rms: 0 }),
    },
});

const styleVars = computed(() => {
    const low = props.bands?.low || 0;
    const mid = props.bands?.mid || 0;
    const high = props.bands?.high || 0;

    return {
        '--radio-low': (1 + low * 0.8).toFixed(3),
        '--radio-mid': (1 + mid * 0.6).toFixed(3),
        '--radio-high': (1 + high * 0.7).toFixed(3),
    };
});
</script>

<template>
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-arbor-night" :style="styleVars">
        <div class="radio-veil radio-veil-a"></div>
        <div class="radio-veil radio-veil-b"></div>
        <div class="radio-veil radio-veil-c"></div>
        <div class="absolute inset-0 opacity-[0.07] radio-grain"></div>
    </div>
</template>

<style scoped>
.radio-veil {
    position: absolute;
    border-radius: 9999px;
    filter: blur(52px);
    opacity: 0.42;
    transform-origin: center;
}

.radio-veil-a {
    width: 52vw;
    height: 52vw;
    left: -12vw;
    top: 6rem;
    background: rgba(71, 131, 89, 0.6);
    transform: scale(var(--radio-low));
}

.radio-veil-b {
    width: 44vw;
    height: 44vw;
    right: -8vw;
    top: 18vh;
    background: rgba(186, 157, 83, 0.34);
    transform: scale(var(--radio-mid));
}

.radio-veil-c {
    width: 36vw;
    height: 36vw;
    left: 34vw;
    bottom: -18vw;
    background: rgba(41, 88, 78, 0.72);
    transform: scale(var(--radio-high));
}

.radio-grain {
    background-image:
        radial-gradient(circle at 20% 30%, rgba(255,255,255,.8) 0 1px, transparent 1px),
        radial-gradient(circle at 80% 70%, rgba(255,255,255,.45) 0 1px, transparent 1px);
    background-size: 31px 31px, 47px 47px;
}
</style>
