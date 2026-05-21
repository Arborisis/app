import { ref, onMounted, onUnmounted } from 'vue';
import echo from '@/echo';

/**
 * Composable pour écouter les notifications de proximité (Phase 2).
 * Écoute le canal privé App.Models.User.{id} pour l'événement user.nearby.
 */
export function useNearbyNotifications(userId) {
    const lastNotification = ref(null);
    const notifications = ref([]);
    let channel = null;

    const dismiss = () => {
        lastNotification.value = null;
    };

    const clearAll = () => {
        notifications.value = [];
        lastNotification.value = null;
    };

    onMounted(() => {
        if (!userId.value) return;

        channel = echo.private(`App.Models.User.${userId.value}`);

        channel.listen('.user.nearby', (data) => {
            const notification = {
                id: Date.now(),
                initiatorId: data.initiator_id,
                initiatorName: data.initiator_name,
                distanceMeters: data.distance_meters,
                interactionType: data.interaction_type,
                timestamp: Date.now(),
            };
            notifications.value.unshift(notification);
            lastNotification.value = notification;

            // Vibrate subtly if supported
            if (navigator.vibrate) {
                navigator.vibrate([30, 50, 30]);
            }
        });
    });

    onUnmounted(() => {
        if (channel) {
            channel.stopListening('.user.nearby');
            echo.leaveChannel(`App.Models.User.${userId.value}`);
            channel = null;
        }
    });

    return {
        lastNotification,
        notifications,
        dismiss,
        clearAll,
    };
}
