<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    message: Object,
});

const page = usePage();
const isSystem = computed(() => props.message.type === 'system');
const isOwn = computed(() => props.message.user?.id === page.props.auth.user?.id);
const isModerator = computed(() => page.props.auth.user?.is_moderator);
const canDelete = computed(() => !isSystem.value && (isOwn.value || isModerator.value));
const canBan = computed(() => isModerator.value && !isOwn.value && !isSystem.value);

const emit = defineEmits(['delete', 'ban']);

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <div v-if="isSystem" class="flex justify-center my-2">
        <span class="text-xs italic text-arbor-sage bg-arbor-glass/30 px-3 py-1 rounded-full">{{ message.body }}</span>
    </div>
    <div v-else class="flex gap-3 mb-4" :class="isOwn ? 'flex-row-reverse' : 'flex-row'">
        <div class="w-8 h-8 rounded-full bg-arbor-moss/30 flex items-center justify-center text-xs font-medium text-arbor-emerald shrink-0">
            {{ message.user?.name?.charAt(0).toUpperCase() || '?' }}
        </div>
        <div class="max-w-[70%]" :class="isOwn ? 'text-right' : 'text-left'">
            <div class="flex items-center gap-2 mb-0.5" :class="isOwn ? 'justify-end' : 'justify-start'">
                <span class="text-xs font-medium text-arbor-sage">{{ message.user?.name || 'Inconnu' }}</span>
                <span class="text-[10px] text-arbor-sage/60">{{ formatTime(message.created_at) }}</span>
                <span v-if="message.user?.is_moderator || message.is_moderator" class="text-[10px] bg-arbor-amber/20 text-arbor-amber px-1 rounded">Modo</span>
            </div>
            <div
                class="inline-block px-3 py-2 rounded-xl text-sm"
                :class="isOwn ? 'bg-arbor-emerald/20 text-arbor-cream' : 'bg-arbor-glass text-arbor-cream'"
            >
                {{ message.body }}
            </div>
            <button v-if="canBan" @click="emit('ban')" class="ml-2 text-arbor-sage/70 hover:text-arbor-amber text-xs">
                Exclure
            </button>
            <button v-if="canDelete" @click="emit('delete')" class="ml-2 text-arbor-sage/70 hover:text-red-400 text-xs">
                Suppr.
            </button>
        </div>
    </div>
</template>
