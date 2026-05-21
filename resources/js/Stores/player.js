import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';

const STORAGE_KEY = 'arborisis-player-state';

function loadState() {
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

function saveState(state) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch {
        // ignore
    }
}

export const usePlayerStore = defineStore('player', () => {
    const saved = loadState();

    const currentSound = ref(saved?.currentSound || null);
    const isPlaying = ref(false); // never auto-play on load
    const currentTime = ref(saved?.currentTime || 0);
    const duration = ref(saved?.duration || 0);
    const volume = ref(saved?.volume ?? 1);
    const isMuted = ref(saved?.isMuted || false);
    const currentMode = ref(saved?.currentMode || 'sound');
    const radioMetadata = ref(saved?.radioMetadata || null);
    const radioStreamUrl = ref(saved?.radioStreamUrl || '/radio/stream');

    const hasActiveTrack = computed(() => currentSound.value !== null);
    const hasActiveRadio = computed(() => radioMetadata.value !== null);

    function requestNativePlay(sound) {
        if (typeof window === 'undefined' || !sound?.audioUrl) {
            return;
        }

        window.dispatchEvent(new CustomEvent('arborisis:play-sound', {
            detail: { sound },
        }));
    }

    function play(sound = null, options = {}) {
        currentMode.value = 'sound';
        radioMetadata.value = null;
        if (sound) {
            if (currentSound.value?.id === sound.id) {
                isPlaying.value = true;
                if (options.direct) {
                    requestNativePlay(sound);
                }
                return;
            }
            currentSound.value = sound;
            isPlaying.value = true;
            currentTime.value = 0;
            duration.value = sound.duration || 0;
            if (options.direct) {
                requestNativePlay(sound);
            }
        } else if (currentSound.value) {
            isPlaying.value = true;
        }
    }

    function pause() {
        isPlaying.value = false;
    }

    function togglePlay() {
        isPlaying.value = !isPlaying.value;
    }

    function stop() {
        isPlaying.value = false;
        currentTime.value = 0;
    }

    function setTime(time) {
        currentTime.value = time;
    }

    function setDuration(dur) {
        duration.value = dur;
    }

    function setVolume(vol) {
        volume.value = vol;
        if (vol > 0) isMuted.value = false;
    }

    function toggleMute() {
        isMuted.value = !isMuted.value;
    }

    function close() {
        currentSound.value = null;
        radioMetadata.value = null;
        isPlaying.value = false;
        currentTime.value = 0;
        duration.value = 0;
    }

    function connectRadio(metadata = null, streamUrl = '/radio/stream') {
        currentMode.value = 'radio';
        currentSound.value = null;
        radioMetadata.value = metadata || radioMetadata.value || { title: 'Arborisis Radio', artist: 'En direct' };
        radioStreamUrl.value = streamUrl;
        currentTime.value = 0;
        duration.value = 0;
    }

    function updateRadioMetadata(metadata) {
        if (!metadata) return;
        radioMetadata.value = metadata;
    }

    function resumeRadio() {
        currentMode.value = 'radio';
        isPlaying.value = true;
    }

    // Persist state (excluding isPlaying)
    watch(
        () => ({
            currentSound: currentSound.value,
            currentMode: currentMode.value,
            radioMetadata: radioMetadata.value,
            radioStreamUrl: radioStreamUrl.value,
            currentTime: currentTime.value,
            duration: duration.value,
            volume: volume.value,
            isMuted: isMuted.value,
        }),
        (state) => saveState(state),
        { deep: true },
    );

    return {
        currentSound,
        isPlaying,
        currentTime,
        duration,
        volume,
        isMuted,
        currentMode,
        radioMetadata,
        radioStreamUrl,
        hasActiveTrack,
        hasActiveRadio,
        play,
        pause,
        togglePlay,
        stop,
        setTime,
        setDuration,
        setVolume,
        toggleMute,
        close,
        connectRadio,
        updateRadioMetadata,
        resumeRadio,
    };
});
