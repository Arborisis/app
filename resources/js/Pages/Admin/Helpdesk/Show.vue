<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    ticket: Object,
    pendingSuggestion: Object,
});

const validateForm = useForm({
    action: 'validate',
    edited_body: props.pendingSuggestion?.suggested_body || '',
    rejection_reason: '',
});

function submitValidation() {
    validateForm.post(`/api/helpdesk/ia-suggestions/${props.pendingSuggestion.id}/validate`, {
        onSuccess: () => window.location.reload(),
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="`Validation IA #${ticket.ticket_number}`" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6">
                <Link href="/admin/helpdesk" class="text-sm text-arbor-sage transition hover:text-arbor-cream">
                    &larr; Retour à l'admin
                </Link>
            </div>

            <!-- Ticket info -->
            <div class="mb-8 rounded-2xl border border-arbor-glass-border bg-arbor-glass/10 p-6">
                <span class="font-mono text-sm text-arbor-sage">{{ ticket.ticket_number }}</span>
                <h1 class="mt-2 font-display text-2xl text-arbor-cream">{{ ticket.subject }}</h1>
                <p class="mt-4 text-sm leading-relaxed text-arbor-cream">{{ ticket.body }}</p>
                <p class="mt-4 text-xs text-arbor-sage">Par {{ ticket.user.name }} · {{ formatDate(ticket.created_at) }}</p>
            </div>

            <!-- Validation IA -->
            <div v-if="pendingSuggestion" class="rounded-2xl border border-arbor-emerald/30 bg-arbor-emerald/5 p-6">
                <div class="mb-4 flex items-center gap-2">
                    <svg class="h-5 w-5 text-arbor-firefly" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <h2 class="text-lg font-medium text-arbor-firefly">Suggestion IA — Validation requise</h2>
                </div>

                <p class="mb-2 text-xs text-arbor-sage">Modèle : {{ pendingSuggestion.model_used || 'Sylve' }}</p>

                <form @submit.prevent="submitValidation" class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-arbor-cream">Réponse suggérée (modifiable)</label>
                        <textarea
                            v-model="validateForm.edited_body"
                            rows="8"
                            class="w-full resize-none rounded-xl border border-arbor-glass-border bg-arbor-night/70 px-4 py-3 text-sm text-arbor-cream focus:border-arbor-emerald focus:ring-arbor-emerald/40"
                        ></textarea>
                    </div>

                    <div v-if="validateForm.action === 'reject'">
                        <label class="mb-2 block text-sm font-medium text-arbor-cream">Motif du rejet</label>
                        <textarea
                            v-model="validateForm.rejection_reason"
                            rows="3"
                            class="w-full resize-none rounded-xl border border-arbor-glass-border bg-arbor-night/70 px-4 py-3 text-sm text-arbor-cream focus:border-arbor-emerald focus:ring-arbor-emerald/40"
                            placeholder="Pourquoi cette suggestion est-elle rejetée ?"
                        ></textarea>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <label
                            class="cursor-pointer rounded-xl border px-5 py-2.5 text-sm font-medium transition"
                            :class="validateForm.action === 'validate' ? 'border-arbor-emerald/40 bg-arbor-emerald/10 text-arbor-firefly' : 'border-arbor-glass-border bg-arbor-glass/10 text-arbor-sage'"
                        >
                            <input v-model="validateForm.action" type="radio" value="validate" class="sr-only" />
                            Valider et envoyer
                        </label>

                        <label
                            class="cursor-pointer rounded-xl border px-5 py-2.5 text-sm font-medium transition"
                            :class="validateForm.action === 'edit' ? 'border-arbor-emerald/40 bg-arbor-emerald/10 text-arbor-firefly' : 'border-arbor-glass-border bg-arbor-glass/10 text-arbor-sage'"
                        >
                            <input v-model="validateForm.action" type="radio" value="edit" class="sr-only" />
                            Modifier et envoyer
                        </label>

                        <label
                            class="cursor-pointer rounded-xl border px-5 py-2.5 text-sm font-medium transition"
                            :class="validateForm.action === 'reject' ? 'border-red-500/40 bg-red-500/10 text-red-300' : 'border-arbor-glass-border bg-arbor-glass/10 text-arbor-sage'"
                        >
                            <input v-model="validateForm.action" type="radio" value="reject" class="sr-only" />
                            Rejeter
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="validateForm.processing"
                        class="rounded-xl bg-arbor-emerald px-6 py-3 text-sm font-semibold text-arbor-night transition hover:bg-arbor-firefly disabled:opacity-50"
                    >
                        {{ validateForm.processing ? 'Traitement...' : 'Confirmer' }}
                    </button>
                </form>
            </div>

            <div v-else class="rounded-2xl border border-arbor-glass-border bg-arbor-glass/10 p-6 text-center">
                <p class="text-arbor-sage">Aucune suggestion IA en attente pour ce ticket.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
