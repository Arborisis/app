import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';

const STORAGE_KEY = 'arborisis-consent';

function loadConsent() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            return JSON.parse(raw);
        }
    } catch {
        // ignore
    }
    return null;
}

function saveConsent(state) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch {
        // ignore
    }
}

function updateGtag(consent) {
    if (typeof window === 'undefined' || typeof window.gtag !== 'function') {
        return;
    }
    window.gtag('consent', 'update', {
        analytics_storage: consent.analytics ? 'granted' : 'denied',
        ad_storage: consent.ads ? 'granted' : 'denied',
        ad_user_data: consent.ads ? 'granted' : 'denied',
        ad_personalization: consent.ads ? 'granted' : 'denied',
    });
}

export const useConsentStore = defineStore('consent', () => {
    const saved = loadConsent();

    // null = pas encore choisi, true = accordé, false = refusé
    const analytics = ref(saved?.analytics ?? null);
    const ads = ref(saved?.ads ?? null);
    const showBanner = ref(saved === null);
    const showDetails = ref(false);

    const hasDecided = computed(() => saved !== null || (analytics.value !== null && ads.value !== null));

    function acceptAll() {
        analytics.value = true;
        ads.value = true;
        showBanner.value = false;
        showDetails.value = false;
        saveConsent({ analytics: true, ads: true });
        updateGtag({ analytics: true, ads: true });
    }

    function rejectAll() {
        analytics.value = false;
        ads.value = false;
        showBanner.value = false;
        showDetails.value = false;
        saveConsent({ analytics: false, ads: false });
        updateGtag({ analytics: false, ads: false });
    }

    function savePreferences({ analytics: a, ads: d }) {
        analytics.value = a;
        ads.value = d;
        showBanner.value = false;
        showDetails.value = false;
        saveConsent({ analytics: a, ads: d });
        updateGtag({ analytics: a, ads: d });
    }

    function openBanner() {
        showBanner.value = true;
        showDetails.value = false;
    }

    function openDetails() {
        showDetails.value = true;
    }

    function closeDetails() {
        showDetails.value = false;
    }

    // Sync across tabs
    if (typeof window !== 'undefined') {
        window.addEventListener('storage', (e) => {
            if (e.key === STORAGE_KEY && e.newValue) {
                const parsed = JSON.parse(e.newValue);
                analytics.value = parsed.analytics ?? null;
                ads.value = parsed.ads ?? null;
            }
        });
    }

    // Initial sync with gtag if already saved
    if (saved && typeof window !== 'undefined' && typeof window.gtag === 'function') {
        updateGtag({ analytics: saved.analytics, ads: saved.ads });
    }

    return {
        analytics,
        ads,
        showBanner,
        showDetails,
        hasDecided,
        acceptAll,
        rejectAll,
        savePreferences,
        openBanner,
        openDetails,
        closeDetails,
    };
});
