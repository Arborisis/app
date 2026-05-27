<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    sound: {
        type: Object,
        required: true,
    },
    isPlaying: Boolean,
    currentTime: Number,
    duration: Number,
});

const emit = defineEmits(['play', 'pause', 'seek', 'close']);

const isFullscreen = ref(false);
const waveformBars = ref([]);

// Generate waveform bars from audio data (or random if not available)
const generateWaveform = () => {
    const bars = [];
    const count = 60;
    for (let i = 0; i < count; i++) {
        // Use audio features if available, otherwise generate organic-looking bars
        const baseHeight = props.sound?.audio_features?.spectral_centroid 
            ? Math.min(props.sound.audio_features.spectral_centroid / 100, 1) 
            : 0.3 + Math.sin(i * 0.2) * 0.3 + Math.random() * 0.2;
        bars.push(baseHeight);
    }
    return bars;
};

waveformBars.value = generateWaveform();

const formatTime = (seconds) => {
    if (!seconds || seconds < 0) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const progressPercent = computed(() => {
    if (!props.duration || props.duration <= 0) return 0;
    return (props.currentTime / props.duration) * 100;
});

const handleSeek = (event) => {
    const rect = event.currentTarget.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const percent = x / rect.width;
    const newTime = percent * props.duration;
    emit('seek', newTime);
};

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
    if (isFullscreen.value) {
        document.documentElement.requestFullscreen?.();
    } else {
        document.exitFullscreen?.();
    }
};

onMounted(() => {
    // Keyboard shortcuts
    const handleKeydown = (e) => {
        if (e.code === 'Space') {
            e.preventDefault();
            props.isPlaying ? emit('pause') : emit('play');
        }
        if (e.code === 'Escape' && isFullscreen.value) {
            isFullscreen.value = false;
        }
    };
    window.addEventListener('keydown', handleKeydown);

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeydown);
    });
});
</script>

<template>
    <div 
        class="fixed bottom-0 left-0 right-0 z-50 transition-all duration-500"
        :class="[
            isFullscreen ? 'h-screen bg-[#050A08]' : 'bg-[#0B0F0E]/95 backdrop-blur-xl border-t border-[#FFFFFF08]'
        ]"
    >
        <!-- Fullscreen background image -->
        <div 
            v-if="isFullscreen && sound.cover_url" 
            class="absolute inset-0 z-0"
        >
            <img 
                :src="sound.cover_url" 
                :alt="sound.title"
                class="w-full h-full object-cover opacity-20"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-[#050A08] via-[#050A08]/80 to-transparent" />
        </div>

        <!-- Content -->
        <div class="relative z-10 h-full flex flex-col" :class="isFullscreen ? 'justify-center' : ''">
            
            <!-- Close button (fullscreen only) -->
            <button 
                v-if="isFullscreen"
                @click="toggleFullscreen"
                class="absolute top-6 right-6 w-12 h-12 rounded-full bg-[#FFFFFF08] flex items-center justify-center text-[#E8F0EC] hover:bg-[#FFFFFF14] transition-colors"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Main player area -->
            <div 
                class="mx-auto w-full transition-all duration-500"
                :class="isFullscreen ? 'max-w-4xl px-8' : 'max-w-7xl px-4 sm:px-6 lg:px-8 py-4'"
            >
                <!-- Sound info (fullscreen) -->
                <div v-if="isFullscreen" class="text-center mb-12">
                    <span class="text-[#34D399] text-sm font-mono tracking-wider uppercase mb-4 block">
                        {{ sound.category?.name || 'Field Recording' }}
                    </span>
                    <h2 class="text-5xl sm:text-6xl font-semibold text-[#E8F0EC] mb-4">
                        {{ sound.title }}
                    </h2>
                    <p class="text-xl text-[#8FA69E]">
                        {{ sound.user_name || sound.user?.name }} · {{ sound.location_name || sound.location }}
                    </p>
                </div>

                <!-- Waveform visualization -->
                <div 
                    class="relative cursor-pointer group mb-6"
                    :class="isFullscreen ? 'h-64' : 'h-16'"
                    @click="handleSeek"
                >
                    <div class="absolute inset-0 flex items-center gap-[2px]">
                        <div
                            v-for="(height, index) in waveformBars"
                            :key="index"
                            class="flex-1 rounded-full transition-all duration-300"
                            :class="(index / waveformBars.length) * 100 < progressPercent ? 'bg-[#34D399]' : 'bg-[#FFFFFF14]'"
                            :style="{
                                height: `${height * 100}%`,
                                opacity: isPlaying ? 0.8 + Math.sin(Date.now() * 0.01 + index) * 0.2 : 0.6,
                            }"
                        />
                    </div>
                    
                    <!-- Progress indicator -->
                    <div 
                        class="absolute top-0 bottom-0 w-px bg-[#34D399] transition-all duration-100"
                        :style="{ left: `${progressPercent}%` }"
                    >
                        <div class="absolute -top-1 -left-1.5 w-3 h-3 rounded-full bg-[#34D399] shadow-[0_0_10px_rgba(52,211,153,0.5)]" />
                    </div>
                </div>

                <!-- Time and controls -->
                <div class="flex items-center justify-between mb-6">
                    <span class="text-sm text-[#5A6B65] font-mono">
                        {{ formatTime(currentTime) }}
                    </span>
                    
                    <!-- Play/Pause button -->
                    <button 
                        @click="isPlaying ? $emit('pause') : $emit('play')"
                        class="w-16 h-16 rounded-full bg-[#34D399] text-[#050A08] flex items-center justify-center hover:scale-110 transition-transform shadow-[0_0_30px_rgba(52,211,153,0.3)]"
                    >
                        <svg v-if="!isPlaying" class="w-8 h-8 ml-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                        <svg v-else class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                        </svg>
                    </button>
                    
                    <span class="text-sm text-[#5A6B65] font-mono">
                        {{ formatTime(duration) }}
                    </span>
                </div>

                <!-- Compact info (non-fullscreen) -->
                <div v-if="!isFullscreen" class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <img 
                            v-if="sound.cover_url"
                            :src="sound.cover_url" 
                            :alt="sound.title"
                            class="w-12 h-12 rounded-lg object-cover"
                        />
                        <div>
                            <h3 class="text-sm font-medium text-[#E8F0EC]">{{ sound.title }}</h3>
                            <p class="text-xs text-[#8FA69E]">{{ sound.user_name || sound.user?.name }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button 
                            @click="toggleFullscreen"
                            class="w-10 h-10 rounded-lg bg-[#FFFFFF08] flex items-center justify-center text-[#8FA69E] hover:text-[#E8F0EC] hover:bg-[#FFFFFF14] transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </button>
                        <button 
                            @click="$emit('close')"
                            class="w-10 h-10 rounded-lg bg-[#FFFFFF08] flex items-center justify-center text-[#8FA69E] hover:text-[#E8F0EC] hover:bg-[#FFFFFF14] transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Fullscreen metadata -->
                <div v-if="isFullscreen" class="mt-12 grid grid-cols-3 gap-8 text-center">
                    <div>
                        <div class="text-3xl font-semibold text-[#34D399] mb-2">
                            {{ sound.play_count?.toLocaleString('fr-FR') || 0 }}
                        </div>
                        <div class="text-sm text-[#8FA69E]">Écoutes</div>
                    </div>
                    <div>
                        <div class="text-3xl font-semibold text-[#34D399] mb-2">
                            {{ sound.like_count?.toLocaleString('fr-FR') || 0 }}
                        </div>
                        <div class="text-sm text-[#8FA69E]">J'aime</div>
                    </div>
                    <div>
                        <div class="text-3xl font-semibold text-[#34D399] mb-2">
                            {{ formatTime(duration) }}
                        </div>
                        <div class="text-sm text-[#8FA69E]">Durée</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>