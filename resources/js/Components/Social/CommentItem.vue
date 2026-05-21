<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    comment: Object,
});

const emit = defineEmits(['reply']);

const showReplies = ref(true);

const deleteForm = useForm({});

const destroy = () => {
    if (confirm('Supprimer ce commentaire ?')) {
        deleteForm.delete(route('comments.destroy', props.comment.id), {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getAvatarUrl = (user) => {
    return user?.avatar_url || user?.profile?.avatar_url || user?.profile?.avatarUrl || null;
};
</script>

<template>
    <div class="space-y-3">
        <div class="flex gap-3">
            <div class="w-8 h-8 overflow-hidden rounded-full bg-arbor-moss/30 flex items-center justify-center shrink-0">
                <img
                    v-if="getAvatarUrl(comment.user)"
                    :src="getAvatarUrl(comment.user)"
                    :alt="`Avatar de ${comment.user?.name ?? 'Anonyme'}`"
                    class="h-full w-full object-cover"
                    loading="lazy"
                />
                <span v-else class="text-xs font-bold text-arbor-emerald">
                    {{ comment.user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="glass-card p-3">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-arbor-cream">{{ comment.user?.name ?? 'Anonyme' }}</span>
                            <span class="text-xs text-arbor-sage">{{ formatDate(comment.created_at) }}</span>
                        </div>
                        <div v-if="comment.user?.id === $page.props.auth.user?.id || $page.props.auth.user?.is_moderator" class="flex items-center gap-2">
                            <button
                                @click="destroy"
                                class="text-xs text-red-400 hover:text-red-300 transition-colors"
                            >
                                Supprimer
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-arbor-sage leading-relaxed">{{ comment.body }}</p>
                </div>
                <div class="flex items-center gap-3 mt-1 ml-1">
                    <button
                        v-if="$page.props.auth.user"
                        @click="emit('reply', comment.id)"
                        class="text-xs text-arbor-sage hover:text-arbor-emerald transition-colors"
                    >
                        Répondre
                    </button>
                    <button
                        v-if="comment.replies?.length > 0"
                        @click="showReplies = !showReplies"
                        class="text-xs text-arbor-sage hover:text-arbor-cream transition-colors"
                    >
                        {{ showReplies ? 'Masquer' : 'Voir' }} {{ comment.replies.length }} réponse{{ comment.replies.length > 1 ? 's' : '' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Replies -->
        <div v-if="showReplies && comment.replies?.length > 0" class="ml-11 space-y-3">
            <div v-for="reply in comment.replies" :key="reply.id" class="flex gap-3">
                <div class="w-6 h-6 overflow-hidden rounded-full bg-arbor-moss/20 flex items-center justify-center shrink-0">
                    <img
                        v-if="getAvatarUrl(reply.user)"
                        :src="getAvatarUrl(reply.user)"
                        :alt="`Avatar de ${reply.user?.name ?? 'Anonyme'}`"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                    <span v-else class="text-xs font-bold text-arbor-emerald">
                        {{ reply.user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
                    </span>
                </div>
                <div class="flex-1">
                    <div class="glass-card p-3">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-arbor-cream">{{ reply.user?.name ?? 'Anonyme' }}</span>
                                <span class="text-xs text-arbor-sage">{{ formatDate(reply.created_at) }}</span>
                            </div>
                            <div v-if="reply.user?.id === $page.props.auth.user?.id || $page.props.auth.user?.is_moderator">
                                <button
                                    @click="deleteForm.delete(route('comments.destroy', reply.id), { preserveScroll: true })"
                                    class="text-xs text-red-400 hover:text-red-300 transition-colors"
                                >
                                    Supprimer
                                </button>
                            </div>
                        </div>
                        <p class="text-sm text-arbor-sage leading-relaxed">{{ reply.body }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
