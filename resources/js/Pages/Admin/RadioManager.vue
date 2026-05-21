<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    station: Object,
    status: Object,
    nowPlaying: Object,
    history: Array,
    metrics: Object,
    playlist: Array,
    jingles: Array,
    podcasts: Array,
    generatedSchedule: Array,
    schedules: Array,
    podcast_config: Object,
});

const activeTab = ref('programme');
const tabs = [
    { key: 'programme', label: 'Programme' },
    { key: 'podcasts', label: 'Podcasts & Flashs' },
    { key: 'grille', label: 'Grille' },
    { key: 'reglages', label: 'Réglages' },
];

const liveStatus = ref(props.status);
const liveNowPlaying = ref(props.nowPlaying);
const liveHistory = ref(props.history ?? []);
const copied = ref(false);
let interval = null;

const form = useForm({
    public_stream_url: props.station.public_stream_url ?? '',
    icecast_base_url: props.station.icecast_base_url ?? '',
    icecast_mount: props.station.icecast_mount ?? '/arborisis.mp3',
    crossfade_seconds: props.station.crossfade_seconds ?? 4,
    dj_enabled: props.station.dj_enabled ?? true,
    dj_announcement_frequency: props.station.dj_announcement_frequency ?? 3,
    dj_voice_id: props.station.dj_voice_id ?? '',
    discord_voice_channel_id: props.station.discord_voice_channel_id ?? '',
    discord_auto_join: props.station.discord_auto_join ?? true,
});

const streamUrl = computed(() => {
    if (form.public_stream_url) return form.public_stream_url;
    if (!form.icecast_base_url) return null;
    return `${form.icecast_base_url.replace(/\/$/, '')}${form.icecast_mount}`;
});

const statusLabel = computed(() => liveStatus.value?.online ? 'On air' : 'En attente');
const statusClass = computed(() => liveStatus.value?.online ? 'bg-emerald-400' : 'bg-amber-400');

const refreshStatus = async () => {
    try {
        const response = await fetch(route('admin.radio-manager.status'));
        if (!response.ok) return;
        const data = await response.json();
        liveStatus.value = data.status;
        liveNowPlaying.value = data.now_playing;
        liveHistory.value = data.history ?? [];
    } catch {
        // best effort
    }
};

onMounted(() => { interval = setInterval(refreshStatus, 5000); });
onUnmounted(() => { if (interval) clearInterval(interval); });

const submitSettings = () => {
    form.put(route('admin.radio-manager.settings.update'), { preserveScroll: true });
};

const requestReload = () => {
    router.post(route('admin.radio-manager.reload'), {}, { preserveScroll: true, onSuccess: refreshStatus });
};

const copyStream = () => {
    if (!streamUrl.value) return;
    navigator.clipboard.writeText(streamUrl.value);
    copied.value = true;
    setTimeout(() => copied.value = false, 1800);
};

const formatDuration = (seconds) => {
    if (!seconds) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const formatDate = (iso) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
};

const generateContent = (showType) => {
    router.post(route('admin.radio-manager.generate'), { show_type: showType }, { preserveScroll: true });
};

const publishPodcast = (id) => {
    router.post(route('admin.radio-manager.podcasts.publish', id), {}, { preserveScroll: true });
};

const rejectPodcast = (id) => {
    router.post(route('admin.radio-manager.podcasts.reject', id), {}, { preserveScroll: true });
};

const deletePodcast = (id) => {
    if (!confirm('Supprimer ce contenu ?')) return;
    router.delete(route('admin.radio-manager.podcasts.destroy', id), { preserveScroll: true });
};

const statusBadge = (status) => {
    const map = {
        pending: { label: 'En attente', cls: 'bg-amber-500/20 text-amber-300' },
        generating: { label: 'Génération…', cls: 'bg-blue-500/20 text-blue-300 animate-pulse' },
        validating: { label: 'À valider', cls: 'bg-orange-500/20 text-orange-300' },
        published: { label: 'En antenne', cls: 'bg-emerald-500/20 text-emerald-300' },
        failed: { label: 'Échec', cls: 'bg-red-500/20 text-red-400' },
        rejected: { label: 'Rejeté', cls: 'bg-stone-500/20 text-stone-400' },
    };
    return map[status] ?? { label: status, cls: 'bg-stone-500/20 text-stone-400' };
};

const typeBadge = (type) => {
    const map = {
        podcast: { label: 'Podcast', cls: 'bg-violet-500/20 text-violet-300' },
        flash: { label: 'Flash info', cls: 'bg-cyan-500/20 text-cyan-300' },
        emission: { label: 'Émission', cls: 'bg-pink-500/20 text-pink-300' },
    };
    return map[type] ?? { label: type ?? '?', cls: 'bg-stone-500/20 text-stone-400' };
};

const repeatLabel = (repeat) => {
    const map = { none: 'Unique', daily: 'Quotidien', weekly: 'Hebdo', monthly: 'Mensuel' };
    return map[repeat] ?? repeat ?? '—';
};

const trackKindLabel = (track) => {
    if (track.kind === 'podcast') return typeBadge(track.show_type).label;
    if (track.kind === 'jingle') return 'Jingle';
    return 'Son';
};
</script>

<template>
    <Head title="Radio Manager" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-arbor-sage">Station control</p>
                    <h1 class="font-display text-3xl font-semibold text-arbor-cream">Radio Manager</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full border border-arbor-glass-border bg-arbor-glass px-4 py-2 text-sm text-arbor-cream">
                        <span class="h-2.5 w-2.5 rounded-full" :class="statusClass"></span>
                        {{ statusLabel }}
                    </span>
                    <button
                        type="button"
                        @click="requestReload"
                        class="rounded-lg bg-arbor-emerald px-4 py-2 text-sm font-semibold text-arbor-night transition hover:bg-arbor-cream"
                    >
                        Reload
                    </button>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

            <!-- Onglets -->
            <div class="mb-6 flex gap-1 rounded-xl border border-arbor-glass-border bg-arbor-night/60 p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    @click="activeTab = tab.key"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition"
                    :class="activeTab === tab.key
                        ? 'bg-arbor-emerald text-arbor-night'
                        : 'text-arbor-sage hover:text-arbor-cream'"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- ─── ONGLET PROGRAMME ─── -->
            <div v-show="activeTab === 'programme'" class="space-y-6">

                <!-- Métriques -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                    <div class="rounded-lg border border-arbor-glass-border bg-arbor-glass p-4">
                        <p class="text-xs text-arbor-sage">Playlist</p>
                        <p class="mt-1 text-2xl font-semibold text-arbor-cream">{{ metrics.playlist_tracks }}</p>
                    </div>
                    <div class="rounded-lg border border-arbor-glass-border bg-arbor-glass p-4">
                        <p class="text-xs text-arbor-sage">Sons publics</p>
                        <p class="mt-1 text-2xl font-semibold text-arbor-cream">{{ metrics.tracks }}</p>
                    </div>
                    <div class="rounded-lg border border-arbor-glass-border bg-arbor-glass p-4">
                        <p class="text-xs text-arbor-sage">Schedules</p>
                        <p class="mt-1 text-2xl font-semibold text-arbor-cream">{{ metrics.schedules }}</p>
                    </div>
                    <div class="rounded-lg border border-arbor-glass-border bg-arbor-glass p-4">
                        <p class="text-xs text-arbor-sage">Jingles</p>
                        <p class="mt-1 text-2xl font-semibold text-arbor-cream">{{ metrics.jingles }}</p>
                    </div>
                    <div class="rounded-lg border border-arbor-glass-border bg-arbor-glass p-4">
                        <p class="text-xs text-arbor-sage">Voix DJ</p>
                        <p class="mt-1 text-2xl font-semibold text-arbor-cream">{{ metrics.dj_announcements }}</p>
                    </div>
                    <div class="rounded-lg border border-arbor-glass-border bg-arbor-glass p-4">
                        <p class="text-xs text-arbor-sage">Podcasts on air</p>
                        <p class="mt-1 text-2xl font-semibold text-arbor-cream">{{ metrics.podcasts_published }}</p>
                    </div>
                    <div class="rounded-lg border border-arbor-glass-border bg-arbor-glass p-4">
                        <p class="text-xs text-arbor-sage">En génération</p>
                        <p class="mt-1 text-2xl font-semibold text-arbor-cream">{{ metrics.podcasts_pending }}</p>
                    </div>
                </div>

                <!-- Now playing + historique -->
                <div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
                    <section class="overflow-hidden rounded-lg border border-arbor-glass-border bg-arbor-deep">
                        <div class="grid gap-0 md:grid-cols-[220px_1fr]">
                            <div class="relative min-h-56 bg-arbor-night">
                                <img
                                    v-if="liveNowPlaying?.cover"
                                    :src="liveNowPlaying.cover"
                                    alt=""
                                    class="absolute inset-0 h-full w-full object-cover"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-arbor-night via-arbor-night/30 to-transparent"></div>
                                <div class="absolute bottom-5 left-5 right-5">
                                    <p class="mb-1 text-xs uppercase tracking-[0.24em] text-arbor-emerald">Now playing</p>
                                    <h2 class="line-clamp-2 font-display text-2xl font-semibold text-arbor-cream">
                                        {{ liveNowPlaying?.title ?? 'Aucun titre reçu' }}
                                    </h2>
                                    <p class="mt-1 truncate text-sm text-arbor-sage">
                                        {{ liveNowPlaying?.artist ?? 'En attente de metadata' }}
                                    </p>
                                    <p v-if="liveNowPlaying?.duration" class="mt-1 font-mono text-xs text-arbor-sage">
                                        {{ formatDuration(liveNowPlaying.duration) }}
                                    </p>
                                </div>
                            </div>
                            <div class="p-6">
                                <p class="mb-3 text-xs uppercase tracking-[0.2em] text-arbor-sage">Flux public</p>
                                <div class="flex items-center gap-2 rounded-lg border border-arbor-glass-border bg-arbor-night/40 p-3">
                                    <p class="min-w-0 flex-1 truncate font-mono text-sm text-arbor-cream">{{ streamUrl ?? 'Non configuré' }}</p>
                                    <button
                                        type="button"
                                        @click="copyStream"
                                        class="shrink-0 rounded px-2 py-1 text-xs text-arbor-sage transition hover:text-arbor-cream"
                                    >
                                        {{ copied ? 'Copié ✓' : 'Copier' }}
                                    </button>
                                </div>

                                <div v-if="podcast_config.enabled" class="mt-4 rounded-lg border border-arbor-glass-border bg-arbor-night/40 p-3">
                                    <p class="text-xs text-arbor-sage">
                                        Podcasts/flashs insérés tous les
                                        <span class="font-semibold text-arbor-cream">{{ podcast_config.interval_tracks }} sons</span>
                                    </p>
                                </div>
                                <div v-else class="mt-4 rounded-lg border border-amber-500/20 bg-amber-500/5 p-3">
                                    <p class="text-xs text-amber-300">Podcasts désactivés — activer <code class="font-mono">RADIO_PODCAST_ENABLED</code> ou <code class="font-mono">RADIO_HOST_FLASH_ENABLED</code></p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-arbor-glass-border bg-arbor-deep p-6">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-arbor-sage">Historique live</h3>
                        <div class="mt-4 space-y-2">
                            <div
                                v-for="item in liveHistory"
                                :key="`${item.sound_id}-${item.played_at}`"
                                class="flex items-center justify-between gap-4 border-b border-arbor-glass-border pb-2 last:border-0"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-arbor-cream">{{ item.title }}</p>
                                    <p class="truncate text-xs text-arbor-sage">{{ item.artist }}</p>
                                </div>
                                <span class="shrink-0 font-mono text-xs text-arbor-sage">{{ formatDuration(item.duration) }}</span>
                            </div>
                            <p v-if="!liveHistory?.length" class="text-sm text-arbor-sage">Aucun historique pour le moment.</p>
                        </div>
                    </section>
                </div>

                <!-- Playlist aperçu -->
                <section class="rounded-lg border border-arbor-glass-border bg-arbor-deep p-6">
                    <h3 class="font-display text-lg font-semibold text-arbor-cream">Playlist moteur <span class="ml-2 text-sm font-normal text-arbor-sage">(30 premiers sons)</span></h3>
                    <div class="mt-4 max-h-72 overflow-y-auto">
                        <div
                            v-for="track in playlist"
                            :key="`${track.kind}-${track.id}-${track.position}`"
                            class="grid grid-cols-[2rem_1fr_auto] items-center gap-3 border-b border-arbor-glass-border py-2 last:border-0"
                        >
                            <span class="font-mono text-xs text-arbor-sage">{{ track.position + 1 }}</span>
                            <div class="min-w-0">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                                        :class="track.kind === 'podcast' ? typeBadge(track.show_type).cls : 'bg-arbor-glass text-arbor-sage'"
                                    >
                                        {{ trackKindLabel(track) }}
                                    </span>
                                    <p class="truncate text-sm font-medium text-arbor-cream">{{ track.title }}</p>
                                </div>
                                <p class="truncate text-xs text-arbor-sage">
                                    {{ track.artist }}
                                    <span v-if="track.dj_announcement_url" class="text-arbor-emerald"> · DJ</span>
                                </p>
                            </div>
                            <span class="font-mono text-xs text-arbor-sage">{{ formatDuration(track.duration) }}</span>
                        </div>
                        <p v-if="!playlist?.length" class="text-sm text-arbor-sage">Aucun son jouable exporté.</p>
                    </div>
                </section>
            </div>

            <!-- ─── ONGLET PODCASTS & FLASHS ─── -->
            <div v-show="activeTab === 'podcasts'" class="space-y-6">

                <!-- Boutons de génération -->
                <section class="rounded-lg border border-arbor-glass-border bg-arbor-deep p-6">
                    <h3 class="mb-1 font-display text-xl font-semibold text-arbor-cream">Générer un contenu</h3>
                    <p class="mb-5 text-sm text-arbor-sage">Les générations sont asynchrones (queue radio) — le statut apparaîtra dans la liste ci-dessous.</p>
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            @click="generateContent('podcast')"
                            class="rounded-lg border border-violet-500/30 bg-violet-500/10 px-5 py-2.5 text-sm font-semibold text-violet-300 transition hover:bg-violet-500/20"
                        >
                            Générer un podcast
                        </button>
                        <button
                            type="button"
                            @click="generateContent('flash')"
                            class="rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-5 py-2.5 text-sm font-semibold text-cyan-300 transition hover:bg-cyan-500/20"
                        >
                            Flash info
                        </button>
                        <button
                            type="button"
                            @click="generateContent('emission')"
                            class="rounded-lg border border-pink-500/30 bg-pink-500/10 px-5 py-2.5 text-sm font-semibold text-pink-300 transition hover:bg-pink-500/20"
                        >
                            Émission
                        </button>
                    </div>
                </section>

                <!-- Table podcasts -->
                <section class="rounded-lg border border-arbor-glass-border bg-arbor-deep">
                    <div class="border-b border-arbor-glass-border px-6 py-4">
                        <h3 class="font-display text-xl font-semibold text-arbor-cream">Contenus générés</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-arbor-glass-border text-left">
                                    <th class="px-6 py-3 text-xs font-medium uppercase tracking-wide text-arbor-sage">Type</th>
                                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-arbor-sage">Titre</th>
                                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-arbor-sage">Statut</th>
                                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-arbor-sage">Durée</th>
                                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-arbor-sage">Créé</th>
                                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-arbor-sage">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="podcast in podcasts"
                                    :key="podcast.id"
                                    class="border-b border-arbor-glass-border last:border-0 hover:bg-arbor-glass/30"
                                >
                                    <td class="px-6 py-4">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="typeBadge(podcast.show_type).cls">
                                            {{ typeBadge(podcast.show_type).label }}
                                        </span>
                                    </td>
                                    <td class="max-w-xs px-4 py-4">
                                        <p class="truncate font-medium text-arbor-cream">{{ podcast.title }}</p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadge(podcast.status).cls">
                                            {{ statusBadge(podcast.status).label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 font-mono text-xs text-arbor-sage">
                                        {{ formatDuration(podcast.duration) }}
                                    </td>
                                    <td class="px-4 py-4 text-xs text-arbor-sage">
                                        {{ formatDate(podcast.created_at) }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            <button
                                                v-if="podcast.status === 'validating'"
                                                type="button"
                                                @click="publishPodcast(podcast.id)"
                                                class="rounded px-2 py-1 text-xs font-medium text-emerald-300 transition hover:bg-emerald-500/10"
                                            >
                                                Mettre en antenne
                                            </button>
                                            <button
                                                v-if="podcast.status === 'validating'"
                                                type="button"
                                                @click="rejectPodcast(podcast.id)"
                                                class="rounded px-2 py-1 text-xs font-medium text-amber-300 transition hover:bg-amber-500/10"
                                            >
                                                Rejeter
                                            </button>
                                            <button
                                                type="button"
                                                @click="deletePodcast(podcast.id)"
                                                class="rounded px-2 py-1 text-xs font-medium text-red-400 transition hover:bg-red-500/10"
                                            >
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="!podcasts?.length" class="px-6 py-8 text-sm text-arbor-sage">Aucun contenu généré pour le moment.</p>
                    </div>
                </section>
            </div>

            <!-- ─── ONGLET GRILLE ─── -->
            <div v-show="activeTab === 'grille'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-display text-xl font-semibold text-arbor-cream">Grille de programmation</h3>
                        <p class="mt-1 text-sm text-arbor-sage">Schedules actifs et contenus générés insérés dans le flux.</p>
                    </div>
                    <a
                        href="/admin/radio-schedules"
                        class="rounded-lg border border-arbor-glass-border px-4 py-2 text-sm text-arbor-sage transition hover:text-arbor-cream"
                    >
                        Gérer les schedules →
                    </a>
                </div>

                <section class="rounded-lg border border-arbor-glass-border bg-arbor-deep p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h4 class="font-semibold text-arbor-cream">Flashs, émissions et podcasts</h4>
                            <p class="mt-1 text-sm text-arbor-sage">
                                Insertion automatique tous les {{ podcast_config.interval_tracks }} sons, avec rotation des contenus publiés.
                            </p>
                        </div>
                        <span class="rounded border border-arbor-glass-border px-2 py-0.5 text-xs text-arbor-sage">
                            {{ generatedSchedule?.length ?? 0 }} prêt{{ generatedSchedule?.length !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    <div v-if="generatedSchedule?.length" class="mt-4 space-y-2">
                        <div
                            v-for="item in generatedSchedule"
                            :key="`generated-${item.id}`"
                            class="grid gap-2 border-t border-arbor-glass-border pt-3 sm:grid-cols-[auto_1fr_auto]"
                        >
                            <span class="w-fit rounded-full px-2 py-0.5 text-xs font-medium" :class="typeBadge(item.show_type).cls">
                                {{ typeBadge(item.show_type).label }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-arbor-cream">{{ item.title }}</p>
                                <p class="text-xs text-arbor-sage">Publié {{ formatDate(item.published_at) }}</p>
                            </div>
                            <span class="font-mono text-xs text-arbor-sage">{{ formatDuration(item.duration) }}</span>
                        </div>
                    </div>
                    <p v-else class="mt-4 border-t border-arbor-glass-border pt-4 text-sm text-arbor-sage">
                        Aucun contenu généré publié pour l'antenne.
                    </p>
                </section>

                <div v-if="schedules?.length" class="space-y-3">
                    <div
                        v-for="schedule in schedules"
                        :key="schedule.id"
                        class="rounded-lg border bg-arbor-deep p-5 transition"
                        :class="schedule.is_currently_active
                            ? 'border-arbor-emerald/40 bg-arbor-emerald/5'
                            : 'border-arbor-glass-border'"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-semibold text-arbor-cream">{{ schedule.name }}</h4>
                                    <span
                                        v-if="schedule.is_currently_active"
                                        class="rounded-full bg-arbor-emerald/20 px-2 py-0.5 text-xs font-medium text-arbor-emerald"
                                    >
                                        En cours
                                    </span>
                                </div>
                                <p class="mt-1 font-mono text-sm text-arbor-sage">
                                    {{ schedule.starts_at ?? '--:--' }} → {{ schedule.ends_at ?? 'fin de journée' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded border border-arbor-glass-border px-2 py-0.5 text-arbor-sage">
                                    {{ repeatLabel(schedule.repeat) }}
                                </span>
                                <span class="rounded border border-arbor-glass-border px-2 py-0.5 text-arbor-sage">
                                    Priorité {{ schedule.priority }}
                                </span>
                                <span class="rounded border border-arbor-glass-border px-2 py-0.5 text-arbor-sage">
                                    {{ schedule.sounds_count }} son{{ schedule.sounds_count !== 1 ? 's' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-lg border border-arbor-glass-border bg-arbor-deep p-10 text-center">
                    <p class="text-arbor-sage">Aucun schedule actif.</p>
                    <a href="/admin/radio-schedules" class="mt-3 inline-block text-sm text-arbor-emerald hover:underline">
                        Créer un schedule →
                    </a>
                </div>
            </div>

            <!-- ─── ONGLET RÉGLAGES ─── -->
            <div v-show="activeTab === 'reglages'">
                <form class="max-w-2xl rounded-lg border border-arbor-glass-border bg-arbor-deep p-6" @submit.prevent="submitSettings">
                    <h3 class="font-display text-2xl font-semibold text-arbor-cream">Réglages station</h3>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <label class="sm:col-span-2">
                            <span class="text-sm text-arbor-sage">URL publique du flux</span>
                            <input v-model="form.public_stream_url" type="url" class="mt-1 w-full rounded-lg border-arbor-glass-border bg-arbor-night text-arbor-cream" />
                        </label>
                        <label>
                            <span class="text-sm text-arbor-sage">Base Icecast</span>
                            <input v-model="form.icecast_base_url" type="url" class="mt-1 w-full rounded-lg border-arbor-glass-border bg-arbor-night text-arbor-cream" />
                        </label>
                        <label>
                            <span class="text-sm text-arbor-sage">Mount</span>
                            <input v-model="form.icecast_mount" type="text" class="mt-1 w-full rounded-lg border-arbor-glass-border bg-arbor-night text-arbor-cream" />
                        </label>
                        <label>
                            <span class="text-sm text-arbor-sage">Crossfade (secondes)</span>
                            <input v-model="form.crossfade_seconds" type="number" min="0" max="30" class="mt-1 w-full rounded-lg border-arbor-glass-border bg-arbor-night text-arbor-cream" />
                        </label>
                        <label>
                            <span class="text-sm text-arbor-sage">Fréquence annonces DJ</span>
                            <input v-model="form.dj_announcement_frequency" type="number" min="1" max="20" class="mt-1 w-full rounded-lg border-arbor-glass-border bg-arbor-night text-arbor-cream" />
                        </label>
                        <label class="sm:col-span-2">
                            <span class="text-sm text-arbor-sage">ElevenLabs voice ID</span>
                            <input v-model="form.dj_voice_id" type="text" class="mt-1 w-full rounded-lg border-arbor-glass-border bg-arbor-night text-arbor-cream" />
                        </label>
                        <label class="sm:col-span-2">
                            <span class="text-sm text-arbor-sage">Discord voice channel ID</span>
                            <input v-model="form.discord_voice_channel_id" type="text" class="mt-1 w-full rounded-lg border-arbor-glass-border bg-arbor-night text-arbor-cream" />
                        </label>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-arbor-cream">
                            <input v-model="form.dj_enabled" type="checkbox" class="rounded border-arbor-glass-border bg-arbor-night text-arbor-emerald" />
                            DJ automatique
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-arbor-cream">
                            <input v-model="form.discord_auto_join" type="checkbox" class="rounded border-arbor-glass-border bg-arbor-night text-arbor-emerald" />
                            Auto-join Discord
                        </label>
                    </div>
                    <div class="mt-6 flex items-center gap-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-arbor-emerald px-5 py-2.5 text-sm font-semibold text-arbor-night transition hover:bg-arbor-cream disabled:opacity-60"
                        >
                            Enregistrer
                        </button>
                        <span v-if="form.wasSuccessful" class="text-sm text-arbor-emerald">Sauvegardé ✓</span>
                    </div>
                </form>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
