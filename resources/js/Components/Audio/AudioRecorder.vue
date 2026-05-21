<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useAudioRecorder } from '@/Composables/useAudioRecorder';

const emit = defineEmits(['confirm', 'cancel']);

const {
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
    enumerateDevices,
    startRecording,
    pauseRecording,
    resumeRecording,
    stopRecording,
    resetRecording,
    formatDuration,
} = useAudioRecorder();

const canvasRef = ref(null);
const previewAudioRef = ref(null);
const isPreviewPlaying = ref(false);
const showAdvanced = ref(false);
const echoCancellation = ref(false);
const noiseSuppression = ref(false);
const autoGainControl = ref(false);

let drawRaf = null;

const state = computed(() => {
    if (audioUrl.value) return 'preview';
    if (isRecording.value && isPaused.value) return 'paused';
    if (isRecording.value) return 'recording';
    return 'idle';
});

const timerDisplay = computed(() => formatDuration(duration.value));

const start = async () => {
    await startRecording({
        deviceId: selectedDevice.value,
        echoCancellation: echoCancellation.value,
        noiseSuppression: noiseSuppression.value,
        autoGainControl: autoGainControl.value,
    });
    nextTick(() => startDrawing());
};

const stop = () => {
    stopRecording();
    if (drawRaf) {
        cancelAnimationFrame(drawRaf);
        drawRaf = null;
    }
};

const discard = () => {
    resetRecording();
    isPreviewPlaying.value = false;
};

const confirm = () => {
    if (audioBlob.value && audioUrl.value) {
        emit('confirm', audioBlob.value, audioUrl.value);
    }
};

const togglePreview = () => {
    if (!previewAudioRef.value) return;
    if (isPreviewPlaying.value) {
        previewAudioRef.value.pause();
    } else {
        previewAudioRef.value.play();
    }
};

const onPreviewPlay = () => { isPreviewPlaying.value = true; };
const onPreviewPause = () => { isPreviewPlaying.value = false; };
const onPreviewEnded = () => { isPreviewPlaying.value = false; };

/* ─── Canvas waveform drawing ─── */
const startDrawing = () => {
    if (drawRaf) cancelAnimationFrame(drawRaf);
    const canvas = canvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;

    const resize = () => {
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        if (ctx.resetTransform) ctx.resetTransform();
        ctx.scale(dpr, dpr);
    };
    resize();

    const bufferLength = 2048;
    const dataArray = new Uint8Array(bufferLength);

    const draw = () => {
        drawRaf = requestAnimationFrame(draw);
        if (!analyser.value) return;

        const w = canvas.width / dpr;
        const h = canvas.height / dpr;
        ctx.clearRect(0, 0, w, h);

        analyser.value.getByteTimeDomainData(dataArray);

        ctx.lineWidth = 2;
        ctx.strokeStyle = 'rgba(143, 230, 193, 0.62)';
        ctx.shadowBlur = 6;
        ctx.shadowColor = 'rgba(143, 230, 193, 0.28)';

        ctx.beginPath();
        const sliceWidth = w / bufferLength;
        let x = 0;
        for (let i = 0; i < bufferLength; i++) {
            const v = dataArray[i] / 128.0;
            const y = (v * h) / 2;
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
            x += sliceWidth;
        }
        ctx.lineTo(w, h / 2);
        ctx.stroke();
        ctx.shadowBlur = 0;

        // Draw a subtle mirrored wave
        ctx.beginPath();
        ctx.strokeStyle = 'rgba(215, 180, 106, 0.16)';
        ctx.lineWidth = 1;
        x = 0;
        for (let i = 0; i < bufferLength; i++) {
            const v = dataArray[i] / 128.0;
            const y = h - (v * h) / 2;
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
            x += sliceWidth;
        }
        ctx.lineTo(w, h / 2);
        ctx.stroke();
    };
    draw();
};

watch(state, (s) => {
    if (s !== 'recording' && drawRaf) {
        cancelAnimationFrame(drawRaf);
        drawRaf = null;
    }
});

onMounted(() => {
    enumerateDevices();
});

onUnmounted(() => {
    if (drawRaf) cancelAnimationFrame(drawRaf);
});
</script>

<template>
    <div class="relative w-full min-h-[70vh] flex flex-col items-center justify-center overflow-hidden">
        <!-- Subtle ambient radial glow behind recorder -->
        <div
            class="pointer-events-none absolute inset-0 opacity-40"
            style="background: radial-gradient(ellipse 60% 40% at 50% 55%, rgba(143,230,193,0.08), transparent 70%);"
        />

        <!-- Error banner -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="-translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-2 opacity-0"
        >
            <div
                v-if="error"
                class="absolute top-4 left-4 right-4 z-20 mx-auto max-w-md rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-center text-sm text-red-300 backdrop-blur-sm"
            >
                {{ error }}
                <button
                    class="ml-2 inline-block text-xs underline opacity-80 hover:opacity-100"
                    @click="error = null"
                >
                    Fermer
                </button>
            </div>
        </transition>

        <!-- Device selector + advanced -->
        <div class="z-10 mb-10 flex w-full max-w-md flex-col items-center gap-4 px-6">
            <div class="relative w-full">
                <label class="mb-1.5 block text-center text-xs font-medium tracking-widest text-arbor-sage/70 uppercase">
                    Source audio
                </label>
                <select
                    v-model="selectedDevice"
                    :disabled="isRecording || isLoadingDevices"
                    class="w-full appearance-none rounded-lg border border-arbor-mineral/10 bg-arbor-deep/60 px-4 py-2.5 text-sm text-arbor-cream shadow-sm backdrop-blur-sm transition-colors focus:border-arbor-lichen focus:outline-none focus:ring-1 focus:ring-arbor-lichen disabled:opacity-50"
                >
                    <option v-for="dev in devices" :key="dev.deviceId" :value="dev.deviceId">
                        {{ dev.label }}
                    </option>
                </select>
                <div class="pointer-events-none absolute right-3 top-[2.1rem] text-arbor-sage/70">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <button
                class="text-xs text-arbor-sage/70 underline-offset-2 transition-colors hover:text-arbor-sage"
                @click="showAdvanced = !showAdvanced"
            >
                {{ showAdvanced ? 'Masquer les options' : 'Options avancées' }}
            </button>

            <transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="max-h-0 opacity-0"
                enter-to-class="max-h-40 opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="max-h-40 opacity-100"
                leave-to-class="max-h-0 opacity-0"
            >
                <div v-show="showAdvanced" class="w-full overflow-hidden">
                    <div class="flex flex-wrap justify-center gap-4 rounded-lg border border-arbor-mineral/10 bg-arbor-deep/40 px-4 py-3">
                        <label class="flex cursor-pointer items-center gap-2 text-xs text-arbor-sage">
                            <input v-model="echoCancellation" type="checkbox" class="rounded border-arbor-glass-border bg-arbor-deep text-arbor-lichen focus:ring-arbor-lichen" />
                            Réduction d'écho
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 text-xs text-arbor-sage">
                            <input v-model="noiseSuppression" type="checkbox" class="rounded border-arbor-glass-border bg-arbor-deep text-arbor-lichen focus:ring-arbor-lichen" />
                            Suppression de bruit
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 text-xs text-arbor-sage">
                            <input v-model="autoGainControl" type="checkbox" class="rounded border-arbor-glass-border bg-arbor-deep text-arbor-lichen focus:ring-arbor-lichen" />
                            AGC
                        </label>
                    </div>
                </div>
            </transition>
        </div>

        <!-- Visualizer canvas (visible during recording / paused) -->
        <div class="relative z-10 mb-8 h-24 w-full max-w-2xl px-6">
            <canvas
                ref="canvasRef"
                class="h-full w-full rounded-xl opacity-0 transition-opacity duration-500"
                :class="{ 'opacity-100': state === 'recording' || state === 'paused' }"
            />
        </div>

        <!-- Main recorder ring -->
        <div class="relative z-10 flex flex-col items-center">
            <!-- Concentric decorative rings (idle) -->
            <div
                class="absolute inset-0 -m-6 rounded-full border border-arbor-lichen/10 transition-all duration-700"
                :class="{ 'scale-110 opacity-0': state !== 'idle' }"
            />
            <div
                class="absolute inset-0 -m-12 rounded-full border border-arbor-firefly/10 transition-all duration-700 delay-100"
                :class="{ 'scale-110 opacity-0': state !== 'idle' }"
            />

            <!-- Pulsing ring during recording -->
            <div
                class="absolute inset-0 -m-4 rounded-full border border-arbor-firefly/35 transition-all duration-300"
                :style="{
                    transform: state === 'recording' ? `scale(${1 + peak * 0.08})` : 'scale(1)',
                    opacity: state === 'recording' ? 0.35 + peak * 0.35 : 0,
                }"
            />
            <div
                class="absolute inset-0 -m-8 rounded-full border border-arbor-lichen/18 transition-all duration-500"
                :style="{
                    transform: state === 'recording' ? `scale(${1 + rms * 0.12})` : 'scale(1)',
                    opacity: state === 'recording' ? 0.24 + rms * 0.28 : 0,
                }"
            />

            <!-- Central button -->
            <button
                class="relative flex h-28 w-28 items-center justify-center rounded-full transition-all duration-300 active:scale-[0.96]"
                :class="[
                    state === 'recording'
                        ? 'bg-arbor-firefly/10 shadow-[0_0_34px_rgba(143,230,193,0.18)]'
                        : state === 'paused'
                          ? 'bg-arbor-amber/10 shadow-[0_0_40px_rgba(212,165,116,0.15)]'
                          : 'bg-arbor-glass shadow-[0_0_28px_rgba(215,180,106,0.08)] hover:shadow-[0_0_42px_rgba(143,230,193,0.12)] hover:bg-arbor-lichen/5',
                ]"
                @click="
                    () => {
                        if (state === 'idle') start();
                        else if (state === 'recording') pauseRecording();
                        else if (state === 'paused') resumeRecording();
                    }
                "
            >
                <!-- Idle mic icon -->
                <svg
                    v-if="state === 'idle'"
                    class="h-10 w-10 text-arbor-lichen transition-transform duration-300"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                    />
                </svg>

                <!-- Recording pause icon -->
                <svg
                    v-else-if="state === 'recording'"
                    class="h-10 w-10 text-arbor-firefly"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <rect x="6" y="4" width="4" height="16" rx="1" />
                    <rect x="14" y="4" width="4" height="16" rx="1" />
                </svg>

                <!-- Paused play icon -->
                <svg
                    v-else-if="state === 'paused'"
                    class="ml-1 h-10 w-10 text-arbor-amber"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path d="M8 5v14l11-7z" />
                </svg>
            </button>

            <!-- Timer -->
            <div
                class="mt-5 font-mono text-3xl tracking-widest transition-colors duration-300"
                :class="{
                    'text-arbor-firefly': state === 'recording',
                    'text-arbor-amber': state === 'paused',
                    'text-arbor-sage/70': state === 'idle',
                }"
            >
                {{ timerDisplay }}
            </div>

            <!-- State label -->
            <p class="mt-2 text-sm font-display italic text-arbor-sage/70 transition-all duration-300">
                <span v-if="state === 'idle'">Appuyez pour écouter le silence</span>
                <span v-else-if="state === 'recording'">Enregistrement en cours…</span>
                <span v-else-if="state === 'paused'">En pause</span>
            </p>
        </div>

        <!-- Stop button (recording / paused) -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-3 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-3 opacity-0"
        >
            <div v-if="state === 'recording' || state === 'paused'" class="z-10 mt-10 flex gap-6">
                <button
                    class="flex h-12 w-12 items-center justify-center rounded-full border border-arbor-glass-border bg-arbor-deep/60 text-arbor-cream backdrop-blur-sm transition-all hover:bg-red-500/10 hover:text-red-400 hover:border-red-500/30"
                    @click="stop"
                    title="Arrêter"
                >
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <rect x="6" y="6" width="12" height="12" rx="2" />
                    </svg>
                </button>
            </div>
        </transition>

        <!-- Preview panel -->
        <transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="translate-y-6 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-6 opacity-0"
        >
            <div v-if="state === 'preview'" class="z-10 mt-8 flex w-full max-w-lg flex-col items-center gap-6 px-6">
                <div class="w-full rounded-lg border border-arbor-mineral/10 bg-arbor-deep/40 p-6 backdrop-blur-sm">
                    <p class="atlas-kicker mb-2 text-center">
                        Capture prête
                    </p>
                    <p class="mb-4 text-center font-display text-xl text-arbor-cream">
                        Votre capture
                    </p>

                    <audio
                        ref="previewAudioRef"
                        :src="audioUrl"
                        class="w-full"
                        @play="onPreviewPlay"
                        @pause="onPreviewPause"
                        @ended="onPreviewEnded"
                        controls
                    />

                    <div class="mt-6 flex justify-center gap-4">
                        <button
                            class="btn-secondary inline-flex items-center gap-2 px-5 py-2.5 text-sm"
                            @click="discard"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Réenregistrer
                        </button>

                        <button
                            class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 text-sm"
                            @click="confirm"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Utiliser cet enregistrement
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
