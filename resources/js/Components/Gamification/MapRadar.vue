<script setup>
import { computed } from 'vue';

const props = defineProps({
    active: { type: Boolean, default: false },
    style: { type: Object, default: () => ({}) },
});

const containerClass = computed(() =>
    props.active ? 'opacity-100' : 'opacity-0 pointer-events-none'
);
</script>

<template>
    <div
        class="absolute z-[400] pointer-events-none transition-opacity duration-700"
        :class="containerClass"
        :style="style"
        aria-hidden="true"
    >
        <div class="relative -translate-x-1/2 -translate-y-1/2">
            <div class="radar-ring radar-ring-1" />
            <div class="radar-ring radar-ring-2" />
            <div class="radar-ring radar-ring-3" />
        </div>
    </div>
</template>

<style scoped>
.radar-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    border-radius: 50%;
    border: 1px solid rgba(52, 211, 153, 0.15);
    background: radial-gradient(circle, rgba(52, 211, 153, 0.04) 0%, transparent 70%);
    transform: translate(-50%, -50%);
    pointer-events: none;
}

.radar-ring-1 {
    width: 120px;
    height: 120px;
    animation: radarExpand 3s ease-out infinite;
}

.radar-ring-2 {
    width: 120px;
    height: 120px;
    animation: radarExpand 3s ease-out infinite 1s;
}

.radar-ring-3 {
    width: 120px;
    height: 120px;
    animation: radarExpand 3s ease-out infinite 2s;
}

@keyframes radarExpand {
    0% {
        width: 40px;
        height: 40px;
        opacity: 0.6;
    }
    100% {
        width: 280px;
        height: 280px;
        opacity: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .radar-ring {
        animation: none !important;
        opacity: 0.15;
    }
}
</style>
