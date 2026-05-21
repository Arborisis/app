import { ref, shallowRef, onUnmounted } from 'vue';

const SUPPORTED_MIME_TYPES = [
    'audio/webm;codecs=opus',
    'audio/webm',
    'audio/mp4',
    'audio/mp4;codecs=mp4a',
    'audio/aac',
];

function getBestMimeType() {
    for (const type of SUPPORTED_MIME_TYPES) {
        if (MediaRecorder.isTypeSupported(type)) {
            return type;
        }
    }
    return '';
}

export function useAudioRecorder() {
    const isRecording = ref(false);
    const isPaused = ref(false);
    const duration = ref(0);
    const audioBlob = ref(null);
    const audioUrl = ref(null);
    const error = ref(null);
    const devices = ref([]);
    const selectedDevice = ref(null);
    const rms = ref(0);
    const peak = ref(0);
    const isLoadingDevices = ref(false);

    let mediaRecorder = null;
    let mediaStream = null;
    let audioContext = null;
    const analyser = shallowRef(null);
    const sourceNode = shallowRef(null);
    let chunks = [];
    let timerInterval = null;
    let rafId = null;
    let startTime = 0;
    let pausedDuration = 0;

    const cleanup = () => {
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            try {
                mediaRecorder.stop();
            } catch {
                // ignore
            }
        }
        if (mediaStream) {
            mediaStream.getTracks().forEach((t) => t.stop());
            mediaStream = null;
        }
        if (sourceNode.value) {
            try {
                sourceNode.value.disconnect();
            } catch {
                // ignore
            }
            sourceNode.value = null;
        }
        if (analyser.value) {
            try {
                analyser.value.disconnect();
            } catch {
                // ignore
            }
            analyser.value = null;
        }
        if (audioContext && audioContext.state !== 'closed') {
            try {
                audioContext.close();
            } catch {
                // ignore
            }
            audioContext = null;
        }
        mediaRecorder = null;
    };

    const enumerateDevices = async () => {
        isLoadingDevices.value = true;
        error.value = null;
        try {
            // Safari iOS hides labels until permission is granted once.
            // We try to get a dummy stream first if no labels are present.
            let deviceList = await navigator.mediaDevices.enumerateDevices();
            const needsPermission = deviceList.every((d) => !d.label);
            if (needsPermission) {
                try {
                    const tempStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    tempStream.getTracks().forEach((t) => t.stop());
                    deviceList = await navigator.mediaDevices.enumerateDevices();
                } catch (permErr) {
                    error.value = 'Veuillez autoriser l\'accès au micro pour voir les périphériques.';
                    isLoadingDevices.value = false;
                    return;
                }
            }
            devices.value = deviceList
                .filter((d) => d.kind === 'audioinput')
                .map((d) => ({
                    deviceId: d.deviceId,
                    label: d.label || `Micro ${d.deviceId.slice(0, 8)}`,
                }));
            if (devices.value.length > 0 && !selectedDevice.value) {
                selectedDevice.value = devices.value[0].deviceId;
            }
        } catch (e) {
            error.value = e.message || 'Impossible d\'accéder aux périphériques audio.';
        } finally {
            isLoadingDevices.value = false;
        }
    };

    const startAnalysisLoop = () => {
        if (!analyser.value) return;
        const dataArray = new Uint8Array(analyser.value.frequencyBinCount);
        const timeArray = new Uint8Array(analyser.value.frequencyBinCount);

        const loop = () => {
            if (!analyser.value) return;
            analyser.value.getByteFrequencyData(dataArray);
            analyser.value.getByteTimeDomainData(timeArray);

            // RMS
            let sum = 0;
            for (let i = 0; i < timeArray.length; i++) {
                const normalized = (timeArray[i] - 128) / 128;
                sum += normalized * normalized;
            }
            rms.value = Math.sqrt(sum / timeArray.length);

            // Peak
            let max = 0;
            for (let i = 0; i < dataArray.length; i++) {
                if (dataArray[i] > max) max = dataArray[i];
            }
            peak.value = max / 255;

            rafId = requestAnimationFrame(loop);
        };
        loop();
    };

    const startRecording = async (opts = {}) => {
        cleanup();
        error.value = null;
        audioBlob.value = null;
        if (audioUrl.value) {
            URL.revokeObjectURL(audioUrl.value);
            audioUrl.value = null;
        }
        duration.value = 0;
        pausedDuration = 0;
        chunks = [];

        const constraints = {
            audio: {
                deviceId: opts.deviceId ? { exact: opts.deviceId } : undefined,
                echoCancellation: opts.echoCancellation ?? false,
                noiseSuppression: opts.noiseSuppression ?? false,
                autoGainControl: opts.autoGainControl ?? false,
                sampleRate: opts.sampleRate ?? 48000,
                channelCount: opts.channelCount ?? 2,
            },
        };

        try {
            mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
        } catch (e) {
            error.value = e.message || 'Accès au micro refusé.';
            return;
        }

        // Build AudioContext + Analyser for viz
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        analyser.value = audioContext.createAnalyser();
        analyser.value.fftSize = 2048;
        analyser.value.smoothingTimeConstant = 0.8;
        sourceNode.value = audioContext.createMediaStreamSource(mediaStream);
        sourceNode.value.connect(analyser.value);

        const mimeType = getBestMimeType();
        const recorderOpts = mimeType ? { mimeType } : {};
        try {
            mediaRecorder = new MediaRecorder(mediaStream, recorderOpts);
        } catch (e) {
            error.value = e.message || 'Votre navigateur ne supporte pas l\'enregistrement audio.';
            cleanup();
            return;
        }

        mediaRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                chunks.push(event.data);
            }
        };

        mediaRecorder.onstop = () => {
            const blob = new Blob(chunks, { type: mediaRecorder.mimeType || 'audio/webm' });
            audioBlob.value = blob;
            audioUrl.value = URL.createObjectURL(blob);
            chunks = [];
            cleanup();
        };

        mediaRecorder.onerror = (e) => {
            error.value = 'Erreur lors de l\'enregistrement : ' + (e.message || 'inconnue');
            cleanup();
        };

        mediaRecorder.start(1000); // collect every second
        isRecording.value = true;
        isPaused.value = false;
        startTime = Date.now();

        startAnalysisLoop();

        timerInterval = setInterval(() => {
            if (!isPaused.value) {
                duration.value = Math.floor((Date.now() - startTime - pausedDuration) / 1000);
            }
        }, 500);
    };

    const pauseRecording = () => {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.pause();
            isPaused.value = true;
            pausedDuration += Date.now() - startTime - pausedDuration - duration.value * 1000;
        }
    };

    const resumeRecording = () => {
        if (mediaRecorder && mediaRecorder.state === 'paused') {
            mediaRecorder.resume();
            isPaused.value = false;
            startTime = Date.now() - duration.value * 1000 - pausedDuration;
        }
    };

    const stopRecording = () => {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        isRecording.value = false;
        isPaused.value = false;
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    };

    const resetRecording = () => {
        cleanup();
        isRecording.value = false;
        isPaused.value = false;
        duration.value = 0;
        if (audioUrl.value) {
            URL.revokeObjectURL(audioUrl.value);
            audioUrl.value = null;
        }
        audioBlob.value = null;
        error.value = null;
        rms.value = 0;
        peak.value = 0;
    };

    const formatDuration = (seconds) => {
        const m = Math.floor(seconds / 60)
            .toString()
            .padStart(2, '0');
        const s = Math.floor(seconds % 60)
            .toString()
            .padStart(2, '0');
        return `${m}:${s}`;
    };

    onUnmounted(() => {
        cleanup();
        if (audioUrl.value) {
            URL.revokeObjectURL(audioUrl.value);
        }
    });

    return {
        isRecording,
        isPaused,
        duration,
        audioBlob,
        audioUrl,
        error,
        devices,
        selectedDevice,
        rms,
        peak,
        isLoadingDevices,
        analyser,
        sourceNode,
        enumerateDevices,
        startRecording,
        pauseRecording,
        resumeRecording,
        stopRecording,
        resetRecording,
        formatDuration,
    };
}
