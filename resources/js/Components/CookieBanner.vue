<script setup>
import { ref, watch } from 'vue';
import { useConsentStore } from '@/Stores/consent';

const store = useConsentStore();

const localAnalytics = ref(store.analytics ?? true);
const localAds = ref(store.ads ?? false);

watch(() => store.showDetails, (visible) => {
    if (visible) {
        localAnalytics.value = store.analytics ?? true;
        localAds.value = store.ads ?? false;
    }
});

function handleSavePreferences() {
    store.savePreferences({
        analytics: localAnalytics.value,
        ads: localAds.value,
    });
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-500 ease-out"
        enter-from-class="opacity-0 translate-y-8"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-300 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-8"
    >
        <div
            v-if="store.showBanner"
            class="fixed bottom-0 left-0 right-0 z-banner p-4 sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-label="Gestion des cookies"
        >
            <div class="mx-auto max-w-4xl rounded-2xl border border-arbor-glass-border bg-arbor-deep/95 backdrop-blur-xl shadow-2xl shadow-black/40">
                <!-- Vue résumée -->
                <div v-if="!store.showDetails" class="p-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4 sm:gap-6">
                        <div class="shrink-0 pt-1">
                            <svg class="w-8 h-8 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-base font-semibold text-arbor-cream mb-2">
                                Votre vie privée compte
                            </h2>
                            <p class="text-sm text-arbor-sage leading-relaxed mb-4">
                                Nous utilisons des cookies pour analyser le trafic et améliorer votre expérience.
                                Vous pouvez accepter, refuser ou personnaliser vos choix à tout moment.
                                <a href="/privacy" class="text-arbor-emerald hover:text-arbor-emerald-dark underline underline-offset-2">En savoir plus</a>.
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2 sm:shrink-0">
                            <button
                                @click="store.rejectAll"
                                class="px-4 py-2.5 rounded-xl text-sm font-medium text-arbor-sage bg-arbor-glass hover:bg-arbor-glass-border border border-arbor-glass-border transition-colors"
                            >
                                Tout refuser
                            </button>
                            <button
                                @click="store.openDetails"
                                class="px-4 py-2.5 rounded-xl text-sm font-medium text-arbor-cream bg-arbor-glass hover:bg-arbor-glass-border border border-arbor-glass-border transition-colors"
                            >
                                Personnaliser
                            </button>
                            <button
                                @click="store.acceptAll"
                                class="px-4 py-2.5 rounded-xl text-sm font-medium text-arbor-night bg-arbor-emerald hover:bg-arbor-emerald-dark transition-colors"
                            >
                                Tout accepter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Vue détaillée -->
                <div v-else class="p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-arbor-cream">
                            Préférences de cookies
                        </h2>
                        <button
                            @click="store.closeDetails"
                            class="min-w-[44px] min-h-[44px] flex items-center justify-center rounded-lg text-arbor-sage hover:text-arbor-cream hover:bg-arbor-glass transition-colors"
                            aria-label="Fermer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Essentiels (toujours actif) -->
                        <div class="flex items-start justify-between gap-4 p-3 rounded-xl bg-arbor-glass/50 border border-arbor-glass-border">
                            <div>
                                <p class="text-sm font-medium text-arbor-cream">Cookies essentiels</p>
                                <p class="text-xs text-arbor-sage mt-1">Nécessaires au fonctionnement du site (connexion, sécurité, préférences de base). Ne peuvent pas être désactivés.</p>
                            </div>
                            <div class="relative inline-flex h-6 w-11 shrink-0 cursor-not-allowed opacity-60">
                                <span class="inline-block h-6 w-11 rounded-full bg-arbor-emerald"></span>
                                <span class="absolute top-1 right-1 inline-block h-4 w-4 rounded-full bg-white shadow"></span>
                            </div>
                        </div>

                        <!-- Analytics -->
                        <div class="flex items-start justify-between gap-4 p-3 rounded-xl bg-arbor-glass/30 border border-arbor-glass-border">
                            <div>
                                <p class="text-sm font-medium text-arbor-cream">Cookies analytiques</p>
                                <p class="text-xs text-arbor-sage mt-1">Nous aident à comprendre comment vous utilisez le site pour l'améliorer (Google Analytics).</p>
                            </div>
                            <button
                                @click="localAnalytics = !localAnalytics"
                                :class="localAnalytics ? 'bg-arbor-emerald' : 'bg-arbor-fog'"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-arbor-emerald focus:ring-offset-2 focus:ring-offset-arbor-deep"
                                role="switch"
                                :aria-checked="localAnalytics"
                            >
                                <span
                                    :class="localAnalytics ? 'translate-x-6' : 'translate-x-1'"
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out mt-1"
                                />
                            </button>
                        </div>

                        <!-- Publicité -->
                        <div class="flex items-start justify-between gap-4 p-3 rounded-xl bg-arbor-glass/30 border border-arbor-glass-border">
                            <div>
                                <p class="text-sm font-medium text-arbor-cream">Cookies publicitaires</p>
                                <p class="text-xs text-arbor-sage mt-1">Utilisés pour vous proposer des contenus pertinents et mesurer l'efficacité des campagnes.</p>
                            </div>
                            <button
                                @click="localAds = !localAds"
                                :class="localAds ? 'bg-arbor-emerald' : 'bg-arbor-fog'"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-arbor-emerald focus:ring-offset-2 focus:ring-offset-arbor-deep"
                                role="switch"
                                :aria-checked="localAds"
                            >
                                <span
                                    :class="localAds ? 'translate-x-6' : 'translate-x-1'"
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out mt-1"
                                />
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 mt-6">
                        <button
                            @click="store.rejectAll"
                            class="px-4 py-2.5 rounded-xl text-sm font-medium text-arbor-sage bg-arbor-glass hover:bg-arbor-glass-border border border-arbor-glass-border transition-colors"
                        >
                            Tout refuser
                        </button>
                        <button
                            @click="handleSavePreferences"
                            class="px-4 py-2.5 rounded-xl text-sm font-medium text-arbor-cream bg-arbor-glass hover:bg-arbor-glass-border border border-arbor-glass-border transition-colors"
                        >
                            Enregistrer mes choix
                        </button>
                        <button
                            @click="store.acceptAll"
                            class="px-4 py-2.5 rounded-xl text-sm font-medium text-arbor-night bg-arbor-emerald hover:bg-arbor-emerald-dark transition-colors"
                        >
                            Tout accepter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
