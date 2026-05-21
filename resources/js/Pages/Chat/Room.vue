<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import ChatLayout from '@/Components/Chat/ChatLayout.vue';
import ChatMessageItem from '@/Components/Chat/ChatMessageItem.vue';
import ChatInput from '@/Components/Chat/ChatInput.vue';
import echo from '@/echo';

const props = defineProps({
    room: Object,
    messages: Array,
    nextPageUrl: String,
    isMember: Boolean,
    isBanned: Boolean,
    rooms: Array,
    conversations: Array,
});

const messages = ref([...props.messages].reverse());
const loading = ref(false);
const listRef = ref(null);

const page = usePage();
const isModerator = computed(() => page.props.auth.user?.is_moderator);

watch(() => props.messages, (newMessages) => {
    messages.value = [...newMessages].reverse();
    scrollToBottom();
});

const scrollToBottom = () => {
    nextTick(() => {
        listRef.value?.scrollTo({ top: listRef.value.scrollHeight, behavior: 'smooth' });
    });
};

onMounted(() => {
    scrollToBottom();

    const channel = echo.private(`chat.room.${props.room.id}`);
    channel.listen('.MessageSent', (e) => {
        messages.value.push(e.message);
        scrollToBottom();
    });
    channel.listen('.UserBanned', (e) => {
        messages.value.push(e.message);
        scrollToBottom();
    });
});

onUnmounted(() => {
    echo.leave(`chat.room.${props.room.id}`);
});

const sendMessage = async (body) => {
    loading.value = true;
    try {
        const res = await fetch(route('chat.messages.store', props.room.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ body }),
        });
        if (res.ok) {
            const data = await res.json();
            messages.value.push(data.message);
            scrollToBottom();
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const deleteMessage = async (msg) => {
    if (!confirm('Supprimer ce message ?')) return;
    try {
        const res = await fetch(route('chat.messages.destroy', msg.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (res.ok) {
            const idx = messages.value.findIndex(m => m.id === msg.id);
            if (idx !== -1) messages.value.splice(idx, 1);
        }
    } catch (e) {
        console.error(e);
    }
};

const joinRoom = async () => {
    try {
        const res = await fetch(route('chat.rooms.join', props.room.slug), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (res.ok) router.reload();
    } catch (e) {
        console.error(e);
    }
};

const leaveRoom = async () => {
    try {
        const res = await fetch(route('chat.rooms.leave', props.room.slug), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (res.ok) router.reload();
    } catch (e) {
        console.error(e);
    }
};

const banUser = async (user) => {
    if (!user) return;
    if (!confirm(`Exclure ${user.name} de ce salon ?`)) return;
    try {
        const res = await fetch(route('chat.moderation.ban', props.room.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ user_id: user.id }),
        });
        if (res.ok) {
            router.reload();
        }
    } catch (e) {
        console.error(e);
    }
};
</script>

<template>
    <ChatLayout :rooms="rooms" :conversations="conversations" :active-room="room">
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-arbor-glass-border flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-arbor-cream">{{ room.name }}</h2>
                    <p v-if="room.description" class="text-xs text-arbor-sage">{{ room.description }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="room.type === 'admin_only'" class="text-xs bg-arbor-amber/20 text-arbor-amber px-2 py-1 rounded">Admin</span>
                    <button v-if="!isMember && !isBanned" @click="joinRoom" class="text-xs px-3 py-1.5 rounded-lg bg-arbor-emerald text-arbor-night font-medium hover:bg-arbor-emerald-dark transition">Rejoindre</button>
                    <button v-if="isMember" @click="leaveRoom" class="text-xs px-3 py-1.5 rounded-lg bg-arbor-glass text-arbor-sage hover:bg-white/10 transition">Quitter</button>
                </div>
            </div>

            <!-- Messages -->
            <div ref="listRef" class="flex-1 overflow-y-auto p-6">
                <div v-if="isBanned" class="text-center text-red-400 text-sm">Vous êtes exclu de ce salon.</div>
                <template v-else>
                    <ChatMessageItem
                        v-for="msg in messages"
                        :key="msg.id"
                        :message="msg"
                        @delete="deleteMessage(msg)"
                        @ban="banUser(msg.user)"
                    />
                </template>
            </div>

            <!-- Input -->
            <ChatInput v-if="isMember && !isBanned" :loading="loading" @send="sendMessage" />
            <div v-else-if="!isBanned && !isMember" class="p-4 border-t border-arbor-glass-border text-center text-sm text-arbor-sage">
                Rejoignez le salon pour participer.
            </div>
        </div>
    </ChatLayout>
</template>
