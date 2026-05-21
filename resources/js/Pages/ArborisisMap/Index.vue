<script setup>
import { ref, onMounted, watch, computed, nextTick, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PointDetailDrawer from '@/Components/Gamification/PointDetailDrawer.vue';
import CreateArborisisPointForm from '@/Components/Gamification/CreateArborisisPointForm.vue';
import VisitButton from '@/Components/Gamification/VisitButton.vue';
import VisitSuccessModal from '@/Components/Gamification/VisitSuccessModal.vue';
import PresenceToggle from '@/Components/Gamification/PresenceToggle.vue';
import PrivacyModeSelector from '@/Components/Gamification/PrivacyModeSelector.vue';
import MapRadar from '@/Components/Gamification/MapRadar.vue';
import NearbyUserDrawer from '@/Components/Gamification/NearbyUserDrawer.vue';
import GroupEventDrawer from '@/Components/Gamification/GroupEventDrawer.vue';
import { useMapPresence } from '@/Composables/useMapPresence';
import { useNearbyNotifications } from '@/Composables/useNearbyNotifications';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '@css/map.css';

const props = defineProps({
    auth: Object,
});

const points = ref([]);
const loading = ref(false);
const selectedPoint = ref(null);
const drawerOpen = ref(false);
const createMode = ref(false);
const mapContainer = ref(null);
const map = ref(null);
const markersLayer = ref(null);
const groupEventLayer = ref(null);
const visitSuccessOpen = ref(false);
const visitSuccessTitle = ref('');
const createLat = ref(null);
const createLng = ref(null);
const tempMarker = ref(null);

const presenceActive = ref(false);
const presenceMode = ref('invisible');
const flashMessage = ref('');
const flashType = ref('error');
const sidebarCollapsed = ref(typeof window !== 'undefined' ? window.innerWidth < 768 : false);

/* ── Nearby & group event drawers ───────────────────── */
const selectedNearbyUser = ref(null);
const nearbyDrawerOpen = ref(false);
const proximityToast = ref(null);

const selectedGroupEvent = ref(null);
const groupEventDrawerOpen = ref(false);
const groupEvents = ref([]);
const myEventIds = ref(new Set());

/* ── Map presence & radar ───────────────────────────── */
const radarStyle = ref({ left: '50%', top: '50%' });
const currentUserId = computed(() => props.auth?.user?.id ?? null);

const handleUserClick = (presence) => {
    selectedNearbyUser.value = presence;
    nearbyDrawerOpen.value = true;
};

const { presences: otherPresences, isConnected: presenceSocketConnected } = useMapPresence(map, currentUserId, {
    onUserClick: handleUserClick,
});

const { lastNotification, dismiss: dismissNotification } = useNearbyNotifications(currentUserId);

const setFlash = (message, type = 'error') => {
    flashMessage.value = message;
    flashType.value = type;
    setTimeout(() => { flashMessage.value = ''; }, 5000);
};

/* ── Real-time location ─────────────────────────────── */
const userLocationMarker = ref(null);
const updateRadarPosition = () => {
    if (!map.value || !userLocationMarker.value) return;
    const point = map.value.latLngToContainerPoint(userLocationMarker.value.getLatLng());
    radarStyle.value = {
        left: `${point.x}px`,
        top: `${point.y}px`,
    };
};
const userLocationWatchId = ref(null);
const userLocationAccuracyCircle = ref(null);
const presenceUpdateInterval = ref(null);
const lastSentPosition = ref(null);
const lastGpsAccuracy = ref(10);

/* ── Category colors ─────────────────────────────────── */
const categoryColors = {
    birds: '#FBBF24',
    forest: '#34D399',
    water: '#60A5FA',
    insects: '#A3E635',
    wind: '#C7D2FE',
    night_ambience: '#A78BFA',
    meeting_point: '#F472B6',
    quiet_spot: '#8FA68E',
    educational_zone: '#38BDF8',
    other: '#9CA3AF',
};

const getCategoryColor = (cat) => categoryColors[cat] || '#9CA3AF';

/* ── Data fetching ──────────────────────────────────── */
const fetchPoints = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/arborisis-points', { credentials: 'same-origin' });
        const data = await response.json();
        points.value = data.features ?? [];
    } catch (e) {
        console.error('Failed to load points:', e);
        points.value = [];
    } finally {
        loading.value = false;
    }
};

/* ── Map ────────────────────────────────────────────── */
const initMap = () => {
    if (!mapContainer.value) return;

    map.value = L.map(mapContainer.value, {
        zoomControl: false,
        attributionControl: false,
        scrollWheelZoom: window.innerWidth >= 768,
    }).setView([46.603354, 1.888334], 5);

    L.control.zoom({ position: 'bottomright' }).addTo(map.value);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CARTO',
        subdomains: 'abcd',
        maxZoom: 19,
    }).addTo(map.value);

    markersLayer.value = L.layerGroup().addTo(map.value);
    groupEventLayer.value = L.layerGroup().addTo(map.value);
    updateMarkers();

    map.value.on('click', handleMapClick);
    map.value.on('move', updateRadarPosition);
    map.value.on('zoom', updateRadarPosition);

    fetchGroupEvents();
};

const clearTempMarker = () => {
    if (tempMarker.value) {
        map.value?.removeLayer(tempMarker.value);
        tempMarker.value = null;
    }
    createLat.value = null;
    createLng.value = null;
};

const handleMapClick = (e) => {
    if (!map.value) return;

    // Only create a point if we're already in create mode (user clicked "Proposer un lieu" first)
    if (!createMode.value) return;

    drawerOpen.value = true;
    selectedPoint.value = null;

    createLat.value = e.latlng.lat;
    createLng.value = e.latlng.lng;

    if (!tempMarker.value) {
        const tempIcon = L.divIcon({
            className: 'temp-create-marker',
            html: `<div class="w-5 h-5 rounded-full bg-arbor-emerald border-2 border-white shadow-lg relative">
                <div class="absolute inset-0 rounded-full bg-arbor-emerald animate-ping opacity-50"></div>
            </div>`,
            iconSize: [20, 20],
            iconAnchor: [10, 10],
        });
        tempMarker.value = L.marker([e.latlng.lat, e.latlng.lng], {
            icon: tempIcon,
            draggable: true,
            zIndexOffset: 2000,
        }).addTo(map.value);

        tempMarker.value.on('dragend', (event) => {
            const latLng = event.target.getLatLng();
            createLat.value = latLng.lat;
            createLng.value = latLng.lng;
        });
    } else {
        tempMarker.value.setLatLng([e.latlng.lat, e.latlng.lng]);
    }
};

const updateMarkers = () => {
    if (!markersLayer.value || !map.value) return;
    markersLayer.value.clearLayers();

    points.value.forEach((point) => {
        const coords = point.geometry.coordinates;
        const p = point.properties;
        const color = getCategoryColor(p.category_value);

        const marker = L.circleMarker([coords[1], coords[0]], {
            radius: 8,
            fillColor: color,
            color: color,
            weight: 2,
            opacity: 0.8,
            fillOpacity: 0.4,
        });

        marker.on('click', (e) => {
            e.originalEvent.stopPropagation();
            clearTempMarker();
            selectedPoint.value = {
                ...p,
                latitude: coords[1],
                longitude: coords[0],
            };
            drawerOpen.value = true;
            createMode.value = false;
        });

        markersLayer.value.addLayer(marker);
    });
};

watch(points, updateMarkers, { deep: true });

watch(drawerOpen, (open) => {
    if (!open) {
        createMode.value = false;
        clearTempMarker();
    }
});

/* ── User location marker ───────────────────────────── */
const updateUserLocationMarker = (lat, lng, accuracy = 0) => {
    if (!map.value) return;

    if (!userLocationMarker.value) {
        const userIcon = L.divIcon({
            className: 'user-location-marker',
            html: `<div class="w-4 h-4 rounded-full bg-arbor-emerald border-2 border-white shadow-lg shadow-arbor-emerald/50 relative">
                <div class="absolute inset-0 rounded-full bg-arbor-emerald animate-ping opacity-40"></div>
            </div>`,
            iconSize: [16, 16],
            iconAnchor: [8, 8],
        });
        userLocationMarker.value = L.marker([lat, lng], { icon: userIcon, zIndexOffset: 1000 }).addTo(map.value);
    } else {
        userLocationMarker.value.setLatLng([lat, lng]);
    }

    nextTick(updateRadarPosition);

    if (accuracy > 0) {
        if (!userLocationAccuracyCircle.value) {
            userLocationAccuracyCircle.value = L.circle([lat, lng], {
                radius: accuracy,
                fillColor: '#10B981',
                color: '#10B981',
                weight: 1,
                opacity: 0.3,
                fillOpacity: 0.1,
            }).addTo(map.value);
        } else {
            userLocationAccuracyCircle.value.setLatLng([lat, lng]);
            userLocationAccuracyCircle.value.setRadius(accuracy);
        }
    }
};

const clearUserLocationMarker = () => {
    if (userLocationMarker.value) {
        map.value?.removeLayer(userLocationMarker.value);
        userLocationMarker.value = null;
    }
    if (userLocationAccuracyCircle.value) {
        map.value?.removeLayer(userLocationAccuracyCircle.value);
        userLocationAccuracyCircle.value = null;
    }
};

const sendPresenceUpdate = async (lat, lng) => {
    try {
        await fetch('/api/presence/update', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({
                latitude: lat,
                longitude: lng,
                visibility_mode: presenceMode.value,
            }),
        });
    } catch (e) {
        console.error('Presence update failed:', e);
    }
};

const haversineDistance = (lat1, lng1, lat2, lng2) => {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

/* ── Actions ────────────────────────────────────────── */
const handleVisit = async () => {
    if (!selectedPoint.value?.slug) {
        setFlash('Point non valide pour la visite.', 'error');
        return;
    }

    const sendVisit = (lat, lng, accuracy) => {
        fetch(`/api/arborisis-points/${selectedPoint.value.slug}/visit`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                latitude: lat,
                longitude: lng,
                accuracy: accuracy,
                consent_given: true,
            }),
        })
        .then(async (response) => {
            const data = await response.json();
            if (response.ok) {
                visitSuccessTitle.value = selectedPoint.value.title;
                visitSuccessOpen.value = true;
                drawerOpen.value = false;
            } else if (response.status === 401) {
                setFlash('Vous devez être connecté pour visiter un lieu.', 'error');
            } else {
                setFlash(data.message || 'Visite impossible', 'error');
            }
        })
        .catch((e) => {
            console.error('Visit error:', e);
            setFlash('Erreur lors de la visite', 'error');
        });
    };

    // Fallback coordinates (point itself)
    const fallbackLat = selectedPoint.value.latitude;
    const fallbackLng = selectedPoint.value.longitude;
    const fallbackAccuracy = lastGpsAccuracy.value || 50;

    let hasResponded = false;
    const doVisit = (lat, lng, accuracy) => {
        if (hasResponded) return;
        hasResponded = true;
        sendVisit(lat, lng, accuracy);
    };

    if (navigator.geolocation) {
        // Force fallback after 6s if geolocation never responds (blocked, timeout, etc.)
        const timeoutId = setTimeout(() => {
            doVisit(fallbackLat, fallbackLng, fallbackAccuracy);
        }, 6000);

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                clearTimeout(timeoutId);
                doVisit(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
            },
            (err) => {
                clearTimeout(timeoutId);
                console.warn('Geolocation error in visit:', err);
                doVisit(fallbackLat, fallbackLng, fallbackAccuracy);
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 60000 }
        );
    } else {
        doVisit(fallbackLat, fallbackLng, fallbackAccuracy);
    }
};

const handleCreatePoint = async (formData) => {
    try {
        const response = await fetch('/api/arborisis-points', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(formData),
        });

        const data = await response.json();

        if (response.ok) {
            createMode.value = false;
            drawerOpen.value = false;
            clearTempMarker();
            fetchPoints();
            setFlash(data.message || 'Lieu proposé avec succès', 'success');
        } else if (response.status === 401) {
            setFlash('Vous devez être connecté pour proposer un lieu. Veuillez rafraîchir la page.', 'error');
        } else if (data.errors) {
            setFlash(Object.values(data.errors).flat().join('\n'), 'error');
        } else if (data.message) {
            setFlash(data.message, 'error');
        } else {
            setFlash('Une erreur est survenue lors de la création.', 'error');
        }
    } catch (e) {
        console.error(e);
        setFlash('Erreur lors de la création', 'error');
    }
};

const startCreateMode = () => {
    createMode.value = true;
    drawerOpen.value = true;
    selectedPoint.value = null;

    // Try to use current geolocation as fallback
    if (!createLat.value && !createLng.value && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                createLat.value = lat;
                createLng.value = lng;

                if (map.value) {
                    const tempIcon = L.divIcon({
                        className: 'temp-create-marker',
                        html: `<div class="w-5 h-5 rounded-full bg-arbor-emerald border-2 border-white shadow-lg relative">
                            <div class="absolute inset-0 rounded-full bg-arbor-emerald animate-ping opacity-50"></div>
                        </div>`,
                        iconSize: [20, 20],
                        iconAnchor: [10, 10],
                    });
                    if (!tempMarker.value) {
                        tempMarker.value = L.marker([lat, lng], {
                            icon: tempIcon,
                            draggable: true,
                            zIndexOffset: 2000,
                        }).addTo(map.value);
                        tempMarker.value.on('dragend', (event) => {
                            const latLng = event.target.getLatLng();
                            createLat.value = latLng.lat;
                            createLng.value = latLng.lng;
                        });
                    } else {
                        tempMarker.value.setLatLng([lat, lng]);
                    }
                }
            },
            () => {
                // Silent fail — user will click on map
            },
            { enableHighAccuracy: true, timeout: 5000 }
        );
    }
};

const togglePresence = async () => {
    if (presenceActive.value) {
        // Turn off
        if (userLocationWatchId.value !== null) {
            navigator.geolocation.clearWatch(userLocationWatchId.value);
            userLocationWatchId.value = null;
        }
        if (presenceUpdateInterval.value) {
            clearInterval(presenceUpdateInterval.value);
            presenceUpdateInterval.value = null;
        }
        await fetch('/api/presence', { method: 'DELETE', credentials: 'same-origin' });
        presenceActive.value = false;
        clearUserLocationMarker();
        lastSentPosition.value = null;
    } else {
        // Turn on - real-time tracking
        if (!navigator.geolocation) {
            console.warn('Géolocalisation non supportée par ce navigateur');
            return;
        }

        userLocationWatchId.value = navigator.geolocation.watchPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const accuracy = pos.coords.accuracy;

                updateUserLocationMarker(lat, lng, accuracy);
                lastGpsAccuracy.value = accuracy;

                const isFirstUpdate = !lastSentPosition.value;
                const hasMovedSignificantly = lastSentPosition.value &&
                    haversineDistance(lastSentPosition.value.lat, lastSentPosition.value.lng, lat, lng) > 50;

                if (isFirstUpdate || hasMovedSignificantly) {
                    sendPresenceUpdate(lat, lng);
                    lastSentPosition.value = { lat, lng };
                }

                presenceActive.value = true;
            },
            (err) => {
                console.error('Geolocation error:', err);
            },
            { enableHighAccuracy: true, maximumAge: 10000, timeout: 10000 }
        );

        // Periodic server updates every 30s
        presenceUpdateInterval.value = setInterval(() => {
            if (userLocationMarker.value) {
                const latLng = userLocationMarker.value.getLatLng();
                sendPresenceUpdate(latLng.lat, latLng.lng);
                lastSentPosition.value = { lat: latLng.lat, lng: latLng.lng };
            }
        }, 30000);
    }
};

/* ── Proximity toast ────────────────────────────────── */
watch(lastNotification, (notif) => {
    if (notif) {
        proximityToast.value = notif;
        setTimeout(() => { proximityToast.value = null; }, 8000);
    }
});

/* ── Nearby interactions ────────────────────────────── */
const handleGreet = async () => {
    if (!selectedNearbyUser.value) return;
    try {
        const res = await fetch(`/api/nearby/greet/${selectedNearbyUser.value.user_id}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });
        const data = await res.json();
        setFlash(data.message, res.ok ? 'success' : 'error');
        if (res.ok) nearbyDrawerOpen.value = false;
    } catch (e) {
        setFlash('Erreur lors du salut', 'error');
    }
};

const handleShare = () => {
    setFlash('Fonctionnalité de partage à venir en Phase 2+', 'success');
};

const handleInvite = () => {
    setFlash('Fonctionnalité d\'invitation à venir en Phase 3', 'success');
};

/* ── Group events ───────────────────────────────────── */
const fetchGroupEvents = async () => {
    if (!map.value) return;
    const center = map.value.getCenter();
    try {
        const res = await fetch(`/api/group-events/nearby?lat=${center.lat}&lng=${center.lng}&radius=10`, {
            credentials: 'same-origin',
        });
        if (!res.ok) return;
        const data = await res.json();
        groupEvents.value = data.features ?? [];
    } catch (e) {
        console.error('Failed to fetch group events:', e);
    }
};

const renderGroupEvents = () => {
    if (!groupEventLayer.value || !map.value) return;
    groupEventLayer.value.clearLayers();

    const typeColors = {
        dawn_chorus: '#FBBF24',
        soundwalk: '#38BDF8',
        night_ambience: '#A78BFA',
        freestyle: '#34D399',
    };

    groupEvents.value.forEach((feature) => {
        const coords = feature.geometry.coordinates;
        const p = feature.properties;
        const color = typeColors[p.event_type] || '#9CA3AF';

        const marker = L.circleMarker([coords[1], coords[0]], {
            radius: 10,
            fillColor: color,
            color: color,
            weight: 2,
            opacity: 0.9,
            fillOpacity: 0.25,
        });

        marker.on('click', (e) => {
            e.originalEvent.stopPropagation();
            selectedGroupEvent.value = feature;
            groupEventDrawerOpen.value = true;
        });

        groupEventLayer.value.addLayer(marker);
    });
};

watch(groupEvents, renderGroupEvents, { deep: true });

const handleJoinEvent = async () => {
    if (!selectedGroupEvent.value) return;
    try {
        const res = await fetch(`/api/group-events/${selectedGroupEvent.value.properties.id}/join`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });
        const data = await res.json();
        setFlash(data.message, res.ok ? 'success' : 'error');
        if (res.ok) {
            myEventIds.value.add(selectedGroupEvent.value.properties.id);
            selectedGroupEvent.value.properties.participants_count += 1;
        }
    } catch (e) {
        setFlash('Erreur', 'error');
    }
};

const handleLeaveEvent = async () => {
    if (!selectedGroupEvent.value) return;
    try {
        const res = await fetch(`/api/group-events/${selectedGroupEvent.value.properties.id}/leave`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });
        const data = await res.json();
        setFlash(data.message, res.ok ? 'success' : 'error');
        if (res.ok) {
            myEventIds.value.delete(selectedGroupEvent.value.properties.id);
            selectedGroupEvent.value.properties.participants_count = Math.max(0, selectedGroupEvent.value.properties.participants_count - 1);
        }
    } catch (e) {
        setFlash('Erreur', 'error');
    }
};

const handleCheckInEvent = async () => {
    if (!selectedGroupEvent.value) return;
    if (!navigator.geolocation) {
        setFlash('Géolocalisation non disponible', 'error');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            try {
                const res = await fetch(`/api/group-events/${selectedGroupEvent.value.properties.id}/check-in`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify({
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: pos.coords.accuracy,
                    }),
                });
                const data = await res.json();
                setFlash(data.message, res.ok ? 'success' : 'error');
            } catch (e) {
                setFlash('Erreur de check-in', 'error');
            }
        },
        () => setFlash('Impossible d\'obtenir ta position', 'error'),
        { enableHighAccuracy: true, timeout: 5000 }
    );
};

onMounted(() => {
    fetchPoints();
    nextTick(() => initMap());
});

onUnmounted(() => {
    if (userLocationWatchId.value !== null) {
        navigator.geolocation.clearWatch(userLocationWatchId.value);
    }
    if (presenceUpdateInterval.value) {
        clearInterval(presenceUpdateInterval.value);
    }
});
</script>

<template>
    <Head title="Carte Arborisis" />
    <AuthenticatedLayout>
        <div class="relative isolate h-[calc(100vh-4rem)] min-h-[600px] overflow-hidden">
            <!-- Map -->
            <div ref="mapContainer" class="w-full h-full" />

            <!-- Radar overlay -->
            <MapRadar :active="presenceActive" :style="radarStyle" />

            <!-- Flash message -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="-translate-y-4 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="-translate-y-4 opacity-0"
            >
                <div
                    v-if="flashMessage"
                    class="absolute top-4 left-1/2 -translate-x-1/2 z-toast px-4 py-3 rounded-xl shadow-lg max-w-sm w-[calc(100%-2rem)] text-sm text-center pointer-events-auto"
                    :class="flashType === 'success' ? 'bg-arbor-emerald/20 border border-arbor-emerald/30 text-arbor-emerald' : 'bg-red-500/20 border border-red-500/30 text-red-400'"
                >
                    {{ flashMessage }}
                </div>
            </Transition>

            <!-- Floating header -->
            <div class="absolute top-6 left-1/2 -translate-x-1/2 z-map text-center pointer-events-none">
                <h1 class="font-display text-3xl font-semibold text-arbor-cream tracking-tight drop-shadow-lg">
                    Carte Arborisis
                </h1>
                <p class="text-arbor-sage/80 text-sm mt-1 font-light drop-shadow">
                    Découvre les échos naturels autour de toi
                </p>
            </div>

            <!-- Controls sidebar -->
            <div class="absolute top-4 left-4 z-map max-w-[calc(100vw-2rem)]">
                <!-- Collapsed chip -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="-translate-x-2 opacity-0"
                    enter-to-class="translate-x-0 opacity-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="translate-x-0 opacity-100"
                    leave-to-class="-translate-x-2 opacity-0"
                >
                    <button
                        v-if="sidebarCollapsed"
                        @click="sidebarCollapsed = false"
                        class="glass-card shadow-2xl shadow-black/25 p-3 flex items-center gap-2 text-arbor-cream hover:text-white transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span class="text-xs font-medium">Menu</span>
                        <span
                            v-if="presenceActive"
                            class="w-2 h-2 rounded-full bg-arbor-emerald animate-pulse"
                        />
                    </button>
                </Transition>

                <!-- Expanded panel -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="-translate-x-2 opacity-0"
                    enter-to-class="translate-x-0 opacity-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="translate-x-0 opacity-100"
                    leave-to-class="-translate-x-2 opacity-0"
                >
                    <div
                        v-if="!sidebarCollapsed"
                        class="glass-card shadow-2xl shadow-black/25 p-4 space-y-4 w-80"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-arbor-sage/50 uppercase tracking-wider">Menu</span>
                            <button
                                @click="sidebarCollapsed = true"
                                class="text-arbor-sage/40 hover:text-arbor-cream transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Create button -->
                        <button
                            @click="startCreateMode"
                            class="w-full py-2.5 rounded-xl bg-arbor-emerald text-arbor-night font-semibold text-sm hover:bg-arbor-emerald/90 transition-colors flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Proposer un lieu
                        </button>

                        <!-- Presence -->
                        <div class="pt-3 border-t border-white/10">
                            <PresenceToggle
                                :is-active="presenceActive"
                                :mode="presenceMode"
                                @toggle="togglePresence"
                            />
                            <div v-if="presenceActive" class="mt-3">
                                <PrivacyModeSelector v-model="presenceMode" />
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="pt-3 border-t border-white/10 text-xs text-arbor-sage/60">
                            <p>{{ points.length }} point{{ points.length > 1 ? 's' : '' }} sur la carte</p>
                            <p v-if="otherPresences.size > 0" class="mt-1">
                                {{ otherPresences.size }} enregistreur{{ otherPresences.size > 1 ? 's' : '' }} à proximité
                            </p>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Point Detail Drawer -->
            <PointDetailDrawer
                :point="selectedPoint"
                :is-open="drawerOpen && !createMode"
                @close="drawerOpen = false"
                @visit="handleVisit"
            />

            <!-- Create Point Drawer -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="translate-x-full opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-full opacity-0"
            >
                <div
                    v-if="drawerOpen && createMode"
                    class="absolute top-4 right-4 bottom-4 w-80 max-w-[calc(100vw-2rem)] z-map bg-arbor-night/95 backdrop-blur-xl border border-white/5 rounded-2xl shadow-2xl overflow-hidden flex flex-col p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-arbor-cream">Proposer un lieu</h2>
                        <button @click="drawerOpen = false" class="text-arbor-sage/50 hover:text-arbor-cream">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div
                            v-if="createLat == null || createLng == null"
                            class="mb-4 rounded-xl bg-arbor-emerald/10 border border-arbor-emerald/20 px-3 py-2.5 text-center"
                        >
                            <p class="text-xs text-arbor-emerald font-medium">
                                👆 Cliquez sur la carte pour positionner votre point
                            </p>
                        </div>
                        <CreateArborisisPointForm
                            :initial-lat="createLat"
                            :initial-lng="createLng"
                            @submit="handleCreatePoint"
                            @cancel="drawerOpen = false"
                        />
                    </div>
                </div>
            </Transition>

            <!-- Proximity toast -->
            <Transition
                enter-active-class="transition ease-out duration-500"
                enter-from-class="translate-y-4 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-300"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-4 opacity-0"
            >
                <div
                    v-if="proximityToast"
                    class="absolute bottom-6 left-1/2 -translate-x-1/2 z-toast px-5 py-3 rounded-2xl shadow-2xl max-w-sm w-[calc(100%-2rem)] text-sm pointer-events-auto bg-arbor-night/95 backdrop-blur-xl border border-arbor-emerald/20"
                >
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-arbor-emerald/20 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-arbor-cream font-medium">
                                {{ proximityToast.initiatorName }} est à {{ proximityToast.distanceMeters }}m
                            </p>
                            <p class="text-xs text-arbor-sage/60 mt-0.5">
                                Un enregistreur est tout près — peut-être une belle rencontre ?
                            </p>
                        </div>
                        <button @click="proximityToast = null" class="text-arbor-sage/40 hover:text-arbor-cream shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Nearby user drawer -->
            <NearbyUserDrawer
                :is-open="nearbyDrawerOpen"
                :user="selectedNearbyUser"
                :distance="selectedNearbyUser ? haversineDistance(
                    userLocationMarker?.getLatLng()?.lat ?? 0,
                    userLocationMarker?.getLatLng()?.lng ?? 0,
                    selectedNearbyUser.latitude,
                    selectedNearbyUser.longitude
                ) : null"
                @close="nearbyDrawerOpen = false"
                @greet="handleGreet"
                @share="handleShare"
                @invite="handleInvite"
            />

            <!-- Group event drawer -->
            <GroupEventDrawer
                :is-open="groupEventDrawerOpen"
                :event="selectedGroupEvent?.properties"
                :is-participant="selectedGroupEvent ? myEventIds.has(selectedGroupEvent.properties.id) : false"
                :can-check-in="false"
                @close="groupEventDrawerOpen = false"
                @join="handleJoinEvent"
                @leave="handleLeaveEvent"
                @checkIn="handleCheckInEvent"
            />

            <!-- Visit Success Modal -->
            <VisitSuccessModal
                :is-open="visitSuccessOpen"
                :point-title="visitSuccessTitle"
                :xp-gained="10"
                @close="visitSuccessOpen = false"
            />
        </div>
    </AuthenticatedLayout>
</template>
