<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';

const props = defineProps({
    points: Object,
    filters: Object,
    catalog: Object,
});

const search = ref(props.filters?.q ?? '');
const habitat = ref(props.filters?.habitat ?? '');
const sort = ref(props.filters?.sort ?? 'recent');
const viewMode = ref(props.filters?.view ?? 'map'); // 'map' | 'aggregator'
const mapContainer = ref(null);
const activeMapPoint = ref(null);
let map = null;
let markerClusterGroup = null;
let heatmapLayer = null;
const showHeatmap = ref(false);
const timelineData = ref([]);
const loadingTimeline = ref(false);

const baseHabitats = [
    { value: '', label: 'Tous', longLabel: 'Tous les habitats', emoji: '◎' },
    { value: 'forest', label: 'Forêt', longLabel: 'Forêt', emoji: '🌲' },
    { value: 'wetland', label: 'Zone humide', longLabel: 'Zone humide', emoji: '💧' },
    { value: 'river', label: 'Rivière', longLabel: 'Rivière', emoji: '🌊' },
    { value: 'meadow', label: 'Prairie', longLabel: 'Prairie', emoji: '🌾' },
    { value: 'ocean', label: 'Océan', longLabel: 'Océan', emoji: '🌊' },
    { value: 'mountain', label: 'Montagne', longLabel: 'Montagne', emoji: '⛰️' },
    { value: 'urban_nature', label: 'Urbain', longLabel: 'Nature urbaine', emoji: '⌂' },
    { value: 'desert', label: 'Désert', longLabel: 'Désert', emoji: '◌' },
];

const sortOptions = [
    { value: 'recent', label: 'Dernière écoute' },
    { value: 'active', label: 'Plus documentés' },
    { value: 'species', label: 'Biodiversité' },
    { value: 'oldest', label: 'Archives anciennes' },
    { value: 'alpha', label: 'A à Z' },
];

const allPoints = computed(() => props.points?.data ?? []);
const hasFilters = computed(() => Boolean(search.value || habitat.value || sort.value !== 'recent' || viewMode.value !== 'map'));

const habitatCounts = computed(() => {
    const counts = new Map();
    for (const row of props.catalog?.habitats ?? []) {
        counts.set(row.value, row);
    }
    return counts;
});

const habitatOptions = computed(() => baseHabitats.map((option) => ({
    ...option,
    points_count: option.value === ''
        ? props.catalog?.total_points ?? 0
        : habitatCounts.value.get(option.value)?.points_count ?? 0,
    recordings_count: option.value === ''
        ? props.catalog?.total_recordings ?? 0
        : habitatCounts.value.get(option.value)?.recordings_count ?? 0,
})));

const selectedHabitat = computed(() => habitatOptions.value.find((option) => option.value === habitat.value) ?? habitatOptions.value[0]);

const queryParams = computed(() => {
    const params = {};
    if (search.value.trim() !== '') params.q = search.value.trim();
    if (habitat.value !== '') params.habitat = habitat.value;
    if (sort.value !== 'recent') params.sort = sort.value;
    if (viewMode.value !== 'map') params.view = viewMode.value;
    return params;
});

const habitatQuery = (value) => {
    const params = { ...queryParams.value };
    if (value) {
        params.habitat = value;
    } else {
        delete params.habitat;
    }
    return params;
};

const habitatEmoji = (type) => habitatOptions.value.find((option) => option.value === type)?.emoji ?? '📍';
const habitatLabel = (type) => habitatOptions.value.find((option) => option.value === type)?.longLabel ?? 'Habitat non classé';

const formatNumber = (value) => new Intl.NumberFormat('fr-FR').format(value ?? 0);

const formatDate = (value) => {
    if (!value) return 'date inconnue';
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

const formatYear = (value) => value ? new Date(value).getFullYear() : 'nouveau';

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const submitFilters = () => {
    router.get(route('listening-points.index'), queryParams.value, {
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    search.value = '';
    habitat.value = '';
    sort.value = 'recent';
    viewMode.value = 'map';
    router.get(route('listening-points.index'), {}, {
        preserveScroll: true,
        replace: true,
    });
};

const setViewMode = (mode) => {
    viewMode.value = mode;
    router.get(route('listening-points.index'), queryParams.value, {
        preserveScroll: true,
        replace: true,
    });
};

/* ─────────────── Timeline API ─────────────── */
const fetchTimeline = async () => {
    if (timelineData.value.length > 0) return;
    loadingTimeline.value = true;
    try {
        const res = await fetch(route('listening-points.timeline'));
        const json = await res.json();
        timelineData.value = json.data ?? [];
    } catch (e) {
        timelineData.value = [];
    } finally {
        loadingTimeline.value = false;
    }
};

/* ─────────────── Heatmap API ─────────────── */
const fetchHeatmap = async () => {
    try {
        const res = await fetch(route('listening-points.heatmap'));
        const json = await res.json();
        return json.data ?? [];
    } catch (e) {
        return [];
    }
};

const toggleHeatmap = async () => {
    showHeatmap.value = !showHeatmap.value;
    if (!map) return;

    if (showHeatmap.value) {
        const data = await fetchHeatmap();
        if (heatmapLayer) map.removeLayer(heatmapLayer);
        heatmapLayer = L.layerGroup();
        data.forEach((cell) => {
            const intensity = Math.min(1, Math.log10(cell.count + 1) / 2);
            const radius = 8000 + (cell.count * 2000);
            const circle = L.circle([cell.lat, cell.lng], {
                radius,
                fillColor: `rgba(52, 211, 153, ${0.15 + intensity * 0.5})`,
                color: `rgba(52, 211, 153, ${0.3 + intensity * 0.4})`,
                weight: 1,
                fillOpacity: 0.6,
            });
            circle.bindPopup(`<strong>${cell.count}</strong> enregistrements`);
            heatmapLayer.addLayer(circle);
        });
        heatmapLayer.addTo(map);
        if (markerClusterGroup) map.removeLayer(markerClusterGroup);
    } else {
        if (heatmapLayer) {
            map.removeLayer(heatmapLayer);
            heatmapLayer = null;
        }
        if (markerClusterGroup) map.addLayer(markerClusterGroup);
    }
};

/* ─────────────── Carte ─────────────── */
const initMap = () => {
    if (!mapContainer.value || allPoints.value.length === 0) return;

    map = L.map(mapContainer.value, {
        zoomControl: false,
        attributionControl: false,
        scrollWheelZoom: window.innerWidth >= 768,
    });

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap & CARTO',
        subdomains: 'abcd',
        maxZoom: 19,
    }).addTo(map);

    markerClusterGroup = L.markerClusterGroup({
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        spiderfyOnMaxZoom: true,
        removeOutsideVisibleBounds: true,
        animate: true,
        animateAddingMarkers: true,
        disableClusteringAtZoom: 16,
        maxClusterRadius: 60,
        iconCreateFunction: (cluster) => {
            const count = cluster.getChildCount();
            const size = count < 10 ? 'small' : count < 50 ? 'medium' : 'large';
            return L.divIcon({
                className: 'lp-cluster-icon',
                html: `<div class="lp-cluster lp-cluster-${size}"><span>${count}</span></div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
            });
        },
    });

    allPoints.value.forEach((point) => {
        const lat = parseFloat(point.public_latitude);
        const lng = parseFloat(point.public_longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

        const recordings = Number(point.recordings_count ?? 0);
        const intensity = recordings >= 20 ? 'high' : recordings >= 6 ? 'medium' : 'low';
        const icon = L.divIcon({
            className: 'listening-point-marker',
            html: `<div class="lp-marker lp-marker-${intensity}"><span class="lp-marker-emoji">${habitatEmoji(point.habitat_type)}</span></div>`,
            iconSize: [38, 38],
            iconAnchor: [19, 19],
        });

        const marker = L.marker([lat, lng], { icon })
            .bindPopup(`
                <div class="lp-popup-card">
                    <p class="lp-popup-kicker">${escapeHtml(habitatLabel(point.habitat_type))}</p>
                    <a href="/listening-points/${escapeHtml(point.slug)}" class="lp-popup-title">${escapeHtml(point.title)}</a>
                    <p class="lp-popup-meta">${formatNumber(recordings)} enregistrement${recordings > 1 ? 's' : ''} · précision publique ${formatNumber(point.public_accuracy_meters ?? 0)} m</p>
                </div>
            `, {
                className: 'lp-popup',
                closeButton: false,
                offset: [0, -10],
            });

        marker.on('click', () => {
            activeMapPoint.value = point;
            marker.openPopup();
        });

        markerClusterGroup.addLayer(marker);
    });

    map.addLayer(markerClusterGroup);

    if (markerClusterGroup.getLayers().length > 0) {
        map.fitBounds(markerClusterGroup.getBounds().pad(0.18), { animate: true });
    } else {
        map.setView([46.603354, 1.888334], 5);
    }
};

const destroyMap = () => {
    if (map) {
        map.remove();
        map = null;
        markerClusterGroup = null;
        heatmapLayer = null;
    }
};

watch(viewMode, async (mode) => {
    if (mode === 'map') {
        showHeatmap.value = false;
        await nextTick();
        initMap();
    } else {
        destroyMap();
        fetchTimeline();
    }
});

onMounted(() => {
    if (viewMode.value === 'map') {
        initMap();
    } else {
        fetchTimeline();
    }
});
</script>

<template>
    <Head title="Points d'écoute" />
    <GuestLayout>
        <main class="min-h-screen bg-arbor-night text-arbor-cream">
            <!-- Hero -->
            <section class="relative overflow-hidden border-b border-arbor-glass-border/60">
                <div class="absolute inset-0 lp-hero-texture pointer-events-none"></div>
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-8">
                    <div class="grid lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)] gap-8 items-end">
                        <div>
                            <div class="flex flex-wrap items-center gap-3 mb-5">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-arbor-emerald shadow-[0_0_20px_rgba(52,211,153,0.8)]"></span>
                                <span class="text-xs font-semibold text-arbor-emerald uppercase tracking-widest">Atlas acoustique public</span>
                                <span class="text-xs text-arbor-sage">coordonnées publiques approximées</span>
                            </div>
                            <h1 class="font-display text-4xl md:text-6xl font-bold leading-[0.95]">
                                Points d'écoute
                            </h1>
                            <p class="mt-5 max-w-3xl text-base md:text-lg text-arbor-sage leading-relaxed">
                                Une carte vivante des lieux suivis dans le temps: habitats, séries d'enregistrements, signaux d'espèces et archives de modification pour comprendre l'évolution sonore des milieux.
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="lp-stat">
                                <span>{{ formatNumber(catalog?.total_points) }}</span>
                                <p>points</p>
                            </div>
                            <div class="lp-stat">
                                <span>{{ formatNumber(catalog?.total_recordings) }}</span>
                                <p>sons</p>
                            </div>
                            <div class="lp-stat">
                                <span>{{ formatNumber(catalog?.total_species_signals) }}</span>
                                <p>signaux</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Controls -->
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 relative z-10">
                <!-- View Toggle -->
                <div class="flex flex-wrap items-center gap-3 mb-4 relative">
                    <div class="inline-flex bg-arbor-glass/30 rounded-lg p-1 border border-arbor-glass-border/40">
                        <button
                            type="button"
                            @click="setViewMode('map')"
                            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                            :class="viewMode === 'map' ? 'bg-arbor-emerald/20 text-arbor-emerald' : 'text-arbor-sage hover:text-arbor-cream'"
                        >
                            ◎ Carte
                        </button>
                        <button
                            type="button"
                            @click="setViewMode('aggregator')"
                            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                            :class="viewMode === 'aggregator' ? 'bg-arbor-emerald/20 text-arbor-emerald' : 'text-arbor-sage hover:text-arbor-cream'"
                        >
                            ▣ Agrégateur
                        </button>
                    </div>

                    <button
                        v-if="viewMode === 'map'"
                        type="button"
                        @click="toggleHeatmap"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors"
                        :class="showHeatmap
                            ? 'bg-arbor-amber/20 text-arbor-amber border-arbor-amber/40'
                            : 'border-arbor-glass-border/40 text-arbor-sage hover:text-arbor-cream hover:bg-arbor-glass/30'"
                    >
                        🔥 Heatmap
                    </button>
                </div>

                <form class="lp-filter-panel" @submit.prevent="submitFilters">
                    <div class="lg:col-span-2">
                        <label for="listening-point-search" class="sr-only">Rechercher un point d'écoute</label>
                        <input
                            id="listening-point-search"
                            v-model="search"
                            type="search"
                            placeholder="Rechercher un lieu, une région, un pays, une note..."
                            class="lp-input"
                        />
                    </div>

                    <div>
                        <label for="listening-point-sort" class="sr-only">Trier</label>
                        <select id="listening-point-sort" v-model="sort" class="lp-input">
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <button type="submit" class="lp-filter-button">
                        Filtrer
                    </button>

                    <button v-if="hasFilters" type="button" class="lp-reset-button" @click="resetFilters">
                        Réinitialiser
                    </button>
                </form>

                <div class="mt-4 flex gap-2 overflow-x-auto pb-2">
                    <Link
                        v-for="option in habitatOptions"
                        :key="option.value || 'all'"
                        :href="route('listening-points.index', habitatQuery(option.value))"
                        class="lp-habitat-chip"
                        :class="{ 'lp-habitat-chip-active': option.value === habitat }"
                        preserve-scroll
                    >
                        <span>{{ option.emoji }}</span>
                        <strong>{{ option.label }}</strong>
                        <small>{{ formatNumber(option.points_count) }}</small>
                    </Link>
                </div>
            </section>

            <!-- ═════ MODE CARTE ═════ -->
            <template v-if="viewMode === 'map'">
                <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
                    <div class="lp-map-shell">
                        <div v-if="allPoints.length > 0" ref="mapContainer" class="w-full h-[460px] md:h-[560px]"></div>
                        <div v-else class="h-[360px] flex items-center justify-center text-center px-6">
                            <div>
                                <p class="font-display text-2xl text-arbor-cream">Aucun point ne correspond à cette recherche.</p>
                                <p class="mt-2 text-sm text-arbor-sage">Essayez un habitat plus large ou retirez quelques termes.</p>
                            </div>
                        </div>

                        <div class="lp-map-panel">
                            <p class="text-xs uppercase tracking-widest text-arbor-emerald">{{ selectedHabitat.longLabel }}</p>
                            <p class="mt-2 font-display text-2xl text-arbor-cream">{{ formatNumber(points.total) }} point{{ points.total > 1 ? 's' : '' }}</p>
                            <p class="mt-2 text-sm text-arbor-sage">
                                Les marqueurs sont regroupés automatiquement par zone. Zoomez pour explorer.
                            </p>

                            <div v-if="activeMapPoint" class="mt-5 border-t border-arbor-glass-border pt-4">
                                <p class="text-xs text-arbor-sage">Sélection carte</p>
                                <Link :href="route('listening-points.show', activeMapPoint.slug)" class="mt-1 block font-semibold text-arbor-cream hover:text-arbor-emerald">
                                    {{ activeMapPoint.title }}
                                </Link>
                                <p class="mt-1 text-xs text-arbor-sage">
                                    {{ formatNumber(activeMapPoint.recordings_count) }} sons · précision {{ formatNumber(activeMapPoint.public_accuracy_meters) }} m
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
                    <div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-arbor-emerald">Catalogue</p>
                            <h2 class="font-display text-3xl text-arbor-cream">Stations disponibles</h2>
                        </div>
                        <p class="text-sm text-arbor-sage">
                            Page {{ points.current_page }} / {{ points.last_page }} · {{ formatNumber(points.total) }} résultat{{ points.total > 1 ? 's' : '' }}
                        </p>
                    </div>

                    <div v-if="allPoints.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <Link
                            v-for="point in allPoints"
                            :key="point.id"
                            :href="route('listening-points.show', point.slug)"
                            class="lp-point-card group"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 text-xs text-arbor-sage uppercase tracking-wider">
                                        <span class="text-lg leading-none">{{ habitatEmoji(point.habitat_type) }}</span>
                                        <span>{{ habitatLabel(point.habitat_type) }}</span>
                                    </div>
                                    <h3 class="mt-3 font-display text-2xl font-semibold text-arbor-cream group-hover:text-arbor-emerald transition-colors">
                                        {{ point.title }}
                                    </h3>
                                </div>
                                <span class="lp-accuracy">{{ formatNumber(point.public_accuracy_meters) }} m</span>
                            </div>

                            <p class="mt-3 text-sm text-arbor-sage line-clamp-3 min-h-[3.9rem]">
                                {{ point.description || 'Point public documenté par les enregistrements de la communauté Arborisis.' }}
                            </p>

                            <div class="mt-5 grid grid-cols-3 gap-2">
                                <div class="lp-card-metric">
                                    <span>{{ formatNumber(point.recordings_count) }}</span>
                                    <p>sons</p>
                                </div>
                                <div class="lp-card-metric">
                                    <span>{{ formatNumber(point.species_detected_count) }}</span>
                                    <p>espèces</p>
                                </div>
                                <div class="lp-card-metric">
                                    <span>{{ formatNumber(point.versions_count) }}</span>
                                    <p>versions</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2 min-h-[1.75rem]">
                                <span
                                    v-for="tag in point.dominant_tags"
                                    :key="tag"
                                    class="lp-tag"
                                >
                                    {{ tag }}
                                </span>
                            </div>

                            <div class="mt-5 flex items-center justify-between gap-3 text-xs text-arbor-sage/80">
                                <span>{{ point.admin_level_1 || point.country_code || 'zone publique' }}</span>
                                <span>depuis {{ formatYear(point.first_recorded_at) }} · maj {{ formatDate(point.last_recorded_at) }}</span>
                            </div>

                            <div class="mt-4 flex items-center gap-2 border-t border-arbor-glass-border pt-4">
                                <div class="flex -space-x-2">
                                    <div
                                        v-for="contributor in point.contributors"
                                        :key="contributor.id"
                                        class="lp-avatar"
                                    >
                                        {{ contributor.name?.charAt(0)?.toUpperCase() }}
                                    </div>
                                </div>
                                <span class="text-xs text-arbor-sage">
                                    {{ formatNumber(point.contributors_count || point.contributors?.length || 1) }} contributeur{{ (point.contributors_count || point.contributors?.length || 1) > 1 ? 's' : '' }}
                                </span>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="lp-empty">
                        <p class="font-display text-3xl text-arbor-cream">Aucune station trouvée</p>
                        <p class="mt-2 text-arbor-sage">La carte publique reste volontairement filtrée aux points approuvés avec sons publiés.</p>
                        <button class="mt-5 lp-filter-button" type="button" @click="resetFilters">Voir tout l'atlas</button>
                    </div>

                    <div v-if="points.links.length > 3" class="mt-10 flex justify-center">
                        <div class="flex flex-wrap justify-center gap-2">
                            <Link
                                v-for="link in points.links.filter((item) => item.url)"
                                :key="link.label"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-sm transition-colors border"
                                :class="link.active
                                    ? 'bg-arbor-emerald/20 text-arbor-emerald border-arbor-emerald/30'
                                    : 'text-arbor-sage hover:text-arbor-cream hover:bg-arbor-glass/30 border-transparent'
                                "
                                preserve-scroll
                            />
                        </div>
                    </div>
                </section>
            </template>

            <!-- ═════ MODE AGRÉGATEUR ═════ -->
            <template v-else>
                <!-- Timeline globale -->
                <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
                    <div class="mb-4">
                        <p class="text-xs uppercase tracking-widest text-arbor-emerald">Timeline globale</p>
                        <h2 class="font-display text-2xl text-arbor-cream">Évolution des enregistrements</h2>
                    </div>

                    <div class="lp-timeline-shell">
                        <div v-if="loadingTimeline" class="h-40 flex items-center justify-center text-arbor-sage text-sm">
                            Chargement de la timeline…
                        </div>
                        <div v-else-if="timelineData.length === 0" class="h-40 flex items-center justify-center text-arbor-sage text-sm">
                            Pas assez de données pour construire la timeline.
                        </div>
                        <div v-else class="lp-timeline-chart">
                            <div
                                v-for="bar in timelineData"
                                :key="bar.month"
                                class="lp-timeline-bar"
                                :style="{ height: `${Math.min(100, (bar.count / Math.max(...timelineData.map(d => d.count))) * 100)}%` }"
                                :title="`${bar.month}: ${bar.count} enregistrements`"
                            >
                                <span class="lp-timeline-label">{{ bar.month.slice(5) }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Grille photo -->
                <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
                    <div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-arbor-emerald">Atlas visuel</p>
                            <h2 class="font-display text-3xl text-arbor-cream">Stations en images</h2>
                        </div>
                        <p class="text-sm text-arbor-sage">
                            Page {{ points.current_page }} / {{ points.last_page }} · {{ formatNumber(points.total) }} résultat{{ points.total > 1 ? 's' : '' }}
                        </p>
                    </div>

                    <div v-if="allPoints.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                        <Link
                            v-for="point in allPoints"
                            :key="point.id"
                            :href="route('listening-points.show', point.slug)"
                            class="lp-photo-card group"
                        >
                            <div class="lp-photo-image">
                                <img
                                    v-if="point.last_cover_url"
                                    :src="point.last_cover_url"
                                    :alt="point.title"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    loading="lazy"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-arbor-moss/20">
                                    <span class="text-5xl">{{ habitatEmoji(point.habitat_type) }}</span>
                                </div>
                                <div class="lp-photo-overlay"></div>
                                <div class="lp-photo-badge">
                                    <span>{{ formatNumber(point.recordings_count) }} sons</span>
                                </div>
                            </div>
                            <div class="lp-photo-info">
                                <div class="flex items-center gap-2 text-xs text-arbor-sage uppercase tracking-wider">
                                    <span>{{ habitatEmoji(point.habitat_type) }}</span>
                                    <span>{{ habitatLabel(point.habitat_type) }}</span>
                                </div>
                                <h3 class="mt-2 font-display text-xl font-semibold text-arbor-cream group-hover:text-arbor-emerald transition-colors line-clamp-2">
                                    {{ point.title }}
                                </h3>
                                <div class="mt-3 flex items-center gap-3 text-xs text-arbor-sage/80">
                                    <span>{{ formatNumber(point.species_detected_count) }} espèces</span>
                                    <span>·</span>
                                    <span>depuis {{ formatYear(point.first_recorded_at) }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="lp-empty">
                        <p class="font-display text-3xl text-arbor-cream">Aucune station trouvée</p>
                        <p class="mt-2 text-arbor-sage">La carte publique reste volontairement filtrée aux points approuvés avec sons publiés.</p>
                        <button class="mt-5 lp-filter-button" type="button" @click="resetFilters">Voir tout l'atlas</button>
                    </div>

                    <div v-if="points.links.length > 3" class="mt-10 flex justify-center">
                        <div class="flex flex-wrap justify-center gap-2">
                            <Link
                                v-for="link in points.links.filter((item) => item.url)"
                                :key="link.label"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-sm transition-colors border"
                                :class="link.active
                                    ? 'bg-arbor-emerald/20 text-arbor-emerald border-arbor-emerald/30'
                                    : 'text-arbor-sage hover:text-arbor-cream hover:bg-arbor-glass/30 border-transparent'
                                "
                                preserve-scroll
                            />
                        </div>
                    </div>
                </section>
            </template>
        </main>
    </GuestLayout>
</template>

<style>
.lp-hero-texture {
    background:
        radial-gradient(circle at 18% 10%, rgba(52, 211, 153, 0.18), transparent 24rem),
        linear-gradient(135deg, rgba(16, 55, 45, 0.45), transparent 45%),
        repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.035) 0 1px, transparent 1px 72px);
}

.lp-stat,
.lp-card-metric {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.045);
    backdrop-filter: blur(18px);
}

.lp-stat {
    min-height: 6.5rem;
    padding: 1rem;
}

.lp-stat span,
.lp-card-metric span {
    display: block;
    color: #f5f0dc;
    font-family: var(--font-display, serif);
    font-size: clamp(1.45rem, 3vw, 2.5rem);
    line-height: 1;
}

.lp-stat p,
.lp-card-metric p {
    margin-top: 0.45rem;
    color: #8fa68e;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.lp-filter-panel {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(9, 22, 18, 0.78);
    padding: 0.75rem;
    backdrop-filter: blur(18px);
}

@media (min-width: 1024px) {
    .lp-filter-panel {
        grid-template-columns: minmax(0, 2fr) minmax(13rem, 0.55fr) auto auto;
    }
}

.lp-input {
    width: 100%;
    min-height: 2.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(3, 12, 10, 0.75);
    color: #f5f0dc;
    border-radius: 0.65rem;
    padding: 0.7rem 0.9rem;
}

.lp-input::placeholder {
    color: rgba(143, 166, 142, 0.72);
}

.lp-input:focus {
    outline: none;
    border-color: rgba(52, 211, 153, 0.62);
    box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.12);
}

.lp-filter-button,
.lp-reset-button {
    min-height: 2.75rem;
    border-radius: 0.65rem;
    padding: 0.7rem 1rem;
    font-size: 0.88rem;
    font-weight: 700;
    transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
}

.lp-filter-button {
    background: rgba(52, 211, 153, 0.18);
    color: #6ee7b7;
    border: 1px solid rgba(52, 211, 153, 0.35);
}

.lp-reset-button {
    background: rgba(255, 255, 255, 0.045);
    color: #8fa68e;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.lp-filter-button:hover,
.lp-reset-button:hover {
    transform: translateY(-1px);
}

.lp-habitat-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex: 0 0 auto;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.045);
    color: #8fa68e;
    border-radius: 999px;
    padding: 0.55rem 0.75rem;
    font-size: 0.84rem;
    transition: border-color 0.18s ease, color 0.18s ease, background 0.18s ease;
}

.lp-habitat-chip strong {
    color: #f5f0dc;
    font-weight: 700;
}

.lp-habitat-chip small {
    color: rgba(143, 166, 142, 0.78);
    font-variant-numeric: tabular-nums;
}

.lp-habitat-chip-active {
    border-color: rgba(52, 211, 153, 0.48);
    background: rgba(52, 211, 153, 0.13);
}

.lp-map-shell {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(7, 15, 13, 0.82);
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.32);
}

.lp-map-panel {
    position: absolute;
    left: 1rem;
    bottom: 1rem;
    width: min(22rem, calc(100% - 2rem));
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(5, 16, 13, 0.86);
    backdrop-filter: blur(18px);
    padding: 1rem;
    pointer-events: none;
    z-index: 400;
}

.lp-map-panel > * {
    pointer-events: auto;
}

.lp-point-card {
    display: block;
    min-height: 25rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        linear-gradient(145deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.025)),
        rgba(8, 22, 18, 0.72);
    padding: 1.25rem;
    transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}

.lp-point-card:hover {
    transform: translateY(-3px);
    border-color: rgba(52, 211, 153, 0.36);
    background:
        linear-gradient(145deg, rgba(52, 211, 153, 0.08), rgba(255, 255, 255, 0.03)),
        rgba(8, 22, 18, 0.86);
}

.lp-accuracy,
.lp-tag {
    flex: 0 0 auto;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(0, 0, 0, 0.18);
    color: #8fa68e;
    border-radius: 999px;
    font-size: 0.72rem;
}

.lp-accuracy {
    padding: 0.35rem 0.55rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.lp-tag {
    padding: 0.32rem 0.55rem;
}

.lp-card-metric {
    padding: 0.75rem;
}

.lp-card-metric span {
    font-size: 1.55rem;
}

.lp-avatar {
    width: 1.85rem;
    height: 1.85rem;
    border-radius: 999px;
    border: 2px solid #07120f;
    background: #315342;
    color: #f5f0dc;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
}

.lp-empty {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    padding: 3rem 1.25rem;
    text-align: center;
}

/* ── Markers ── */
.listening-point-marker {
    background: transparent !important;
    border: none !important;
}

.lp-marker {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(10, 22, 18, 0.9);
    border: 2px solid rgba(52, 211, 153, 0.58);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 0 6px rgba(52, 211, 153, 0.08), 0 12px 30px rgba(0, 0, 0, 0.4);
    transition: transform 0.2s ease, border-color 0.2s ease;
    cursor: pointer;
}

.lp-marker-medium {
    border-color: rgba(251, 191, 36, 0.72);
}

.lp-marker-high {
    border-color: rgba(110, 231, 183, 0.92);
    box-shadow: 0 0 0 9px rgba(52, 211, 153, 0.11), 0 14px 34px rgba(0, 0, 0, 0.45);
}

.lp-marker:hover {
    transform: scale(1.12);
}

.lp-marker-emoji {
    font-size: 17px;
    line-height: 1;
}

/* ── Clusters ── */
.lp-cluster-icon {
    background: transparent !important;
    border: none !important;
}

.lp-cluster {
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-family: var(--font-display, serif);
    color: #f5f0dc;
    transition: transform 0.2s ease;
    cursor: pointer;
}

.lp-cluster:hover {
    transform: scale(1.08);
}

.lp-cluster-small {
    width: 34px;
    height: 34px;
    background: rgba(10, 22, 18, 0.92);
    border: 2px solid rgba(52, 211, 153, 0.55);
    box-shadow: 0 0 0 4px rgba(52, 211, 153, 0.08);
    font-size: 13px;
}

.lp-cluster-medium {
    width: 44px;
    height: 44px;
    background: rgba(10, 22, 18, 0.94);
    border: 2px solid rgba(52, 211, 153, 0.72);
    box-shadow: 0 0 0 7px rgba(52, 211, 153, 0.1);
    font-size: 15px;
}

.lp-cluster-large {
    width: 56px;
    height: 56px;
    background: rgba(10, 22, 18, 0.96);
    border: 2px solid rgba(110, 231, 183, 0.88);
    box-shadow: 0 0 0 10px rgba(52, 211, 153, 0.13), 0 14px 34px rgba(0, 0, 0, 0.45);
    font-size: 17px;
}

/* ── Popups ── */
.lp-popup .leaflet-popup-content-wrapper {
    background: rgba(8, 19, 16, 0.96) !important;
    backdrop-filter: blur(14px) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 0 !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4) !important;
}

.lp-popup .leaflet-popup-content {
    margin: 12px 14px !important;
}

.lp-popup .leaflet-popup-tip {
    background: rgba(8, 19, 16, 0.96) !important;
}

.lp-popup-card {
    min-width: 210px;
}

.lp-popup-kicker {
    margin: 0 0 4px;
    color: #6ee7b7;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.lp-popup-title {
    color: #f5f0dc;
    font-weight: 700;
    text-decoration: none;
    font-size: 14px;
}

.lp-popup-title:hover {
    color: #6ee7b7;
}

.lp-popup-meta {
    margin: 6px 0 0;
    color: #8fa68e;
    font-size: 12px;
}

/* ── Photo cards (mode agrégateur) ── */
.lp-photo-card {
    display: block;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(8, 22, 18, 0.72);
    overflow: hidden;
    transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.lp-photo-card:hover {
    transform: translateY(-4px);
    border-color: rgba(52, 211, 153, 0.4);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
}

.lp-photo-image {
    position: relative;
    width: 100%;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    background: rgba(5, 16, 13, 0.9);
}

.lp-photo-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(5, 16, 13, 0.7) 0%, transparent 50%);
    pointer-events: none;
}

.lp-photo-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(5, 16, 13, 0.82);
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    padding: 0.3rem 0.6rem;
    border-radius: 999px;
    font-size: 0.72rem;
    color: #f5f0dc;
    font-weight: 600;
}

.lp-photo-info {
    padding: 1rem;
}

/* ── Timeline ── */
.lp-timeline-shell {
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(7, 15, 13, 0.82);
    padding: 1.25rem;
    overflow-x: auto;
}

.lp-timeline-chart {
    display: flex;
    align-items: flex-end;
    gap: 3px;
    min-height: 160px;
    padding-bottom: 1.5rem;
}

.lp-timeline-bar {
    flex: 1;
    min-width: 18px;
    background: linear-gradient(to top, rgba(52, 211, 153, 0.45), rgba(52, 211, 153, 0.12));
    border-radius: 2px 2px 0 0;
    position: relative;
    transition: background 0.2s ease;
    cursor: pointer;
}

.lp-timeline-bar:hover {
    background: linear-gradient(to top, rgba(52, 211, 153, 0.7), rgba(52, 211, 153, 0.25));
}

.lp-timeline-label {
    position: absolute;
    bottom: -1.25rem;
    left: 50%;
    transform: translateX(-50%) rotate(-45deg);
    transform-origin: left center;
    font-size: 0.65rem;
    color: #8fa68e;
    white-space: nowrap;
}
</style>
