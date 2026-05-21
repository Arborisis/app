import { computed, onBeforeUnmount, ref } from 'vue';

const audioContext = ref(null);
const analyser = ref(null);
const source = ref(null);
const bands = ref({ low: 0, mid: 0, high: 0, rms: 0 });
const connectedElement = ref(null);
let rafId = null;

function ensureContext() {
    if (typeof window === 'undefined') return null;

    const Context = window.AudioContext || window.webkitAudioContext;
    if (!Context) return null;

    if (!audioContext.value) {
        audioContext.value = new Context();
    }

    if (!analyser.value) {
        analyser.value = audioContext.value.createAnalyser();
        analyser.value.fftSize = 2048;
        analyser.value.smoothingTimeConstant = 0.82;
        analyser.value.connect(audioContext.value.destination);
    }

    return audioContext.value;
}

function startMetering() {
    if (rafId || !analyser.value) return;

    const frequency = new Uint8Array(analyser.value.frequencyBinCount);

    const tick = () => {
        analyser.value.getByteFrequencyData(frequency);

        const third = Math.max(1, Math.floor(frequency.length / 3));
        const average = (start, end) => {
            let sum = 0;
            for (let i = start; i < end; i += 1) sum += frequency[i] || 0;
            return sum / Math.max(1, end - start) / 255;
        };

        const low = average(0, third);
        const mid = average(third, third * 2);
        const high = average(third * 2, frequency.length);
        bands.value = {
            low,
            mid,
            high,
            rms: Math.sqrt((low * low + mid * mid + high * high) / 3),
        };

        rafId = requestAnimationFrame(tick);
    };

    rafId = requestAnimationFrame(tick);
}

function stopMetering() {
    if (rafId) {
        cancelAnimationFrame(rafId);
        rafId = null;
    }
}

export function useRadioAudio() {
    async function connect(audioElement) {
        const context = ensureContext();
        if (!context || !audioElement) return null;

        if (context.state === 'suspended') {
            await context.resume();
        }

        if (connectedElement.value !== audioElement) {
            if (source.value) {
                source.value.disconnect();
            }

            source.value = context.createMediaElementSource(audioElement);
            source.value.connect(analyser.value);
            connectedElement.value = audioElement;
        }

        startMetering();

        return analyser.value;
    }

    onBeforeUnmount(() => {
        stopMetering();
    });

    return {
        analyser: computed(() => analyser.value),
        bands,
        connect,
    };
}
