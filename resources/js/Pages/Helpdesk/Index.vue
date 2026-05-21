<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    tickets: Object,
    categories: Array,
    filters: Object,
    isAgent: Boolean,
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
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Helpdesk" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="font-display text-3xl text-arbor-cream">Helpdesk</h1>
                    <p class="mt-1 text-sm text-arbor-sage">
                        {{ isAgent ? 'Gestion des tickets support' : 'Centre d\'aide et support' }}
                    </p>
                </div>
                <Link
                    href="/helpdesk/create"
                    class="rounded-xl bg-arbor-emerald px-5 py-2.5 text-sm font-semibold text-arbor-night transition hover:bg-arbor-firefly"
                >
                    Nouveau ticket
                </Link>
            </div>

            <!-- Filtres -->
            <div class="mb-6 flex flex-wrap gap-3">
                <Link
                    href="/helpdesk"
                    class="rounded-lg border px-3 py-1.5 text-xs transition"
                    :class="!filters.status ? 'border-arbor-emerald/40 bg-arbor-emerald/10 text-arbor-firefly' : 'border-arbor-glass-border bg-arbor-glass/10 text-arbor-sage hover:text-arbor-cream'"
                >
                    Tous
                </Link>
                <Link
                    v-for="(label, key) in statusLabels"
                    :key="key"
                    :href="`/helpdesk?status=${key}`"
                    class="rounded-lg border px-3 py-1.5 text-xs transition"
                    :class="filters.status === key ? 'border-arbor-emerald/40 bg-arbor-emerald/10 text-arbor-firefly' : 'border-arbor-glass-border bg-arbor-glass/10 text-arbor-sage hover:text-arbor-cream'"
                >
                    {{ label }}
                </Link>
            </div>

            <!-- Liste des tickets -->
            <div class="space-y-3">
                <div
                    v-for="ticket in tickets.data"
                    :key="ticket.id"
                    class="rounded-2xl border border-arbor-glass-border bg-arbor-glass/10 p-5 transition hover:border-arbor-emerald/20"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-mono text-arbor-sage">{{ ticket.ticket_number }}</span>
                                <span
                                    class="rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                    :class="statusColors[ticket.status]"
                                >
                                    {{ statusLabels[ticket.status] }}
                                </span>
                            </div>
                            <Link
                                :href="`/helpdesk/${ticket.id}`"
                                class="mt-2 block truncate text-lg font-medium text-arbor-cream transition hover:text-arbor-firefly"
                            >
                                {{ ticket.subject }}
                            </Link>
                            <p class="mt-1 line-clamp-2 text-sm text-arbor-sage">{{ ticket.body }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <span
                                class="text-xs font-medium uppercase tracking-wide"
                                :class="priorityColors[ticket.priority]"
                            >
                                {{ ticket.priority }}
                            </span>
                            <p class="mt-2 text-xs text-arbor-sage">{{ formatDate(ticket.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="tickets.data.length === 0" class="py-16 text-center">
                    <p class="text-arbor-sage">Aucun ticket trouvé.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="tickets.links.length > 3" class="mt-6 flex justify-center gap-2">
                <Link
                    v-for="(link, index) in tickets.links"
                    :key="index"
                    :href="link.url"
                    class="rounded-lg px-3 py-1.5 text-sm transition"
                    :class="link.active ? 'bg-arbor-emerald text-arbor-night' : 'text-arbor-sage hover:text-arbor-cream'"
                    v-html="link.label"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
