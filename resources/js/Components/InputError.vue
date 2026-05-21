<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-red-400 mt-1.5">
            {{ message }}
        </p>
    </div>
</template>
