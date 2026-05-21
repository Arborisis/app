import { ref, onMounted, onUnmounted } from 'vue';

const isSupported = typeof navigator !== 'undefined' && 'wakeLock' in navigator;
const isActive = ref(false);
let wakeLockSentinel = null;

export function useWakeLock() {
    async function request() {
        if (!isSupported) return;
        try {
            wakeLockSentinel = await navigator.wakeLock.request('screen');
            isActive.value = true;

            wakeLockSentinel.addEventListener('release', () => {
                isActive.value = false;
                wakeLockSentinel = null;
            });
        } catch (err) {
            console.error('[WakeLock] Request failed:', err);
            isActive.value = false;
        }
    }

    async function release() {
        if (!isSupported || !wakeLockSentinel) return;
        try {
            await wakeLockSentinel.release();
            wakeLockSentinel = null;
            isActive.value = false;
        } catch (err) {
            console.error('[WakeLock] Release failed:', err);
        }
    }

    // Re-acquire wake lock when visibility returns
    async function handleVisibilityChange() {
        if (document.visibilityState === 'visible' && isActive.value && !wakeLockSentinel) {
            await request();
        }
    }

    onMounted(() => {
        document.addEventListener('visibilitychange', handleVisibilityChange);
    });

    onUnmounted(() => {
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        release();
    });

    return {
        isSupported,
        isActive,
        request,
        release,
    };
}
