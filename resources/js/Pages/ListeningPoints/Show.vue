<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    point: Object,
    timeline: Array,
    species: Array,
    metrics: Object,
    currentWeather: Object,
    versions: Array,
    nearbyPoints: Array,
});

const activeTab = ref('timeline');
const selectedSound = ref(props.timeline[0] ?? null);

const tabs = [
    { key: 'timeline', label: 'Timeline' },
    { key: 'species', label: 'Espèces' },
    { key: 'weather', label: 'Météo' },
    { key: 'stats', label: 'Statistiques' },
    { key: 'archives', label: 'Archives' },
];

const habitatEmoji = computed(() => {
    const map = {
        forest: '🌲', wetland: '💧', river: '🌊', meadow: '🌾',
        ocean: '🌊', mountain: '⛰️', urban_nature: '🏙️', desert: '🏜️',
    };
    return map[props.point.habitat_type] ?? '📍';
});

function formatDate(iso) {
    if (!iso) return '-';
    const d = new Date(iso);
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
}

function formatMonthYear(iso) {
    if (!iso) return '-';
    const d = new Date(iso);
    return d.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
}

function formatDateTime(iso) {
    if (!iso) return '-';
    const d = new Date(iso);
    return d.toLocaleString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

function weatherLabel(condition) {
    const labels = {
        clear: 'Ciel clair',
        cloudy: 'Nuageux',
        fog: 'Brume',
        drizzle: 'Bruine',
        rain: 'Pluie',
        snow: 'Neige',
        storm: 'Orage',
    };

    return labels[condition] ?? 'Conditions inconnues';
}
</script>

<template>
    <Head :title="point.title" />
    <GuestLayout>
        <div class="min-h-screen bg-arbor-night">
            <!-- Hero -->
            <div class="relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-arbor-moss/10 via-transparent to-transparent pointer-events-none"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-10">
                    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-2xl">{{ habitatEmoji }}</span>
                                <span class="text-xs font-medium text-arbor-emerald uppercase tracking-widest">{{ point.habitat_type }}</span>
                            </div>
                            <h1 class="font-display text-3xl md:text-4xl font-bold text-arbor-cream leading-tight">
                                {{ point.title }}
                            </h1>
                            <p v-if="point.description" class="mt-3 text-arbor-sage text-lg max-w-2xl">
                                {{ point.description }}
                            </p>
                        </div>
                        <div class="text-right hidden md:block">
                            <p class="text-xs text-arbor-sage/70 font-mono">
                                {{ point.public_latitude?.toFixed(2) }}, {{ point.public_longitude?.toFixed(2) }}
                            </p>
                            <p class="text-xs text-arbor-sage/70 font-mono mt-1">
                                ~{{ point.public_accuracy_meters }}m de précision
                            </p>
                            <div class="mt-3 flex justify-end gap-2">
                                <Link
                                    :href="route('sounds.record', { listening_point_id: point.id })"
                                    class="inline-block px-4 py-2 bg-arbor-emerald/20 text-arbor-emerald border border-arbor-emerald/30 rounded-lg hover:bg-arbor-emerald/30 transition-colors text-sm font-medium"
                                >
                                    + Enregistrer une nouvelle prise
                                </Link>
                                <Link
                                    :href="route('sounds.create', { listening_point_id: point.id })"
                                    class="inline-block px-4 py-2 bg-arbor-glass/30 text-arbor-sage border border-arbor-glass-border rounded-lg hover:text-arbor-cream transition-colors text-sm font-medium"
                                >
                                    Importer
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Quick stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                        <div class="glass-card p-4 text-center">
                            <p class="text-2xl font-display font-bold text-arbor-cream">{{ point.recordings_count }}</p>
                            <p class="text-xs text-arbor-sage uppercase tracking-wider">Enregistrements</p>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <p class="text-2xl font-display font-bold text-arbor-cream">{{ species.length }}</p>
                            <p class="text-xs text-arbor-sage uppercase tracking-wider">Espèces détectées</p>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <p class="text-2xl font-display font-bold text-arbor-cream">{{ formatDate(point.first_recorded_at) }}</p>
                            <p class="text-xs text-arbor-sage uppercase tracking-wider">Premier enregistrement</p>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <p class="text-2xl font-display font-bold text-arbor-emerald">{{ Math.round(metrics.biodiversity_score ?? 0) }}</p>
                            <p class="text-xs text-arbor-sage uppercase tracking-wider">Score biodiversité</p>
                        </div>
                    </div>

                    <div v-if="currentWeather" class="mt-4 glass-card p-4">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <p class="text-xs text-arbor-sage uppercase tracking-wider">Météo temps réel</p>
                                <p class="mt-1 text-lg font-display text-arbor-cream">
                                    {{ weatherLabel(currentWeather.weather_condition) }}
                                    <span v-if="currentWeather.temperature_c !== null" class="text-arbor-emerald">
                                        · {{ Math.round(currentWeather.temperature_c) }}°C
                                    </span>
                                </p>
                            </div>
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div>
                                    <p class="text-sm font-mono text-arbor-cream">{{ currentWeather.humidity_percent ?? '-' }}%</p>
                                    <p class="text-[10px] text-arbor-sage uppercase tracking-wider">Humidité</p>
                                </div>
                                <div>
                                    <p class="text-sm font-mono text-arbor-cream">{{ currentWeather.wind_speed_kmh ?? '-' }} km/h</p>
                                    <p class="text-[10px] text-arbor-sage uppercase tracking-wider">Vent</p>
                                </div>
                                <div>
                                    <p class="text-sm font-mono text-arbor-cream">{{ currentWeather.wind_direction ?? '-' }}</p>
                                    <p class="text-[10px] text-arbor-sage uppercase tracking-wider">Direction</p>
                                </div>
                            </div>
                        </div>
                    </div>
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

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
                <!-- Timeline -->
                <div v-if="activeTab === 'timeline'" class="space-y-6 animate-fade-in">
                    <!-- Player -->
                    <div v-if="selectedSound" class="glass-card p-6">
                        <div class="flex items-center gap-4">
                            <img
                                v-if="selectedSound.cover_url"
                                :src="selectedSound.cover_url"
                                class="w-16 h-16 rounded-lg object-cover"
                                alt=""
                            />
                            <div v-else class="w-16 h-16 rounded-lg bg-arbor-deep flex items-center justify-center text-2xl">
                                🎵
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-display text-lg font-semibold text-arbor-cream truncate">
                                    <Link :href="route('sounds.show', selectedSound.slug)" class="hover:text-arbor-emerald transition-colors">
                                        {{ selectedSound.title }}
                                    </Link>
                                </h3>
                                <p class="text-sm text-arbor-sage">
                                    {{ formatDate(selectedSound.recorded_at) }} · {{ selectedSound.duration }}s · {{ selectedSound.user.name }}
                                </p>
                            </div>
                            <Link
                                :href="route('sounds.show', selectedSound.slug)"
                                class="px-4 py-2 bg-arbor-emerald/20 text-arbor-emerald border border-arbor-emerald/30 rounded-lg hover:bg-arbor-emerald/30 transition-colors text-sm font-medium"
                            >
                                Écouter
                            </Link>
                        </div>
                    </div>

                    <!-- Timeline list -->
                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-px bg-arbor-glass-border"></div>
                        <div class="space-y-4">
                            <div
                                v-for="(sound, i) in timeline"
                                :key="sound.id"
                                class="relative pl-12 cursor-pointer group"
                                @click="selectedSound = sound"
                            >
                                <div class="absolute left-2 top-2 w-5 h-5 rounded-full border-2 transition-colors"
                                    :class="selectedSound?.id === sound.id
                                        ? 'bg-arbor-emerald border-arbor-emerald'
                                        : 'bg-arbor-deep border-arbor-glass-border group-hover:border-arbor-emerald/50'
                                    "
                                ></div>
                                <div class="glass-card p-4 transition-all duration-200"
                                    :class="selectedSound?.id === sound.id ? 'border-arbor-emerald/30' : 'hover:border-arbor-glass-border/80'"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-arbor-cream">{{ sound.title }}</p>
                                            <p class="text-xs text-arbor-sage mt-1">
                                                {{ formatDate(sound.recorded_at) }} · {{ sound.duration }}s
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span v-if="sound.species_count > 0" class="text-xs text-arbor-emerald">
                                                {{ sound.species_count }} espèce{{ sound.species_count > 1 ? 's' : '' }}
                                            </span>
                                            <span v-if="sound.biodiversity_score" class="text-xs font-mono text-arbor-sage">
                                                SBS {{ Math.round(sound.biodiversity_score) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Species -->
                <div v-if="activeTab === 'species'" class="space-y-6 animate-fade-in">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="s in species"
                            :key="s.scientific_name"
                            class="glass-card p-5"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-display text-lg font-semibold text-arbor-cream">{{ s.common_name }}</h4>
                                    <p class="text-sm text-arbor-sage italic">{{ s.scientific_name }}</p>
                                </div>
                                <span class="text-xs font-mono text-arbor-emerald">{{ s.count }}×</span>
                            </div>
                            <div class="mt-3">
                                <div class="flex items-center justify-between text-xs text-arbor-sage mb-1">
                                    <span>Confiance moy.</span>
                                    <span>{{ Math.round(s.avg_confidence * 100) }}%</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-arbor-deep overflow-hidden">
                                    <div class="h-full rounded-full bg-arbor-emerald" :style="{ width: `${s.avg_confidence * 100}%` }"></div>
                                </div>
                            </div>
                            <div class="mt-3 flex gap-1 flex-wrap">
                                <span
                                    v-for="det in s.detections.slice(0, 5)"
                                    :key="det.sound_id"
                                    class="text-[10px] px-2 py-0.5 rounded-full bg-arbor-deep text-arbor-sage"
                                >
                                    {{ formatMonthYear(det.recorded_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div v-if="activeTab === 'stats'" class="space-y-6 animate-fade-in">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Scores acoustiques</h3>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-arbor-sage">Biodiversité sonore (SBS)</span>
                                        <span class="text-arbor-cream font-mono">{{ Math.round(metrics.biodiversity_score ?? 0) }}/100</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-arbor-deep overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-arbor-emerald to-arbor-moss" :style="{ width: `${Math.min(metrics.biodiversity_score ?? 0, 100)}%` }"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-arbor-sage">Activité acoustique (AAS)</span>
                                        <span class="text-arbor-cream font-mono">{{ Math.round(metrics.acoustic_activity_score ?? 0) }}/100</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-arbor-deep overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-arbor-amber to-arbor-emerald" :style="{ width: `${Math.min(metrics.acoustic_activity_score ?? 0, 100)}%` }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Indices de diversité</h3>
                            <div class="space-y-4">
                                <div v-if="metrics.shannon_index !== null">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-arbor-sage">Shannon (H')</span>
                                        <span class="text-arbor-cream font-mono">{{ metrics.shannon_index.toFixed(2) }}</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-arbor-deep overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-arbor-emerald to-arbor-moss" :style="{ width: `${Math.min((metrics.shannon_index / 3) * 100, 100)}%` }"></div>
                                    </div>
                                </div>
                                <div v-if="metrics.simpson_index !== null">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-arbor-sage">Simpson (1-D)</span>
                                        <span class="text-arbor-cream font-mono">{{ metrics.simpson_index.toFixed(2) }}</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-arbor-deep overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-arbor-amber to-arbor-emerald" :style="{ width: `${(metrics.simpson_index) * 100}%` }"></div>
                                    </div>
                                </div>
                                <div v-if="metrics.species_richness !== null">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-arbor-sage">Richesse spécifique</span>
                                        <span class="text-arbor-cream font-mono">{{ Math.round(metrics.species_richness) }} espèces</span>
                                    </div>
                                </div>
                                <div v-if="metrics.acoustic_complexity_index !== null">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-arbor-sage">Complexité acoustique (ACI)</span>
                                        <span class="text-arbor-cream font-mono">{{ metrics.acoustic_complexity_index.toFixed(2) }}</span>
                                    </div>
                                </div>
                                <div v-if="metrics.temporal_turnover !== null">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-arbor-sage">Turnover temporel</span>
                                        <span class="text-arbor-cream font-mono">{{ (metrics.temporal_turnover * 100).toFixed(1) }}%</span>
                                    </div>
                                    <p class="text-xs text-arbor-sage">Taux de remplacement des espèces entre la première et la dernière période.</p>
                                </div>
                                <div v-if="metrics.acoustic_consistency_score !== null">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-arbor-sage">Cohérence acoustique</span>
                                        <span class="text-arbor-cream font-mono">{{ Math.round(metrics.acoustic_consistency_score) }}/100</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-arbor-deep overflow-hidden">
                                        <div class="h-full rounded-full bg-arbor-emerald" :style="{ width: `${Math.min(metrics.acoustic_consistency_score, 100)}%` }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="glass-card p-6">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Répartition temporelle</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="sound in timeline.slice(0, 10)"
                                    :key="sound.id"
                                    class="flex items-center gap-3"
                                >
                                    <span class="text-xs text-arbor-sage w-20">{{ formatMonthYear(sound.recorded_at) }}</span>
                                    <div class="flex-1 h-1.5 rounded-full bg-arbor-deep overflow-hidden">
                                        <div class="h-full rounded-full bg-arbor-emerald/60" :style="{ width: `${Math.min((sound.duration || 60) / 300 * 100, 100)}%` }"></div>
                                    </div>
                                    <span class="text-xs text-arbor-sage w-8 text-right">{{ sound.duration }}s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weather -->
                <div v-if="activeTab === 'weather'" class="space-y-6 animate-fade-in">
                    <div v-if="currentWeather" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="glass-card p-6 lg:col-span-1">
                            <p class="text-xs text-arbor-sage uppercase tracking-wider">Maintenant</p>
                            <p class="mt-3 text-4xl font-display font-bold text-arbor-cream">
                                {{ currentWeather.temperature_c !== null ? `${Math.round(currentWeather.temperature_c)}°C` : '-' }}
                            </p>
                            <p class="mt-2 text-arbor-emerald">{{ weatherLabel(currentWeather.weather_condition) }}</p>
                            <p class="mt-4 text-xs text-arbor-sage">
                                Relevé vers {{ formatDateTime(currentWeather.observed_at) }}
                            </p>
                        </div>
                        <div class="glass-card p-6 lg:col-span-2">
                            <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Conditions du point</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="rounded-lg bg-arbor-deep/50 p-4">
                                    <p class="text-xs text-arbor-sage uppercase tracking-wider">Humidité</p>
                                    <p class="mt-2 text-xl font-mono text-arbor-cream">{{ currentWeather.humidity_percent ?? '-' }}%</p>
                                </div>
                                <div class="rounded-lg bg-arbor-deep/50 p-4">
                                    <p class="text-xs text-arbor-sage uppercase tracking-wider">Vent</p>
                                    <p class="mt-2 text-xl font-mono text-arbor-cream">{{ currentWeather.wind_speed_kmh ?? '-' }} km/h</p>
                                </div>
                                <div class="rounded-lg bg-arbor-deep/50 p-4">
                                    <p class="text-xs text-arbor-sage uppercase tracking-wider">Direction</p>
                                    <p class="mt-2 text-xl font-mono text-arbor-cream">{{ currentWeather.wind_direction ?? '-' }}</p>
                                </div>
                                <div class="rounded-lg bg-arbor-deep/50 p-4">
                                    <p class="text-xs text-arbor-sage uppercase tracking-wider">Précip.</p>
                                    <p class="mt-2 text-xl font-mono text-arbor-cream">
                                        {{ currentWeather.is_snowing ? 'Neige' : currentWeather.is_raining ? 'Pluie' : 'Non' }}
                                    </p>
                                </div>
                            </div>
                            <p class="mt-4 text-xs text-arbor-sage">
                                Source {{ currentWeather.source }} · coordonnées publiques approximées.
                            </p>
                        </div>
                    </div>
                    <div v-else class="glass-card p-6">
                        <p class="text-arbor-sage">La météo temps réel n'est pas disponible pour ce point.</p>
                    </div>
                </div>

                <!-- Archives -->
                <div v-if="activeTab === 'archives'" class="space-y-4 animate-fade-in">
                    <div v-if="versions.length > 0" class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-px bg-arbor-glass-border"></div>
                        <div class="space-y-4">
                            <div v-for="version in versions" :key="version.version_hash" class="relative pl-12">
                                <div class="absolute left-2 top-2 w-5 h-5 rounded-full bg-arbor-deep border-2 border-arbor-emerald/50"></div>
                                <div class="glass-card p-5">
                                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-display font-semibold text-arbor-cream">
                                                v{{ version.version_number }} · {{ version.event_label }}
                                            </p>
                                            <p class="mt-1 text-xs text-arbor-sage">
                                                {{ formatDateTime(version.captured_at) }}
                                                <span v-if="version.actor"> · {{ version.actor.name }}</span>
                                            </p>
                                        </div>
                                        <div class="text-left md:text-right">
                                            <p class="font-mono text-xs text-arbor-emerald">{{ version.short_hash }}</p>
                                            <p v-if="version.short_parent_hash" class="font-mono text-[10px] text-arbor-sage">
                                                parent {{ version.short_parent_hash }}
                                            </p>
                                        </div>
                                    </div>
                                    <div v-if="version.changed_fields.length > 0" class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="field in version.changed_fields.slice(0, 8)"
                                            :key="field"
                                            class="px-2 py-1 rounded bg-arbor-deep/60 text-[10px] font-mono text-arbor-sage"
                                        >
                                            {{ field }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="glass-card p-6">
                        <p class="text-arbor-sage">Aucune archive n'a encore été capturée pour ce point.</p>
                    </div>
                </div>

                <!-- Nearby points -->
                <div v-if="nearbyPoints.length > 0" class="mt-10">
                    <h3 class="font-display text-xl font-semibold text-arbor-cream mb-4">Points d'écoute proches</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Link
                            v-for="np in nearbyPoints"
                            :key="np.slug"
                            :href="route('listening-points.show', np.slug)"
                            class="glass-card p-4 hover:border-arbor-emerald/30 transition-colors"
                        >
                            <h4 class="text-sm font-medium text-arbor-cream">{{ np.title }}</h4>
                            <p class="text-xs text-arbor-sage mt-1">{{ Math.round(np.distance_meters) }}m</p>
                        </Link>
                    </div>
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
