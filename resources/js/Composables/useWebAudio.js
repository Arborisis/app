import { ref, onUnmounted } from 'vue';

export function useWebAudio() {
    let audioContext = null;
    let analyser = null;
    let source = null;
    let audioElement = null;
    let rafId = null;

    const isInitialized = ref(false);
    const isPlaying = ref(false);
    const currentTime = ref(0);
    const duration = ref(0);

    // Data buffers
    const frequencyData = ref(new Uint8Array(0));
    const timeDomainData = ref(new Uint8Array(0));
    const spectrogramHistory = ref([]);
    const rms = ref(0);
    const zcr = ref(0);

    const createAnalyzer = (element, fftSize = 2048) => {
        audioElement = element;

        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }

        if (analyser) {
            analyser.disconnect();
        }

        analyser = audioContext.createAnalyser();
        analyser.fftSize = fftSize;
        analyser.smoothingTimeConstant = 0.8;

        if (source) {
            source.disconnect();
        }

        source = audioContext.createMediaElementSource(audioElement);
        source.connect(analyser);
        analyser.connect(audioContext.destination);

        frequencyData.value = new Uint8Array(analyser.frequencyBinCount);
        timeDomainData.value = new Uint8Array(analyser.frequencyBinCount);

        isInitialized.value = true;
        duration.value = audioElement.duration || 0;

        audioElement.addEventListener('play', () => { isPlaying.value = true; });
        audioElement.addEventListener('pause', () => { isPlaying.value = false; });
        audioElement.addEventListener('timeupdate', () => {
            currentTime.value = audioElement.currentTime;
        });
    };

    const startAnalysisLoop = () => {
        if (!analyser) return;

        const loop = () => {
            if (!analyser) return;

            analyser.getByteFrequencyData(frequencyData.value);
            analyser.getByteTimeDomainData(timeDomainData.value);

            // Calcul RMS
            let sum = 0;
            for (let i = 0; i < timeDomainData.value.length; i++) {
                const normalized = (timeDomainData.value[i] - 128) / 128;
                sum += normalized * normalized;
            }
            rms.value = Math.sqrt(sum / timeDomainData.value.length);

            // Calcul ZCR approximatif
            let crossings = 0;
            for (let i = 1; i < timeDomainData.value.length; i++) {
                if ((timeDomainData.value[i] - 128) * (timeDomainData.value[i - 1] - 128) < 0) {
                    crossings++;
                }
            }
            zcr.value = crossings / timeDomainData.value.length;

            // Accumulate spectrogram
            spectrogramHistory.value.push([...frequencyData.value]);
            if (spectrogramHistory.value.length > 200) {
                spectrogramHistory.value.shift();
            }

            rafId = requestAnimationFrame(loop);
        };

        loop();
    };

    const play = async () => {
        if (audioContext?.state === 'suspended') {
            await audioContext.resume();
        }
        audioElement?.play();
        startAnalysisLoop();
    };

    const pause = () => {
        audioElement?.pause();
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    };

    const seek = (time) => {
        if (audioElement) {
            audioElement.currentTime = time;
        }
    };

    const dispose = () => {
        if (rafId) {
            cancelAnimationFrame(rafId);
        }
        if (source) {
            source.disconnect();
            source = null;
        }
        if (analyser) {
            analyser.disconnect();
            analyser = null;
        }
        if (audioContext) {
            audioContext.close();
            audioContext = null;
        }
        isInitialized.value = false;
    };

    onUnmounted(() => {
        dispose();
    });

    return {
        isInitialized,
        isPlaying,
        currentTime,
        duration,
        frequencyData,
        timeDomainData,
        spectrogramHistory,
        rms,
        zcr,
        createAnalyzer,
        play,
        pause,
        seek,
        dispose,
    };
}
