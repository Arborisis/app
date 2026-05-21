<script setup>
import { computed, ref, defineAsyncComponent } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import StatCard from '@/Components/Scientific/StatCard.vue';

const CategoryChart = defineAsyncComponent(() => import('@/Components/Scientific/CategoryChart.vue'));
const EnvironmentChart = defineAsyncComponent(() => import('@/Components/Scientific/EnvironmentChart.vue'));
const TemporalChart = defineAsyncComponent(() => import('@/Components/Scientific/TemporalChart.vue'));
const GeoHeatmap = defineAsyncComponent(() => import('@/Components/Scientific/GeoHeatmap.vue'));
const AudioFeaturesChart = defineAsyncComponent(() => import('@/Components/Scientific/AudioFeaturesChart.vue'));
const DataTable = defineAsyncComponent(() => import('@/Components/Scientific/DataTable.vue'));
const ApiDocs = defineAsyncComponent(() => import('@/Components/Scientific/ApiDocs.vue'));
const EquipmentChart = defineAsyncComponent(() => import('@/Components/Scientific/EquipmentChart.vue'));

const props = defineProps({
    stats: Object,
    categoryDistribution: Array,
    environmentDistribution: Array,
    temporalDistribution: Array,
    geoHeatmap: Array,
    audioFeatures: Object,
    audioFeatureDistribution: Object,
    topLocations: Array,
    equipmentDistribution: Array,
    rawDataSample: Array,
    listeningPoints: Object,
    speciesDistribution: Array,
    globalMetrics: Object,
    qualityOverview: Object,
    datasetCompleteness: Object,
    datasetSchema: Array,
    environmentalOverview: Object,
});

const activeTab = ref('overview');

const audioFeaturesSafe = computed(() => props.audioFeatures ?? {});
const audioDistributionsSafe = computed(() => props.audioFeatureDistribution ?? {});
const statsSafe = computed(() => props.stats ?? {});
const datasetCompletenessSafe = computed(() => props.datasetCompleteness ?? { fields: [], scientific_readiness_score: 0 });
const datasetSchemaSafe = computed(() => props.datasetSchema ?? []);
const qualityOverviewSafe = computed(() => props.qualityOverview ?? {});
const modelWeatherContext = computed(() => props.globalMetrics?.weather_context ?? props.environmentalOverview ?? {});

const datasetSummary = computed(() => {
    const fields = datasetCompletenessSafe.value.fields ?? [];
    const criticalFields = ['recorded_at', 'duration', 'category', 'environment', 'public_location', 'completed_analysis', 'species_detection'];
    const criticalCoverage = fields.filter((field) => criticalFields.includes(field.field));
    const meanCriticalCoverage = criticalCoverage.length
        ? Math.round(criticalCoverage.reduce((sum, field) => sum + Number(field.percentage ?? 0), 0) / criticalCoverage.length)
        : 0;

    return {
        readiness: Math.round(Number(datasetCompletenessSafe.value.scientific_readiness_score ?? 0)),
        rows: Number(datasetCompletenessSafe.value.sounds_count ?? statsSafe.value.total_sounds ?? 0),
        columns: datasetSchemaSafe.value.length,
        criticalCoverage: meanCriticalCoverage,
        fields,
    };
});

const modelCards = computed(() => [
    {
        key: 'biodiversity_score',
        title: 'SBS',
        name: 'Sound Biodiversity Score',
        metric: props.globalMetrics?.biodiversity_score ?? {},
        desc: 'Score descriptif combinant espèces détectées, diversité acoustique et équilibre spectral.',
        endpoint: '/api/scientific-stats/model-stats',
    },
    {
        key: 'acoustic_activity_score',
        title: 'AAS',
        name: 'Acoustic Activity Score',
        metric: props.globalMetrics?.acoustic_activity_score ?? {},
        desc: 'Score descriptif basé sur loudness, événements acoustiques, silence et énergie RMS.',
        endpoint: '/api/scientific-stats/model-stats',
    },
]);

const modelWeatherSummary = computed(() => ({
    coverage: modelWeatherContext.value.coverage ?? {},
    averages: modelWeatherContext.value.averages ?? {},
    conditions: modelWeatherContext.value.weather_conditions ?? [],
    activityByCondition: modelWeatherContext.value.activity_by_weather_condition ?? [],
    biodiversityByCondition: modelWeatherContext.value.biodiversity_by_weather_condition ?? [],
    individualSounds: modelWeatherContext.value.individual_sounds ?? [],
}));

const audioFeatureRows = computed(() => Object.entries(audioFeaturesSafe.value).map(([key, stats]) => ({
    key,
    label: featureLabels[key] ?? key,
    description: featureDescriptions[key] ?? 'Feature acoustique extraite automatiquement du signal.',
    stats,
})));

const audioSummary = computed(() => {
    const totalAnalyses = Number(statsSafe.value.total_analyses ?? 0);
    const completedAnalyses = Number(statsSafe.value.completed_analyses ?? 0);
    const totalSounds = Number(statsSafe.value.total_sounds ?? 0);
    const featureCount = audioFeatureRows.value.length;
    const distributionCount = Object.keys(audioDistributionsSafe.value).length;

    return {
        totalAnalyses,
        completedAnalyses,
        pendingAnalyses: Math.max(totalAnalyses - completedAnalyses, 0),
        coverage: totalSounds > 0 ? Math.round((completedAnalyses / totalSounds) * 100) : 0,
        featureCount,
        distributionCount,
        hasFeatures: featureCount > 0,
    };
});

const featureLabels = {
    zcr: 'Zero Crossing Rate',
    rms: 'RMS Energy',
    spectral_centroid: 'Centroide spectral',
    spectral_rolloff: 'Rolloff spectral',
    spectral_bandwidth: 'Bande passante spectrale',
    zero_crossing_rate: 'Zero Crossing Rate',
};

const featureDescriptions = {
    zcr: 'Taux de passages par zéro, utile pour distinguer textures bruitées, impulsions et signaux tonals.',
    rms: 'Énergie moyenne du signal, indicateur de présence sonore et de dynamique globale.',
    spectral_centroid: 'Centre de gravité fréquentiel, souvent perçu comme la brillance du son.',
    spectral_rolloff: 'Fréquence sous laquelle se concentre la majorité de l’énergie spectrale.',
    spectral_bandwidth: 'Dispersion du spectre autour du centroide, liée à la richesse fréquentielle.',
    zero_crossing_rate: 'Alias du taux de passages par zéro conservé pour compatibilité dataset.',
};

const pipelineSteps = [
    { title: 'Ingestion', desc: 'Les sons publics publiés sont indexés sans exposer les fichiers privés ni les coordonnées exactes.' },
    { title: 'Prétraitement', desc: 'Décodage audio, normalisation et extraction de fenêtres temporelles adaptées aux sons naturels.' },
    { title: 'Features', desc: 'Calcul de métriques temporelles et spectrales pour comparer les paysages sonores.' },
    { title: 'Agrégation', desc: 'Publication de statistiques anonymisées, distributions et échantillons limités pour la recherche.' },
];

const qualityChecks = [
    'Données limitées aux sons publics et publiés.',
    'Coordonnées exactes exclues des exports publics.',
    'Agrégations géographiques arrondies avant affichage.',
    'API conçue pour des analyses reproductibles et non intrusives.',
];

const tabs = [
    { key: 'overview', label: 'Vue d\'ensemble' },
    { key: 'dataset', label: 'Dataset' },
    { key: 'listening-points', label: 'Points d\'écoute' },
    { key: 'species', label: 'Espèces' },
    { key: 'models', label: 'Modèles' },
    { key: 'geo', label: 'Géographie' },
    { key: 'audio', label: 'Analyse audio' },
    { key: 'data', label: 'Données brutes' },
    { key: 'api', label: 'API' },
];

function formatDuration(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h}h ${m}m`;
    return `${m}m`;
}
</script>

<template>
    <Head title="Données scientifiques & statistiques" />
    <GuestLayout>
        <div class="min-h-screen bg-arbor-night">
            <!-- Hero -->
            <div class="relative overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_16%_0%,rgba(120,214,214,0.09),transparent_28rem),radial-gradient(circle_at_82%_12%,rgba(215,180,106,0.09),transparent_24rem)] pointer-events-none"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-10">
                    <div class="trace-frame p-6 sm:p-8">
                    <div class="relative z-10 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="inline-block w-2 h-2 rounded-full bg-arbor-firefly animate-pulse"></span>
                                <span class="atlas-kicker">Laboratoire naturaliste</span>
                            </div>
                            <h1 class="atlas-heading text-5xl md:text-6xl">
                                Données scientifiques du vivant sonore
                            </h1>
                            <p class="mt-3 text-arbor-sage text-lg max-w-2xl">
                                Dataset public, documenté et anonymisé pour observer les espèces, les lieux, les saisons et les dynamiques acoustiques sans exposer les coordonnées sensibles.
                            </p>
                        </div>
                        <div class="text-right hidden md:block">
                            <p class="text-xs text-arbor-sage/70 font-mono">
                                Mis à jour : {{ new Date().toLocaleDateString('fr-FR') }}
                            </p>
                            <p class="text-xs text-arbor-sage/70 font-mono">
                                {{ props.stats.total_sounds?.toLocaleString('fr-FR') }} sons indexés
                            </p>
                        </div>
                    </div>
                    </div>
                </div>
            </div>

            <!-- Stats row -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <StatCard label="Enregistrements" :value="props.stats.total_sounds" icon="audio" />
                    <StatCard label="Durée totale" :value="Math.round((props.stats.total_duration_seconds || 0) / 3600)" suffix="h" icon="time" />
                    <StatCard label="Créateurs" :value="props.stats.total_creators" icon="users" />
                    <StatCard label="Lieux uniques" :value="props.stats.total_locations" icon="location" />
                    <StatCard label="Analyses" :value="props.stats.completed_analyses" icon="data" />
                    <StatCard label="Score dataset" :value="datasetSummary.readiness" suffix="%" icon="score" />
                </div>
            </div>

            <!-- Tabs -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-4">
                <div class="flex flex-wrap gap-1 p-1 rounded-xl bg-arbor-deep/60 border border-arbor-glass-border backdrop-blur-sm">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200',
                            activeTab === tab.key
                                ? 'bg-arbor-moss/20 text-arbor-emerald border border-arbor-emerald/30'
                                : 'text-arbor-sage hover:text-arbor-cream hover:bg-arbor-glass/30',
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <!-- Tab content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
                <!-- Overview -->
                <div v-if="activeTab === 'overview'" class="space-y-6 animate-fade-in">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <CategoryChart :categories="props.categoryDistribution" />
                        <EnvironmentChart :data="props.environmentDistribution" />
                    </div>
                    <TemporalChart :data="props.temporalDistribution" />
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Lieux les plus enregistrés</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="(loc, i) in props.topLocations.slice(0, 10)"
                                    :key="i"
                                    class="flex items-center justify-between p-2.5 rounded-lg bg-arbor-deep/50 border border-arbor-glass-border/50 hover:border-arbor-emerald/30 transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-mono text-arbor-sage w-5">{{ i + 1 }}</span>
                                        <span class="text-sm text-arbor-cream">{{ loc.name }}</span>
                                    </div>
                                    <span class="text-xs font-mono text-arbor-emerald">{{ loc.count }}</span>
                                </div>
                            </div>
                        </div>
                        <EquipmentChart :data="props.equipmentDistribution" />
                    </div>
                </div>

                <!-- Dataset -->
                <div v-if="activeTab === 'dataset'" class="space-y-6 animate-fade-in">
                    <div class="grid grid-cols-1 lg:grid-cols-[1.15fr_0.85fr] gap-6">
                        <div class="glass-card p-6 overflow-hidden relative">
                            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-arbor-emerald/70 to-transparent"></div>
                            <p class="text-xs uppercase tracking-widest text-arbor-emerald mb-3">Dataset chercheur</p>
                            <h3 class="font-display text-2xl font-semibold text-arbor-cream mb-3">Table publique exploitable en notebook</h3>
                            <p class="text-sm leading-relaxed text-arbor-sage max-w-3xl">
                                L'endpoint dataset retourne des lignes normalisées, un schéma machine-readable, la pagination, la licence, la citation et les garanties de confidentialité. Les IDs utilisateur, clés fichiers privées et coordonnées exactes sont exclus.
                            </p>
                            <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div class="rounded-xl bg-arbor-deep/45 border border-arbor-glass-border p-4">
                                    <p class="text-xs uppercase tracking-wider text-arbor-sage">Lignes</p>
                                    <p class="mt-2 text-2xl font-bold text-arbor-cream font-mono">{{ datasetSummary.rows.toLocaleString('fr-FR') }}</p>
                                </div>
                                <div class="rounded-xl bg-arbor-deep/45 border border-arbor-glass-border p-4">
                                    <p class="text-xs uppercase tracking-wider text-arbor-sage">Colonnes</p>
                                    <p class="mt-2 text-2xl font-bold text-arbor-cream font-mono">{{ datasetSummary.columns }}</p>
                                </div>
                                <div class="rounded-xl bg-arbor-deep/45 border border-arbor-glass-border p-4">
                                    <p class="text-xs uppercase tracking-wider text-arbor-sage">Readiness</p>
                                    <p class="mt-2 text-2xl font-bold text-arbor-emerald font-mono">{{ datasetSummary.readiness }}%</p>
                                </div>
                                <div class="rounded-xl bg-arbor-deep/45 border border-arbor-glass-border p-4">
                                    <p class="text-xs uppercase tracking-wider text-arbor-sage">Critiques</p>
                                    <p class="mt-2 text-2xl font-bold text-arbor-emerald font-mono">{{ datasetSummary.criticalCoverage }}%</p>
                                </div>
                            </div>
                        </div>

                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Accès direct</h3>
                            <div class="space-y-3">
                                <div class="rounded-xl bg-arbor-deep/45 border border-arbor-glass-border p-4">
                                    <p class="text-xs text-arbor-sage mb-2">Dataset paginé</p>
                                    <code class="text-sm text-arbor-emerald break-all">/api/scientific-stats/dataset?limit=100&offset=0</code>
                                </div>
                                <div class="rounded-xl bg-arbor-deep/45 border border-arbor-glass-border p-4">
                                    <p class="text-xs text-arbor-sage mb-2">Schéma de colonnes</p>
                                    <code class="text-sm text-arbor-emerald break-all">/api/scientific-stats/schema</code>
                                </div>
                                <div class="rounded-xl bg-arbor-amber/10 border border-arbor-amber/25 p-4">
                                    <p class="text-sm leading-relaxed text-arbor-sage">Citation incluse dans la réponse API. Les données restent limitées aux sons publics publiés et aux coordonnées approximées.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Complétude des champs</h3>
                            <div class="space-y-3">
                                <div v-for="field in datasetSummary.fields" :key="field.field" class="rounded-xl bg-arbor-deep/35 border border-arbor-glass-border/60 p-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <code class="text-xs text-arbor-cream">{{ field.field }}</code>
                                        <span class="text-xs font-mono text-arbor-emerald">{{ Math.round(field.percentage) }}%</span>
                                    </div>
                                    <div class="mt-2 h-1.5 rounded-full bg-arbor-night overflow-hidden">
                                        <div class="h-full rounded-full bg-arbor-emerald" :style="{ width: `${Math.min(field.percentage, 100)}%` }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Dictionnaire de données</h3>
                            <div class="space-y-2 max-h-[520px] overflow-y-auto custom-scrollbar pr-2">
                                <div v-for="column in datasetSchemaSafe" :key="column.name" class="rounded-xl bg-arbor-deep/35 border border-arbor-glass-border/60 p-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <code class="text-xs text-arbor-emerald">{{ column.name }}</code>
                                        <span class="text-[11px] uppercase tracking-wider text-arbor-sage">{{ column.type }}</span>
                                        <span v-if="column.unit !== 'none'" class="text-[11px] text-arbor-sage/70">{{ column.unit }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-arbor-sage">{{ column.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Listening Points -->
                <div v-if="activeTab === 'listening-points'" class="space-y-6 animate-fade-in">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="glass-card p-5 text-center">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">Points d'écoute</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-cream">{{ props.listeningPoints?.total_points ?? 0 }}</p>
                        </div>
                        <div class="glass-card p-5 text-center">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">Avec enregistrements</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-emerald">{{ props.listeningPoints?.points_with_recordings ?? 0 }}</p>
                        </div>
                        <div class="glass-card p-5 text-center">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">Habitats</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-cream">{{ props.listeningPoints?.by_habitat?.length ?? 0 }}</p>
                        </div>
                        <div class="glass-card p-5 text-center">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">Espèces détectées</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-emerald">{{ props.stats?.total_species ?? props.speciesDistribution?.length ?? 0 }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Répartition par habitat</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="(item, i) in props.listeningPoints?.by_habitat"
                                    :key="item.habitat"
                                    class="flex items-center justify-between p-2 rounded-lg bg-arbor-deep/30"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-mono text-arbor-sage">{{ i + 1 }}</span>
                                        <span class="text-sm text-arbor-cream capitalize">{{ item.habitat }}</span>
                                    </div>
                                    <span class="text-xs font-mono text-arbor-emerald">{{ item.count }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Points les plus actifs</h3>
                            <div class="space-y-2">
                                <Link
                                    v-for="item in props.listeningPoints?.most_active"
                                    :key="item.slug"
                                    :href="route('listening-points.show', item.slug)"
                                    class="flex items-center justify-between p-2.5 rounded-lg bg-arbor-deep/30 hover:bg-arbor-deep/50 transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-arbor-cream">{{ item.title }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-arbor-sage">{{ item.species_count }} esp.</span>
                                        <span class="text-xs font-mono text-arbor-emerald">{{ item.recordings_count }}</span>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Species -->
                <div v-if="activeTab === 'species'" class="space-y-6 animate-fade-in">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="glass-card p-5 text-center">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">Espèces détectées</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-cream">{{ props.speciesDistribution?.length ?? 0 }}</p>
                        </div>
                        <div class="glass-card p-5 text-center">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">Détections totales</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-emerald">{{ props.speciesDistribution?.reduce((a, s) => a + s.detections_count, 0) ?? 0 }}</p>
                        </div>
                        <div class="glass-card p-5 text-center">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">Confiance moy.</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-cream">
                                {{ props.speciesDistribution?.length
                                    ? Math.round((props.speciesDistribution.reduce((a, s) => a + s.mean_confidence, 0) / props.speciesDistribution.length) * 100)
                                    : 0 }}%
                            </p>
                        </div>
                        <div class="glass-card p-5 text-center">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">Sons concernés</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-emerald">{{ props.speciesDistribution?.reduce((a, s) => a + s.sounds_count, 0) ?? 0 }}</p>
                        </div>
                    </div>

                    <div class="glass-card p-6">
                        <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Top espèces détectées</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="s in props.speciesDistribution?.slice(0, 30)"
                                :key="s.scientific_name"
                                class="p-4 rounded-xl bg-arbor-deep/30 border border-arbor-glass-border/30"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="text-sm font-medium text-arbor-cream">{{ s.common_name }}</h4>
                                        <p class="text-xs text-arbor-sage italic">{{ s.scientific_name }}</p>
                                    </div>
                                    <span class="text-xs font-mono text-arbor-emerald">{{ s.sounds_count }} son{{ s.sounds_count > 1 ? 's' : '' }}</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between text-xs text-arbor-sage">
                                    <span>{{ s.detections_count }} détection{{ s.detections_count > 1 ? 's' : '' }}</span>
                                    <span>conf. {{ Math.round(s.mean_confidence * 100) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Models -->
                <div v-if="activeTab === 'models'" class="space-y-6 animate-fade-in">
                    <div class="glass-card p-6">
                        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
                            <div>
                                <p class="text-xs uppercase tracking-widest text-arbor-emerald mb-2">Model stats</p>
                                <h3 class="font-display text-2xl font-semibold text-arbor-cream">Indicateurs descriptifs versionnés</h3>
                                <p class="mt-2 text-sm leading-relaxed text-arbor-sage max-w-3xl">
                                    Les modèles Arborisis sont publiés comme variables explicables du dataset, pas comme vérité naturaliste. Chaque score expose son échantillon, ses bornes et son endpoint API.
                                </p>
                            </div>
                            <div class="rounded-xl border border-arbor-emerald/25 bg-arbor-emerald/10 px-4 py-3">
                                <p class="text-xs uppercase tracking-wider text-arbor-sage">Endpoint</p>
                                <code class="mt-1 block text-sm text-arbor-emerald">/api/scientific-stats/model-stats</code>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div
                                v-for="model in modelCards"
                                :key="model.key"
                                class="rounded-xl border border-arbor-glass-border bg-arbor-deep/35 p-5"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-mono text-arbor-emerald">{{ model.title }}</p>
                                        <h4 class="mt-1 text-lg font-semibold text-arbor-cream">{{ model.name }}</h4>
                                        <p class="mt-2 text-sm leading-relaxed text-arbor-sage">{{ model.desc }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs uppercase tracking-wider text-arbor-sage">N</p>
                                        <p class="mt-1 text-2xl font-bold text-arbor-cream font-mono">{{ model.metric.count ?? 0 }}</p>
                                    </div>
                                </div>
                                <div class="mt-5 grid grid-cols-2 md:grid-cols-4 gap-2">
                                    <div class="rounded-lg bg-arbor-night/70 p-3">
                                        <p class="text-[11px] uppercase tracking-wider text-arbor-sage">Moy.</p>
                                        <p class="mt-1 text-sm font-mono text-arbor-cream">{{ model.metric.mean ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-lg bg-arbor-night/70 p-3">
                                        <p class="text-[11px] uppercase tracking-wider text-arbor-sage">Méd.</p>
                                        <p class="mt-1 text-sm font-mono text-arbor-cream">{{ model.metric.median ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-lg bg-arbor-night/70 p-3">
                                        <p class="text-[11px] uppercase tracking-wider text-arbor-sage">Min</p>
                                        <p class="mt-1 text-sm font-mono text-arbor-cream">{{ model.metric.min ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-lg bg-arbor-night/70 p-3">
                                        <p class="text-[11px] uppercase tracking-wider text-arbor-sage">Max</p>
                                        <p class="mt-1 text-sm font-mono text-arbor-cream">{{ model.metric.max ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card p-6">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
                            <div>
                                <h3 class="font-display text-xl font-semibold text-arbor-cream">Contexte météo des modèles</h3>
                                <p class="mt-2 text-sm leading-relaxed text-arbor-sage max-w-3xl">
                                    Les scores sont croisés avec les observations météo publiques issues des coordonnées approximées. Ces variables servent à contextualiser l'activité acoustique et la biodiversité sonore.
                                </p>
                            </div>
                            <div class="rounded-xl border border-arbor-glass-border bg-arbor-deep/45 px-4 py-3">
                                <p class="text-xs uppercase tracking-wider text-arbor-sage">Couverture météo</p>
                                <p class="mt-1 text-2xl font-bold text-arbor-emerald font-mono">{{ Math.round(modelWeatherSummary.coverage.percentage ?? 0) }}%</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="rounded-xl border border-arbor-glass-border bg-arbor-deep/35 p-4">
                                <p class="text-xs uppercase tracking-wider text-arbor-sage">Température moyenne</p>
                                <p class="mt-2 text-xl font-semibold text-arbor-cream font-mono">{{ modelWeatherSummary.averages.temperature_c ?? '-' }}<span v-if="modelWeatherSummary.averages.temperature_c !== null && modelWeatherSummary.averages.temperature_c !== undefined"> °C</span></p>
                            </div>
                            <div class="rounded-xl border border-arbor-glass-border bg-arbor-deep/35 p-4">
                                <p class="text-xs uppercase tracking-wider text-arbor-sage">Humidité moyenne</p>
                                <p class="mt-2 text-xl font-semibold text-arbor-cream font-mono">{{ modelWeatherSummary.averages.humidity_percent ?? '-' }}<span v-if="modelWeatherSummary.averages.humidity_percent !== null && modelWeatherSummary.averages.humidity_percent !== undefined">%</span></p>
                            </div>
                            <div class="rounded-xl border border-arbor-glass-border bg-arbor-deep/35 p-4">
                                <p class="text-xs uppercase tracking-wider text-arbor-sage">Vent moyen</p>
                                <p class="mt-2 text-xl font-semibold text-arbor-cream font-mono">{{ modelWeatherSummary.averages.wind_speed_kmh ?? '-' }}<span v-if="modelWeatherSummary.averages.wind_speed_kmh !== null && modelWeatherSummary.averages.wind_speed_kmh !== undefined"> km/h</span></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="rounded-xl border border-arbor-glass-border bg-arbor-deep/35 p-4">
                                <h4 class="text-sm font-semibold text-arbor-cream mb-3">Conditions météo</h4>
                                <div class="space-y-2">
                                    <div v-for="condition in modelWeatherSummary.conditions.slice(0, 8)" :key="condition.condition" class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-arbor-sage">{{ condition.condition }}</span>
                                        <span class="font-mono text-arbor-emerald">{{ condition.count }}</span>
                                    </div>
                                    <p v-if="modelWeatherSummary.conditions.length === 0" class="text-sm text-arbor-sage">Aucune météo associée pour le moment.</p>
                                </div>
                            </div>

                            <div class="rounded-xl border border-arbor-glass-border bg-arbor-deep/35 p-4">
                                <h4 class="text-sm font-semibold text-arbor-cream mb-3">AAS par météo</h4>
                                <div class="space-y-2">
                                    <div v-for="row in modelWeatherSummary.activityByCondition.slice(0, 8)" :key="row.condition" class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-arbor-sage">{{ row.condition }}</span>
                                        <span class="font-mono text-arbor-emerald">{{ row.mean_acoustic_activity_score }}</span>
                                    </div>
                                    <p v-if="modelWeatherSummary.activityByCondition.length === 0" class="text-sm text-arbor-sage">Aucun score AAS croisé météo.</p>
                                </div>
                            </div>

                            <div class="rounded-xl border border-arbor-glass-border bg-arbor-deep/35 p-4">
                                <h4 class="text-sm font-semibold text-arbor-cream mb-3">SBS par météo</h4>
                                <div class="space-y-2">
                                    <div v-for="row in modelWeatherSummary.biodiversityByCondition.slice(0, 8)" :key="row.condition" class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-arbor-sage">{{ row.condition }}</span>
                                        <span class="font-mono text-arbor-emerald">{{ row.mean_value }}</span>
                                    </div>
                                    <p v-if="modelWeatherSummary.biodiversityByCondition.length === 0" class="text-sm text-arbor-sage">Aucun score SBS croisé météo.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 rounded-xl border border-arbor-glass-border bg-arbor-deep/35 p-4">
                            <div class="flex items-center justify-between gap-4 mb-3">
                                <h4 class="text-sm font-semibold text-arbor-cream">Sons individuels enrichis</h4>
                                <span class="text-xs font-mono text-arbor-emerald">{{ modelWeatherSummary.individualSounds.length }}</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-xs text-arbor-sage uppercase border-b border-arbor-glass-border">
                                        <tr>
                                            <th class="px-3 py-2">Son</th>
                                            <th class="px-3 py-2">Coordonnées publiques</th>
                                            <th class="px-3 py-2">Temp.</th>
                                            <th class="px-3 py-2">Humidité</th>
                                            <th class="px-3 py-2">Vent</th>
                                            <th class="px-3 py-2">Condition</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="sound in modelWeatherSummary.individualSounds.slice(0, 25)" :key="sound.sound_id" class="border-b border-arbor-glass-border/40">
                                            <td class="px-3 py-2 text-arbor-cream max-w-[260px] truncate">{{ sound.title }}</td>
                                            <td class="px-3 py-2 text-arbor-sage font-mono text-xs">
                                                {{ Number(sound.public_latitude).toFixed(2) }}, {{ Number(sound.public_longitude).toFixed(2) }}
                                            </td>
                                            <td class="px-3 py-2 text-arbor-sage font-mono">{{ sound.weather?.temperature_c ?? '-' }}</td>
                                            <td class="px-3 py-2 text-arbor-sage font-mono">{{ sound.weather?.humidity_percent ?? '-' }}</td>
                                            <td class="px-3 py-2 text-arbor-sage font-mono">{{ sound.weather?.wind_speed_kmh ?? '-' }}</td>
                                            <td class="px-3 py-2 text-arbor-emerald">{{ sound.weather?.weather_condition ?? 'à enrichir' }}</td>
                                        </tr>
                                        <tr v-if="modelWeatherSummary.individualSounds.length === 0">
                                            <td colspan="6" class="px-3 py-6 text-center text-arbor-sage">Aucun son public avec météo individuelle pour le moment.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Score de biodiversité sonore (SBS)</h3>
                            <div v-if="props.globalMetrics?.biodiversity_score?.count" class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-arbor-sage">Moyenne</span>
                                    <span class="text-arbor-cream font-mono">{{ props.globalMetrics.biodiversity_score.mean }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-arbor-sage">Médiane</span>
                                    <span class="text-arbor-cream font-mono">{{ props.globalMetrics.biodiversity_score.median }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-arbor-sage">Min / Max</span>
                                    <span class="text-arbor-cream font-mono">{{ props.globalMetrics.biodiversity_score.min }} / {{ props.globalMetrics.biodiversity_score.max }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-arbor-sage">Échantillon</span>
                                    <span class="text-arbor-cream font-mono">{{ props.globalMetrics.biodiversity_score.count }}</span>
                                </div>
                            </div>
                            <p v-else class="text-sm text-arbor-sage">Aucune métrique de biodiversité calculée pour le moment.</p>
                        </div>
                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Score d'activité acoustique (AAS)</h3>
                            <div v-if="props.globalMetrics?.acoustic_activity_score?.count" class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-arbor-sage">Moyenne</span>
                                    <span class="text-arbor-cream font-mono">{{ props.globalMetrics.acoustic_activity_score.mean }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-arbor-sage">Médiane</span>
                                    <span class="text-arbor-cream font-mono">{{ props.globalMetrics.acoustic_activity_score.median }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-arbor-sage">Min / Max</span>
                                    <span class="text-arbor-cream font-mono">{{ props.globalMetrics.acoustic_activity_score.min }} / {{ props.globalMetrics.acoustic_activity_score.max }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-arbor-sage">Échantillon</span>
                                    <span class="text-arbor-cream font-mono">{{ props.globalMetrics.acoustic_activity_score.count }}</span>
                                </div>
                            </div>
                            <p v-else class="text-sm text-arbor-sage">Aucune métrique d'activité calculée pour le moment.</p>
                        </div>
                    </div>

                    <div class="glass-card p-6">
                        <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Méthodologie des modèles</h3>
                        <div class="space-y-4 text-sm text-arbor-sage">
                            <p>Les scores présentés sont des <strong class="text-arbor-cream">indicateurs descriptifs</strong> calculés à partir des features audio et des détections d'espèces. Ils ne remplacent pas une expertise naturaliste.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div class="p-4 rounded-xl bg-arbor-deep/30 border border-arbor-glass-border/30">
                                    <h4 class="text-arbor-cream font-medium mb-2">SBS — Sound Biodiversity Score</h4>
                                    <p>Combinaison pondérée du nombre d'espèces, de l'ADI (Acoustic Diversity Index), de la diversité des tags et de l'équilibre spectral.</p>
                                </div>
                                <div class="p-4 rounded-xl bg-arbor-deep/30 border border-arbor-glass-border/30">
                                    <h4 class="text-arbor-cream font-medium mb-2">AAS — Acoustic Activity Score</h4>
                                    <p>Basé sur la loudness LUFS, la densité d'événements sonores, le ratio de silence et l'énergie RMS moyenne.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Geography -->
                <div v-if="activeTab === 'geo'" class="space-y-6 animate-fade-in">
                    <GeoHeatmap :points="props.geoHeatmap" />
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="glass-card p-6 lg:col-span-2">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Densité par région</h3>
                            <div class="space-y-2 max-h-[400px] overflow-y-auto custom-scrollbar pr-2">
                                <div
                                    v-for="(loc, i) in props.geoHeatmap.slice(0, 50)"
                                    :key="i"
                                    class="flex items-center justify-between p-2 rounded-lg bg-arbor-deep/30"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-mono text-arbor-sage">{{ i + 1 }}</span>
                                        <span class="text-sm text-arbor-cream font-mono">{{ loc.lat.toFixed(1) }}, {{ loc.lng.toFixed(1) }}</span>
                                        <span class="text-xs text-arbor-sage/70 truncate max-w-[200px]">{{ loc.categories }}</span>
                                    </div>
                                    <span class="text-xs font-mono text-arbor-emerald">{{ loc.count }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Méthodologie</h3>
                            <div class="space-y-3 text-sm text-arbor-sage">
                                <p>Les coordonnées sont agrégées par cellules de <strong class="text-arbor-cream">0.1°</strong> (environ 11 km) pour préserver la confidentialité des lieux sensibles.</p>
                                <p>Les coordonnées exactes des enregistrements ne sont jamais exposées publiquement.</p>
                                <p>Seuls les enregistrements <strong class="text-arbor-cream">publics</strong> et <strong class="text-arbor-cream">publiés</strong> sont comptabilisés.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Audio -->
                <div v-if="activeTab === 'audio'" class="space-y-6 animate-fade-in">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                        <div class="glass-card p-5">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">Analyses terminées</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-cream">{{ audioSummary.completedAnalyses.toLocaleString('fr-FR') }}</p>
                            <p class="mt-1 text-xs text-arbor-sage">sur {{ audioSummary.totalAnalyses.toLocaleString('fr-FR') }} analyses créées</p>
                        </div>
                        <div class="glass-card p-5">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">Couverture dataset</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-emerald">{{ audioSummary.coverage }}%</p>
                            <div class="mt-3 h-1.5 rounded-full bg-arbor-deep overflow-hidden">
                                <div class="h-full rounded-full bg-arbor-emerald" :style="{ width: `${Math.min(audioSummary.coverage, 100)}%` }"></div>
                            </div>
                        </div>
                        <div class="glass-card p-5">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">Features publiées</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-cream">{{ audioSummary.featureCount }}</p>
                            <p class="mt-1 text-xs text-arbor-sage">{{ audioSummary.distributionCount }} distributions disponibles</p>
                        </div>
                        <div class="glass-card p-5">
                            <p class="text-xs uppercase tracking-wider text-arbor-sage">File d'attente</p>
                            <p class="mt-2 font-display text-3xl font-bold text-arbor-amber">{{ audioSummary.pendingAnalyses.toLocaleString('fr-FR') }}</p>
                            <p class="mt-1 text-xs text-arbor-sage">analyses non finalisées</p>
                        </div>
                    </div>

                    <div v-if="audioSummary.hasFeatures" class="space-y-6">
                        <AudioFeaturesChart :features="audioFeaturesSafe" :distributions="audioDistributionsSafe" />
                    </div>

                    <div v-else class="glass-card p-8 overflow-hidden relative">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-arbor-emerald/60 to-transparent"></div>
                        <div class="grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-8 items-center">
                            <div>
                                <p class="text-xs uppercase tracking-widest text-arbor-emerald mb-3">Analyse audio</p>
                                <h3 class="font-display text-2xl font-semibold text-arbor-cream mb-3">Aucune feature exploitable n'est encore publiée</h3>
                                <p class="text-sm leading-relaxed text-arbor-sage">
                                    La page reste prête pour les résultats du pipeline. Dès que les analyses auront des métriques dans
                                    <code class="px-1.5 py-0.5 rounded bg-arbor-deep text-arbor-cream">features_json</code>, les graphiques,
                                    histogrammes et statistiques descriptives apparaîtront automatiquement.
                                </p>
                            </div>
                            <div class="rounded-xl border border-arbor-glass-border bg-arbor-deep/50 p-5">
                                <p class="text-xs uppercase tracking-wider text-arbor-sage mb-4">À vérifier côté pipeline</p>
                                <div class="space-y-3">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-1 h-2 w-2 rounded-full bg-arbor-emerald"></span>
                                        <p class="text-sm text-arbor-sage">Les analyses sont marquées comme traitées via <code class="text-arbor-cream">processed_at</code>.</p>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="mt-1 h-2 w-2 rounded-full bg-arbor-amber"></span>
                                        <p class="text-sm text-arbor-sage">Le JSON contient les chemins attendus : temporal, spectral, stats et values.</p>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="mt-1 h-2 w-2 rounded-full bg-arbor-moss"></span>
                                        <p class="text-sm text-arbor-sage">Les sons associés sont publics et publiés, sinon ils restent exclus.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Pipeline d'analyse</h3>
                            <div class="space-y-4">
                                <div
                                    v-for="(step, index) in pipelineSteps"
                                    :key="step.title"
                                    class="flex gap-4 rounded-xl border border-arbor-glass-border/70 bg-arbor-deep/35 p-4"
                                >
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-arbor-emerald/10 text-xs font-mono text-arbor-emerald">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-arbor-cream">{{ step.title }}</h4>
                                        <p class="mt-1 text-sm leading-relaxed text-arbor-sage">{{ step.desc }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Qualité & confidentialité</h3>
                            <div class="space-y-3">
                                <div
                                    v-for="check in qualityChecks"
                                    :key="check"
                                    class="flex items-start gap-3 rounded-lg bg-arbor-deep/35 px-3 py-2.5"
                                >
                                    <span class="mt-1 h-2 w-2 rounded-full bg-arbor-emerald"></span>
                                    <p class="text-sm text-arbor-sage">{{ check }}</p>
                                </div>
                            </div>
                            <div class="mt-5 rounded-xl border border-arbor-amber/25 bg-arbor-amber/10 p-4">
                                <p class="text-sm leading-relaxed text-arbor-sage">
                                    Les métriques audio sont des indicateurs descriptifs. Elles ne remplacent pas une annotation naturaliste
                                    ni une validation experte des espèces ou habitats.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card p-6">
                        <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Statistiques descriptives — Features audio</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-arbor-sage uppercase border-b border-arbor-glass-border">
                                    <tr>
                                        <th class="px-4 py-3">Feature</th>
                                        <th class="px-4 py-3">N</th>
                                        <th class="px-4 py-3">Moyenne</th>
                                        <th class="px-4 py-3">Médiane</th>
                                        <th class="px-4 py-3">Écart-type</th>
                                        <th class="px-4 py-3">Min</th>
                                        <th class="px-4 py-3">Max</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in audioFeatureRows"
                                        :key="row.key"
                                        class="border-b border-arbor-glass-border/50 hover:bg-arbor-glass/20 transition-colors"
                                    >
                                        <td class="px-4 py-3">
                                            <div class="text-arbor-cream font-medium">{{ row.label }}</div>
                                            <div class="mt-1 max-w-sm text-xs text-arbor-sage/70">{{ row.description }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-arbor-sage font-mono">{{ row.stats.count }}</td>
                                        <td class="px-4 py-3 text-arbor-emerald font-mono">{{ Number(row.stats.mean).toExponential(3) }}</td>
                                        <td class="px-4 py-3 text-arbor-sage font-mono">{{ Number(row.stats.median).toExponential(3) }}</td>
                                        <td class="px-4 py-3 text-arbor-sage font-mono">{{ Number(row.stats.std).toExponential(3) }}</td>
                                        <td class="px-4 py-3 text-arbor-sage font-mono">{{ Number(row.stats.min).toExponential(3) }}</td>
                                        <td class="px-4 py-3 text-arbor-sage font-mono">{{ Number(row.stats.max).toExponential(3) }}</td>
                                    </tr>
                                    <tr v-if="audioFeatureRows.length === 0">
                                        <td colspan="7" class="px-4 py-8 text-center text-arbor-sage">
                                            Aucune statistique descriptive disponible pour le moment.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="glass-card p-6">
                        <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Dictionnaire des features</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                            <div
                                v-for="(description, key) in featureDescriptions"
                                :key="key"
                                class="rounded-xl border border-arbor-glass-border bg-arbor-deep/35 p-4"
                            >
                                <code class="text-xs text-arbor-emerald">{{ key }}</code>
                                <h4 class="mt-2 text-sm font-semibold text-arbor-cream">{{ featureLabels[key] ?? key }}</h4>
                                <p class="mt-1 text-sm leading-relaxed text-arbor-sage">{{ description }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data -->
                <div v-if="activeTab === 'data'" class="space-y-6 animate-fade-in">
                    <DataTable :data="props.rawDataSample" />
                    <div class="glass-card p-6">
                        <h3 class="font-display text-xl font-semibold text-arbor-cream mb-2">Licence d'utilisation des données</h3>
                        <p class="text-sm text-arbor-sage">
                            Les données exposées sur cette page et via l'API sont agrégées et anonymisées. Elles sont mises à disposition à des fins de recherche scientifique, d'éducation et de conservation.
                            Merci de citer <strong class="text-arbor-cream">Arborisis</strong> dans vos publications. Pour un accès à des données plus granulaires ou spécifiques, contactez l'équipe.
                        </p>
                    </div>
                </div>

                <!-- API -->
                <div v-if="activeTab === 'api'" class="space-y-6 animate-fade-in">
                    <ApiDocs />
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
