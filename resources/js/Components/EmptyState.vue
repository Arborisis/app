<script setup>
const props = defineProps({
    icon: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    cta: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <div class="poetic-empty py-16 animate-fade-in">
        <div v-if="icon" class="poetic-empty-icon flex items-center justify-center transition-transform duration-300 hover:scale-110">
            <svg class="relative w-8 h-8 text-arbor-lichen/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" :d="icon" />
            </svg>
        </div>
        <h3 class="font-display text-2xl text-arbor-cream mb-2">{{ title }}</h3>
        <p v-if="description" class="text-arbor-sage text-sm mb-6 max-w-sm mx-auto leading-relaxed">{{ description }}</p>
        <slot />
    </div>
</template>
