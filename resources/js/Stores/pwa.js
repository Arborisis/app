import { defineStore } from 'pinia';
import { ref, computed, onMounted, onUnmounted } from 'vue';

export const usePwaStore = defineStore('pwa', () => {
    // Installation state
    const isInstalled = ref(false);
    const installPrompt = ref(null);
    const canInstall = computed(() => !!installPrompt.value && !isInstalled.value);

    // Update state
    const updateAvailable = ref(false);

    // Network state
    const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);

    // Offline ready
    const offlineReady = ref(false);

    // Notification badge
    const unreadCount = ref(0);

    let beforeInstallPromptHandler = null;
    let appInstalledHandler = null;

    function init() {
        if (typeof window === 'undefined') return;

        // Check if already installed (standalone or display-mode standalone)
        isInstalled.value =
            window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true;

        // beforeinstallprompt
        beforeInstallPromptHandler = (e) => {
            e.preventDefault();
            installPrompt.value = e;
        };
        window.addEventListener('beforeinstallprompt', beforeInstallPromptHandler);

        // appinstalled
        appInstalledHandler = () => {
            isInstalled.value = true;
            installPrompt.value = null;
        };
        window.addEventListener('appinstalled', appInstalledHandler);

        // SW update available
        window.addEventListener('sw-update-available', () => {
            updateAvailable.value = true;
        });

        // SW offline ready
        window.addEventListener('sw-offline-ready', () => {
            offlineReady.value = true;
            setTimeout(() => {
                offlineReady.value = false;
            }, 5000);
        });

        // Online/offline
        window.addEventListener('online', () => { isOnline.value = true; });
        window.addEventListener('offline', () => { isOnline.value = false; });

        // Display mode change
        window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
            isInstalled.value = e.matches;
        });
    }

    async function install() {
        if (!installPrompt.value) return;
        installPrompt.value.prompt();
        const { outcome } = await installPrompt.value.userChoice;
        if (outcome === 'accepted') {
            isInstalled.value = true;
        }
        installPrompt.value = null;
    }

    function dismissInstall() {
        installPrompt.value = null;
    }

    function updateApp() {
        if (window.__pwa_update) {
            window.__pwa_update();
        }
        updateAvailable.value = false;
    }

    function dismissUpdate() {
        updateAvailable.value = false;
    }

    function setBadge(count) {
        unreadCount.value = count;
        if ('setAppBadge' in navigator) {
            if (count > 0) {
                navigator.setAppBadge(count).catch(() => {});
            } else {
                navigator.clearAppBadge().catch(() => {});
            }
        }
    }

    function vibrate(pattern = 50) {
        if ('vibrate' in navigator) {
            navigator.vibrate(pattern);
        }
    }

    return {
        isInstalled,
        installPrompt,
        canInstall,
        updateAvailable,
        isOnline,
        offlineReady,
        unreadCount,
        init,
        install,
        dismissInstall,
        updateApp,
        dismissUpdate,
        setBadge,
        vibrate,
    };
});
