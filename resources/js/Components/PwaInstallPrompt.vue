<script setup>
import { usePwaStore } from '@/Stores/pwa';
import { computed, onMounted } from 'vue';

const store = usePwaStore();

const show = computed(() => store.canInstall);

function handleInstall() {
    store.vibrate(40);
    store.install();
}

function handleDismiss() {
    store.dismissInstall();
    localStorage.setItem('pwa-install-dismissed', Date.now().toString());
}

onMounted(() => {
    // Restore install prompt if not recently dismissed
    const dismissed = localStorage.getItem('pwa-install-dismissed');
    if (dismissed && Date.now() - parseInt(dismissed) < 1000 * 60 * 60 * 24 * 7) {
        // Dismissed less than 7 days ago
        store.dismissInstall();
    }
});
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="show"
            class="fixed bottom-20 left-4 right-4 z-popover md:left-auto md:right-6 md:w-80 md:bottom-6"
        >
            <div class="bg-arbor-deep/95 backdrop-blur-xl border border-arbor-glass-border rounded-2xl p-4 shadow-2xl shadow-black/40">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-xl bg-arbor-emerald/10 flex items-center justify-center shrink-0">
                        <img src="/pwa-icons/icon-192x192.png" alt="Arborisis" class="w-8 h-8 rounded-lg" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-display font-semibold text-arbor-cream text-sm">
                            Installer Arborisis
                        </h3>
                        <p class="text-xs text-arbor-sage mt-0.5 leading-relaxed">
                            Accédez rapidement à l'archive sonore depuis votre écran d'accueil, même hors ligne.
                        </p>
                    </div>
                    <button
                        @click="handleDismiss"
                        class="text-arbor-sage hover:text-arbor-cream shrink-0 min-w-[44px] min-h-[44px] flex items-center justify-center"
                        aria-label="Fermer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex gap-2 mt-3">
                    <button
                        @click="handleDismiss"
                        class="flex-1 px-3 py-2 text-xs font-medium text-arbor-sage bg-arbor-charcoal/60 hover:bg-arbor-charcoal rounded-lg transition-colors"
                    >
                        Plus tard
                    </button>
                    <button
                        @click="handleInstall"
                        class="flex-1 px-3 py-2 text-xs font-medium text-arbor-night bg-arbor-emerald hover:bg-arbor-emerald-dark rounded-lg transition-colors"
                    >
                        Installer
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>
