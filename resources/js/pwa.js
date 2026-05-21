import { registerSW } from 'virtual:pwa-register';

export function registerServiceWorker() {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }

    const updateSW = registerSW({
        immediate: true,
        onRegisteredSW(swUrl, registration) {
            console.log('[PWA] Service Worker registered:', swUrl);

            // Periodic SW update check every 60 minutes
            setInterval(() => {
                registration.update().catch(() => {});
            }, 60 * 60 * 1000);
        },
        onRegisterError(error) {
            console.error('[PWA] Service Worker registration failed:', error);
        },
        onNeedRefresh() {
            window.dispatchEvent(new CustomEvent('sw-update-available'));
        },
        onOfflineReady() {
            window.dispatchEvent(new CustomEvent('sw-offline-ready'));
        },
    });

    // Expose update function globally for the update prompt component
    window.__pwa_update = () => {
        updateSW(true);
    };
}
