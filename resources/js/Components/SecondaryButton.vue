<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="btn-secondary text-xs font-semibold uppercase tracking-widest disabled:opacity-25"
    >
        <slot />
    </button>
</template>
