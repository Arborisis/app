<script setup>
import { ref, onMounted, watch, computed, nextTick, defineAsyncComponent } from 'vue';
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import '@css/map.css';

const SoundMap = defineAsyncComponent(() => import('@/Components/Map/SoundMap.vue'));

const props = defineProps({
    categories: Array,
});

const sounds = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const selectedCategory = ref('');
const searchTimeout = ref(null);
const initialLoad = ref(true);
const sidebarOpen = ref(false);
const titleHidden = ref(false);
const activeSoundId = ref(null);
const titleTimeout = ref(null);
const soundMapRef = ref(null);
const activeMode = ref('discover');

const mapModes = [
    { key: 'discover', label: 'Découverte' },
    { key: 'species', label: 'Espèces' },
    { key: 'places', label: 'Lieux' },
    { key: 'seasons', label: 'Saisons' },
    { key: 'archives', label: 'Archives' },
];

/* ── Category color mapping for pills ───────────────── */
const categoryColorMap = {
    'Forêts': { bg: 'bg-emerald-500/15', text: 'text-emerald-400', border: 'border-emerald-500/30', glow: 'shadow-emerald-500/5' },
    'Océans': { bg: 'bg-sky-500/15', text: 'text-sky-400', border: 'border-sky-500/30', glow: 'shadow-sky-500/5' },
    'Montagnes': { bg: 'bg-stone-500/15', text: 'text-stone-400', border: 'border-stone-500/30', glow: 'shadow-stone-500/5' },
    'Déserts': { bg: 'bg-amber-500/15', text: 'text-amber-400', border: 'border-amber-500/30', glow: 'shadow-amber-500/5' },
    'Rivières': { bg: 'bg-cyan-500/15', text: 'text-cyan-400', border: 'border-cyan-500/30', glow: 'shadow-cyan-500/5' },
    'Urbain': { bg: 'bg-pink-500/15', text: 'text-pink-400', border: 'border-pink-500/30', glow: 'shadow-pink-500/5' },
    'Cavernes': { bg: 'bg-violet-500/15', text: 'text-violet-400', border: 'border-violet-500/30', glow: 'shadow-violet-500/5' },
    'Prairies': { bg: 'bg-lime-500/15', text: 'text-lime-400', border: 'border-lime-500/30', glow: 'shadow-lime-500/5' },
    'Lacs': { bg: 'bg-blue-400/15', text: 'text-blue-400', border: 'border-blue-400/30', glow: 'shadow-blue-400/5' },
    'Marais': { bg: 'bg-teal-500/15', text: 'text-teal-400', border: 'border-teal-500/30', glow: 'shadow-teal-500/5' },
    'Jungles': { bg: 'bg-green-500/15', text: 'text-green-400', border: 'border-green-500/30', glow: 'shadow-green-500/5' },
    'Glaciers': { bg: 'bg-indigo-300/15', text: 'text-indigo-300', border: 'border-indigo-300/30', glow: 'shadow-indigo-300/5' },
};

const getCategoryStyle = (name) => {
    return categoryColorMap[name] || { bg: 'bg-arbor-emerald/15', text: 'text-arbor-emerald', border: 'border-arbor-emerald/30', glow: 'shadow-arbor-emerald/5' };
};

/* ── Computed ───────────────────────────────────────── */
const hasSounds = computed(() => sounds.value.length > 0);

const activeCategoryName = computed(() => {
    if (!selectedCategory.value) return '';
    const cat = props.categories.find(c => String(c.id) === String(selectedCategory.value));
    return cat?.name || '';
});

/* ── Data fetching ──────────────────────────────────── */
const fetchSounds = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (selectedCategory.value) {
            params.append('category', selectedCategory.value);
        }

        const response = await fetch(`/api/map/sounds?${params.toString()}`);
        const data = await response.json();
        sounds.value = data.features ?? [];
    } catch (e) {
        console.error('Failed to load sounds:', e);
        sounds.value = [];
    } finally {
        loading.value = false;
        initialLoad.value = false;
    }
};

const searchSounds = async () => {
    if (!searchQuery.value || searchQuery.value.length < 2) {
        fetchSounds();
        return;
    }

    loading.value = true;
    try {
        const response = await fetch(`/api/map/sounds/search?q=${encodeURIComponent(searchQuery.value)}`);
        const data = await response.json();
        sounds.value = data.features ?? [];
    } catch (e) {
        console.error('Search failed:', e);
        sounds.value = [];
    } finally {
        loading.value = false;
    }
};

const onSearchInput = () => {
    clearTimeout(searchTimeout.value);
    searchTimeout.value = setTimeout(searchSounds, 400);
};

const clearSearch = () => {
    searchQuery.value = '';
    fetchSounds();
};

const selectCategory = (id) => {
    selectedCategory.value = selectedCategory.value === id ? '' : id;
};

watch(selectedCategory, fetchSounds);

/* ── Sound list interactions ────────────────────────── */
const formatDuration = (seconds) => {
    if (!seconds) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const onSoundClick = (sound) => {
    const p = sound.properties;
    const coords = sound.geometry.coordinates;
    activeSoundId.value = p.id;
    hideTitle();

    if (soundMapRef.value && coords && isFinite(coords[0]) && isFinite(coords[1])) {
        soundMapRef.value.flyToSound(coords, 14);
        soundMapRef.value.highlightMarker(p.id);
        nextTick(() => {
            soundMapRef.value.openPopup(p.id);
        });
    }

    if (window.innerWidth < 768) {
        sidebarOpen.value = false;
    }
};

const onSoundHover = (sound) => {
    if (soundMapRef.value) {
        soundMapRef.value.highlightMarker(sound.properties.id);
    }
};

const onSoundHoverLeave = () => {
    if (soundMapRef.value) {
        soundMapRef.value.highlightMarker(activeSoundId.value);
    }
};

/* ── Title auto-hide ────────────────────────────────── */
const hideTitle = () => {
    titleHidden.value = true;
    if (titleTimeout.value) clearTimeout(titleTimeout.value);
};

/* ── Lifecycle ──────────────────────────────────────── */
onMounted(() => {
    fetchSounds();
    titleTimeout.value = setTimeout(() => {
        titleHidden.value = true;
    }, 4000);
});
</script>

<template>
    <Head title="Carte sonore" />
    <GuestLayout>
        <div class="relative isolate h-[calc(100vh-4rem)] min-h-[600px] overflow-hidden">
            <!-- Ambient gradient overlay -->
            <div class="absolute inset-0 z-[5] pointer-events-none bg-gradient-radial from-arbor-moss/10 via-transparent to-transparent"></div>

            <!-- Grain texture -->
            <div class="absolute inset-0 z-[6] pointer-events-none map-grain"></div>

            <!-- Fog vignette for depth -->
            <div class="absolute inset-0 z-[5] pointer-events-none map-fog-vignette"></div>

            <!-- Floating header title -->
            <div
                class="absolute top-6 left-1/2 -translate-x-1/2 z-map text-center map-title-fade pointer-events-none"
                :class="{ 'map-title-hidden': titleHidden }"
            >
                <h1 class="font-display text-3xl md:text-4xl font-semibold text-arbor-cream tracking-tight">
                    Carte vivante
                </h1>
                <p class="text-arbor-sage/70 text-sm mt-1 font-light">
                    Les traces sonores publiques du monde vivant
                </p>
            </div>

            <!-- Mobile sidebar toggle -->
            <button
                class="md:hidden fixed bottom-6 left-4 z-map w-12 h-12 rounded-xl glass-card flex items-center justify-center shadow-lg shadow-black/30"
                :class="{ 'ring-2 ring-arbor-emerald/40': sidebarOpen }"
                aria-label="Ouvrir le panneau de l'explorateur"
                @click="sidebarOpen = !sidebarOpen"
            >
                <svg v-if="!sidebarOpen" class="w-5 h-5 text-arbor-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg v-else class="w-5 h-5 text-arbor-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Sidebar — Panneau de l'explorateur -->
            <div
                class="absolute top-4 left-4 z-map w-[340px] lg:w-[380px] max-w-[calc(100vw-2rem)] mobile-sidebar"
                :class="sidebarOpen ? 'mobile-sidebar-open' : 'mobile-sidebar-closed md:mobile-sidebar-open'"
                @mouseenter="hideTitle"
                @click="hideTitle"
            >
                <div class="trace-frame shadow-2xl shadow-black/25 overflow-hidden flex flex-col max-h-[calc(100vh-7rem)]">
                    
                    <!-- Section 1: Recherche -->
                    <div class="p-4 border-b border-arbor-glass-border animate-fade-in-up">
                        <div class="mb-4">
                            <p class="atlas-kicker mb-1">Atlas acoustique</p>
                            <h2 class="font-display text-2xl font-semibold text-arbor-cream">Explorer les traces</h2>
                            <p class="mt-1 text-xs leading-5 text-arbor-sage/75">
                                Coordonnées publiques approximées pour protéger les lieux sensibles.
                            </p>
                        </div>

                        <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
                            <button
                                v-for="mode in mapModes"
                                :key="mode.key"
                                class="map-mode-pill shrink-0"
                                :class="{ 'map-mode-pill-active': activeMode === mode.key }"
                                @click="activeMode = mode.key"
                            >
                                {{ mode.label }}
                            </button>
                        </div>

                        <div class="relative">
                            <svg
                                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-arbor-sage/60"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Espèce, forêt, aube, créateur..."
                                class="w-full pl-10 pr-9 py-2.5 bg-arbor-ink/50 border border-arbor-mineral/10 rounded-lg text-base text-arbor-cream placeholder:text-arbor-sage/60 focus:outline-none focus:border-arbor-lichen/50 focus:ring-2 focus:ring-arbor-lichen/10 transition-colors search-pulse"
                                aria-label="Rechercher un son ou un lieu"
                                @input="onSearchInput"
                            />
                            <button
                                v-if="searchQuery"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-arbor-sage/50 hover:text-arbor-cream transition-colors p-2 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                aria-label="Effacer la recherche"
                                @click="clearSearch"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Section 2: Catégories -->
                    <div class="px-4 py-3 border-b border-arbor-glass-border animate-fade-in-up stagger-1">
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                :class="[
                                    'px-3 py-2 min-h-[44px] rounded-lg text-xs font-medium transition-colors duration-200 flex items-center',
                                    selectedCategory === ''
                                        ? 'bg-arbor-lichen/15 text-arbor-lichen border border-arbor-lichen/30 shadow-sm shadow-arbor-lichen/5'
                                        : 'bg-arbor-ink/45 text-arbor-sage border border-arbor-mineral/10 hover:border-arbor-mineral/25 hover:bg-arbor-mist/5',
                                ]"
                                @click="selectCategory('')"
                            >
                                Tous
                            </button>
                            <button
                                v-for="category in categories"
                                :key="category.id"
                                :class="[
                                    'px-3 py-2 min-h-[44px] rounded-lg text-xs font-medium transition-colors duration-200 border flex items-center',
                                    selectedCategory == category.id
                                        ? `${getCategoryStyle(category.name).bg} ${getCategoryStyle(category.name).text} ${getCategoryStyle(category.name).border} shadow-sm ${getCategoryStyle(category.name).glow}`
                                        : 'bg-arbor-ink/45 text-arbor-sage border-arbor-mineral/10 hover:border-arbor-mineral/25 hover:bg-arbor-mist/5',
                                ]"
                                @click="selectCategory(category.id)"
                            >
                                {{ category.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Section 3: Compteur & statut -->
                    <div class="px-4 py-2.5 border-b border-arbor-glass-border flex items-center justify-between animate-fade-in-up stagger-2">
                                <span class="text-xs">
                                    <span v-if="initialLoad" class="text-arbor-sage/70">Chargement...</span>
                                    <span v-else-if="hasSounds" class="text-arbor-sage">
                                <span class="text-arbor-lichen font-semibold tabular-nums">{{ sounds.length }}</span>
                                trace{{ sounds.length > 1 ? 's' : '' }} trouvée{{ sounds.length > 1 ? 's' : '' }}
                                <span v-if="activeCategoryName" class="text-arbor-sage/60"> dans <span class="text-arbor-sage">{{ activeCategoryName }}</span></span>
                            </span>
                            <span v-else class="text-arbor-sage/60">Aucun son trouvé</span>
                        </span>

                        <!-- Waveform loader -->
                        <div v-if="loading && !initialLoad" class="waveform-loader">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>

                    <!-- Section 4: Liste des sons -->
                    <div class="flex-1 overflow-y-auto min-h-0 custom-scrollbar">
                        <!-- Skeleton loading -->
                        <div v-if="initialLoad || loading" class="p-3 space-y-3">
                            <div v-for="n in 6" :key="n" class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-lg bg-arbor-charcoal animate-pulse shrink-0" />
                                <div class="flex-1 space-y-2">
                                    <div class="h-3 bg-arbor-charcoal rounded animate-pulse w-3/4" />
                                    <div class="h-2.5 bg-arbor-charcoal rounded animate-pulse w-1/2" />
                                </div>
                            </div>
                        </div>
                        <!-- Sound list -->
                        <div v-else-if="hasSounds" class="p-3 space-y-1.5">
                            <div
                                v-for="(sound, index) in sounds"
                                :key="sound.properties.id"
                                class="map-sound-item animate-sound-item-enter"
                                :class="{ 'map-sound-item-active': activeSoundId == sound.properties.id }"
                                :style="`animation-delay: ${index * 0.04}s; opacity: 0;`"
                                @click="onSoundClick(sound)"
                                @mouseenter="onSoundHover(sound)"
                                @mouseleave="onSoundHoverLeave"
                            >
                                <!-- Cover thumbnail -->
                                <div class="w-11 h-11 rounded-lg overflow-hidden shrink-0 bg-arbor-charcoal">
                                    <img
                                        v-if="sound.properties.cover_url"
                                        :src="sound.properties.cover_url"
                                        :alt="sound.properties.title"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-arbor-sage/30">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 mb-0.5">
                                        <span
                                            v-if="sound.properties.category"
                                            class="text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded"
                                            :class="`${getCategoryStyle(sound.properties.category).bg} ${getCategoryStyle(sound.properties.category).text}`"
                                        >
                                            {{ sound.properties.category }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-medium text-arbor-cream truncate">{{ sound.properties.title }}</h4>
                                    <div class="flex items-center gap-2 text-[11px] text-arbor-sage/70">
                                        <span class="truncate">{{ sound.properties.user_name }}</span>
                                        <span class="shrink-0">·</span>
                                        <span class="tabular-nums shrink-0">{{ formatDuration(sound.properties.duration) }}</span>
                                    </div>
                                </div>

                                <!-- Chevron -->
                                <svg class="w-4 h-4 text-arbor-sage/30 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Empty state inside sidebar -->
                        <div
                            v-if="!loading && !hasSounds && !initialLoad"
                            class="p-6 text-center animate-scale-in"
                        >
                            <svg class="w-12 h-12 text-arbor-moss/30 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 7m0 13V7m0 0L9 7" />
                            </svg>
                            <p class="text-sm text-arbor-sage mb-1">Aucun enregistrement ici</p>
                            <p class="text-xs text-arbor-sage/70 mb-4">Essayez une autre recherche ou catégorie</p>
                            <button
                                class="text-xs text-arbor-emerald hover:text-arbor-emerald-dark transition-colors font-medium"
                                @click="clearSearch(); selectedCategory = ''; fetchSounds();"
                            >
                                Réinitialiser les filtres →
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <SoundMap
                ref="soundMapRef"
                :sounds="sounds"
                :active-sound-id="activeSoundId"
                @marker-click="activeSoundId = $event"
            />
        </div>
    </GuestLayout>
</template>
