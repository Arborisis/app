const DB_NAME = 'arborisis-offline-queue';
const DB_VERSION = 1;
const STORE_NAME = 'actions';

function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

export async function queueAction(action) {
    const db = await openDB();
    const tx = db.transaction(STORE_NAME, 'readwrite');
    const store = tx.objectStore(STORE_NAME);

    const payload = {
        ...action,
        createdAt: Date.now(),
        retries: 0,
    };

    return new Promise((resolve, reject) => {
        const request = store.add(payload);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

export async function getPendingActions() {
    const db = await openDB();
    const tx = db.transaction(STORE_NAME, 'readonly');
    const store = tx.objectStore(STORE_NAME);

    return new Promise((resolve, reject) => {
        const request = store.getAll();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

export async function clearPendingActions() {
    const db = await openDB();
    const tx = db.transaction(STORE_NAME, 'readwrite');
    const store = tx.objectStore(STORE_NAME);

    return new Promise((resolve, reject) => {
        const request = store.clear();
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

export async function requestBackgroundSync() {
    if (!('serviceWorker' in navigator) || !('sync' in navigator.serviceWorker.registration)) {
        return false;
    }
    try {
        const registration = await navigator.serviceWorker.ready;
        await registration.sync.register('sync-actions');
        return true;
    } catch (err) {
        console.error('[BackgroundSync] Registration failed:', err);
        return false;
    }
}

export function useOfflineQueue() {
    return {
        queueAction,
        getPendingActions,
        clearPendingActions,
        requestBackgroundSync,
    };
}
