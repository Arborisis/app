<script setup>
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { usePlayerStore } from '@/Stores/player';
import { useWakeLock } from '@/Composables/useWakeLock';
import { Link } from '@inertiajs/vue3';

const player = usePlayerStore();
const { request: requestWakeLock, release: releaseWakeLock } = useWakeLock();
const audioRef = ref(null);
const showVolume = ref(false);
const visualizerBars = ref(Array.from({ length: 32 }, () => 8));
const canAnalyzeAudio = ref(true);

let audioContext = null;
let sourceNode = null;
let analyserNode = null;
let timeDomainData = null;
let frequencyData = null;
let visualizerRaf = null;

const progressPercent = computed(() => {
    if (!player.duration) return 0;
    return (player.currentTime / player.duration) * 100;
});

const effectiveVolume = computed(() => (player.isMuted ? 0 : player.volume));
const volumePercent = computed(() => Math.round(effectiveVolume.value * 100));

const volumeMeterBars = computed(() => {
    const levels = [20, 34, 52, 74, 96, 74, 52, 34, 20];
    const activeCount = Math.round((effectiveVolume.value || 0) * levels.length);

    return levels.map((height, index) => ({
        height,
        active: index < activeCount,
    }));
});

const formatTime = (seconds) => {
    if (!seconds || isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

function resetVisualizer() {
    visualizerBars.value = visualizerBars.value.map(() => 8);
}

async function setupAudioAnalyser() {
    if (!audioRef.value || analyserNode || !canAnalyzeAudio.value) return;

    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            canAnalyzeAudio.value = false;
            return;
        }

        audioContext = audioContext || new AudioContextClass();
        if (audioContext.state === 'suspended') {
            await audioContext.resume();
        }

        sourceNode = sourceNode || audioContext.createMediaElementSource(audioRef.value);
        analyserNode = audioContext.createAnalyser();
        analyserNode.fftSize = 1024;
        analyserNode.smoothingTimeConstant = 0.72;
        timeDomainData = new Uint8Array(analyserNode.fftSize);
        frequencyData = new Uint8Array(analyserNode.frequencyBinCount);

        sourceNode.connect(analyserNode);
        analyserNode.connect(audioContext.destination);
    } catch (error) {
        console.warn('Audio analyser unavailable:', error);
        canAnalyzeAudio.value = false;
        resetVisualizer();
    }
}

function stopVisualizer() {
    if (visualizerRaf) {
        cancelAnimationFrame(visualizerRaf);
        visualizerRaf = null;
    }
}

function drawVisualizer() {
    if (!analyserNode || !timeDomainData || audioRef.value?.paused) {
        stopVisualizer();
        return;
    }

    analyserNode.getByteTimeDomainData(timeDomainData);
    analyserNode.getByteFrequencyData(frequencyData);

    const bars = visualizerBars.value.length;
    const samplesPerBar = Math.max(1, Math.floor(timeDomainData.length / bars));
    const binsPerBar = Math.max(1, Math.floor(frequencyData.length / bars));

    visualizerBars.value = visualizerBars.value.map((previous, index) => {
        const start = index * samplesPerBar;
        const end = Math.min(start + samplesPerBar, timeDomainData.length);
        const binStart = index * binsPerBar;
        const binEnd = Math.min(binStart + binsPerBar, frequencyData.length);
        let peak = 0;
        let energy = 0;
        let spectralPeak = 0;
        let spectralEnergy = 0;

        for (let i = start; i < end; i += 1) {
            const centered = Math.abs(timeDomainData[i] - 128) / 128;
            peak = Math.max(peak, centered);
            energy += centered * centered;
        }

        for (let i = binStart; i < binEnd; i += 1) {
            const bin = frequencyData[i] / 255;
            spectralPeak = Math.max(spectralPeak, bin);
            spectralEnergy += bin * bin;
        }

        const rms = Math.sqrt(energy / Math.max(1, end - start));
        const spectralRms = Math.sqrt(spectralEnergy / Math.max(1, binEnd - binStart));
        const normalized = Math.min(1, Math.max(peak, rms * 1.8, spectralPeak * 0.92, spectralRms * 2.6) * 3.5);
        const target = Math.max(12, Math.min(100, 12 + normalized * 88));

        return previous * 0.48 + target * 0.52;
    });

    visualizerRaf = requestAnimationFrame(drawVisualizer);
}

async function startVisualizer() {
    await setupAudioAnalyser();
    if (!analyserNode || visualizerRaf) return;
    drawVisualizer();
}

function onNativePlay() {
    canAnalyzeAudio.value = true;
    startVisualizer();
    requestWakeLock();
}

function onNativePause() {
    stopVisualizer();
    resetVisualizer();
    releaseWakeLock();
}

function playAudioElement() {
    if (!audioRef.value || !player.currentSound?.audioUrl) return;

    audioRef.value.volume = player.isMuted ? 0 : player.volume;
    audioRef.value.muted = player.isMuted;

    if (audioRef.value.src !== player.currentSound.audioUrl) {
        audioRef.value.src = player.currentSound.audioUrl;
        audioRef.value.load();
    }

    audioRef.value.play().catch(() => {
        stopVisualizer();
        resetVisualizer();
        player.pause();
    });
}

function onDirectPlayRequest(event) {
    const sound = event.detail?.sound;
    if (!sound?.audioUrl || sound.id !== player.currentSound?.id) return;

    playAudioElement();
}

watch(() => player.isPlaying, (playing) => {
    if (!audioRef.value) return;
    if (playing) {
        playAudioElement();
    } else {
        audioRef.value.pause();
    }
});

watch(() => player.currentSound, async (sound) => {
    if (sound && audioRef.value) {
        await nextTick();
        audioRef.value.currentTime = player.currentTime || 0;
        if (player.isPlaying) {
            playAudioElement();
        }
    }
});

onMounted(() => {
    window.addEventListener('arborisis:play-sound', onDirectPlayRequest);

    if (audioRef.value && player.currentSound) {
        audioRef.value.currentTime = player.currentTime || 0;
        audioRef.value.volume = player.isMuted ? 0 : player.volume;
        audioRef.value.muted = player.isMuted;
    }
});

watch(() => player.isMuted, (muted) => {
    if (audioRef.value) {
        audioRef.value.muted = muted;
    }
});

watch(() => player.volume, (vol) => {
    if (audioRef.value) {
        audioRef.value.volume = vol;
    }
});

function onTimeUpdate() {
    if (audioRef.value) {
        player.setTime(audioRef.value.currentTime);
    }
}

function onLoadedMetadata() {
    if (audioRef.value) {
        player.setDuration(audioRef.value.duration);
    }
}

function onEnded() {
    stopVisualizer();
    resetVisualizer();
    player.stop();
    releaseWakeLock();
}

function setupMediaSession() {
    if (!('mediaSession' in navigator)) return;

    const sound = player.currentSound;
    if (!sound) return;

    navigator.mediaSession.metadata = new MediaMetadata({
        title: sound.title || 'Arborisis',
        artist: sound.userName || 'Anonyme',
        album: 'Arborisis — Archive sonore',
        artwork: [
            { src: sound.coverUrl || '/pwa-icons/icon-192x192.png', sizes: '192x192', type: 'image/png' },
            { src: sound.coverUrl || '/pwa-icons/icon-512x512.png', sizes: '512x512', type: 'image/png' },
        ],
    });

    navigator.mediaSession.setActionHandler('play', () => player.play());
    navigator.mediaSession.setActionHandler('pause', () => player.pause());
    navigator.mediaSession.setActionHandler('stop', () => player.stop());
    navigator.mediaSession.setActionHandler('seekbackward', (details) => {
        const skip = details.seekOffset || 10;
        if (audioRef.value) {
            audioRef.value.currentTime = Math.max(0, audioRef.value.currentTime - skip);
            player.setTime(audioRef.value.currentTime);
        }
    });
    navigator.mediaSession.setActionHandler('seekforward', (details) => {
        const skip = details.seekOffset || 10;
        if (audioRef.value) {
            audioRef.value.currentTime = Math.min(audioRef.value.duration || Infinity, audioRef.value.currentTime + skip);
            player.setTime(audioRef.value.currentTime);
        }
    });
    navigator.mediaSession.setActionHandler('seekto', (details) => {
        if (details.seekTime && audioRef.value) {
            audioRef.value.currentTime = details.seekTime;
            player.setTime(audioRef.value.currentTime);
        }
    });
    navigator.mediaSession.setActionHandler('previoustrack', null);
    navigator.mediaSession.setActionHandler('nexttrack', null);
    navigator.mediaSession.playbackState = player.isPlaying ? 'playing' : 'paused';
}

watch(() => player.currentSound, () => {
    setupMediaSession();
});

watch(() => player.currentSound?.audioUrl, () => {
    stopVisualizer();
    resetVisualizer();
});

watch(() => player.isPlaying, (playing) => {
    if ('mediaSession' in navigator) {
        navigator.mediaSession.playbackState = playing ? 'playing' : 'paused';
    }
});

function seek(event) {
    if (!audioRef.value || !player.duration) return;
    const rect = event.currentTarget.getBoundingClientRect();
    const percent = (event.clientX - rect.left) / rect.width;
    audioRef.value.currentTime = percent * player.duration;
    player.setTime(audioRef.value.currentTime);
}

onUnmounted(() => {
    window.removeEventListener('arborisis:play-sound', onDirectPlayRequest);
    stopVisualizer();
    if (audioContext && audioContext.state !== 'closed') {
        audioContext.close().catch(() => {});
    }
});
</script>

<template>
    <audio
        ref="audioRef"
        :src="player.currentSound?.audioUrl"
        crossorigin="anonymous"
        preload="metadata"
        @play="onNativePlay"
        @playing="onNativePlay"
        @pause="onNativePause"
        @timeupdate="onTimeUpdate"
        @loadedmetadata="onLoadedMetadata"
        @ended="onEnded"
        class="hidden"
    />

    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="player.hasActiveTrack"
            class="fixed bottom-0 left-0 right-0 z-50 border-t border-arbor-mineral/10 bg-arbor-forest/92 backdrop-blur-xl shadow-[0_-18px_70px_rgba(0,0,0,0.34)]"
        >
            <div class="max-w-7xl mx-auto px-4 py-2 sm:px-6 sm:py-3 lg:px-8">
                <div class="flex items-center gap-3 sm:gap-4">
                    <!-- Play/Pause -->
                    <button
                        @click="player.togglePlay"
                        :aria-label="player.isPlaying ? 'Mettre en pause' : 'Lecture'"
                        class="audio-play-button h-11 w-11 min-h-[44px] min-w-[44px] shrink-0 active:scale-95"
                        :class="{ 'is-playing': player.isPlaying }"
                    >
                        <svg v-if="!player.isPlaying" class="w-4 h-4 sm:w-5 sm:h-5 text-arbor-night ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                        <svg v-else class="w-4 h-4 sm:w-5 sm:h-5 text-arbor-night" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                        </svg>
                    </button>

                    <!-- Info -->
                    <div class="min-w-0 flex-1">
                        <Link
                            v-if="player.currentSound?.slug"
                            :href="route('sounds.show', player.currentSound.slug)"
                            class="block truncate font-display text-base font-semibold leading-tight text-arbor-cream transition-colors hover:text-arbor-lichen"
                        >
                            {{ player.currentSound.title }}
                        </Link>
                        <div v-else class="truncate font-display text-base font-semibold leading-tight text-arbor-cream">
                            {{ player.currentSound.title }}
                        </div>
                        <div class="truncate text-[11px] text-arbor-sage sm:text-xs">
                            {{ player.currentSound.userName || 'Anonyme' }}
                        </div>
                    </div>

                    <!-- Real-time audio visualization (desktop only) -->
                    <div class="hidden h-10 w-56 shrink-0 items-center justify-center gap-[3px] overflow-hidden rounded-full border border-arbor-mineral/10 bg-arbor-night/35 px-4 lg:flex xl:w-72">
                        <span
                            v-for="(height, index) in visualizerBars"
                            :key="index"
                            class="block w-[3px] rounded-full transition-[height,background-color,opacity] duration-75"
                            :class="player.isPlaying && canAnalyzeAudio ? 'bg-arbor-firefly' : 'bg-arbor-mineral/12'"
                            :style="{ height: `${height}%`, opacity: player.isPlaying && canAnalyzeAudio ? 0.88 : 0.35 }"
                        />
                    </div>

                    <!-- Progress bar (always visible) -->
                    <div class="flex items-center gap-2 flex-1 max-w-[140px] sm:max-w-xs">
                        <span class="text-[10px] sm:text-xs text-arbor-sage w-7 sm:w-8 text-right hidden sm:block">{{ formatTime(player.currentTime) }}</span>
                        <div
                            class="relative h-1 flex-1 cursor-pointer rounded-full bg-arbor-mineral/12"
                            @click="seek"
                            :aria-label="`Progression : ${formatTime(player.currentTime)} sur ${formatTime(player.duration)}`"
                            role="slider"
                            :aria-valuenow="Math.round(progressPercent)"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                class="absolute left-0 top-0 h-full rounded-full bg-gradient-to-r from-arbor-lichen to-arbor-firefly transition-transform duration-100"
                                :style="`width: ${progressPercent}%`"
                            />
                        </div>
                        <span class="text-[10px] sm:text-xs text-arbor-sage w-7 sm:w-8 hidden sm:block">{{ formatTime(player.duration) }}</span>
                    </div>

                    <!-- Volume (desktop) / Volume toggle (mobile) -->
                    <div class="relative flex items-center justify-center">
                        <button
                            @click="showVolume = !showVolume"
                            :aria-label="player.isMuted ? 'Activer le son' : 'Couper le son'"
                            class="text-arbor-sage hover:text-arbor-cream p-1.5 min-h-[44px] min-w-[44px] flex items-center justify-center"
                        >
                            <svg v-if="player.isMuted || player.volume === 0" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            </svg>
                        </button>
                        <!-- Volume control popup -->
                        <div
                            v-if="showVolume"
                            class="absolute bottom-full left-1/2 mb-3 hidden w-44 -translate-x-1/2 rounded-xl border border-arbor-mineral/10 bg-arbor-forest/95 p-4 shadow-xl shadow-black/35 backdrop-blur-xl sm:block"
                        >
                            <div class="mb-3 flex items-end justify-center gap-[3px] h-12" aria-hidden="true">
                                <span
                                    v-for="(bar, index) in volumeMeterBars"
                                    :key="index"
                                    class="w-[5px] rounded-full transition-all duration-150"
                                    :class="bar.active ? 'bg-arbor-firefly' : 'bg-arbor-mineral/14'"
                                    :style="{ height: `${bar.height}%`, opacity: bar.active ? 0.9 : 0.42 }"
                                />
                            </div>

                            <input
                                type="range"
                                min="0"
                                max="1"
                                step="0.05"
                                :value="effectiveVolume"
                                @input="player.setVolume(parseFloat($event.target.value))"
                                class="w-full accent-arbor-firefly"
                                aria-label="Volume"
                            />
                            <div class="mt-2 flex items-center justify-between font-mono text-[10px] text-arbor-sage">
                                <span>VOL</span>
                                <span>{{ volumePercent }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Close -->
                    <button @click="player.close" aria-label="Fermer le lecteur" class="text-arbor-sage hover:text-arbor-cream p-1.5 min-h-[44px] min-w-[44px] flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>
