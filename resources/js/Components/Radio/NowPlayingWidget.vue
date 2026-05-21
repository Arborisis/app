<script setup>
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { usePlayerStore } from '@/Stores/player';

const player = usePlayerStore();
const audioRef = ref(null);

const visible = computed(() => player.currentMode === 'radio' && player.radioMetadata && !route().current('radio.index'));
const streamUrl = computed(() => player.radioStreamUrl || '/radio/stream');

watch(() => player.isPlaying, (playing) => {
    if (!audioRef.value || player.currentMode !== 'radio') return;
    if (playing) audioRef.value.play().catch(() => player.pause());
    else audioRef.value.pause();
});

watch(() => player.volume, (volume) => {
    if (audioRef.value) audioRef.value.volume = player.isMuted ? 0 : volume;
});

watch(() => player.isMuted, (muted) => {
    if (audioRef.value) audioRef.value.volume = muted ? 0 : player.volume;
});

async function toggle() {
    player.connectRadio(player.radioMetadata, streamUrl.value);
    if (!audioRef.value) return;
    audioRef.value.volume = player.isMuted ? 0 : player.volume;

    if (player.isPlaying) {
        player.pause();
        return;
    }

    try {
        await audioRef.value.play();
        player.resumeRadio();
    } catch {
        player.pause();
    }
}
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div v-if="visible" class="fixed bottom-5 right-5 z-tooltip w-[min(360px,calc(100vw-2.5rem))] rounded-lg border border-arbor-glass-border bg-arbor-deep/95 p-3 shadow-2xl shadow-black/30 backdrop-blur">
            <audio ref="audioRef" :src="streamUrl" crossorigin="anonymous" class="hidden" />
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-arbor-emerald text-arbor-night transition hover:bg-arbor-emerald-dark"
                    :aria-label="player.isPlaying ? 'Mettre la radio en pause' : 'Lire la radio'"
                    @click="toggle"
                >
                    <svg v-if="!player.isPlaying" class="h-5 w-5 translate-x-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
                    <svg v-else class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" /></svg>
                </button>
                <div class="min-w-0 flex-1">
                    <Link href="/radio" class="block truncate text-sm font-semibold text-arbor-cream hover:text-arbor-emerald">
                        {{ player.radioMetadata?.title || 'Arborisis Radio' }}
                    </Link>
                    <p class="truncate text-xs text-arbor-sage">{{ player.radioMetadata?.artist || 'En direct' }}</p>
                </div>
                <button type="button" class="min-w-[44px] min-h-[44px] flex items-center justify-center text-arbor-sage hover:text-arbor-cream" aria-label="Fermer le widget radio" @click="player.close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    </transition>
</template>
