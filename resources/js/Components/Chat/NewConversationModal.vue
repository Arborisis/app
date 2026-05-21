<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    modelValue: Boolean,
});

const emit = defineEmits(['update:modelValue']);

const form = useForm({
    user_id: null,
});

const search = ref('');
const results = ref([]);
const loading = ref(false);

const close = () => emit('update:modelValue', false);

const searchUsers = async () => {
    if (search.value.length < 2) {
        results.value = [];
        return;
    }
    loading.value = true;
    try {
        const res = await fetch(`/api/users/search?q=${encodeURIComponent(search.value)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (res.ok) results.value = await res.json();
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

let debounce;
watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(searchUsers, 300);
});

const selectUser = (user) => {
    form.user_id = user.id;
    form.post(route('chat.conversations.store'), {
        onSuccess: () => {
            form.reset();
            search.value = '';
            results.value = [];
            close();
        },
    });
};
</script>

<template>
    <Modal :show="modelValue" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-arbor-cream mb-4">Nouvelle conversation</h2>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-arbor-sage mb-1">Rechercher un utilisateur</label>
                    <input v-model="search" type="text" placeholder="Nom..." class="w-full rounded-lg bg-arbor-glass border border-arbor-glass-border px-3 py-2 text-arbor-cream text-base focus:outline-none focus:border-arbor-emerald/50" />
                </div>
                <div v-if="loading" class="text-sm text-arbor-sage">Chargement...</div>
                <div v-else-if="results.length" class="space-y-1 max-h-48 overflow-y-auto">
                    <button
                        v-for="user in results"
                        :key="user.id"
                        @click="selectUser(user)"
                        class="w-full text-left px-3 py-2 rounded-lg text-sm text-arbor-cream hover:bg-arbor-glass transition flex items-center gap-2"
                    >
                        <div class="w-6 h-6 rounded-full bg-arbor-moss/30 flex items-center justify-center text-xs font-medium text-arbor-emerald">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        {{ user.name }}
                    </button>
                </div>
                <div v-else-if="search.length >= 2" class="text-sm text-arbor-sage">Aucun résultat</div>
                <div class="flex justify-end pt-2">
                    <button type="button" @click="close" class="px-4 py-2 rounded-lg text-sm text-arbor-sage hover:bg-arbor-glass transition">Annuler</button>
                </div>
            </div>
        </div>
    </Modal>
</template>
