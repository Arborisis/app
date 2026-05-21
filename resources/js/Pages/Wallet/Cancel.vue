<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
</script>

<template>
    <Head title="Paiement annulé" />

    <AuthenticatedLayout>
        <div class="relative min-h-screen bg-arbor-night flex items-center justify-center">
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-arbor-amber/5 rounded-full blur-3xl" />
            </div>

            <div class="relative z-10 max-w-md w-full mx-4">
                <div class="glass-card p-10 text-center animate-slide-up">
                    <!-- Icon -->
                    <div class="w-20 h-20 rounded-full bg-arbor-amber/10 flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-arbor-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>

                    <h1 class="font-display text-3xl font-semibold text-arbor-cream mb-3">
                        Paiement annulé
                    </h1>
                    <p class="text-arbor-sage leading-relaxed mb-8">
                        Le paiement a été annulé. Aucun montant n'a été débité. Vous pouvez réessayer quand vous le souhaitez.
                    </p>

                    <div class="space-y-3">
                        <Link href="/wallet" class="btn-amber w-full justify-center">
                            Réessayer
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
