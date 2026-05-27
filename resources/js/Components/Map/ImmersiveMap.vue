<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    sounds: {
        type: Array,
        required: true,
    },
    center: {
        type: Array,
        default: () => [46.603354, 1.888334], // France center
    },
    zoom: {
        type: Number,
        default: 5,
    },
});

const mapContainer = ref(null);
let map = null;
let markers = [];

onMounted(async () => {
    if (!mapContainer.value) return;

    // Dynamic import of Leaflet
    const L = await import('leaflet');
    
    // Dark themed map tiles
    map = L.map(mapContainer.value, {
        center: props.center,
        zoom: props.zoom,
        zoomControl: false,
        attributionControl: false,
    });

    // Add dark themed tiles (CartoDB Dark Matter)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        subdomains: 'abcd',
        maxZoom: 19,
    }).addTo(map);

    // Custom marker icon
    const createCustomIcon = (count = 1) => {
        const size = count > 1 ? 40 : 24;
        return L.divIcon({
            className: 'custom-marker',
            html: `
                <div class="relative">
                    <div class="absolute inset-0 bg-[#34D399] rounded-full animate-ping opacity-30"></div>
                    <div class="relative w-${size} h-${size} bg-[#34D399] rounded-full flex items-center justify-center shadow-[0_0_20px_rgba(52,211,153,0.5)] border-2 border-[#050A08]">
                        ${count > 1 ? `<span class="text-[#050A08] text-xs font-bold">${count}</span>` : ''}
                    </div>
                </div>
            `,
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
        });
    };

    // Group sounds by location
    const locationGroups = {};
    props.sounds.forEach(sound => {
        if (sound.latitude && sound.longitude) {
            const key = `${sound.latitude.toFixed(3)},${sound.longitude.toFixed(3)}`;
            if (!locationGroups[key]) {
                locationGroups[key] = [];
            }
            locationGroups[key].push(sound);
        }
    });

    // Add markers
    Object.entries(locationGroups).forEach(([key, sounds]) => {
        const [lat, lng] = key.split(',').map(Number);
        const marker = L.marker([lat, lng], {
            icon: createCustomIcon(sounds.length),
        }).addTo(map);

        // Popup content
        const popupContent = `
            <div class="bg-[#111815] text-[#E8F0EC] p-4 rounded-xl min-w-[200px]">
                <h3 class="font-semibold mb-2">${sounds.length} enregistrement${sounds.length > 1 ? 's' : ''}</h3>
                <div class="space-y-2">
                    ${sounds.slice(0, 3).map(s => `
                        <a href="/sounds/${s.slug}" class="block text-sm text-[#34D399] hover:text-[#6EE7B7] transition-colors">
                            ${s.title}
                        </a>
                    `).join('')}
                    ${sounds.length > 3 ? `<div class="text-xs text-[#5A6B65]">+ ${sounds.length - 3} autres</div>` : ''}
                </div>
            </div>
        `;

        marker.bindPopup(popupContent, {
            className: 'dark-popup',
            closeButton: false,
        });

        markers.push(marker);
    });

    // Fit bounds if markers exist
    if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
});

onUnmounted(() => {
    if (map) {
        map.remove();
    }
});
</script>

<template>
    <div ref="mapContainer" class="w-full h-full min-h-[500px] rounded-2xl overflow-hidden" />
</template>

<style>
@import 'leaflet/dist/leaflet.css';

.dark-popup .leaflet-popup-content-wrapper {
    background: #111815;
    border: 1px solid rgba(255, 255, 255, 0.04);
    border-radius: 1rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
}

.dark-popup .leaflet-popup-tip {
    background: #111815;
    border: 1px solid rgba(255, 255, 255, 0.04);
}

.custom-marker {
    background: transparent;
    border: none;
}
</style>
