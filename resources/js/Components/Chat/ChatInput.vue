<script setup>
import { ref } from 'vue';

const props = defineProps({
    loading: Boolean,
});

const emit = defineEmits(['send']);

const body = ref('');

const submit = () => {
    const text = body.value.trim();
    if (!text) return;
    emit('send', text);
    body.value = '';
};

const onKeydown = (e) => {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
        submit();
    }
};
</script>

<template>
    <div class="p-4 border-t border-arbor-glass-border bg-arbor-deep/30">
        <div class="flex gap-3">
            <textarea
                v-model="body"
                @keydown="onKeydown"
                rows="1"
                class="flex-1 resize-none rounded-xl bg-arbor-glass border border-arbor-glass-border px-4 py-2.5 text-base text-arbor-cream placeholder-arbor-sage/50 focus:outline-none focus:border-arbor-emerald/50"
                placeholder="Écrivez un message... (Ctrl+Enter pour envoyer)"
            />
            <button
                @click="submit"
                :disabled="loading || !body.trim()"
                class="shrink-0 px-4 py-2 rounded-xl bg-arbor-emerald text-arbor-night font-medium text-sm hover:bg-arbor-emerald-dark disabled:opacity-50 transition"
            >
                Envoyer
            </button>
        </div>
    </div>
</template>
