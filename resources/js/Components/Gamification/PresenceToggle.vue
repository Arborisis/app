<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    isActive: { type: Boolean, default: false },
    mode: { type: String, default: 'invisible' },
});

const emit = defineEmits(['toggle', 'change-mode']);

const isLoading = ref(false);

const statusText = computed(() => {
    if (props.isActive) {
        return props.mode === 'invisible' ? 'Invisible' : 'Visible sur la carte';
    }
    return 'Hors ligne';
});

const statusColor = computed(() => {
    if (!props.isActive) return 'bg-slate-500/20 text-slate-400';
    if (props.mode === 'invisible') return 'bg-amber-500/20 text-amber-400';
    return 'bg-emerald-500/20 text-emerald-400';
});

const handleToggle = () => {
    isLoading.value = true;
    emit('toggle');
    setTimeout(() => { isLoading.value = false; }, 1000);
};
</script>

<template>
    <div class="flex items-center gap-3">
        <button
            @click="handleToggle"
            :disabled="isLoading"
            class="relative w-12 h-7 rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-arbor-emerald/30"
            :class="isActive ? 'bg-arbor-emerald' : 'bg-white/10'"
        >
            <span
                class="absolute top-0.5 left-0.5 w-6 h-6 rounded-full bg-white shadow-sm transition-transform duration-300"
                :class="isActive ? 'translate-x-5' : 'translate-x-0'"
            />
        </button>
        <div>
            <p class="text-sm font-medium text-arbor-cream">Présence sur la carte</p>
            <p class="text-[11px]" :class="statusColor.split(' ')[1]">
                <span class="inline-block w-1.5 h-1.5 rounded-full mr-1" :class="statusColor.split(' ')[0]" />
                {{ statusText }}
            </p>
        </div>
    </div>
</template>
