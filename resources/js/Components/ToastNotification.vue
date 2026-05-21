<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'info', // 'success' | 'error' | 'info'
    },
    message: {
        type: String,
        required: true,
    },
    duration: {
        type: Number,
        default: 4000,
    },
});

const emit = defineEmits(['close']);
const visible = ref(false);
const leaving = ref(false);
let timeoutId = null;

const typeClasses = {
    success: 'border-arbor-emerald/30 bg-arbor-emerald/10 text-arbor-emerald',
    error: 'border-red-500/30 bg-red-500/10 text-red-400',
    info: 'border-arbor-sage/30 bg-arbor-sage/10 text-arbor-sage',
};

onMounted(() => {
    requestAnimationFrame(() => {
        visible.value = true;
    });
    timeoutId = setTimeout(() => {
        close();
    }, props.duration);
});

onUnmounted(() => {
    if (timeoutId) clearTimeout(timeoutId);
});

const close = () => {
    leaving.value = true;
    setTimeout(() => {
        emit('close');
    }, 300);
};
</script>

<template>
    <div
        role="status"
        aria-live="polite"
        class="fixed bottom-24 right-4 z-toast px-5 py-3.5 rounded-xl border shadow-lg max-w-sm w-[calc(100%-2rem)] sm:w-auto transition-transform duration-300"
        :class="[
            typeClasses[type],
            visible && !leaving ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0',
        ]"
    >
        <div class="flex items-center gap-3">
            <svg
                v-if="type === 'success'"
                class="w-5 h-5 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <svg
                v-else-if="type === 'error'"
                class="w-5 h-5 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg
                v-else
                class="w-5 h-5 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm">{{ message }}</p>
            <button
                @click="close"
                aria-label="Fermer la notification"
                class="ml-2 text-current opacity-60 hover:opacity-100 transition-opacity shrink-0 min-w-[44px] min-h-[44px] flex items-center justify-center"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>
