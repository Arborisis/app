<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ChatSidebar from './ChatSidebar.vue';

const props = defineProps({
    rooms: Array,
    conversations: Array,
    activeRoom: Object,
    activeConversation: Object,
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="flex h-[calc(100vh-4rem)]">
            <ChatSidebar
                :rooms="rooms"
                :conversations="conversations"
                :active-room="activeRoom"
                :active-conversation="activeConversation"
            />
            <div class="flex-1 flex flex-col min-w-0">
                <slot />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
