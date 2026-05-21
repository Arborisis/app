const STORAGE_KEY = 'arborisis-radio-session-token';

function uuid() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }

    return `radio-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

export function useRadioSession() {
    function token() {
        try {
            const existing = localStorage.getItem(STORAGE_KEY);
            if (existing) return existing;

            const created = uuid();
            localStorage.setItem(STORAGE_KEY, created);

            return created;
        } catch {
            return uuid();
        }
    }

    return {
        sessionToken: token(),
    };
}
