<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    soundWalk: Object,
});

const mapContainer = ref(null);
const map = ref(null);

function formatDuration(minutes) {
    if (!minutes) return '-';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h > 0) return `${h}h ${m}min`;
    return `${m} min`;
}

function formatRouteDistance(meters) {
    if (!meters) return null;
    if (meters >= 1000) return `${(meters / 1000).toFixed(1)} km`;
    return `${Math.round(meters)} m`;
}

function formatRouteDuration(seconds) {
    if (!seconds) return null;
    return formatDuration(Math.round(seconds / 60));
}

function routeCoordinatesFromGeometry(geometry) {
    if (Array.isArray(geometry?.coordinates)) {
        return geometry.coordinates;
    }

    if (Array.isArray(geometry)) {
        return geometry
            .map((waypoint) => {
                const lat = waypoint.lat ?? waypoint.latitude;
                const lng = waypoint.lng ?? waypoint.longitude;

                if (lat === undefined || lng === undefined) {
                    return null;
                }

                return [Number(lng), Number(lat)];
            })
            .filter(Boolean);
    }

    return [];
}

onMounted(() => {
    const routeCoordinates = routeCoordinatesFromGeometry(props.soundWalk.route_geometry);
    if (!mapContainer.value || !routeCoordinates.length) return;

    const start = routeCoordinates[0];
    const lat = start[1] ?? 46.6;
    const lng = start[0] ?? 1.9;

    map.value = L.map(mapContainer.value).setView([lat, lng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map.value);

    const coords = routeCoordinates.map((coordinate) => [
        coordinate[1],
        coordinate[0],
    ]);

    L.polyline(coords, { color: '#34D399', weight: 4, opacity: 0.85 }).addTo(map.value);

    props.soundWalk.points.forEach((point, index) => {
        const marker = L.circleMarker([point.latitude, point.longitude], {
            radius: 10,
            fillColor: '#34D399',
            color: '#065f46',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.85,
        }).addTo(map.value);

        marker.bindPopup(
            `<div class="font-sans text-sm">
                <p class="font-semibold text-arbor-night">${index + 1}. ${point.title || 'Arrêt'}</p>
                ${point.description ? `<p class="text-xs text-gray-600 mt-1">${point.description}</p>` : ''}
            </div>`,
            { closeButton: false }
        );
    });

    map.value.fitBounds(coords, { padding: [40, 40] });
});
</script>

<template>
    <Head :title="soundWalk.title" />
    <GuestLayout>
        <div class="min-h-screen bg-arbor-night">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16">
                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Info -->
                    <div class="lg:w-1/3 space-y-6">
                        <div>
                            <Link
                                href="/sound-walks"
                                class="text-xs text-arbor-sage hover:text-arbor-cream transition"
                            >
                                ← Toutes les balades
                            </Link>
                            <h1 class="mt-3 font-display text-3xl md:text-4xl font-bold text-arbor-cream leading-tight">
                                {{ soundWalk.title }}
                            </h1>
                            <p v-if="soundWalk.description" class="mt-3 text-arbor-sage leading-relaxed">
                                {{ soundWalk.description }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <span
                                v-if="soundWalk.estimated_duration_minutes"
                                class="inline-flex items-center gap-1.5 rounded-[8px] border border-arbor-mineral/20 bg-arbor-ink/60 px-3 py-1.5 text-xs text-arbor-sage"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                {{ formatDuration(soundWalk.estimated_duration_minutes) }}
                            </span>
                            <span
                                v-if="formatRouteDistance(soundWalk.route_distance_meters)"
                                class="inline-flex items-center gap-1.5 rounded-[8px] border border-arbor-mineral/20 bg-arbor-ink/60 px-3 py-1.5 text-xs text-arbor-sage"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l18-5-5 18-4-8-9-5z"/></svg>
                                {{ formatRouteDistance(soundWalk.route_distance_meters) }}
                            </span>
                            <span
                                v-if="formatRouteDuration(soundWalk.route_duration_seconds)"
                                class="inline-flex items-center gap-1.5 rounded-[8px] border border-arbor-mineral/20 bg-arbor-ink/60 px-3 py-1.5 text-xs text-arbor-sage"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3h12M6 21h12M8 3c0 5 8 5 8 9s-8 4-8 9M16 3c0 5-8 5-8 9s8 4 8 9"/></svg>
                                {{ formatRouteDuration(soundWalk.route_duration_seconds) }}
                            </span>
                            <span
                                v-if="soundWalk.difficulty_level"
                                class="inline-flex items-center gap-1.5 rounded-[8px] border border-arbor-mineral/20 bg-arbor-ink/60 px-3 py-1.5 text-xs text-arbor-sage"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                Difficulté {{ soundWalk.difficulty_level }}/5
                            </span>
                            <span
                                v-if="soundWalk.moderation_status === 'pending'"
                                class="inline-flex items-center gap-1.5 rounded-[8px] border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs text-amber-400"
                            >
                                En attente de modération
                            </span>
                        </div>

                        <div v-if="soundWalk.tags?.length" class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in soundWalk.tags"
                                :key="tag"
                                class="rounded-[6px] bg-arbor-emerald/10 px-2 py-1 text-[11px] text-arbor-emerald"
                            >
                                {{ tag }}
                            </span>
                        </div>

                        <div v-if="soundWalk.user" class="flex items-center gap-3 pt-4 border-t border-arbor-mineral/10">
                            <div class="h-8 w-8 rounded-full bg-arbor-emerald/20 grid place-items-center text-arbor-emerald text-xs font-bold">
                                {{ soundWalk.user.name?.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <p class="text-sm text-arbor-cream">{{ soundWalk.user.name }}</p>
                                <p class="text-xs text-arbor-sage">Créée le {{ soundWalk.created_at }}</p>
                            </div>
                        </div>

                        <!-- Waypoints list -->
                        <div class="pt-4 border-t border-arbor-mineral/10">
                            <h2 class="text-sm font-semibold text-arbor-cream mb-3">Arrêts de la balade</h2>
                            <div class="space-y-3">
                                <div
                                    v-for="(point, index) in soundWalk.points"
                                    :key="point.id"
                                    class="flex gap-3 rounded-[8px] border border-arbor-mineral/10 bg-arbor-ink/40 p-3"
                                >
                                    <div class="shrink-0 h-6 w-6 rounded-full bg-arbor-firefly/20 grid place-items-center text-arbor-firefly text-xs font-bold">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm text-arbor-cream font-medium">{{ point.title || `Arrêt ${index + 1}` }}</p>
                                        <p v-if="point.description" class="text-xs text-arbor-sage mt-0.5">{{ point.description }}</p>
                                        <p v-if="point.stop_metadata?.recording_tips" class="text-xs text-arbor-emerald mt-1 italic">
                                            💡 {{ point.stop_metadata.recording_tips }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Map -->
                    <div class="relative z-0 lg:w-2/3">
                        <div
                            ref="mapContainer"
                            class="soundwalk-map relative z-0 h-[min(600px,60vh)] w-full overflow-hidden rounded-[12px] border border-arbor-mineral/15"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.soundwalk-map :deep(.leaflet-pane),
.soundwalk-map :deep(.leaflet-top),
.soundwalk-map :deep(.leaflet-bottom) {
    z-index: 10;
}

.soundwalk-map :deep(.leaflet-control-container) {
    position: relative;
    z-index: 20;
}

.soundwalk-map :deep(.leaflet-popup-pane) {
    z-index: 30;
}
</style>
