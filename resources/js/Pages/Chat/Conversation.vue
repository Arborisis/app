<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ChatLayout from '@/Components/Chat/ChatLayout.vue';
import ChatMessageItem from '@/Components/Chat/ChatMessageItem.vue';
import ChatInput from '@/Components/Chat/ChatInput.vue';
import echo from '@/echo';

const props = defineProps({
    conversation: Object,
    messages: Array,
    nextPageUrl: String,
    rooms: Array,
    conversations: Array,
});

const messages = ref([...props.messages].reverse());
const loading = ref(false);
const listRef = ref(null);

const page = usePage();
const otherUser = computed(() => {
    return props.conversation.users.find(u => u.id !== page.props.auth.user?.id);
});

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

    const channel = echo.private(`chat.conversation.${props.conversation.id}`);
    channel.listen('.PrivateMessageSent', (e) => {
        messages.value.push(e.message);
        scrollToBottom();
    });
});

onUnmounted(() => {
    echo.leave(`chat.conversation.${props.conversation.id}`);
});

const sendMessage = async (body) => {
    loading.value = true;
    try {
        const res = await fetch(route('chat.private_messages.store', props.conversation.id), {
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
</script>

<template>
    <ChatLayout :rooms="rooms" :conversations="conversations" :active-conversation="conversation">
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-arbor-glass-border flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-arbor-moss/30 flex items-center justify-center text-xs font-medium text-arbor-emerald">
                    {{ otherUser?.name?.charAt(0).toUpperCase() || '?' }}
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-arbor-cream">{{ otherUser?.name || 'Conversation' }}</h2>
                </div>
            </div>

            <!-- Messages -->
            <div ref="listRef" class="flex-1 overflow-y-auto p-6">
                <ChatMessageItem
                    v-for="msg in messages"
                    :key="msg.id"
                    :message="msg"
                />
            </div>

            <!-- Input -->
            <ChatInput :loading="loading" @send="sendMessage" />
        </div>
    </ChatLayout>
</template>
