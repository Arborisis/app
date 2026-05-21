<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    success: Boolean,
});
</script>

<template>
    <Head title="Désinscription" />

    <GuestLayout>
        <div class="relative min-h-screen bg-arbor-night flex items-center justify-center px-4">
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-hero-glow opacity-40" />
            </div>

            <div class="relative z-10 max-w-md w-full">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-arbor-emerald/10 mb-6">
                        <svg class="w-8 h-8 text-arbor-emerald" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </div>
                </div>

                <div
                    v-if="success"
                    class="text-center p-8 rounded-2xl bg-arbor-glass/30 border border-arbor-glass-border backdrop-blur-sm"
                >
                    <h1 class="font-display text-3xl font-semibold text-arbor-cream mb-4">
                        À bientôt
                    </h1>
                    <p class="text-arbor-sage leading-relaxed mb-6">
                        Vous avez été désinscrit de notre newsletter. Vous ne recevrez plus nos communications.
                    </p>
                    <p class="text-arbor-sage text-sm opacity-70">
                        Vous pouvez vous réinscrire à tout moment depuis le site.
                    </p>
                </div>

                <div
                    v-else
                    class="text-center p-8 rounded-2xl bg-arbor-glass/30 border border-arbor-glass-border backdrop-blur-sm"
                >
                    <h1 class="font-display text-3xl font-semibold text-arbor-cream mb-4">
                        Lien invalide
                    </h1>
                    <p class="text-arbor-sage leading-relaxed mb-6">
                        Ce lien de désinscription est invalide ou a déjà été utilisé.
                    </p>
                    <a
                        href="/"
                        class="inline-flex items-center text-arbor-emerald hover:text-arbor-emerald-dark transition-colors text-sm font-medium"
                    >
                        Retour à l'accueil
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
