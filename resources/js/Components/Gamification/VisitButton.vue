<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    pointId: { type: [String, Number], required: true },
    pointSlug: { type: String, required: true },
    isNearby: { type: Boolean, default: false },
    hasConsent: { type: Boolean, default: false },
    cooldownRemaining: { type: Number, default: 0 },
});

const emit = defineEmits(['visit', 'request-consent']);

const isLoading = ref(false);

const buttonText = computed(() => {
    if (!props.hasConsent) return 'Autoriser la géolocalisation';
    if (props.cooldownRemaining > 0) return `Disponible dans ${formatCooldown(props.cooldownRemaining)}`;
    if (!props.isNearby) return 'Rapprochez-vous pour visiter';
    return 'Visiter ce lieu';
});

const isDisabled = computed(() => {
    return isLoading.value || !props.hasConsent || props.cooldownRemaining > 0 || !props.isNearby;
});

const formatCooldown = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    if (mins > 0) return `${mins}m ${secs}s`;
    return `${secs}s`;
};

const handleClick = () => {
    if (!props.hasConsent) {
        emit('request-consent');
        return;
    }
    isLoading.value = true;
    emit('visit');
    setTimeout(() => { isLoading.value = false; }, 2000);
};
</script>

<template>
    <button
        @click="handleClick"
        :disabled="isDisabled"
        class="w-full py-3 rounded-xl font-semibold text-sm transition-colors duration-200 flex items-center justify-center gap-2"
        :class="[
            isDisabled
                ? 'bg-white/5 text-arbor-sage/40 cursor-not-allowed'
                : 'bg-arbor-emerald text-arbor-night hover:bg-arbor-emerald/90 hover:scale-[1.02] active:scale-[0.98]'
        ]"
    >
        <svg v-if="isLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        {{ buttonText }}
    </button>
</template>
