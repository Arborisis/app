<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    categories: Array,
});

const form = useForm({
    category_id: '',
    subject: '',
    body: '',
    priority: 'normal',
});

const priorities = [
    { value: 'low', label: 'Basse' },
    { value: 'normal', label: 'Normale' },
    { value: 'high', label: 'Haute' },
    { value: 'critical', label: 'Critique' },
];

function submit() {
    form.post('/helpdesk');
}
</script>

<template>
    <Head title="Nouveau ticket" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8">
                <Link href="/helpdesk" class="text-sm text-arbor-sage transition hover:text-arbor-cream">
                    &larr; Retour aux tickets
                </Link>
                <h1 class="mt-4 font-display text-3xl text-arbor-cream">Nouveau ticket</h1>
                <p class="mt-1 text-sm text-arbor-sage">Décrivez votre problème en détail pour que nous puissions vous aider.</p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div>
                    <label class="mb-2 block text-sm font-medium text-arbor-cream">Sujet</label>
                    <input
                        v-model="form.subject"
                        type="text"
                        class="w-full rounded-xl border border-arbor-glass-border bg-arbor-night/70 px-4 py-3 text-sm text-arbor-cream placeholder:text-arbor-sage/50 focus:border-arbor-emerald focus:ring-arbor-emerald/40"
                        placeholder="Ex : Problème d'upload audio"
                        maxlength="255"
                    />
                    <p v-if="form.errors.subject" class="mt-1 text-xs text-red-400">{{ form.errors.subject }}</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-arbor-cream">Catégorie</label>
                    <select
                        v-model="form.category_id"
                        class="w-full rounded-xl border border-arbor-glass-border bg-arbor-night/70 px-4 py-3 text-sm text-arbor-cream focus:border-arbor-emerald focus:ring-arbor-emerald/40"
                    >
                        <option value="">Non catégorisé</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-arbor-cream">Priorité</label>
                    <div class="flex flex-wrap gap-3">
                        <label
                            v-for="p in priorities"
                            :key="p.value"
                            class="cursor-pointer rounded-lg border px-4 py-2 text-sm transition"
                            :class="form.priority === p.value ? 'border-arbor-emerald/40 bg-arbor-emerald/10 text-arbor-firefly' : 'border-arbor-glass-border bg-arbor-glass/10 text-arbor-sage'"
                        >
                            <input v-model="form.priority" type="radio" :value="p.value" class="sr-only" />
                            {{ p.label }}
                        </label>
                    </div>
                    <p v-if="form.errors.priority" class="mt-1 text-xs text-red-400">{{ form.errors.priority }}</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-arbor-cream">Description</label>
                    <textarea
                        v-model="form.body"
                        rows="6"
                        class="w-full resize-none rounded-xl border border-arbor-glass-border bg-arbor-night/70 px-4 py-3 text-sm text-arbor-cream placeholder:text-arbor-sage/50 focus:border-arbor-emerald focus:ring-arbor-emerald/40"
                        placeholder="Décrivez votre problème en détail..."
                        maxlength="10000"
                    ></textarea>
                    <p v-if="form.errors.body" class="mt-1 text-xs text-red-400">{{ form.errors.body }}</p>
                </div>

                <div class="flex items-center gap-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-arbor-emerald px-6 py-3 text-sm font-semibold text-arbor-night transition hover:bg-arbor-firefly disabled:opacity-50"
                    >
                        {{ form.processing ? 'Envoi...' : 'Créer le ticket' }}
                    </button>
                    <Link href="/helpdesk" class="text-sm text-arbor-sage transition hover:text-arbor-cream">Annuler</Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
