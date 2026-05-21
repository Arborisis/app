<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import CreateRoomModal from './CreateRoomModal.vue';
import NewConversationModal from './NewConversationModal.vue';

const props = defineProps({
    rooms: Array,
    conversations: Array,
    activeRoom: Object,
    activeConversation: Object,
});

const showCreateRoom = ref(false);
const showNewConversation = ref(false);

const page = usePage();
const isModerator = computed(() => page.props.auth.user?.is_moderator);
</script>

<template>
    <aside class="w-72 bg-arbor-deep/50 border-r border-arbor-glass-border flex flex-col shrink-0">
        <div class="p-4 border-b border-arbor-glass-border">
            <Link :href="route('chat.index')" class="text-lg font-display font-semibold text-arbor-cream hover:text-arbor-emerald transition">
                Chat
            </Link>
        </div>

        <div class="flex-1 overflow-y-auto">
            <!-- Salons -->
            <div class="p-3">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-arbor-sage">Salons</h3>
                    <button v-if="isModerator" @click="showCreateRoom = true" class="min-w-[44px] min-h-[44px] flex items-center justify-center text-arbor-sage hover:text-arbor-emerald" title="Créer un salon" aria-label="Créer un salon">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <div class="space-y-1">
                    <Link
                        v-for="room in rooms"
                        :key="room.id"
                        :href="route('chat.rooms.show', room.slug)"
                        class="block px-3 py-2 rounded-lg text-sm transition"
                        :class="activeRoom?.id === room.id ? 'bg-arbor-emerald/20 text-arbor-emerald' : 'text-arbor-cream hover:bg-arbor-glass'"
                    >
                        <div class="flex items-center gap-2">
                            <span v-if="room.type === 'admin_only'" class="text-[10px] bg-arbor-amber/20 text-arbor-amber px-1.5 rounded">Admin</span>
                            <span class="truncate">{{ room.name }}</span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Conversations privées -->
            <div class="p-3 border-t border-arbor-glass-border">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-arbor-sage">Messages</h3>
                    <button @click="showNewConversation = true" class="min-w-[44px] min-h-[44px] flex items-center justify-center text-arbor-sage hover:text-arbor-emerald" title="Nouvelle conversation" aria-label="Nouvelle conversation">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <div class="space-y-1">
                    <Link
                        v-for="conv in conversations"
                        :key="conv.id"
                        :href="route('chat.conversations.show', conv.id)"
                        class="block px-3 py-2 rounded-lg text-sm transition"
                        :class="activeConversation?.id === conv.id ? 'bg-arbor-emerald/20 text-arbor-emerald' : 'text-arbor-cream hover:bg-arbor-glass'"
                    >
                        <div class="truncate">
                            {{ conv.users.filter(u => u.id !== $page.props.auth.user.id).map(u => u.name).join(', ') }}
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <CreateRoomModal v-model="showCreateRoom" />
        <NewConversationModal v-model="showNewConversation" />
    </aside>
</template>
