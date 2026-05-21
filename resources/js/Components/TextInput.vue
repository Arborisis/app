<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="w-full bg-arbor-charcoal/80 border border-arbor-fog/60 rounded-xl px-4 py-3.5 text-arbor-cream placeholder:text-arbor-sage/60 focus:border-arbor-emerald/60 focus:ring-2 focus:ring-arbor-emerald/15 focus:outline-none transition-colors duration-300"
        v-model="model"
        ref="input"
        :aria-invalid="$attrs['aria-invalid'] ? true : false"
        :aria-describedby="$attrs['aria-describedby'] || undefined"
    />
</template>
