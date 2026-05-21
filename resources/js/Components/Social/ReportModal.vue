<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    reportableType: String,
    reportableId: Number,
});

const show = ref(false);

const form = useForm({
    reportable_type: props.reportableType,
    reportable_id: props.reportableId,
    reason: '',
    description: '',
});

const reasons = [
    { value: 'spam', label: 'Spam' },
    { value: 'harassment', label: 'Harcèlement' },
    { value: 'inappropriate_content', label: 'Contenu inapproprié' },
    { value: 'copyright', label: 'Violation de droits d\'auteur' },
    { value: 'misinformation', label: 'Désinformation' },
    { value: 'other', label: 'Autre' },
];

const submit = () => {
    form.post(route('reports.store'), {
        preserveScroll: true,
        onSuccess: () => {
            show.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <div>
        <button
            @click="show = true"
            class="text-xs text-arbor-sage hover:text-red-400 transition-colors flex items-center gap-1"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Signaler
        </button>

        <!-- Modal -->
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-arbor-night/80 backdrop-blur-sm"
            @click.self="show = false"
        >
            <div class="glass-card w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-arbor-cream">Signaler un contenu</h3>
                    <button @click="show = false" aria-label="Fermer" class="min-w-[44px] min-h-[44px] flex items-center justify-center text-arbor-sage hover:text-arbor-cream">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm text-arbor-sage mb-2">Motif</label>
                        <select
                            v-model="form.reason"
                            class="w-full rounded-lg bg-arbor-deep border-arbor-glass text-arbor-cream focus:border-arbor-emerald focus:ring-arbor-emerald text-sm"
                            required
                        >
                            <option value="" disabled>Choisir un motif</option>
                            <option v-for="r in reasons" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                        <div v-if="form.errors.reason" class="text-sm text-red-400 mt-1">{{ form.errors.reason }}</div>
                    </div>

                    <div>
                        <label class="block text-sm text-arbor-sage mb-2">Description (optionnel)</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-lg bg-arbor-deep border-arbor-glass text-arbor-cream placeholder-arbor-sage/50 focus:border-arbor-emerald focus:ring-arbor-emerald text-base resize-none"
                            placeholder="Décrivez le problème..."
                        ></textarea>
                        <div v-if="form.errors.description" class="text-sm text-red-400 mt-1">{{ form.errors.description }}</div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            @click="show = false"
                            class="px-4 py-2 text-sm text-arbor-sage hover:text-arbor-cream transition-colors"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 rounded-lg bg-red-500/20 text-red-400 text-sm font-medium hover:bg-red-500/30 transition-colors disabled:opacity-50"
                        >
                            Signaler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
