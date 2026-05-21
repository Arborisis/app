<script setup>
import { ref, onMounted, onUnmounted, watch, defineAsyncComponent } from 'vue';
import L from 'leaflet';

const MapParticleOverlay = defineAsyncComponent(() => import('@/Components/Map/MapParticleOverlay.vue'));
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';

const props = defineProps({
    sounds: {
        type: Array,
        default: () => [],
    },
    initialCenter: {
        type: Array,
        default: () => [46.603354, 1.888334],
    },
    initialZoom: {
        type: Number,
        default: () => 5,
    },
    activeSoundId: {
        type: [String, Number],
        default: null,
    },
});

const emit = defineEmits(['marker-click']);

const mapContainer = ref(null);
const rippleContainer = ref(null);
const map = ref(null);
const markerClusterGroup = ref(null);
const activePopupSound = ref(null);
const markersMap = ref(new Map());

/* ── Category color mapping ─────────────────────────── */
const categoryColors = {
    'Forêts': '#34D399',
    'Océans': '#60A5FA',
    'Montagnes': '#A8A29E',
    'Déserts': '#D4A574',
    'Rivières': '#22D3EE',
    'Urbain': '#F472B6',
    'Cavernes': '#A78BFA',
    'Prairies': '#A3E635',
    'Lacs': '#38BDF8',
    'Marais': '#8FA68E',
    'Jungles': '#4ADE80',
    'Glaciers': '#C7D2FE',
};

const getCategoryColor = (categoryName) => {
    if (!categoryName) return '#34D399';
    return categoryColors[categoryName] || '#34D399';
};

const getCategoryClass = (categoryName) => {
    const map = {
        'Forêts': 'marker-forest',
        'Océans': 'marker-ocean',
        'Montagnes': 'marker-mountain',
        'Déserts': 'marker-desert',
        'Rivières': 'marker-river',
        'Urbain': 'marker-urban',
        'Cavernes': 'marker-cave',
    };
    return map[categoryName] || 'marker-default';
};

/* ── Formatters ─────────────────────────────────────── */
const formatDuration = (seconds) => {
    if (!seconds) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
};

/* ── Marker icon ────────────────────────────────────── */
const createCustomIcon = (categoryName, isHighlighted = false) => {
    const color = getCategoryColor(categoryName);
    const catClass = getCategoryClass(categoryName);
    const scale = isHighlighted ? 'scale(1.4)' : 'scale(1)';
    const glow = isHighlighted ? `0 0 26px ${color}aa, 0 0 9px ${color}ff` : `0 0 16px ${color}80, 0 0 4px ${color}cc`;

    return L.divIcon({
        className: `custom-marker ${catClass}`,
        html: `<div class="sound-marker" style="transform: ${scale}; transition: transform 0.25s ease;">
            <div class="sound-marker-pulse" style="background: ${color}4d;"></div>
            <div class="sound-marker-pulse-slow" style="background: ${color}26;"></div>
            <div class="sound-marker-ring" style="border-color: ${color}66;"></div>
            <div class="sound-marker-dot" style="background: ${color}; box-shadow: ${glow};"></div>
        </div>`,
        iconSize: [20, 20],
        iconAnchor: [10, 10],
        popupAnchor: [0, -12],
    });
};

/* ── Popup builder ──────────────────────────────────── */
const buildPopupContent = (sound) => {
    const p = sound.properties;
    const color = getCategoryColor(p.category);
    const coverHtml = p.cover_url
        ? `<div class="map-popup-cover" style="background-image: url('${p.cover_url}')"></div>`
        : `<div class="map-popup-cover map-popup-cover-empty">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
           </div>`;

    const categoryBadge = p.category
        ? `<span class="map-popup-badge" style="background: ${color}20; color: ${color}; border: 1px solid ${color}40;">${p.category}</span>`
        : '';

    const locationMeta = p.location_name
        ? `<span class="map-popup-location">${p.location_name}</span>`
        : '';
    const dateMeta = p.recorded_at
        ? `<span class="map-popup-date">${formatDate(p.recorded_at)}</span>`
        : '';
    const metaSep = locationMeta && dateMeta ? '<span class="map-popup-meta-dot">·</span>' : '';

    return `
        <div class="map-popup">
            ${coverHtml}
            <div class="map-popup-body">
                <div class="map-popup-header">
                    ${categoryBadge}
                </div>
                <h3 class="map-popup-title">${p.title}</h3>
                <p class="map-popup-user">${p.user_name}</p>
                <div class="map-popup-meta-row">
                    ${locationMeta}${metaSep}${dateMeta}
                </div>
                <div class="map-popup-footer">
                    <span class="map-popup-duration">${formatDuration(p.duration)}</span>
                    <a href="/sounds/${p.slug}" class="map-popup-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        Écouter
                    </a>
                </div>
            </div>
        </div>
    `;
};

/* ── Marker management ──────────────────────────────── */
const updateMarkers = () => {
    if (!map.value || !markerClusterGroup.value) return;

    markerClusterGroup.value.clearLayers();
    markersMap.value.clear();

    if (props.sounds.length === 0) return;

    props.sounds.forEach((sound) => {
        const coords = sound.geometry.coordinates;
        const p = sound.properties;

        if (!coords || !isFinite(coords[0]) || !isFinite(coords[1])) {
            return;
        }

        const marker = L.marker([coords[1], coords[0]], {
            icon: createCustomIcon(p.category, p.id === props.activeSoundId),
        });

        marker.bindPopup(buildPopupContent(sound), {
            className: 'sound-popup-wrapper',
            closeButton: false,
            offset: [0, -8],
        });

        marker.on('popupopen', () => {
            activePopupSound.value = p;
        });

        marker.on('popupclose', () => {
            activePopupSound.value = null;
        });

        marker.on('click', () => {
            emit('marker-click', p.id);
        });

        markerClusterGroup.value.addLayer(marker);
        markersMap.value.set(String(p.id), marker);
    });

    const layers = markerClusterGroup.value.getLayers();
    if (layers.length > 0) {
        const group = L.featureGroup(layers);
        const bounds = group.getBounds();
        if (bounds.isValid()) {
            map.value.fitBounds(bounds.pad(0.15), {
                animate: true,
                duration: 1,
            });
        }
    }
};

/* ── Highlight tracking ─────────────────────────────── */
watch(() => props.activeSoundId, (newId) => {
    markersMap.value.forEach((marker, id) => {
        const sound = props.sounds.find(s => String(s.properties.id) === id);
        if (sound) {
            const p = sound.properties;
            marker.setIcon(createCustomIcon(p.category, id === String(newId)));
        }
    });
});

/* ── Exposed methods ────────────────────────────────── */
const flyToSound = (coords, zoom = 14) => {
    if (!map.value || !coords || !isFinite(coords[0]) || !isFinite(coords[1])) return;
    map.value.flyTo([coords[1], coords[0]], zoom, {
        animate: true,
        duration: 1.2,
    });
};

const highlightMarker = (soundId) => {
    markersMap.value.forEach((m, id) => {
        const sound = props.sounds.find(s => String(s.properties.id) === id);
        if (sound) {
            m.setIcon(createCustomIcon(sound.properties.category, id === String(soundId)));
        }
    });
};

const openPopup = (soundId) => {
    const marker = markersMap.value.get(String(soundId));
    if (marker) {
        marker.openPopup();
    }
};

/* ── Ripple effect ──────────────────────────────────── */
const triggerRipple = (lat, lng, color) => {
    if (!map.value || !rippleContainer.value) return;
    const point = map.value.latLngToContainerPoint([lat, lng]);

    const createRipple = (delayClass = '') => {
        const el = document.createElement('div');
        el.className = `map-ripple ${delayClass}`;
        el.style.left = `${point.x}px`;
        el.style.top = `${point.y}px`;
        el.style.color = color;
        rippleContainer.value.appendChild(el);
        setTimeout(() => el.remove(), delayClass ? 2400 : 2100);
    };

    createRipple();
    createRipple('map-ripple-delayed');
};

defineExpose({
    flyToSound,
    highlightMarker,
    openPopup,
});

/* ── Lifecycle ──────────────────────────────────────── */
onMounted(() => {
    if (!mapContainer.value) return;

    map.value = L.map(mapContainer.value, {
        zoomControl: false,
        attributionControl: false,
        scrollWheelZoom: window.innerWidth >= 768,
    }).setView(props.initialCenter, props.initialZoom);

    L.control.zoom({ position: 'bottomright' }).addTo(map.value);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19,
    }).addTo(map.value);

    markerClusterGroup.value = L.markerClusterGroup({
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        spiderfyOnMaxZoom: true,
        maxClusterRadius: 60,
        iconCreateFunction: (cluster) => {
            const count = cluster.getChildCount();
            return L.divIcon({
                className: 'marker-cluster-custom',
                html: `<div class="marker-cluster-inner">${count}</div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
            });
        },
    });

    map.value.addLayer(markerClusterGroup.value);

    updateMarkers();
});

onUnmounted(() => {
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
});

watch(() => props.sounds, updateMarkers, { deep: true });
</script>

<template>
    <div class="relative w-full h-full">
        <div ref="mapContainer" class="w-full h-full" />
        <MapParticleOverlay />
        <div ref="rippleContainer" class="map-ripple-container" />

        <!-- Empty state overlay -->
        <div
            v-if="sounds.length === 0"
            class="absolute inset-0 z-map flex items-center justify-center pointer-events-none"
        >
            <div class="text-center animate-scale-in">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-arbor-moss/10 mb-6 ring-1 ring-arbor-emerald/10">
                    <div class="relative">
                        <div class="absolute inset-0 rounded-full bg-arbor-emerald/20 animate-ping" style="animation-duration: 3s;"></div>
                        <svg class="w-12 h-12 text-arbor-moss/50 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 7m0 13V7m0 0L9 7" />
                        </svg>
                    </div>
                </div>
                <p class="text-arbor-sage text-base font-medium">Aucun son sur cette zone</p>
                <p class="text-arbor-sage/70 text-sm mt-2 max-w-xs mx-auto">Explorez d'autres catégories ou élargissez votre recherche pour découvrir des enregistrements.</p>
            </div>
        </div>
    </div>
</template>

<style>
/* Marker styles */
.sound-marker {
    position: relative;
    width: 20px;
    height: 20px;
}

.sound-marker-dot {
    position: absolute;
    inset: 5px;
    border-radius: 50%;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.sound-marker-ring {
    position: absolute;
    inset: 2px;
    border: 1px solid;
    border-radius: 40% 60% 55% 45% / 52% 42% 58% 48%;
    background: rgba(7, 17, 13, 0.46);
    backdrop-filter: blur(2px);
}

.sound-marker:hover .sound-marker-dot {
    transform: scale(1.3);
}

.sound-marker-pulse {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    animation: markerPulse 2s ease-out infinite;
}

.sound-marker-pulse-slow {
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    animation: markerPulseSlow 3.5s ease-out infinite;
}

@keyframes markerPulse {
    0% { transform: scale(0.5); opacity: 1; }
    100% { transform: scale(2); opacity: 0; }
}

@keyframes markerPulseSlow {
    0% { transform: scale(0.4); opacity: 0.7; }
    100% { transform: scale(2.8); opacity: 0; }
}

/* Cluster styles */
.marker-cluster-custom {
    background: transparent !important;
}

.marker-cluster-inner {
    width: 40px;
    height: 40px;
    border-radius: 40% 60% 55% 45% / 52% 42% 58% 48%;
    background: rgba(215, 180, 106, 0.9);
    backdrop-filter: blur(4px);
    border: 2px solid rgba(11, 18, 32, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #07110D;
    font-weight: 700;
    font-size: 13px;
    box-shadow: 0 0 24px rgba(215, 180, 106, 0.26);
    transition: transform 0.2s ease;
}

.marker-cluster-custom:hover .marker-cluster-inner {
    transform: scale(1.1);
}

/* Popup styles */
.sound-popup-wrapper .leaflet-popup-content-wrapper {
    background: rgba(7, 17, 13, 0.96) !important;
    backdrop-filter: blur(12px) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 12px !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.05) !important;
    overflow: hidden;
    padding: 0 !important;
}

.sound-popup-wrapper .leaflet-popup-content {
    margin: 0 !important;
    width: 240px !important;
}

.sound-popup-wrapper .leaflet-popup-tip {
    background: rgba(7, 17, 13, 0.96) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    width: 12px !important;
    height: 12px !important;
    margin: -7px auto 0 !important;
}

.map-popup-cover {
    width: 100%;
    height: 110px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.map-popup-cover::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 50px;
    background: linear-gradient(to top, #07110D, transparent);
}

.map-popup-cover-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #07110D, #102018);
    color: rgba(143, 166, 142, 0.3);
}

.map-popup-body {
    padding: 10px 14px 14px;
}

.map-popup-header {
    margin-bottom: 6px;
}

.map-popup-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.map-popup-title {
    font-weight: 600;
    color: #F3F0E7;
    font-size: 14px;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.map-popup-user {
    color: #8FA68E;
    font-size: 11px;
    margin-bottom: 6px;
}

.map-popup-meta-row {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 10px;
    font-size: 11px;
    color: #8FA68E;
}

.map-popup-meta-dot {
    opacity: 0.5;
}

.map-popup-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 8px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.map-popup-duration {
    font-size: 11px;
    color: #8FA68E;
    font-variant-numeric: tabular-nums;
}

.map-popup-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 600;
    color: #D7B46A;
    text-decoration: none;
    transition: color 0.2s;
}

.map-popup-link:hover {
    color: #8FE6C1;
}

.map-popup-link svg {
    opacity: 0.9;
}

/* Leaflet controls override */
.leaflet-control-zoom {
    border: none !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    border-radius: 14px !important;
    overflow: hidden;
}

.leaflet-control-zoom a {
    background: rgba(7, 17, 13, 0.92) !important;
    color: #8FA68E !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    width: 38px !important;
    height: 38px !important;
    line-height: 38px !important;
    font-size: 18px !important;
    transition: all 0.2s ease;
    border-radius: 0 !important;
}

.leaflet-control-zoom a:hover {
    background: rgba(16, 32, 24, 0.96) !important;
    color: #F3F0E7 !important;
}

.leaflet-control-zoom-in {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 14px 14px 0 0 !important;
}

.leaflet-control-zoom-out {
    border-radius: 0 0 14px 14px !important;
}
</style>
