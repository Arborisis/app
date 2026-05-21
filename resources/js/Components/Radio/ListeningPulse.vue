<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    count: {
        type: Number,
        default: 0,
    },
});

const pulsing = ref(false);

watch(() => props.count, () => {
    pulsing.value = true;
    setTimeout(() => {
        pulsing.value = false;
    }, 650);
});

const label = computed(() => `${props.count} auditeur${props.count > 1 ? 's' : ''}`);
</script>

<template>
    <div
        class="inline-flex items-center gap-2 rounded-full border border-arbor-glass-border bg-arbor-deep/75 px-3 py-1.5 text-sm text-arbor-sage transition"
        :class="pulsing ? 'scale-[1.03] border-arbor-emerald/50 text-arbor-cream' : ''"
    >
        <span class="relative flex h-2.5 w-2.5">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-arbor-emerald opacity-50"></span>
            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-arbor-emerald"></span>
        </span>
        <span>{{ label }}</span>
    </div>
</template>
