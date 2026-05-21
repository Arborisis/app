<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    balance: { type: Number, default: 0 },
    transactions: { type: Object, default: () => ({ data: [] }) },
});

const form = useForm({
    amount: 10,
});

const echoRate = 10;

const echoAmount = computed(() => {
    return Math.round(form.amount * echoRate);
});

const presetAmounts = [5, 10, 20, 50, 100];

const submitting = ref(false);

const submitCheckout = () => {
    submitting.value = true;
    form.post(route('wallet.checkout'), {
        preserveScroll: true,
        onFinish: () => {
            submitting.value = false;
        },
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusColor = (status) => {
    const colors = {
        completed: 'text-arbor-emerald bg-arbor-emerald/15',
        pending: 'text-amber-400 bg-amber-400/15',
        failed: 'text-rose-400 bg-rose-400/15',
        cancelled: 'text-arbor-sage bg-arbor-sage/15',
    };
    return colors[status] || colors.pending;
};

const getStatusLabel = (status) => {
    const labels = {
        completed: 'Complété',
        pending: 'En cours',
        failed: 'Échoué',
        cancelled: 'Annulé',
    };
    return labels[status] || status;
};

const getTypeLabel = (type) => {
    const labels = {
        purchase: 'Achat',
        donation: 'Don',
        tip: 'Pourboire',
        withdrawal: 'Retrait',
        refund: 'Remboursement',
        commission: 'Commission',
        community_fund: 'Fonds communautaire',
    };
    return labels[type] || type;
};
</script>

<template>
    <Head title="Portefeuille ECHO" />

    <AuthenticatedLayout>
        <div class="relative min-h-screen bg-arbor-night">
            <!-- Ambient Background -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-hero-glow opacity-40" />
                <div class="absolute top-20 right-20 w-64 h-64 bg-arbor-amber/5 rounded-full blur-3xl" />
            </div>

            <div class="relative z-10 pt-24 pb-24 section-padding">
                <div class="max-w-5xl mx-auto">
                    <!-- Header -->
                    <div class="mb-10 animate-fade-in">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-arbor-amber/20 flex items-center justify-center">
                                <span class="font-mono text-sm font-medium text-arbor-amber">E</span>
                            </div>
                            <span class="text-arbor-sage text-sm font-medium uppercase tracking-wider">Portefeuille</span>
                        </div>
                        <h1 class="font-display text-4xl sm:text-5xl font-semibold text-arbor-cream leading-tight">
                            Vos crédits ECHO
                        </h1>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                        <!-- Left: Balance + Purchase -->
                        <div class="lg:col-span-3 space-y-8">
                            <!-- Balance Card -->
                            <div class="glass-card p-8 relative overflow-hidden animate-slide-up">
                                <div class="absolute top-0 right-0 w-40 h-40 bg-arbor-amber/10 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2" />
                                <div class="relative z-10">
                                    <p class="text-arbor-sage text-sm mb-2">Solde disponible</p>
                                    <div class="font-mono text-5xl font-medium text-arbor-amber mb-1">
                                        {{ balance.toLocaleString('fr-FR') }}
                                    </div>
                                    <p class="text-arbor-sage text-xs">ECHO</p>
                                </div>
                            </div>

                            <!-- Purchase Card -->
                            <div class="glass-card p-8 animate-slide-up" style="animation-delay: 0.1s">
                                <h2 class="font-display text-2xl font-semibold text-arbor-cream mb-2">
                                    Recharger votre solde
                                </h2>
                                <p class="text-arbor-sage text-sm mb-6">
                                    Achetez des crédits ECHO via Stripe. 1 € = {{ echoRate }} ECHO.
                                </p>

                                <!-- Presets -->
                                <div class="flex flex-wrap gap-2 mb-6">
                                    <button
                                        v-for="preset in presetAmounts"
                                        :key="preset"
                                        @click="form.amount = preset"
                                        class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 border"
                                        :class="form.amount === preset
                                            ? 'bg-arbor-amber/20 border-arbor-amber/50 text-arbor-amber'
                                            : 'bg-arbor-glass border-arbor-glass-border text-arbor-sage hover:text-arbor-cream hover:border-arbor-sage/30'"
                                    >
                                        {{ preset }} €
                                    </button>
                                </div>

                                <!-- Custom Amount -->
                                <div class="mb-6">
                                    <label class="block text-arbor-sage text-sm mb-2">Montant personnalisé (1–500 €)</label>
                                    <div class="flex items-center gap-4">
                                        <div class="relative flex-1">
                                            <input
                                                v-model.number="form.amount"
                                                type="number"
                                                min="1"
                                                max="500"
                                                class="w-full bg-arbor-charcoal/50 border border-arbor-fog/50 rounded-xl px-4 py-3 text-arbor-cream focus:border-arbor-amber/50 focus:ring-1 focus:ring-arbor-amber/30 outline-none transition-colors"
                                            />
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-arbor-sage text-sm">EUR</span>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.amount" class="text-rose-400 text-xs mt-2">{{ form.errors.amount }}</p>
                                </div>

                                <!-- Preview -->
                                <div class="flex items-center justify-between p-4 rounded-xl bg-arbor-charcoal/50 border border-arbor-fog/50 mb-6">
                                    <span class="text-arbor-sage text-sm">Vous recevrez</span>
                                    <span class="font-mono text-xl text-arbor-amber">{{ echoAmount.toLocaleString('fr-FR') }} ECHO</span>
                                </div>

                                <!-- Submit -->
                                <button
                                    @click="submitCheckout"
                                    :disabled="submitting || form.amount < 1 || form.amount > 500"
                                    class="btn-amber w-full justify-center text-base py-3 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg v-if="submitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-arbor-night" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span v-else class="flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Payer {{ form.amount }} € avec Stripe
                                    </span>
                                </button>

                                <p class="text-center text-arbor-sage text-xs mt-4">
                                    Paiement sécurisé via Stripe. Les crédits sont ajoutés immédiatement après confirmation.
                                </p>
                            </div>
                        </div>

                        <!-- Right: Actions -->
                        <div class="lg:col-span-2 space-y-8">
                            <div class="glass-card p-6 animate-slide-up" style="animation-delay: 0.2s">
                                <h3 class="font-display text-lg font-semibold text-arbor-cream mb-4">Actions</h3>
                                <div class="space-y-3">
                                    <Link
                                        href="/donations/history"
                                        class="flex items-center gap-4 p-3 rounded-xl hover:bg-arbor-charcoal/50 transition-colors group"
                                    >
                                        <div class="w-10 h-10 rounded-xl bg-arbor-emerald/15 flex items-center justify-center transition-transform group-hover:scale-110">
                                            <svg class="w-5 h-5 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-arbor-cream text-sm font-medium group-hover:text-arbor-emerald transition-colors">Historique des dons</div>
                                            <div class="text-arbor-sage text-xs">Envoyés et reçus</div>
                                        </div>
                                    </Link>
                                    <Link
                                        href="/transparency"
                                        class="flex items-center gap-4 p-3 rounded-xl hover:bg-arbor-charcoal/50 transition-colors group"
                                    >
                                        <div class="w-10 h-10 rounded-xl bg-arbor-moss/20 flex items-center justify-center transition-transform group-hover:scale-110">
                                            <svg class="w-5 h-5 text-arbor-moss-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-arbor-cream text-sm font-medium group-hover:text-arbor-emerald transition-colors">Transparence ECHO</div>
                                            <div class="text-arbor-sage text-xs">Comment fonctionne le système</div>
                                        </div>
                                    </Link>
                                </div>
                            </div>

                            <!-- Info Card -->
                            <div class="glass-card p-6 bg-gradient-to-br from-arbor-moss/10 to-transparent animate-slide-up" style="animation-delay: 0.3s">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-arbor-emerald/20 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-4 h-4 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-arbor-cream text-sm font-medium mb-1">À propos d'ECHO</h4>
                                        <p class="text-arbor-sage text-xs leading-relaxed">
                                            ECHO n'est pas une cryptomonnaie ni un investissement. C'est un système de crédits internes pour soutenir les créateurs.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Table -->
                    <div class="mt-10 glass-card p-6 lg:p-8 animate-slide-up" style="animation-delay: 0.4s">
                        <h2 class="font-display text-2xl font-semibold text-arbor-cream mb-6">
                            Historique des transactions
                        </h2>

                        <div v-if="transactions.data.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-arbor-glass-border">
                                        <th class="pb-3 text-arbor-sage text-xs font-medium uppercase tracking-wider">Date</th>
                                        <th class="pb-3 text-arbor-sage text-xs font-medium uppercase tracking-wider">Type</th>
                                        <th class="pb-3 text-arbor-sage text-xs font-medium uppercase tracking-wider">Montant</th>
                                        <th class="pb-3 text-arbor-sage text-xs font-medium uppercase tracking-wider">Statut</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-arbor-glass-border">
                                    <tr
                                        v-for="tx in transactions.data"
                                        :key="tx.id"
                                        class="hover:bg-arbor-charcoal/30 transition-colors"
                                    >
                                        <td class="py-4 text-arbor-cream text-sm">{{ formatDate(tx.created_at) }}</td>
                                        <td class="py-4 text-arbor-cream text-sm">{{ getTypeLabel(tx.type?.value || tx.type) }}</td>
                                        <td class="py-4 font-mono text-sm" :class="tx.echo_amount > 0 ? 'text-arbor-amber' : 'text-arbor-sage'">
                                            {{ tx.echo_amount > 0 ? '+' : '' }}{{ tx.echo_amount }} ECHO
                                        </td>
                                        <td class="py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusColor(tx.status?.value || tx.status)">
                                                {{ getStatusLabel(tx.status?.value || tx.status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="text-center py-12">
                            <p class="text-arbor-sage text-sm">Aucune transaction pour le moment.</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="transactions.links && transactions.links.length > 3" class="mt-6 flex justify-center gap-2">
                            <Link
                                v-for="link in transactions.links"
                                :key="link.label"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 rounded-lg text-sm transition-colors"
                                :class="link.active
                                    ? 'bg-arbor-amber/20 text-arbor-amber'
                                    : 'text-arbor-sage hover:bg-arbor-charcoal/50'"
                                :preserve-state="true"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
