<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useWebAudio } from '@/Composables/useWebAudio';

const props = defineProps({
    audioUrl: { type: String, required: true },
    duration: { type: Number, default: 0 },
});

const audioRef = ref(null);
const canvasRef = ref(null);
const vuCanvasRef = ref(null);
const blobUrl = ref(null);

const {
    isInitialized,
    isPlaying,
    rms,
    zcr,
    frequencyData,
    spectrogramHistory,
    createAnalyzer,
    play,
    pause,
    dispose,
} = useWebAudio();

let drawRaf = null;

onMounted(async () => {
    try {
        const response = await fetch(props.audioUrl);
        const blob = await response.blob();
        blobUrl.value = URL.createObjectURL(blob);

        if (audioRef.value) {
            audioRef.value.src = blobUrl.value;
            audioRef.value.addEventListener('canplay', () => {
                createAnalyzer(audioRef.value, 2048);
            });
        }
    } catch (e) {
        console.error('Failed to load audio blob for realtime analysis:', e);
    }
});

onUnmounted(() => {
    if (drawRaf) cancelAnimationFrame(drawRaf);
    dispose();
    if (blobUrl.value) {
        URL.revokeObjectURL(blobUrl.value);
    }
});

watch(isPlaying, (playing) => {
    if (playing) {
        startDrawing();
    } else {
        if (drawRaf) cancelAnimationFrame(drawRaf);
    }
});

const togglePlay = () => {
    if (isPlaying.value) {
        pause();
    } else {
        play();
    }
};

const startDrawing = () => {
    const canvas = canvasRef.value;
    const vuCanvas = vuCanvasRef.value;
    if (!canvas || !vuCanvas) return;

    const ctx = canvas.getContext('2d');
    const vuCtx = vuCanvas.getContext('2d');

    const draw = () => {
        const width = canvas.width;
        const height = canvas.height;

        ctx.fillStyle = '#0B1220';
        ctx.fillRect(0, 0, width, height);

        // Draw waveform
        ctx.beginPath();
        ctx.strokeStyle = '#34D399';
        ctx.lineWidth = 2;

        const data = frequencyData.value;
        const sliceWidth = width / data.length;
        let x = 0;

        for (let i = 0; i < data.length; i++) {
            const v = data[i] / 255;
            const y = height - (v * height);

            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
            x += sliceWidth;
        }
        ctx.stroke();

        // Draw spectrogram preview
        const spec = spectrogramHistory.value;
        if (spec.length > 0) {
            const specWidth = width;
            const specHeight = height * 0.4;
            const specY = height * 0.55;
            const binWidth = specWidth / spec.length;

            for (let t = 0; t < spec.length; t++) {
                const frame = spec[t];
                const binHeight = specHeight / frame.length;
                for (let f = 0; f < frame.length; f += 4) {
                    const intensity = frame[f] / 255;
                    ctx.fillStyle = `rgba(52, 211, 153, ${intensity * 0.6})`;
                    ctx.fillRect(t * binWidth, specY + f * binHeight, binWidth + 1, binHeight * 4);
                }
            }
        }

        // VU meter
        const vuWidth = vuCanvas.width;
        const vuHeight = vuCanvas.height;
        vuCtx.fillStyle = '#111827';
        vuCtx.fillRect(0, 0, vuWidth, vuHeight);

        const barWidth = vuWidth * 0.8;
        const barHeight = vuHeight * 0.4;
        const barX = (vuWidth - barWidth) / 2;
        const barY = (vuHeight - barHeight) / 2;

        vuCtx.fillStyle = '#2a3142';
        vuCtx.fillRect(barX, barY, barWidth, barHeight);

        const fillWidth = barWidth * Math.min(rms.value * 2, 1);
        const gradient = vuCtx.createLinearGradient(barX, 0, barX + barWidth, 0);
        gradient.addColorStop(0, '#34D399');
        gradient.addColorStop(0.7, '#D4A574');
        gradient.addColorStop(1, '#ef4444');

        vuCtx.fillStyle = gradient;
        vuCtx.fillRect(barX, barY, fillWidth, barHeight);

        // Text metrics
        vuCtx.fillStyle = '#F3F0E7';
        vuCtx.font = '12px monospace';
        vuCtx.textAlign = 'center';
        vuCtx.fillText(`RMS: ${(rms.value * 100).toFixed(1)}%`, vuWidth / 2, vuHeight - 8);

        drawRaf = requestAnimationFrame(draw);
    };

    draw();
};

const formatTime = (seconds) => {
    if (!seconds || isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};
</script>

<template>
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-arbor-cream text-sm">Analyse temps réel</h3>
            <button @click="togglePlay"
                :aria-label="isPlaying ? 'Pause' : 'Lecture'"
                class="w-10 h-10 rounded-full bg-arbor-emerald flex items-center justify-center hover:bg-arbor-emerald-dark transition-colors">
                <svg v-if="!isPlaying" class="w-4 h-4 text-arbor-night ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
                <svg v-else class="w-4 h-4 text-arbor-night" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                </svg>
            </button>
        </div>

        <audio ref="audioRef" preload="metadata" class="hidden"></audio>

        <div class="space-y-3">
            <canvas ref="canvasRef" width="600" height="200" class="w-full rounded-xl bg-arbor-deep border border-arbor-glass-border"></canvas>
            <canvas ref="vuCanvasRef" width="300" height="60" class="w-full rounded-xl bg-arbor-deep border border-arbor-glass-border"></canvas>
        </div>

        <div class="flex justify-between text-xs text-arbor-sage mt-3">
            <span>ZCR: {{ (zcr * 100).toFixed(1) }}%</span>
            <span>Durée: {{ formatTime(duration) }}</span>
        </div>
    </div>
</template>
