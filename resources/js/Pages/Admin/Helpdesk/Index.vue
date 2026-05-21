<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    tickets: Object,
    pendingSuggestionsCount: Number,
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

const priorityColors = {
    low: 'text-arbor-sage',
    normal: 'text-arbor-cyan-trace',
    high: 'text-amber-400',
    critical: 'text-red-400',
};

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
    <Head title="Admin Helpdesk" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="font-display text-3xl text-arbor-cream">Admin Helpdesk</h1>
                    <p class="mt-1 text-sm text-arbor-sage">Gestion des tickets et validation IA</p>
                </div>
                <div v-if="pendingSuggestionsCount > 0" class="rounded-xl bg-arbor-emerald/10 px-4 py-2 text-sm text-arbor-firefly">
                    {{ pendingSuggestionsCount }} suggestion{{ pendingSuggestionsCount > 1 ? 's' : '' }} en attente
                </div>
            </div>

            <!-- Stats rapides -->
            <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-2xl border border-arbor-glass-border bg-arbor-glass/10 p-4">
                    <p class="text-2xl font-semibold text-arbor-cream">{{ tickets.data.filter(t => t.status === 'open').length }}</p>
                    <p class="text-xs text-arbor-sage">Ouverts</p>
                </div>
                <div class="rounded-2xl border border-arbor-glass-border bg-arbor-glass/10 p-4">
                    <p class="text-2xl font-semibold text-arbor-cream">{{ tickets.data.filter(t => t.status === 'in_progress').length }}</p>
                    <p class="text-xs text-arbor-sage">En cours</p>
                </div>
                <div class="rounded-2xl border border-arbor-glass-border bg-arbor-glass/10 p-4">
                    <p class="text-2xl font-semibold text-arbor-cream">{{ tickets.data.filter(t => t.status === 'resolved').length }}</p>
                    <p class="text-xs text-arbor-sage">Résolus</p>
                </div>
                <div class="rounded-2xl border border-arbor-glass-border bg-arbor-glass/10 p-4">
                    <p class="text-2xl font-semibold text-arbor-cream">{{ tickets.total }}</p>
                    <p class="text-xs text-arbor-sage">Total</p>
                </div>
            </div>

            <!-- Liste tickets -->
            <div class="space-y-3">
                <div
                    v-for="ticket in tickets.data"
                    :key="ticket.id"
                    class="rounded-2xl border border-arbor-glass-border bg-arbor-glass/10 p-5 transition hover:border-arbor-emerald/20"
                >
                    <div class="flex items-center gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-xs text-arbor-sage">{{ ticket.ticket_number }}</span>
                                <span
                                    class="rounded-full border px-2 py-0.5 text-[10px] font-medium"
                                    :class="statusColors[ticket.status]"
                                >
                                    {{ statusLabels[ticket.status] }}
                                </span>
                                <span
                                    v-if="ticket.ia_suggestions?.some(s => s.status === 'pending')"
                                    class="rounded-full bg-arbor-emerald/20 px-2 py-0.5 text-[10px] font-medium text-arbor-firefly"
                                >
                                    IA en attente
                                </span>
                            </div>
                            <Link
                                :href="`/admin/helpdesk/${ticket.id}`"
                                class="mt-1 block truncate text-sm font-medium text-arbor-cream transition hover:text-arbor-firefly"
                            >
                                {{ ticket.subject }}
                            </Link>
                            <p class="mt-1 text-xs text-arbor-sage">
                                {{ ticket.user.name }} · {{ ticket.category?.name || 'Non catégorisé' }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <span
                                class="text-xs font-medium uppercase"
                                :class="priorityColors[ticket.priority]"
                            >
                                {{ ticket.priority }}
                            </span>
                            <p class="mt-1 text-xs text-arbor-sage">{{ formatDate(ticket.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
