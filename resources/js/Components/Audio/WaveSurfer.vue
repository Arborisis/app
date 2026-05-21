<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import WaveSurfer from 'wavesurfer.js';

const props = defineProps({
    audioUrl: { type: String, required: true },
    isPlaying: { type: Boolean, default: false },
    waveColor: { type: String, default: '#4a5d4a' },
    progressColor: { type: String, default: '#7c9a6a' },
    cursorColor: { type: String, default: '#d4c9a8' },
    height: { type: Number, default: 80 },
});

const emit = defineEmits(['ready', 'timeupdate', 'finish', 'play', 'pause']);

const containerRef = ref(null);
let wavesurfer = null;

onMounted(() => {
    if (!containerRef.value || !props.audioUrl) return;

    wavesurfer = WaveSurfer.create({
        container: containerRef.value,
        waveColor: props.waveColor,
        progressColor: props.progressColor,
        cursorColor: props.cursorColor,
        cursorWidth: 2,
        height: props.height,
        barWidth: 2,
        barGap: 1,
        barRadius: 2,
        url: props.audioUrl,
    });

    wavesurfer.on('ready', () => {
        emit('ready', wavesurfer.getDuration());
    });

    wavesurfer.on('audioprocess', (currentTime) => {
        emit('timeupdate', currentTime);
    });

    wavesurfer.on('finish', () => {
        emit('finish');
    });

    wavesurfer.on('play', () => {
        emit('play');
    });

    wavesurfer.on('pause', () => {
        emit('pause');
    });
});

onUnmounted(() => {
    if (wavesurfer) {
        wavesurfer.destroy();
        wavesurfer = null;
    }
});

watch(() => props.isPlaying, (playing) => {
    if (!wavesurfer) return;
    if (playing) {
        wavesurfer.play();
    } else {
        wavesurfer.pause();
    }
});

function seekTo(percent) {
    if (!wavesurfer) return;
    wavesurfer.seekTo(percent);
}

defineExpose({ seekTo });
</script>

<template>
    <div ref="containerRef" class="w-full" />
</template>
