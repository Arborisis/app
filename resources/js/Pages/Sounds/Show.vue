<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import LikeButton from '@/Components/Social/LikeButton.vue';
import FollowButton from '@/Components/Social/FollowButton.vue';
import CommentSection from '@/Components/Social/CommentSection.vue';
import ReportModal from '@/Components/Social/ReportModal.vue';
import WaveSurfer from '@/Components/Audio/WaveSurfer.vue';
import AudioAnalysisPanel from '@/Components/AudioAnalysis/AudioAnalysisPanel.vue';
import { usePlayerStore } from '@/Stores/player';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    sound: Object,
    audioUrl: String,
    coverUrl: String,
    comments: Object,
    isLiked: Boolean,
    isFollowing: Boolean,
    analysis: Object,
});

const page = usePage();
const player = usePlayerStore();
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(props.sound.duration || 0);
const waveSurferRef = ref(null);
const copied = ref(false);
const xenoCanto = ref(null);
const xenoCantoLoading = ref(false);
const xenoCantoError = ref(null);
const xenoCantoId = ref('');

const isOwner = computed(() =>
    page.props.auth.user && page.props.auth.user.id === props.sound.user_id
);

const isCurrentInPlayer = computed(() =>
    player.currentSound?.id === props.sound.id
);

const archiveCode = computed(() => {
    const raw = props.sound.recorded_at || props.sound.created_at || new Date().toISOString();
    const year = new Date(raw).getFullYear();
    const id = String(props.sound.id || '0000').padStart(4, '0');
    return `ARB-${year}-${id}`;
});

const creatorAvatarUrl = computed(() =>
    props.sound.user?.avatar_url
    || props.sound.user?.profile?.avatar_url
    || props.sound.user?.profile?.avatarUrl
    || null
);

const effectiveIsPlaying = computed(() => {
    if (isCurrentInPlayer.value) {
        return player.isPlaying;
    }
    return isPlaying.value;
});

watch(() => player.isPlaying, (playing) => {
    if (isCurrentInPlayer.value) {
        isPlaying.value = playing;
    }
});

const formatTime = (seconds) => {
    if (!seconds || isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const togglePlay = () => {
    if (!isCurrentInPlayer.value) {
        player.play({
            id: props.sound.id,
            title: props.sound.title,
            slug: props.sound.slug,
            audioUrl: props.audioUrl,
            userName: props.sound.user?.name,
            duration: duration.value,
        });
        isPlaying.value = true;
        return;
    }

    player.togglePlay();
};

const onWaveReady = (dur) => {
    duration.value = dur;
};

const onWaveTimeUpdate = (time) => {
    currentTime.value = time;
    if (isCurrentInPlayer.value) {
        player.setTime(time);
    }
};

const onWaveFinish = () => {
    isPlaying.value = false;
    currentTime.value = 0;
    if (isCurrentInPlayer.value) {
        player.stop();
    }
};

const seek = (event) => {
    if (!duration.value) return;
    const rect = event.currentTarget.getBoundingClientRect();
    const percent = (event.clientX - rect.left) / rect.width;
    if (waveSurferRef.value) {
        waveSurferRef.value.seekTo(percent);
    }
    currentTime.value = percent * duration.value;
    if (isCurrentInPlayer.value) {
        player.setTime(currentTime.value);
    }
};

const formatDate = (dateString) => {
    if (!dateString) return null;
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const shareLink = () => {
    navigator.clipboard.writeText(window.location.href);
    copied.value = true;
    setTimeout(() => copied.value = false, 2000);
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const prepareXenoCanto = async () => {
    if (xenoCantoLoading.value) return;
    xenoCantoLoading.value = true;
    xenoCantoError.value = null;

    try {
        const response = await fetch(route('sounds.xeno-canto.prepare', props.sound.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();
        if (!response.ok) {
            xenoCantoError.value = data.message || data.errors?.sound?.[0] || 'Préparation impossible.';
            return;
        }

        xenoCanto.value = data;
    } catch (error) {
        xenoCantoError.value = 'Préparation impossible.';
        console.error(error);
    } finally {
        xenoCantoLoading.value = false;
    }
};

const openXenoCantoUpload = () => {
    if (!xenoCanto.value?.xeno_canto_upload_url) return;
    window.open(xenoCanto.value.xeno_canto_upload_url, '_blank', 'noopener,noreferrer');
};

const markXenoCantoSubmitted = async () => {
    if (!xenoCanto.value || xenoCantoLoading.value) return;
    xenoCantoLoading.value = true;
    xenoCantoError.value = null;

    try {
        const response = await fetch(route('sounds.xeno-canto.submitted', props.sound.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ xeno_canto_id: xenoCantoId.value || null }),
        });

        const data = await response.json();
        if (!response.ok) {
            xenoCantoError.value = data.message || 'Mise à jour impossible.';
            return;
        }

        xenoCanto.value = data;
    } catch (error) {
        xenoCantoError.value = 'Mise à jour impossible.';
        console.error(error);
    } finally {
        xenoCantoLoading.value = false;
    }
};

const copyXenoCantoAudioUrl = () => {
    if (!xenoCanto.value?.audio_download_url) return;
    navigator.clipboard.writeText(xenoCanto.value.audio_download_url);
};

const getMetaIcon = (type) => {
    const icons = {
        category: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        environment: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        equipment: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        date: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        license: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        location: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
    };
    return icons[type] || icons.category;
};
</script>

<template>
    <Head :title="sound.title" />
    <GuestLayout>
        <div class="pt-24 pb-16">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <Breadcrumb
                    :items="[
                        { label: 'Accueil', href: '/' },
                        { label: 'Sons', href: '/sounds' },
                        { label: sound.title },
                    ]"
                />

                <div class="mb-8 trace-frame p-5 sm:p-8">
                    <div class="relative z-10 grid gap-8 lg:grid-cols-[1fr_0.78fr] lg:items-end">
                        <div>
                            <p class="atlas-kicker mb-4">Fiche d'archive vivante · {{ archiveCode }}</p>
                            <h1 class="atlas-heading text-5xl sm:text-6xl">
                                {{ sound.title }}
                            </h1>
                            <p class="mt-5 max-w-2xl text-arbor-sage leading-7">
                                {{ sound.description || 'Une trace sonore publiée dans l’atlas Arborisis, à écouter comme un fragment de territoire.' }}
                            </p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                            <div class="rounded-lg border border-arbor-mineral/10 bg-arbor-ink/40 p-4">
                                <div class="text-xs text-arbor-sage">Créateur</div>
                                <Link
                                    :href="route('creators.show', sound.user?.slug)"
                                    class="mt-1 block font-display text-2xl text-arbor-cream hover:text-arbor-lichen transition-colors"
                                >
                                    {{ sound.user?.name ?? 'Anonyme' }}
                                </Link>
                            </div>
                            <div class="rounded-lg border border-arbor-mineral/10 bg-arbor-ink/40 p-4">
                                <div class="text-xs text-arbor-sage">Lieu public</div>
                                <div class="mt-1 font-display text-2xl text-arbor-cream">
                                    {{ sound.sound_location?.location_name || 'Lieu approximé' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Cover / Visual -->
                        <div class="aspect-[16/9] rounded-xl overflow-hidden bg-arbor-deep relative group border border-arbor-mineral/10">
                            <div
                                v-if="coverUrl"
                                class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105"
                                :style="`background-image: url(${coverUrl})`"
                                loading="lazy"
                            />
                            <div v-else class="absolute inset-0 bg-gradient-to-br from-arbor-moss/20 to-arbor-emerald/10 flex items-center justify-center">
                                <svg class="w-24 h-24 text-arbor-moss/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Audio Player -->
                        <div class="trace-frame p-6 relative">
                            <div class="relative z-10">
                            <div class="flex items-center gap-4 mb-4">
                                <button
                                    @click="togglePlay"
                                    :aria-label="effectiveIsPlaying ? 'Pause' : 'Lire'"
                                    class="audio-play-button w-14 h-14 shrink-0 group"
                                    :class="{ 'is-playing': effectiveIsPlaying }"
                                >
                                    <svg v-if="!effectiveIsPlaying" class="w-6 h-6 text-arbor-night ml-1 transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                    <svg v-else class="w-6 h-6 text-arbor-night" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                                    </svg>
                                </button>
                                <div class="flex-1 min-w-0">
                                    <h2 class="font-display text-xl font-bold text-arbor-cream truncate">
                                        {{ sound.title }}
                                    </h2>
                                    <Link
                                        :href="route('creators.show', sound.user?.slug)"
                                        class="text-sm text-arbor-sage hover:text-arbor-lichen transition-colors"
                                    >
                                        {{ sound.user?.name ?? 'Anonyme' }}
                                    </Link>
                                </div>
                            </div>

                            <!-- WaveSurfer -->
                            <WaveSurfer
                                v-if="audioUrl"
                                ref="waveSurferRef"
                                :audio-url="audioUrl"
                                :is-playing="effectiveIsPlaying"
                                wave-color="#1E293B"
                                progress-color="#D7B46A"
                                cursor-color="#8FA68E"
                                :height="100"
                                @ready="onWaveReady"
                                @timeupdate="onWaveTimeUpdate"
                                @finish="onWaveFinish"
                                @play="isPlaying = true"
                                @pause="isPlaying = false"
                            />

                            <!-- Progress bar fallback / time display -->
                            <div class="flex items-center gap-3 mt-4">
                                <span class="text-xs text-arbor-sage w-10 text-right font-mono">{{ formatTime(currentTime) }}</span>
                                <div
                                    class="flex-1 h-1.5 bg-arbor-glass rounded-full cursor-pointer relative overflow-hidden group"
                                    @click="seek"
                                >
                                    <div
                                    class="absolute top-0 left-0 h-full bg-gradient-to-r from-arbor-lichen to-arbor-firefly rounded-full transition-transform duration-100"
                                        :style="`width: ${duration ? (currentTime / duration) * 100 : 0}%`"
                                    />
                                    <div
                                        class="absolute top-1/2 -translate-y-1/2 w-3 h-3 bg-arbor-cream rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-opacity"
                                        :style="`left: calc(${duration ? (currentTime / duration) * 100 : 0}% - 6px)`"
                                    />
                                </div>
                                <span class="text-xs text-arbor-sage w-10 font-mono">{{ formatTime(duration) }}</span>
                            </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3">
                            <LikeButton
                                :sound-id="sound.id"
                                :initial-liked="isLiked"
                                :initial-count="sound.like_count"
                            />
                            <button
                                @click="shareLink"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-arbor-glass border border-arbor-glass-border text-arbor-sage text-sm hover:text-arbor-cream hover:bg-white/10 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                                <span v-if="copied">Lien copié !</span>
                                <span v-else>Partager</span>
                            </button>
                            <ReportModal
                                reportable-type="sound"
                                :reportable-id="sound.id"
                            />
                        </div>

                        <!-- Description -->
                        <div>
                            <p class="atlas-kicker mb-3">Contexte de terrain</p>
                            <p class="text-arbor-sage leading-relaxed whitespace-pre-line">
                                {{ sound.description || 'Aucune description.' }}
                            </p>
                        </div>

                        <!-- Metadata -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div v-if="sound.category" class="field-stat hover-lift">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-arbor-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="getMetaIcon('category')" />
                                    </svg>
                                    <div class="text-xs text-arbor-sage">Catégorie</div>
                                </div>
                                <div class="text-sm font-medium text-arbor-cream">{{ sound.category.name }}</div>
                            </div>
                            <div v-if="sound.environment" class="field-stat hover-lift">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-arbor-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="getMetaIcon('environment')" />
                                    </svg>
                                    <div class="text-xs text-arbor-sage">Environnement</div>
                                </div>
                                <div class="text-sm font-medium text-arbor-cream">{{ sound.environment.name }}</div>
                            </div>
                            <div v-if="sound.equipment" class="field-stat hover-lift">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-arbor-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="getMetaIcon('equipment')" />
                                    </svg>
                                    <div class="text-xs text-arbor-sage">Matériel</div>
                                </div>
                                <div class="text-sm font-medium text-arbor-cream">{{ sound.equipment }}</div>
                            </div>
                            <div v-if="sound.recorded_at" class="field-stat hover-lift">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-arbor-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="getMetaIcon('date')" />
                                    </svg>
                                    <div class="text-xs text-arbor-sage">Date d'enregistrement</div>
                                </div>
                                <div class="text-sm font-medium text-arbor-cream">{{ formatDate(sound.recorded_at) }}</div>
                            </div>
                            <div class="field-stat hover-lift">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-arbor-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="getMetaIcon('license')" />
                                    </svg>
                                    <div class="text-xs text-arbor-sage">Licence</div>
                                </div>
                                <div class="text-sm font-medium text-arbor-cream">{{ sound.license }}</div>
                            </div>
                            <div v-if="sound.sound_location?.location_name" class="field-stat hover-lift">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-arbor-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="getMetaIcon('location')" />
                                    </svg>
                                    <div class="text-xs text-arbor-sage">Lieu</div>
                                </div>
                                <div class="text-sm font-medium text-arbor-cream">{{ sound.sound_location.location_name }}</div>
                            </div>
                        </div>

                        <!-- Audio Analysis -->
                        <AudioAnalysisPanel
                            :analysis="analysis"
                            :sound="sound"
                            :is-owner="isOwner"
                        />

                        <!-- xeno-canto -->
                        <div v-if="isOwner" class="glass-card p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h2 class="font-semibold text-arbor-cream">xeno-canto</h2>
                                    <p class="text-sm text-arbor-sage mt-1">
                                        Paquet de soumission avec fichier audio temporaire, espèce probable et coordonnées publiques.
                                    </p>
                                </div>
                                <button
                                    @click="prepareXenoCanto"
                                    :disabled="xenoCantoLoading"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-arbor-emerald text-arbor-night text-sm font-semibold hover:bg-arbor-emerald-dark disabled:opacity-50 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v12m0-12l-4 4m4-4l4 4M4 20h16" />
                                    </svg>
                                    Préparer
                                </button>
                            </div>

                            <p v-if="xenoCantoError" class="mt-4 text-sm text-red-300">
                                {{ xenoCantoError }}
                            </p>

                            <div v-if="xenoCanto" class="mt-5 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-lg bg-arbor-glass p-3">
                                        <div class="text-xs text-arbor-sage mb-1">Espèce principale</div>
                                        <div class="text-arbor-cream">
                                            {{ xenoCanto.metadata.species.common_name || 'À confirmer' }}
                                        </div>
                                        <div v-if="xenoCanto.metadata.species.scientific_name" class="text-xs text-arbor-sage italic">
                                            {{ xenoCanto.metadata.species.scientific_name }}
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-arbor-glass p-3">
                                        <div class="text-xs text-arbor-sage mb-1">Lieu exporté</div>
                                        <div class="text-arbor-cream">
                                            {{ xenoCanto.metadata.location.name || 'Lieu non renseigné' }}
                                        </div>
                                        <div v-if="xenoCanto.metadata.location.latitude" class="text-xs text-arbor-sage">
                                            {{ xenoCanto.metadata.location.latitude }}, {{ xenoCanto.metadata.location.longitude }}
                                        </div>
                                    </div>
                                </div>

                                <div v-if="xenoCanto.metadata.license_warning" class="rounded-lg border border-amber-400/30 bg-amber-400/10 p-3 text-sm text-amber-100">
                                    {{ xenoCanto.metadata.license_warning }}
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3">
                                    <button
                                        @click="copyXenoCantoAudioUrl"
                                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-arbor-glass border border-arbor-glass-border text-arbor-sage text-sm hover:text-arbor-cream transition-colors"
                                    >
                                        Copier l'URL audio
                                    </button>
                                    <button
                                        @click="openXenoCantoUpload"
                                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-arbor-glass border border-arbor-glass-border text-arbor-sage text-sm hover:text-arbor-cream transition-colors"
                                    >
                                        Ouvrir xeno-canto
                                    </button>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3">
                                    <input
                                        v-model="xenoCantoId"
                                        type="text"
                                        inputmode="numeric"
                                        placeholder="ID xeno-canto"
                                        class="flex-1 rounded-lg border border-arbor-glass-border bg-arbor-night/60 px-3 py-2 text-sm text-arbor-cream placeholder:text-arbor-sage/60 focus:border-arbor-emerald focus:ring-arbor-emerald"
                                    >
                                    <button
                                        @click="markXenoCantoSubmitted"
                                        :disabled="xenoCantoLoading"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-arbor-moss text-white text-sm font-semibold hover:bg-arbor-moss-light disabled:opacity-50 transition-colors"
                                    >
                                        Marquer soumis
                                    </button>
                                </div>

                                <a
                                    v-if="xenoCanto.xeno_canto_url"
                                    :href="xenoCanto.xeno_canto_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex text-sm text-arbor-emerald hover:text-arbor-cream transition-colors"
                                >
                                    Voir la fiche xeno-canto
                                </a>
                            </div>
                        </div>

                        <!-- Comments -->
                        <CommentSection
                            :sound-id="sound.id"
                            :comments="comments"
                        />
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Creator Card -->
                        <div class="glass-card p-6 hover-lift">
                            <Link :href="route('creators.show', sound.user?.slug)" class="flex items-center gap-3 mb-4 group">
                                <div class="w-12 h-12 rounded-full bg-arbor-moss/30 flex items-center justify-center overflow-hidden ring-2 ring-arbor-glass-border/50 group-hover:ring-arbor-emerald/30 transition-colors">
                                    <img
                                        v-if="creatorAvatarUrl"
                                        :src="creatorAvatarUrl"
                                        :alt="`Avatar de ${sound.user?.name ?? 'Anonyme'}`"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                    <span v-else class="text-lg font-display font-bold text-arbor-emerald">
                                        {{ sound.user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
                                    </span>
                                </div>
                                <div>
                                    <div class="font-semibold text-arbor-cream group-hover:text-arbor-emerald transition-colors">{{ sound.user?.name ?? 'Anonyme' }}</div>
                                    <div class="text-xs text-arbor-sage">Créateur</div>
                                </div>
                            </Link>
                            <FollowButton
                                v-if="$page.props.auth.user && $page.props.auth.user.id !== sound.user_id"
                                :user-id="sound.user_id"
                                :initial-following="isFollowing"
                                size="md"
                                class="w-full"
                            />
                        </div>

                        <!-- Tags -->
                        <div v-if="sound.tags?.length > 0" class="glass-card p-6">
                            <h3 class="font-semibold text-arbor-cream mb-3 text-sm">Tags</h3>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="tag in sound.tags"
                                    :key="tag.id"
                                    class="px-3 py-1 rounded-lg bg-arbor-glass text-arbor-sage text-xs hover:bg-arbor-emerald/10 hover:text-arbor-emerald transition-colors cursor-default"
                                >
                                    {{ tag.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="glass-card p-6">
                            <h3 class="font-semibold text-arbor-cream mb-4 text-sm">Statistiques</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm group">
                                    <span class="text-arbor-sage">Écoutes</span>
                                    <span class="text-arbor-cream font-medium group-hover:text-arbor-emerald transition-colors">{{ sound.play_count }}</span>
                                </div>
                                <div class="flex justify-between text-sm group">
                                    <span class="text-arbor-sage">J'aime</span>
                                    <span class="text-arbor-cream font-medium group-hover:text-rose-400 transition-colors">{{ sound.like_count }}</span>
                                </div>
                                <div class="flex justify-between text-sm group">
                                    <span class="text-arbor-sage">Commentaires</span>
                                    <span class="text-arbor-cream font-medium group-hover:text-sky-400 transition-colors">{{ sound.comment_count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
