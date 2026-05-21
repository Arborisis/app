<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { onMounted, ref } from 'vue';

const showCheck = ref(false);

onMounted(() => {
    setTimeout(() => {
        showCheck.value = true;
    }, 150);
});
</script>

<template>
    <Head title="Paiement confirmé" />

    <AuthenticatedLayout>
        <div class="relative min-h-screen bg-arbor-night flex items-center justify-center">
            <!-- Ambient glow -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-arbor-emerald/10 rounded-full blur-3xl" />
            </div>

            <div class="relative z-10 max-w-md w-full mx-4">
                <div class="glass-card p-10 text-center animate-slide-up">
                    <!-- Success Icon -->
                    <div class="w-20 h-20 rounded-full bg-arbor-emerald/15 flex items-center justify-center mx-auto mb-6 relative">
                        <div class="absolute inset-0 rounded-full bg-arbor-emerald/10 animate-glow-pulse" />
                        <svg
                            class="w-10 h-10 text-arbor-emerald transition-all duration-700"
                            :class="showCheck ? 'scale-100 opacity-100' : 'scale-50 opacity-0'"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <h1 class="font-display text-3xl font-semibold text-arbor-cream mb-3">
                        Paiement confirmé
                    </h1>
                    <p class="text-arbor-sage leading-relaxed mb-8">
                        Vos crédits ECHO ont été ajoutés à votre portefeuille. Merci de soutenir la communauté Arborisis.
                    </p>

                    <div class="space-y-3">
                        <Link href="/wallet" class="btn-primary w-full justify-center">
                            Voir mon portefeuille
                        </Link>
                        <Link href="/dashboard" class="btn-secondary w-full justify-center">
                            Retour au dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
