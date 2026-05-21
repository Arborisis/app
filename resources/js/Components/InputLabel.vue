<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="block text-sm font-medium text-arbor-sage mb-1.5">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
