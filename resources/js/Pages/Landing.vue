<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { computed, ref, onMounted, onUnmounted, defineAsyncComponent } from 'vue';
import { useParallax } from '@/Composables/useParallax.js';
import SoundCard from '@/Components/SoundCard.vue';
import CreatorCard from '@/Components/CreatorCard.vue';

// Lazy load heavy components to reduce initial bundle
const SoundMap = defineAsyncComponent(() => import('@/Components/Map/SoundMap.vue'));
const ParticleField = defineAsyncComponent(() => import('@/Components/Three/ParticleField.vue'));
const NatureScene = defineAsyncComponent(() => import('@/Components/Three/NatureScene.vue'));

const props = defineProps({
    stats: Object,
    featuredSounds: {
        type: Array,
        default: () => [],
    },
    featuredCreators: {
        type: Array,
        default: () => [],
    },
});

// Reactive data — initialized from SSR props, then refreshed client-side
const featuredSounds = ref(props.featuredSounds);
const featuredCreators = ref(props.featuredCreators);
const soundsLoading = ref(props.featuredSounds.length === 0);
const creatorsLoading = ref(props.featuredCreators.length === 0);

const features = ref([
    {
        title: 'Carte sonore interactive',
        description: 'Explorez une carte mondiale des enregistrements naturels, filtrez par environnement et découvrez des sons uniques.',
        icon: 'M9 6.75002V15M15 9.00002V17.25M15.5031 20.7485L20.3781 18.311C20.7592 18.1204 21 17.7309 21 17.3047V4.82031C21 3.98401 20.1199 3.44007 19.3719 3.81408L15.5031 5.74847C15.1864 5.90683 14.8136 5.90683 14.4969 5.74847L9.50312 3.25158C9.1864 3.09322 8.8136 3.09322 8.49688 3.25158L3.62188 5.68908C3.24075 5.87965 3 6.26919 3 6.69531V19.1797C3 20.016 3.8801 20.56 4.62811 20.186L8.49688 18.2516C8.8136 18.0932 9.1864 18.0932 9.50312 18.2516L14.4969 20.7485C14.8136 20.9068 15.1864 20.9068 15.5031 20.7485Z',
    },
    {
        title: 'Lecteur audio immersif',
        description: 'Écoutez chaque son dans un lecteur premium avec visualisation, contrôles intuitifs et expérience contemplative.',
        icon: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        title: 'Soutenez les créateurs',
        description: 'Avec les crédits ECHO, soutenez financièrement les enregistreurs et contribuez à l\'archive sonore collective.',
        icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    },
]);

const steps = ref([
    {
        number: '01',
        title: 'Explorez',
        description: 'Naviguez sur la carte ou parcourez les catégories pour découvrir des paysages sonores uniques.',
        icon: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 7m0 13V7m0 0L9 7',
    },
    {
        number: '02',
        title: 'Écoutez',
        description: 'Lancez la lecture et laissez-vous transporter par les sons de la nature, capturés avec passion.',
        icon: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        number: '03',
        title: 'Partagez',
        description: 'Publiez vos propres enregistrements et soutenez les créateurs qui enrichissent l\'archive.',
        icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    },
]);

const categories = ref([
    { name: 'Forêts', count: 0, color: 'bg-emerald-500/20 text-emerald-400' },
    { name: 'Océans', count: 0, color: 'bg-blue-500/20 text-blue-400' },
    { name: 'Montagnes', count: 0, color: 'bg-stone-500/20 text-stone-400' },
    { name: 'Rivières', count: 0, color: 'bg-cyan-500/20 text-cyan-400' },
    { name: 'Pluie', count: 0, color: 'bg-indigo-500/20 text-indigo-400' },
    { name: 'Crépuscule', count: 0, color: 'bg-amber-500/20 text-amber-400' },
]);

const atlasPillars = ref([
    {
        kicker: 'Explorer',
        title: 'Carte vivante',
        description: 'Des traces sonores approximées, filtrées par biomes, saisons et espèces, sans exposer les coordonnées sensibles.',
    },
    {
        kicker: 'Écouter',
        title: 'Archives précieuses',
        description: 'Chaque enregistrement devient une fiche de terrain : contexte, lieu, heure, météo, espèces et signal audio.',
    },
    {
        kicker: 'Documenter',
        title: 'Laboratoire du vivant',
        description: 'Des visualisations scientifiques lisibles pour observer la biodiversité sonore dans le temps.',
    },
]);

const scientificSignals = ref([
    'Densité sonore',
    'Saisons',
    'Espèces détectées',
    'Météo',
    'Habitats',
    'Évolution du lieu',
]);

const mapSounds = ref([]);
const mapLoading = ref(true);
const mapError = ref(false);

// Parallax refs
const audioDemoTitle = useParallax(0.15);
const howItWorksTitle = useParallax(0.1);
const missionTitle = useParallax(0.12);

const animatedStats = ref({ sounds: 0, creators: 0, countries: 0 });
const statsVisible = ref(false);
let statsObserver = null;

const prefersReducedMotion = ref(false);

const normalizeHeroSound = (sound) => {
    if (!sound) return null;

    if (sound.properties) {
        return {
            id: sound.properties.id,
            title: sound.properties.title,
            slug: sound.properties.slug,
            duration: sound.properties.duration,
            category: sound.properties.category,
            location: sound.properties.location_name,
            user: sound.properties.user_name,
            recordedAt: sound.properties.recorded_at,
        };
    }

    return {
        id: sound.id,
        title: sound.title,
        slug: sound.slug,
        duration: sound.duration,
        category: sound.category?.name || sound.category,
        location: sound.sound_location?.location_name || sound.location_name,
        user: sound.user_name || sound.user?.name,
        recordedAt: sound.recorded_at || sound.created_at,
    };
};

const heroTrace = computed(() => {
    return normalizeHeroSound(featuredSounds.value[0] || mapSounds.value[0]);
});

const heroTraceCode = computed(() => {
    if (!heroTrace.value?.id) return 'ARB-LIVE';
    const rawDate = heroTrace.value.recordedAt || new Date().toISOString();
    const year = new Date(rawDate).getFullYear();
    return `ARB-${year}-${String(heroTrace.value.id).padStart(4, '0')}`;
});

const formatDuration = (seconds) => {
    if (!seconds) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const animateCount = (target, key, duration = 1500) => {
    if (prefersReducedMotion.value) {
        animatedStats.value[key] = target;
        return;
    }
    const start = performance.now();
    const from = 0;
    const to = target;

    const step = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        animatedStats.value[key] = Math.floor(from + (to - from) * eased);
        if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
};

const loadFeaturedSounds = async () => {
    try {
        const response = await fetch('/api/sounds/featured');
        if (!response.ok) throw new Error('Failed to fetch sounds');
        featuredSounds.value = await response.json();
    } catch (e) {
        console.error('Failed to load featured sounds:', e);
    } finally {
        soundsLoading.value = false;
    }
};

const loadFeaturedCreators = async () => {
    try {
        const response = await fetch('/api/creators/featured');
        if (!response.ok) throw new Error('Failed to fetch creators');
        featuredCreators.value = await response.json();
    } catch (e) {
        console.error('Failed to load featured creators:', e);
    } finally {
        creatorsLoading.value = false;
    }
};

onMounted(() => {
    prefersReducedMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    loadMapSounds();
    loadFeaturedSounds();
    loadFeaturedCreators();

    const statsEl = document.getElementById('stats-section');
    if (statsEl) {
        statsObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && !statsVisible.value) {
                    statsVisible.value = true;
                    animateCount(props.stats.sounds, 'sounds');
                    animateCount(props.stats.creators, 'creators');
                    animateCount(props.stats.countries, 'countries');
                }
            });
        }, { threshold: 0.3 });
        statsObserver.observe(statsEl);
    }
});

onUnmounted(() => {
    if (statsObserver) statsObserver.disconnect();
});

const loadMapSounds = async () => {
    try {
        const response = await fetch('/api/map/sounds?limit=50');
        const data = await response.json();
        mapSounds.value = data.features ?? [];
    } catch (e) {
        console.error('Failed to load map sounds:', e);
        mapError.value = true;
    } finally {
        mapLoading.value = false;
    }
};
</script>

<template>
    <Head title="Le vivant s'écoute" />
    <GuestLayout>
        <!-- Hero Section -->
        <section class="relative flex min-h-screen items-center overflow-hidden">
            <!-- Cinematic nature photograph background -->
            <img
                src="/images/hero-leaf.webp"
                alt=""
                class="absolute inset-0 w-full h-full object-cover animate-ken-burns"
                style="filter: brightness(0.55) contrast(1.15) saturate(0.8);"
                fetchpriority="high"
                decoding="async"
            />

            <!-- Cinematic color grade overlay -->
            <div class="absolute inset-0 bg-arbor-night/70" />
            <div class="absolute inset-0 bg-gradient-to-t from-arbor-night via-transparent to-arbor-night/40" />
            <div class="absolute inset-0 bg-gradient-to-br from-arbor-emerald/10 via-transparent to-arbor-night/60" />

            <!-- Vignette effect -->
            <div
                class="absolute inset-0 pointer-events-none"
                style="background: radial-gradient(ellipse at center, transparent 0%, transparent 40%, rgba(2, 15, 8, 0.7) 100%);"
            />

            <!-- Film grain texture -->
            <div class="absolute inset-0 opacity-[0.04] pointer-events-none mix-blend-overlay"
                style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 512 512%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.75%22 numOctaves=%225%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E'); background-repeat: repeat;"
            />

            <!-- Hero glow -->
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_18%,rgba(215,180,106,0.18),transparent_28rem),radial-gradient(circle_at_75%_62%,rgba(143,230,193,0.1),transparent_24rem)] opacity-80" />

            <!-- Floating particles effect (Three.js) -->
            <ParticleField />

            <!-- Subtle scan-line effect -->
            <div class="absolute inset-0 opacity-[0.02] pointer-events-none"
                style="background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.15) 2px, rgba(0,0,0,0.15) 4px);"
            />

            <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-12 px-4 pb-16 pt-28 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
                <div>
                    <p class="atlas-kicker mb-5 animate-slide-up">Atlas acoustique du vivant</p>
                    <h1 class="atlas-heading mb-6 max-w-4xl text-6xl sm:text-7xl lg:text-8xl animate-slide-up">
                        Le vivant<br />
                        s'écoute.
                    </h1>

                    <p class="max-w-2xl text-lg leading-8 text-arbor-sage sm:text-xl animate-slide-up" style="animation-delay: 0.1s">
                        Explorez les sons naturels du monde comme des traces vivantes : lieux, espèces, saisons et mémoires audio capturés par une communauté de créateurs naturalistes.
                    </p>

                    <div class="mt-10 flex flex-col gap-4 sm:flex-row animate-slide-up" style="animation-delay: 0.2s">
                    <Link href="/map" class="btn-primary text-base px-8 py-4 w-full sm:w-auto group">
                        <svg class="w-5 h-5 mr-2 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        Explorer la carte
                    </Link>
                    <Link :href="route('sounds.create')" class="btn-secondary text-base px-8 py-4 w-full sm:w-auto group">
                        <svg class="w-5 h-5 mr-2 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Publier une trace
                    </Link>
                    </div>

                    <div class="mt-12 grid grid-cols-3 gap-3 max-w-xl animate-slide-up" style="animation-delay: 0.28s">
                        <div class="field-stat">
                            <div class="font-mono text-2xl text-arbor-cream tabular-nums">{{ props.stats.sounds }}+</div>
                            <div class="mt-1 text-[11px] uppercase tracking-[0.16em] text-arbor-sage">sons</div>
                        </div>
                        <div class="field-stat">
                            <div class="font-mono text-2xl text-arbor-cream tabular-nums">{{ props.stats.creators }}+</div>
                            <div class="mt-1 text-[11px] uppercase tracking-[0.16em] text-arbor-sage">créateurs</div>
                        </div>
                        <div class="field-stat">
                            <div class="font-mono text-2xl text-arbor-cream tabular-nums">{{ props.stats.countries }}+</div>
                            <div class="mt-1 text-[11px] uppercase tracking-[0.16em] text-arbor-sage">pays</div>
                        </div>
                    </div>
                </div>

                <div class="trace-frame hidden min-h-[520px] p-5 lg:block animate-slide-up" style="animation-delay: 0.18s">
                    <div class="relative z-10 flex h-full flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="atlas-kicker">Trace de l'atlas</span>
                            <span class="rounded-full border border-arbor-firefly/20 bg-arbor-firefly/10 px-3 py-1 font-mono text-[11px] text-arbor-firefly">
                                {{ heroTraceCode }}
                            </span>
                        </div>
                        <div class="my-10 grid flex-1 place-items-center">
                            <div class="sound-trace grid h-64 w-64 place-items-center rounded-full border border-arbor-mineral/10 bg-arbor-forest/50">
                                <div class="grid h-36 w-36 place-items-center rounded-full border border-arbor-lichen/20 bg-arbor-lichen/10">
                                    <div class="trace-wave h-16">
                                        <span></span><span></span><span></span><span></span><span></span><span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-lg border border-arbor-mineral/10 bg-arbor-ink/40 p-4">
                                <div class="text-xs text-arbor-sage">Son publié</div>
                                <Link
                                    v-if="heroTrace?.slug"
                                    :href="route('sounds.show', heroTrace.slug)"
                                    class="mt-1 block truncate font-display text-xl text-arbor-cream transition-colors hover:text-arbor-lichen"
                                >
                                    {{ heroTrace.title }}
                                </Link>
                                <div v-else class="mt-1 font-display text-xl text-arbor-cream">
                                    {{ mapLoading || soundsLoading ? 'Chargement...' : 'Aucune trace disponible' }}
                                </div>
                            </div>
                            <div class="rounded-lg border border-arbor-mineral/10 bg-arbor-ink/40 p-4">
                                <div class="text-xs text-arbor-sage">
                                    {{ heroTrace?.location ? 'Lieu public' : 'Signal' }}
                                </div>
                                <div class="mt-1 truncate font-mono text-xl text-arbor-firefly">
                                    {{ heroTrace?.location || formatDuration(heroTrace?.duration) }}
                                </div>
                            </div>
                        </div>
                        <div v-if="heroTrace?.category || heroTrace?.user" class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-arbor-mineral/10 bg-arbor-ink/30 px-4 py-3 text-xs text-arbor-sage">
                            <span class="truncate">{{ heroTrace.category || 'Archive sonore' }}</span>
                            <span class="truncate">{{ heroTrace.user || 'Créateur Arborisis' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scroll indicator -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-scroll-indicator" aria-hidden="true">
                <div class="flex flex-col items-center gap-2">
                    <span class="text-[10px] uppercase tracking-[0.2em] text-arbor-sage/60">Scroll</span>
                    <svg class="w-5 h-5 text-arbor-sage/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                </div>
            </div>
        </section>

        <!-- Atlas Pillars -->
        <section id="stats-section" class="border-y border-arbor-mineral/10 bg-arbor-ink/40 py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-10 max-w-3xl">
                    <p class="atlas-kicker mb-3">Explorer le vivant par le son</p>
                    <h2 class="atlas-heading text-4xl sm:text-5xl">Une carte, une archive, un laboratoire.</h2>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div
                        v-for="pillar in atlasPillars"
                        :key="pillar.title"
                        class="trace-frame p-6"
                    >
                        <div class="relative z-10">
                            <p class="atlas-kicker mb-5">{{ pillar.kicker }}</p>
                            <h3 class="font-display text-2xl font-semibold text-arbor-cream">{{ pillar.title }}</h3>
                            <p class="mt-4 text-sm leading-6 text-arbor-sage">{{ pillar.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Audio Demo Section -->
        <section class="py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div ref="audioDemoTitle.elementRef" :style="audioDemoTitle.style.value" class="mb-14 max-w-3xl">
                    <p class="atlas-kicker mb-3">Fragments de territoire</p>
                    <h2 class="atlas-heading text-4xl sm:text-5xl">
                        Des sons traités comme des archives vivantes.
                    </h2>
                    <p class="mt-5 text-arbor-sage max-w-2xl leading-7">
                        Chaque carte porte une trace : durée, créateur, contexte et empreinte visuelle du signal.
                    </p>
                </div>

                <!-- Loading skeletons -->
                <div v-if="soundsLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="n in 6" :key="n" class="glass-card overflow-hidden animate-pulse">
                        <div class="aspect-[16/9] bg-arbor-charcoal/60" />
                        <div class="p-4 space-y-3">
                            <div class="h-4 bg-arbor-charcoal/60 rounded w-3/4" />
                            <div class="h-3 bg-arbor-charcoal/60 rounded w-1/2" />
                        </div>
                    </div>
                </div>

                <!-- Sounds grid -->
                <div v-else-if="featuredSounds.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <SoundCard
                        v-for="sound in featuredSounds"
                        :key="sound.id"
                        :sound="sound"
                    />
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-16">
                    <div class="w-16 h-16 rounded-2xl bg-arbor-moss/10 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-arbor-moss/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </div>
                    <h3 class="font-display text-lg text-arbor-cream mb-2">L'archive s'éveille</h3>
                    <p class="text-arbor-sage max-w-sm mx-auto mb-6">
                        Les premiers enregistrements arrivent. Soyez parmi les premiers à capturer et partager les sons de la nature.
                    </p>
                    <Link href="/record" class="btn-primary inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                        </svg>
                        Enregistrer un son
                    </Link>
                </div>

                <div class="text-center mt-10">
                    <Link href="/sounds" class="btn-secondary inline-flex items-center gap-2 group">
                        Découvrir tous les sons
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Map Preview Section -->
        <section class="py-24 bg-arbor-deep/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <p class="atlas-kicker mb-4">Carte vivante</p>
                        <h2 class="atlas-heading text-4xl sm:text-5xl mb-6">
                            Le monde devient audible.
                        </h2>
                        <p class="text-arbor-sage mb-8 leading-relaxed">
                            Chaque point représente un moment capturé : chant à l'aube, rivière en crue, vent dans les cimes. Les coordonnées publiques restent approximées pour protéger les lieux sensibles.
                        </p>
                        <div class="flex flex-wrap gap-3 mb-8">
                            <span v-for="cat in categories" :key="cat.name" class="rounded-full border border-arbor-mineral/10 bg-arbor-mist/5 px-3 py-1.5 text-xs font-medium text-arbor-sage transition-all duration-200 hover:border-arbor-lichen/30 hover:text-arbor-cream cursor-pointer select-none">
                                {{ cat.name }}
                            </span>
                        </div>
                        <Link href="/map" class="btn-primary group">
                            Ouvrir la carte
                            <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                    </div>
                    <div class="trace-frame aspect-[16/10] relative overflow-hidden hover-lift group">
                        <div v-if="mapLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-arbor-deep/80 z-10">
                            <div class="w-10 h-10 border-2 border-arbor-emerald/30 border-t-arbor-emerald rounded-full animate-spin mb-4"></div>
                            <p class="text-arbor-sage text-sm">Chargement de la carte...</p>
                        </div>
                        <SoundMap
                            v-else
                            :sounds="mapSounds"
                            :initial-zoom="2"
                            :initial-center="[25, 10]"
                        />
                        <!-- Overlay for seamless blend -->
                        <div class="absolute inset-0 pointer-events-none rounded-xl ring-1 ring-inset ring-white/5"></div>
                        <!-- Hover overlay -->
                        <Link
                            href="/map"
                            class="absolute inset-0 bg-arbor-night/60 backdrop-blur-sm flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        >
                            <span class="btn-primary">
                                Ouvrir la carte en plein écran
                            </span>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Scientific Section -->
        <section class="relative overflow-hidden py-24">
            <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_18%_20%,rgba(120,214,214,0.08),transparent_24rem)]"></div>
            <div class="relative mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
                <div>
                    <p class="atlas-kicker mb-4">Laboratoire naturaliste</p>
                    <h2 class="atlas-heading text-4xl sm:text-5xl">
                        Beau à écouter. Sérieux à analyser.
                    </h2>
                    <p class="mt-6 max-w-xl text-arbor-sage leading-7">
                        Arborisis relie l'émotion du terrain à des données exploitables : spectres, biodiversité sonore, variations saisonnières et comparaisons d'un même lieu.
                    </p>
                    <Link href="/scientific-stats" class="btn-secondary mt-8">
                        Ouvrir les données scientifiques
                    </Link>
                </div>

                <div class="trace-frame p-6">
                    <div class="relative z-10">
                        <div class="mb-6 flex items-center justify-between">
                            <span class="atlas-kicker">Signaux observés</span>
                            <span class="font-mono text-xs text-arbor-sage">public / anonymisé</span>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="signal in scientificSignals"
                                :key="signal"
                                class="rounded-lg border border-arbor-mineral/10 bg-arbor-mist/[0.04] p-4"
                            >
                                <div class="trace-wave mb-4 h-9 opacity-80">
                                    <span></span><span></span><span></span><span></span><span></span><span></span>
                                </div>
                                <div class="text-sm font-medium text-arbor-cream">{{ signal }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works Section -->
        <section class="py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div ref="howItWorksTitle.elementRef" :style="howItWorksTitle.style.value" class="text-center mb-16">
                    <h2 class="font-display text-3xl sm:text-4xl font-bold text-arbor-cream mb-4">
                        Comment ça marche
                    </h2>
                    <p class="text-arbor-sage max-w-xl mx-auto">
                        Arborisis est simple. Trois étapes pour explorer et partager le monde sonore.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div
                        v-for="(step, index) in steps"
                        :key="step.number"
                        class="glass-card-glow p-8 text-center cursor-pointer"
                        :style="`animation: fadeInUp 0.6s ease-out forwards; animation-delay: ${index * 0.15}s; opacity: 0;`"
                    >
                        <div class="font-display text-5xl text-arbor-emerald/20 font-bold mb-4">
                            {{ step.number }}
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-arbor-moss/20 flex items-center justify-center mx-auto mb-6">
                            <svg class="w-6 h-6 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="step.icon" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-arbor-cream mb-3">{{ step.title }}</h3>
                        <p class="text-arbor-sage text-sm leading-relaxed">{{ step.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3D Nature Scene Section with Parallax -->
        <section data-parallax-section class="relative py-32 overflow-hidden min-h-[80vh] flex items-center">
            <!-- Three.js Nature Scene background -->
            <NatureScene class="absolute inset-0 z-0" />

            <!-- Gradient overlays for seamless blending -->
            <div class="absolute inset-0 bg-gradient-to-b from-arbor-night via-transparent to-arbor-night z-[1] pointer-events-none" />
            <div class="absolute inset-0 bg-arbor-night/30 z-[1] pointer-events-none" />

            <!-- Content overlay -->
            <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="glass-card-glow p-10 sm:p-14 inline-block max-w-2xl">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-arbor-moss/20 mb-6 ring-1 ring-arbor-emerald/20">
                        <svg class="w-7 h-7 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <h2 class="font-display text-3xl sm:text-4xl lg:text-5xl font-bold text-arbor-cream mb-5 leading-tight">
                        Une forêt sonore<br />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-arbor-emerald to-arbor-moss">en perpétuel mouvement</span>
                    </h2>
                    <p class="text-arbor-sage text-base sm:text-lg leading-relaxed max-w-lg mx-auto mb-8">
                        Chaque arbre, chaque feuille, chaque brise porte une mélodie.
                        Notre archive capture l'âme vivante des paysages naturels du monde entier.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link href="/sounds" class="btn-primary group">
                            <svg class="w-5 h-5 mr-2 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Écouter la forêt
                        </Link>
                        <Link href="/map" class="btn-secondary group">
                            Explorer sur la carte
                            <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Scroll hint -->
                <div class="mt-12 animate-scroll-indicator" aria-hidden="true">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-arbor-sage/40 mb-2">Déplacez votre souris et scrollez</p>
                    <svg class="w-5 h-5 text-arbor-sage/40 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                </div>
            </div>
        </section>

        <!-- Featured Creators Section -->
        <section class="py-24 bg-arbor-deep/30 border-y border-arbor-glass-border">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="font-display text-3xl sm:text-4xl font-bold text-arbor-cream mb-4">
                        Créateurs en avant
                    </h2>
                    <p class="text-arbor-sage max-w-xl mx-auto">
                        Rencontrez les enregistreurs qui donnent vie à l'archive.
                    </p>
                </div>

                <!-- Loading skeletons -->
                <div v-if="creatorsLoading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div v-for="n in 3" :key="n" class="glass-card p-6 animate-pulse">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-full bg-arbor-charcoal/60 shrink-0" />
                            <div class="flex-1 space-y-2">
                                <div class="h-4 bg-arbor-charcoal/60 rounded w-2/3" />
                                <div class="h-3 bg-arbor-charcoal/60 rounded w-1/2" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Creators grid -->
                <div v-else-if="featuredCreators.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <CreatorCard
                        v-for="creator in featuredCreators"
                        :key="creator.id"
                        :creator="creator"
                        :featured-sound="creator.featured_sound"
                    />
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-16">
                    <div class="w-16 h-16 rounded-2xl bg-arbor-moss/10 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-arbor-moss/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </div>
                    <h3 class="font-display text-lg text-arbor-cream mb-2">Les pionniers de l'écoute</h3>
                    <p class="text-arbor-sage max-w-sm mx-auto mb-6">
                        Les premiers créateurs rejoindront bientôt l'archive. Devenez l'un d'entre eux et faites entendre votre territoire.
                    </p>
                    <Link href="/register" class="btn-primary inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        Rejoindre la communauté
                    </Link>
                </div>

                <div v-if="featuredCreators.length > 0" class="text-center mt-10">
                    <Link href="/creators" class="btn-secondary inline-flex items-center gap-2 group">
                        Voir tous les créateurs
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ECHO Section -->
        <section class="py-24">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="glass-card-glow p-12 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-arbor-moss/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2" />
                    <div class="organic-blob w-48 h-48 bg-arbor-emerald/20 -top-10 -right-10" />
                    <div class="relative z-10">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-arbor-emerald/20 mb-6 animate-glow-pulse">
                            <span class="text-2xl font-display font-bold text-arbor-emerald">E</span>
                        </div>
                        <h2 class="font-display text-3xl font-bold text-arbor-cream mb-4">
                            Soutenez avec ECHO
                        </h2>
                        <p class="text-arbor-sage mb-8 max-w-lg mx-auto leading-relaxed">
                            Les crédits ECHO vous permettent de soutenir directement les créateurs qui capturent
                            et partagent les sons de la nature. Un geste simple, un impact réel.
                        </p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Link href="/echo" class="btn-primary">
                                Découvrir ECHO
                            </Link>
                            <span class="text-xs text-arbor-sage">
                                Pas une cryptomonnaie. Pas un investissement.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission Section -->
        <section class="py-24 bg-arbor-deep/30 border-y border-arbor-glass-border">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div ref="missionTitle.elementRef" :style="missionTitle.style.value">
                <h2 class="font-display text-3xl font-bold text-arbor-cream mb-6">
                    Préserver l'écoute du monde vivant
                </h2>
                <p class="text-arbor-sage leading-relaxed mb-8">
                    Arborisis naît d'une conviction : les paysages sonores naturels sont un patrimoine fragile
                    méritant d'être documenté, partagé et protégé. En donnant une voix aux espaces silencieux,
                    nous espérons susciter une écologie de l'attention et une reconnaissance envers
                    ceux qui consacrent leur temps à capturer ces instants éphémères.
                </p>
                <div class="flex items-center justify-center gap-2 text-arbor-emerald text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Respect de la nature. Confidentialité des lieux sensibles. Transparence.</span>
                </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-24">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="font-display text-3xl sm:text-4xl font-bold text-arbor-cream mb-6">
                    Rejoignez la communauté
                </h2>
                <p class="text-arbor-sage mb-10 max-w-xl mx-auto">
                    Créez votre profil, publiez vos premiers enregistrements et connectez-vous
                    avec d'autres passionnés de sons naturels.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link href="/register" class="btn-primary text-base px-8 py-4">
                        Créer un compte gratuit
                    </Link>
                    <Link href="/login" class="btn-secondary text-base px-8 py-4">
                        J'ai déjà un compte
                    </Link>
                </div>
            </div>
        </section>
    </GuestLayout>
</template>
