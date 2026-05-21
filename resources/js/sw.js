import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching';
import { NavigationRoute, registerRoute } from 'workbox-routing';
import { StaleWhileRevalidate, CacheFirst, NetworkFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { CacheableResponsePlugin } from 'workbox-cacheable-response';

// Precache all assets injected by vite-plugin-pwa
precacheAndRoute(self.__WB_MANIFEST);

cleanupOutdatedCaches();

// Navigation fallback to offline.html
const navigationRoute = new NavigationRoute(
    new NetworkFirst({
        cacheName: 'pages-cache',
        plugins: [
            new CacheableResponsePlugin({ statuses: [200] }),
        ],
    }),
    {
        denylist: [/^\/(build|js|css|fonts|images|storage|api|webhooks|radio\/stream)/],
    }
);
registerRoute(navigationRoute);

// Fonts — CacheFirst
registerRoute(
    ({ request }) => request.destination === 'font',
    new CacheFirst({
        cacheName: 'fonts-cache',
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({ maxAgeSeconds: 60 * 60 * 24 * 365, maxEntries: 30 }),
        ],
    })
);

// Images — CacheFirst
registerRoute(
    ({ request }) => request.destination === 'image',
    new CacheFirst({
        cacheName: 'images-cache',
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 60 * 60 * 24 * 30 }),
        ],
    })
);

// API — NetworkFirst with stale fallback
registerRoute(
    ({ url }) => url.pathname.startsWith('/api/'),
    new NetworkFirst({
        cacheName: 'api-cache',
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({ maxEntries: 150, maxAgeSeconds: 60 * 60 * 24 * 7 }),
        ],
    })
);

// Static assets (js, css from build) — StaleWhileRevalidate
registerRoute(
    ({ request }) => request.destination === 'script' || request.destination === 'style',
    new StaleWhileRevalidate({
        cacheName: 'assets-cache',
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({ maxEntries: 60, maxAgeSeconds: 60 * 60 * 24 * 30 }),
        ],
    })
);

// Push notifications
self.addEventListener('push', (event) => {
    if (!event.data) return;

    const data = event.data.json();
    const title = data.title || 'Arborisis';
    const options = {
        body: data.body || '',
        icon: data.icon || '/pwa-icons/icon-192x192.png',
        badge: data.badge || '/pwa-icons/icon-maskable-192x192.png',
        tag: data.tag || `arborisis-${Date.now()}`,
        data: {
            url: data.url || '/',
            timestamp: data.timestamp || Date.now(),
        },
        requireInteraction: false,
        silent: false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Notification click
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                for (const client of clientList) {
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }
                if (self.clients.openWindow) {
                    return self.clients.openWindow(urlToOpen);
                }
            })
    );
});

// Background sync
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-actions') {
        event.waitUntil(syncPendingActions());
    }
});

async function syncPendingActions() {
    const db = await openIndexedDB('arborisis-offline-queue', 1, 'actions');
    const tx = db.transaction('actions', 'readonly');
    const store = tx.objectStore('actions');
    const all = await store.getAll();

    for (const action of all) {
        try {
            const response = await fetch(action.url, {
                method: action.method || 'POST',
                headers: action.headers || { 'Content-Type': 'application/json' },
                body: JSON.stringify(action.body),
            });
            if (response.ok || response.status === 422) {
                const delTx = db.transaction('actions', 'readwrite');
                delTx.objectStore('actions').delete(action.id);
                await delTx.done;
            }
        } catch (e) {
            console.error('Background sync failed for action', action.id, e);
        }
    }
}

function openIndexedDB(name, version, storeName) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(name, version);
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        request.onupgradeneeded = () => {
            request.result.createObjectStore(storeName, { keyPath: 'id', autoIncrement: true });
        };
    });
}

// Message handling from client
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
