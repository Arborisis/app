<script setup>
import { usePwaStore } from '@/Stores/pwa';
import { computed } from 'vue';

const store = usePwaStore();
const show = computed(() => store.updateAvailable);

function handleUpdate() {
    store.vibrate(40);
    store.updateApp();
}

function handleDismiss() {
    store.dismissUpdate();
}
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="-translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-4 opacity-0"
    >
        <div
            v-if="show"
            class="fixed top-4 left-4 right-4 z-toast md:left-auto md:right-6 md:w-96"
        >
            <div class="bg-arbor-emerald/10 backdrop-blur-xl border border-arbor-emerald/20 rounded-2xl p-4 shadow-2xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-arbor-emerald/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-medium text-arbor-cream text-sm">
                            Mise à jour disponible
                        </h3>
                        <p class="text-xs text-arbor-sage mt-0.5">
                            Une nouvelle version d'Arborisis est prête.
                        </p>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button
                            @click="handleDismiss"
                            class="px-3 py-1.5 text-xs font-medium text-arbor-sage hover:text-arbor-cream transition-colors"
                        >
                            Plus tard
                        </button>
                        <button
                            @click="handleUpdate"
                            class="px-3 py-1.5 text-xs font-medium text-arbor-night bg-arbor-emerald hover:bg-arbor-emerald-dark rounded-lg transition-colors"
                        >
                            Mettre à jour
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>
