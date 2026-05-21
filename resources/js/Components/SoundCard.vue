<script setup>
import { Link } from '@inertiajs/vue3';
import { usePlayerStore } from '@/Stores/player';
import { computed } from 'vue';

const props = defineProps({
    sound: {
        type: Object,
        required: true,
    },
    size: {
        type: String,
        default: 'default', // 'default' | 'compact'
    },
});

const player = usePlayerStore();

const isPlaying = computed(() => {
    return player.isPlaying && player.currentSound?.id === props.sound.id;
});

const traceBars = computed(() => {
    const seed = Number(props.sound.id || props.sound.duration || 7);
    return Array.from({ length: 18 }, (_, i) => {
        const value = 22 + Math.abs(Math.sin(seed * 0.43 + i * 0.74)) * 72;
        return Math.round(value);
    });
});

const formatDuration = (seconds) => {
    if (!seconds) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const playSound = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isPlaying.value) {
        player.pause();
    } else {
        player.play({
            id: props.sound.id,
            slug: props.sound.slug,
            title: props.sound.title,
            userName: props.sound.user_name || props.sound.user?.name,
            audioUrl: props.sound.audio_url,
            coverUrl: props.sound.cover_url,
            duration: props.sound.duration,
        }, { direct: true });
    }
};
</script>

<template>
    <div
        class="sound-archive-card group relative"
        :class="size === 'compact' ? 'rounded-lg' : 'rounded-xl'"
    >
        <!-- Cover Image -->
        <div class="relative aspect-[16/10] overflow-hidden bg-arbor-charcoal">
            <img
                v-if="sound.cover_url"
                :src="sound.cover_url"
                :alt="`Couverture de ${sound.title}`"
                class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                loading="lazy"
            />
            <div v-else class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_50%_30%,rgba(143,230,193,0.12),transparent_45%),linear-gradient(135deg,#07110D,#102018)]">
                <div class="sound-trace h-12 w-12 rounded-full bg-arbor-firefly/10 text-arbor-firefly"></div>
            </div>

            <!-- Gradient overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-arbor-ink via-arbor-ink/20 to-transparent" />
            <div class="absolute inset-x-0 bottom-0 h-16 bg-[linear-gradient(180deg,transparent,rgba(7,17,13,0.96))]" />

            <!-- Play button overlay -->
            <button
                @click="playSound"
                :aria-label="isPlaying ? `Mettre en pause ${sound.title}` : `Lire ${sound.title}`"
                class="absolute inset-0 flex items-center justify-center opacity-0 transition-all duration-300 cursor-pointer group-hover:opacity-100"
            >
                <div class="sound-trace flex h-14 w-14 items-center justify-center rounded-full bg-arbor-lichen text-arbor-ink shadow-lichen transition-all duration-200 hover:scale-105 active:scale-95">
                    <svg v-if="!isPlaying" class="w-6 h-6 text-arbor-night ml-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <svg v-else class="w-6 h-6 text-arbor-night" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                    </svg>
                </div>
            </button>

            <!-- Category badge -->
            <span
                v-if="sound.category"
                class="absolute left-3 top-3 rounded-full border border-arbor-mineral/15 bg-arbor-ink/70 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-arbor-lichen backdrop-blur-sm"
            >
                {{ sound.category.name || sound.category }}
            </span>

            <!-- Duration -->
            <span class="absolute right-3 bottom-3 rounded-full bg-arbor-ink/70 px-2.5 py-1 font-mono text-[11px] text-arbor-mist backdrop-blur-sm">
                {{ formatDuration(sound.duration) }}
            </span>
        </div>

        <!-- Info -->
        <div class="relative p-4">
            <div class="mb-4 flex h-8 items-end gap-[3px]" aria-hidden="true">
                <span
                    v-for="(height, index) in traceBars"
                    :key="index"
                    class="flex-1 rounded-full bg-arbor-mineral/12 transition-colors duration-300 group-hover:bg-arbor-firefly/45"
                    :style="{ height: `${height}%` }"
                />
            </div>
            <Link
                :href="route('sounds.show', sound.slug)"
                class="block truncate font-display text-lg font-semibold leading-tight text-arbor-cream transition-colors hover:text-arbor-lichen"
            >
                {{ sound.title }}
            </Link>
            <div class="mt-2 flex items-center justify-between gap-3">
                <span class="truncate text-xs text-arbor-sage">
                    {{ sound.user_name || sound.user?.name || 'Anonyme' }}
                </span>
                <div class="flex items-center gap-2 text-[11px] text-arbor-sage/70">
                    <span v-if="sound.play_count" class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ sound.play_count }}
                    </span>
                    <span v-if="sound.like_count" class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        {{ sound.like_count }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
