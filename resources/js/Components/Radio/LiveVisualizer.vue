<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    analyser: Object,
    mode: {
        type: String,
        default: 'spectrum',
    },
    active: {
        type: Boolean,
        default: false,
    },
});

const canvasRef = ref(null);
let rafId = null;

const mode = computed(() => props.mode || 'spectrum');

function resize(canvas) {
    const rect = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    canvas.width = Math.max(1, Math.floor(rect.width * ratio));
    canvas.height = Math.max(1, Math.floor(rect.height * ratio));
}

function drawIdle(ctx, width, height) {
    ctx.clearRect(0, 0, width, height);
    const gradient = ctx.createLinearGradient(0, 0, width, height);
    gradient.addColorStop(0, 'rgba(75, 124, 91, 0.24)');
    gradient.addColorStop(0.55, 'rgba(31, 78, 63, 0.36)');
    gradient.addColorStop(1, 'rgba(226, 207, 154, 0.16)');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, width, height);

    ctx.strokeStyle = 'rgba(226, 207, 154, 0.25)';
    ctx.lineWidth = 1;
    for (let i = 0; i < 9; i += 1) {
        const y = (height / 10) * (i + 1);
        ctx.beginPath();
        ctx.moveTo(width * 0.08, y);
        ctx.bezierCurveTo(width * 0.32, y - 24, width * 0.68, y + 24, width * 0.92, y);
        ctx.stroke();
    }
}

function drawSpectrum(ctx, frequency, width, height) {
    const bars = 72;
    const gap = width * 0.003;
    const barWidth = (width - gap * (bars - 1)) / bars;
    const base = height * 0.76;

    for (let i = 0; i < bars; i += 1) {
        const index = Math.floor((i / bars) * frequency.length * 0.72);
        const value = frequency[index] / 255;
        const eased = Math.pow(value, 0.75);
        const barHeight = Math.max(height * 0.04, eased * height * 0.72);
        const x = i * (barWidth + gap);
        const y = base - barHeight;
        const gradient = ctx.createLinearGradient(0, y, 0, base);
        gradient.addColorStop(0, 'rgba(226, 207, 154, 0.95)');
        gradient.addColorStop(0.45, 'rgba(88, 166, 116, 0.82)');
        gradient.addColorStop(1, 'rgba(21, 56, 48, 0.35)');
        ctx.fillStyle = gradient;
        ctx.fillRect(x, y, barWidth, barHeight);
    }
}

function drawWaveform(ctx, timeData, width, height) {
    ctx.strokeStyle = 'rgba(226, 207, 154, 0.9)';
    ctx.lineWidth = Math.max(2, width * 0.004);
    ctx.beginPath();
    for (let i = 0; i < timeData.length; i += 1) {
        const x = (i / (timeData.length - 1)) * width;
        const centered = (timeData[i] - 128) / 128;
        const y = height * 0.5 + centered * height * 0.32;
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    }
    ctx.stroke();
}

function drawBloom(ctx, frequency, width, height) {
    const cx = width / 2;
    const cy = height / 2;
    const rings = 64;
    const radius = Math.min(width, height) * 0.18;

    for (let i = 0; i < rings; i += 1) {
        const value = frequency[Math.floor((i / rings) * frequency.length * 0.55)] / 255;
        const angle = (Math.PI * 2 * i) / rings;
        const length = radius + value * Math.min(width, height) * 0.28;
        const inner = radius * 0.55;
        ctx.strokeStyle = i % 3 === 0 ? 'rgba(226,207,154,.78)' : 'rgba(91,169,119,.68)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(cx + Math.cos(angle) * inner, cy + Math.sin(angle) * inner);
        ctx.lineTo(cx + Math.cos(angle) * length, cy + Math.sin(angle) * length);
        ctx.stroke();
    }
}

function render() {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;

    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = 'rgba(8, 22, 20, 0.96)';
    ctx.fillRect(0, 0, width, height);

    if (!props.analyser || !props.active) {
        drawIdle(ctx, width, height);
        rafId = requestAnimationFrame(render);
        return;
    }

    const frequency = new Uint8Array(props.analyser.frequencyBinCount);
    const timeData = new Uint8Array(props.analyser.frequencyBinCount);
    props.analyser.getByteFrequencyData(frequency);
    props.analyser.getByteTimeDomainData(timeData);

    if (mode.value === 'waveform') drawWaveform(ctx, timeData, width, height);
    else if (mode.value === 'bloom') drawBloom(ctx, frequency, width, height);
    else drawSpectrum(ctx, frequency, width, height);

    rafId = requestAnimationFrame(render);
}

onMounted(() => {
    resize(canvasRef.value);
    window.addEventListener('resize', () => canvasRef.value && resize(canvasRef.value));
    render();
});

onBeforeUnmount(() => {
    if (rafId) cancelAnimationFrame(rafId);
});

watch(() => props.analyser, render);
</script>

<template>
    <canvas ref="canvasRef" class="h-full w-full" aria-hidden="true" />
</template>
