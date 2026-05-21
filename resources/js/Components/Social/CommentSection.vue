<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import CommentItem from './CommentItem.vue';

const props = defineProps({
    soundId: Number,
    comments: Object,
});

const showForm = ref(false);
const replyingTo = ref(null);

const form = useForm({
    body: '',
    parent_id: null,
});

const submit = () => {
    form.parent_id = replyingTo.value;
    form.post(route('comments.store', props.soundId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
            replyingTo.value = null;
        },
    });
};

const startReply = (commentId) => {
    replyingTo.value = commentId;
    showForm.value = true;
};

const cancelReply = () => {
    replyingTo.value = null;
    form.parent_id = null;
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-arbor-cream">Commentaires</h2>
            <button
                v-if="$page.props.auth.user && !showForm"
                @click="showForm = true"
                class="text-sm text-arbor-emerald hover:text-arbor-emerald-dark transition-colors"
            >
                Ajouter un commentaire
            </button>
        </div>

        <!-- Comment Form -->
        <div v-if="showForm" class="glass-card p-4">
            <div v-if="replyingTo" class="text-sm text-arbor-sage mb-2">
                Réponse à un commentaire
                <button @click="cancelReply" class="text-arbor-emerald ml-2 hover:underline">Annuler</button>
            </div>
            <form @submit.prevent="submit" class="space-y-3">
                <textarea
                    v-model="form.body"
                    rows="3"
                    class="w-full rounded-lg bg-arbor-deep border-arbor-glass text-arbor-cream placeholder-arbor-sage/50 focus:border-arbor-emerald focus:ring-arbor-emerald text-base resize-none"
                    placeholder="Votre commentaire..."
                ></textarea>
                <div v-if="form.errors.body" class="text-sm text-red-400">{{ form.errors.body }}</div>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        @click="showForm = false; cancelReply()"
                        class="px-3 py-1.5 text-sm text-arbor-sage hover:text-arbor-cream transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-1.5 rounded-lg bg-arbor-emerald text-arbor-night text-sm font-medium hover:bg-arbor-emerald-dark transition-colors disabled:opacity-50"
                    >
                        Publier
                    </button>
                </div>
            </form>
        </div>

        <!-- Comments List -->
        <div v-if="comments.data.length > 0" class="space-y-4">
            <CommentItem
                v-for="comment in comments.data"
                :key="comment.id"
                :comment="comment"
                @reply="startReply"
            />
        </div>

        <div v-else class="text-center py-8 text-arbor-sage text-sm">
            Aucun commentaire pour le moment. Soyez le premier à réagir !
        </div>
    </div>
</template>
