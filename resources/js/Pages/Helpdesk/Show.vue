<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    ticket: Object,
    canReply: Boolean,
});

const replyForm = useForm({
    body: '',
    is_internal_note: false,
});

const statusLabels = {
    open: 'Ouvert',
    in_progress: 'En cours',
    resolved: 'Résolu',
    closed: 'Fermé',
};

const statusColors = {
    open: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
    in_progress: 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
    resolved: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
    closed: 'bg-slate-500/20 text-slate-300 border-slate-500/30',
};

function submitReply() {
    replyForm.post(`/helpdesk/${props.ticket.id}/reply`, {
        onSuccess: () => replyForm.reset(),
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="`Ticket #${ticket.ticket_number}`" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6">
                <Link href="/helpdesk" class="text-sm text-arbor-sage transition hover:text-arbor-cream">
                    &larr; Retour aux tickets
                </Link>
            </div>

            <!-- En-tête ticket -->
            <div class="mb-8 rounded-2xl border border-arbor-glass-border bg-arbor-glass/10 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-sm text-arbor-sage">{{ ticket.ticket_number }}</span>
                            <span
                                class="rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                :class="statusColors[ticket.status]"
                            >
                                {{ statusLabels[ticket.status] }}
                            </span>
                        </div>
                        <h1 class="mt-3 font-display text-2xl text-arbor-cream">{{ ticket.subject }}</h1>
                        <p class="mt-2 text-sm text-arbor-sage">
                            Par {{ ticket.user.name }} · {{ formatDate(ticket.created_at) }}
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <span class="text-xs font-medium uppercase tracking-wide text-arbor-cyan-trace">
                            {{ ticket.priority }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 rounded-xl bg-arbor-night/50 p-4 text-sm leading-relaxed text-arbor-cream">
                    {{ ticket.body }}
                </div>

                <!-- Actions agent -->
                <div v-if="canReply && ticket.status !== 'closed'" class="mt-4 flex flex-wrap gap-2">
                    <form :action="`/helpdesk/${ticket.id}/resolve`" method="post" @submit.prevent="$inertia.post(`/helpdesk/${ticket.id}/resolve`)">
                        <button type="submit" class="rounded-lg bg-emerald-500/20 px-3 py-1.5 text-xs font-medium text-emerald-300 transition hover:bg-emerald-500/30">Résoudre</button>
                    </form>
                    <form :action="`/helpdesk/${ticket.id}/close`" method="post" @submit.prevent="$inertia.post(`/helpdesk/${ticket.id}/close`)">
                        <button type="submit" class="rounded-lg bg-slate-500/20 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:bg-slate-500/30">Fermer</button>
                    </form>
                </div>
            </div>

            <!-- Fil de conversation -->
            <div class="space-y-4">
                <div
                    v-for="reply in ticket.replies"
                    :key="reply.id"
                    class="rounded-2xl border p-5"
                    :class="reply.is_internal_note ? 'border-amber-500/20 bg-amber-500/5' : 'border-arbor-glass-border bg-arbor-glass/10'"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-arbor-cream">{{ reply.user.name }}</span>
                            <span v-if="reply.is_ai_generated" class="rounded bg-arbor-emerald/20 px-1.5 py-0.5 text-[10px] font-medium text-arbor-firefly">IA</span>
                            <span v-if="reply.is_internal_note" class="rounded bg-amber-500/20 px-1.5 py-0.5 text-[10px] font-medium text-amber-300">Interne</span>
                        </div>
                        <span class="text-xs text-arbor-sage">{{ formatDate(reply.created_at) }}</span>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-arbor-cream">{{ reply.body }}</p>
                </div>
            </div>

            <!-- Suggestion IA en attente -->
            <div v-if="ticket.ia_suggestions.length > 0" class="mt-6">
                <div
                    v-for="suggestion in ticket.ia_suggestions.filter(s => s.status === 'pending')"
                    :key="suggestion.id"
                    class="rounded-2xl border border-arbor-emerald/30 bg-arbor-emerald/5 p-5"
                >
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-arbor-firefly" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span class="text-sm font-medium text-arbor-firefly">Suggestion IA en attente de validation</span>
                    </div>
                    <p class="mt-3 text-sm italic text-arbor-sage">{{ suggestion.suggested_body }}</p>
                </div>
            </div>

            <!-- Formulaire de réponse -->
            <div v-if="canReply && ticket.status !== 'closed'" class="mt-8 rounded-2xl border border-arbor-glass-border bg-arbor-glass/10 p-5">
                <h3 class="mb-4 text-sm font-medium text-arbor-cream">Ajouter une réponse</h3>
                <form @submit.prevent="submitReply">
                    <textarea
                        v-model="replyForm.body"
                        rows="4"
                        class="w-full resize-none rounded-xl border border-arbor-glass-border bg-arbor-night/70 px-4 py-3 text-sm text-arbor-cream placeholder:text-arbor-sage/50 focus:border-arbor-emerald focus:ring-arbor-emerald/40"
                        placeholder="Votre réponse..."
                    ></textarea>
                    <p v-if="replyForm.errors.body" class="mt-1 text-xs text-red-400">{{ replyForm.errors.body }}</p>

                    <div class="mt-4 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-arbor-sage">
                            <input v-model="replyForm.is_internal_note" type="checkbox" class="rounded border-arbor-glass-border bg-arbor-night/70 text-arbor-emerald" />
                            Note interne (visible uniquement par l'équipe)
                        </label>

                        <button
                            type="submit"
                            :disabled="replyForm.processing"
                            class="rounded-xl bg-arbor-emerald px-5 py-2.5 text-sm font-semibold text-arbor-night transition hover:bg-arbor-firefly disabled:opacity-50"
                        >
                            {{ replyForm.processing ? 'Envoi...' : 'Envoyer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
