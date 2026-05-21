import { ref, computed, onMounted, onUnmounted } from 'vue';

const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const connectionType = ref('unknown');
const saveData = ref(false);
const effectiveType = ref('4g');

export function useNetworkStatus() {
    function updateOnlineStatus() {
        isOnline.value = navigator.onLine;
    }

    function updateConnectionInfo() {
        const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (conn) {
            connectionType.value = conn.type || 'unknown';
            saveData.value = conn.saveData || false;
            effectiveType.value = conn.effectiveType || '4g';
        }
    }

    onMounted(() => {
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);

        const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (conn) {
            conn.addEventListener('change', updateConnectionInfo);
            updateConnectionInfo();
        }
    });

    onUnmounted(() => {
        window.removeEventListener('online', updateOnlineStatus);
        window.removeEventListener('offline', updateOnlineStatus);

        const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (conn) {
            conn.removeEventListener('change', updateConnectionInfo);
        }
    });

    return {
        isOnline,
        isOffline: computed(() => !isOnline.value),
        connectionType,
        saveData,
        effectiveType,
    };
}
