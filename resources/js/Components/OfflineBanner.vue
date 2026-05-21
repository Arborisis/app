<script setup>
import { useNetworkStatus } from '@/Composables/useNetworkStatus';
import { computed } from 'vue';

const { isOnline } = useNetworkStatus();
const show = computed(() => !isOnline.value);
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="-translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-full opacity-0"
    >
        <div
            v-if="show"
            class="fixed top-0 left-0 right-0 z-banner bg-arbor-charcoal/90 backdrop-blur-md border-b border-arbor-glass-border"
        >
            <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="text-xs text-amber-100 font-medium">
                    Mode hors ligne — certaines données peuvent être obsolètes
                </span>
            </div>
        </div>
    </transition>
</template>
