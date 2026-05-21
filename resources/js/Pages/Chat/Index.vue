<script setup>
import ChatLayout from '@/Components/Chat/ChatLayout.vue';

const props = defineProps({
    rooms: Array,
    conversations: Array,
});
</script>

<template>
    <ChatLayout :rooms="rooms" :conversations="conversations">
        <div class="flex-1 flex items-center justify-center text-arbor-sage">
            <div class="text-center">
                <svg class="w-12 h-12 mx-auto mb-4 text-arbor-emerald/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <h2 class="text-xl font-semibold text-arbor-cream mb-2">Bienvenue dans le chat</h2>
                <p class="text-sm">Sélectionnez un salon ou une conversation pour commencer.</p>
            </div>
        </div>
    </ChatLayout>
</template>
