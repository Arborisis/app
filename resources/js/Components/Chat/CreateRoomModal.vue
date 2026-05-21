<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    modelValue: Boolean,
});

const emit = defineEmits(['update:modelValue']);

const form = useForm({
    name: '',
    description: '',
    type: 'public',
});

const close = () => emit('update:modelValue', false);

const submit = () => {
    form.post(route('chat.rooms.store'), {
        onSuccess: () => {
            form.reset();
            close();
        },
    });
};
</script>

<template>
    <Modal :show="modelValue" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-arbor-cream mb-4">Créer un salon</h2>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-arbor-sage mb-1">Nom</label>
                    <input v-model="form.name" type="text" class="w-full rounded-lg bg-arbor-glass border border-arbor-glass-border px-3 py-2 text-arbor-cream text-base focus:outline-none focus:border-arbor-emerald/50" required />
                    <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-arbor-sage mb-1">Description</label>
                    <textarea v-model="form.description" rows="2" class="w-full rounded-lg bg-arbor-glass border border-arbor-glass-border px-3 py-2 text-arbor-cream text-base focus:outline-none focus:border-arbor-emerald/50"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-arbor-sage mb-1">Type</label>
                    <select v-model="form.type" class="w-full rounded-lg bg-arbor-glass border border-arbor-glass-border px-3 py-2 text-arbor-cream text-sm focus:outline-none focus:border-arbor-emerald/50">
                        <option value="public">Public</option>
                        <option value="admin_only">Réservé admin</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="close" class="px-4 py-2 rounded-lg text-sm text-arbor-sage hover:bg-arbor-glass transition">Annuler</button>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-lg text-sm bg-arbor-emerald text-arbor-night font-medium hover:bg-arbor-emerald-dark disabled:opacity-50 transition">Créer</button>
                </div>
            </form>
        </div>
    </Modal>
</template>
