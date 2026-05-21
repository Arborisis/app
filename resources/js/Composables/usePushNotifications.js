import { ref, computed, onMounted } from 'vue';

const isSubscribed = ref(false);
const isLoading = ref(false);
const error = ref(null);

const vapidPublicKey = ref('');

export function usePushNotifications() {
    const isSupported = computed(() => {
        return typeof window !== 'undefined' && 'serviceWorker' in navigator && 'PushManager' in window;
    });

    async function fetchVapidKey() {
        try {
            const res = await fetch('/api/vapid-public-key');
            const data = await res.json();
            vapidPublicKey.value = data.key;
        } catch (e) {
            console.error('Impossible de récupérer la clé VAPID', e);
        }
    }

    async function checkSubscription() {
        if (!isSupported.value) return;

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        isSubscribed.value = !!subscription;
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
    }

    async function subscribe() {
        if (!isSupported.value || !vapidPublicKey.value) return;

        isLoading.value = true;
        error.value = null;

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                throw new Error('Permission de notification refusée.');
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey.value),
            });

            await fetch('/api/push-subscriptions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: subscription.toJSON().keys.p256dh,
                        auth: subscription.toJSON().keys.auth,
                    },
                }),
            });

            isSubscribed.value = true;
        } catch (e) {
            error.value = e.message || 'Erreur lors de l\'abonnement.';
            console.error(e);
        } finally {
            isLoading.value = false;
        }
    }

    async function unsubscribe() {
        if (!isSupported.value) return;

        isLoading.value = true;
        error.value = null;

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await fetch('/api/push-subscriptions', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({ endpoint: subscription.endpoint }),
                });

                await subscription.unsubscribe();
            }

            isSubscribed.value = false;
        } catch (e) {
            error.value = e.message || 'Erreur lors du désabonnement.';
            console.error(e);
        } finally {
            isLoading.value = false;
        }
    }

    onMounted(() => {
        if (isSupported.value) {
            fetchVapidKey();
            checkSubscription();
        }
    });

    return {
        isSupported,
        isSubscribed,
        isLoading,
        error,
        subscribe,
        unsubscribe,
    };
}
