<script setup>
import { usePushNotifications } from '@/Composables/usePushNotifications';

const { isSupported, isSubscribed, isLoading, subscribe, unsubscribe } = usePushNotifications();
</script>

<template>
    <button
        v-if="isSupported"
        @click.stop="isSubscribed ? unsubscribe() : subscribe()"
        :disabled="isLoading"
        class="inline-flex items-center gap-2 text-sm text-arbor-sage hover:text-arbor-emerald transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        :title="isSubscribed ? 'Désactiver les notifications' : 'Activer les notifications'"
    >
        <svg v-if="isSubscribed" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
        </svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <span class="hidden sm:inline">{{ isSubscribed ? 'Notifications activées' : 'Notifications' }}</span>
    </button>
</template>
