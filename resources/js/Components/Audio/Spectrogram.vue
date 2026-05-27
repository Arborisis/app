<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    audioUrl: {
        type: String,
        required: true,
    },
    isPlaying: Boolean,
    currentTime: Number,
});

const canvasRef = ref(null);
let audioContext, analyser, dataArray, source;
let animationId;

const initAudio = async () => {
    if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        analyser = audioContext.createAnalyser();
        analyser.fftSize = 2048;
        analyser.smoothingTimeConstant = 0.8;
        
        const response = await fetch(props.audioUrl);
        const arrayBuffer = await response.arrayBuffer();
        const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
        
        source = audioContext.createBufferSource();
        source.buffer = audioBuffer;
        source.connect(analyser);
        analyser.connect(audioContext.destination);
        
        dataArray = new Uint8Array(analyser.frequencyBinCount);
    }
};

const drawSpectrogram = () => {
    if (!canvasRef.value || !analyser) return;
    
    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    
    analyser.getByteFrequencyData(dataArray);
    
    // Create gradient based on frequency intensity
    const barWidth = width / dataArray.length;
    
    ctx.fillStyle = 'rgba(5, 10, 8, 0.1)';
    ctx.fillRect(0, 0, width, height);
    
    for (let i = 0; i < dataArray.length; i++) {
        const barHeight = (dataArray[i] / 255) * height;
        const x = i * barWidth;
        
        // Color based on frequency (low = green, mid = teal, high = white)
        const hue = 150 + (i / dataArray.length) * 60;
        const saturation = 60 + (dataArray[i] / 255) * 40;
        const lightness = 20 + (dataArray[i] / 255) * 60;
        
        ctx.fillStyle = `hsl(${hue}, ${saturation}%, ${lightness}%)`;
        ctx.fillRect(x, height - barHeight, barWidth + 0.5, barHeight);
    }
    
    // Draw time indicator
    if (props.currentTime > 0) {
        const progressX = (props.currentTime / audioContext.duration) * width;
        ctx.strokeStyle = '#34D399';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(progressX, 0);
        ctx.lineTo(progressX, height);
        ctx.stroke();
    }
    
    animationId = requestAnimationFrame(drawSpectrogram);
};

watch(() => props.isPlaying, (playing) => {
    if (playing) {
        initAudio().then(() => {
            source.start();
            drawSpectrogram();
        });
    } else {
        if (source) {
            source.stop();
        }
        cancelAnimationFrame(animationId);
    }
});

onMounted(() => {
    if (canvasRef.value) {
        canvasRef.value.width = canvasRef.value.offsetWidth * 2;
        canvasRef.value.height = canvasRef.value.offsetHeight * 2;
    }
});
</script>

<template>
    <canvas 
        ref="canvasRef" 
        class="w-full h-64 rounded-2xl bg-[#050A08]"
    />
</template>
