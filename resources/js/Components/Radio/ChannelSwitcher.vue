<script setup>
defineProps({
    channels: {
        type: Array,
        default: () => [],
    },
    activeSlug: {
        type: String,
        default: 'main',
    },
});
</script>

<template>
    <div v-if="channels.length > 1" class="mb-6 flex flex-wrap gap-2">
        <a
            v-for="channel in channels"
            :key="channel.slug"
            :href="channel.url || '/radio'"
            class="rounded-full border px-3 py-1.5 text-sm transition"
            :class="channel.slug === activeSlug
                ? 'border-arbor-emerald bg-arbor-emerald text-arbor-night'
                : 'border-arbor-glass-border bg-arbor-deep/70 text-arbor-sage hover:text-arbor-cream'"
        >
            {{ channel.name }}
        </a>
    </div>
</template>
