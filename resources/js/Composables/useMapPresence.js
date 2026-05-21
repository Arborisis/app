import { ref, onMounted, onUnmounted } from 'vue';
import L from 'leaflet';
import echo from '@/echo';

/**
 * Composable pour gérer la présence temps réel sur la carte.
 * Combine WebSocket (Echo/Reverb) + fallback HTTP polling.
 */
export function useMapPresence(mapInstance, currentUserId, options = {}) {
    const presences = ref(new Map());
    const presenceLayer = ref(null);
    const isConnected = ref(false);
    const fetchInterval = ref(null);
    const echoChannel = ref(null);

    const PRESENCE_FETCH_MS = 10000; // 10s fallback
    const MARKER_ANIMATION_MS = 500;

    /* ── Helpers ────────────────────────────────────────── */
    const getMarkerColor = (mode, userId) => {
        if (userId === currentUserId.value) return { fill: '#10B981', glow: 'shadow-emerald-500/50' };
        if (mode === 'friends_only') return { fill: '#38BDF8', glow: 'shadow-sky-400/40' };
        if (mode === 'public_zone') return { fill: '#FBBF24', glow: 'shadow-amber-400/40' };
        return { fill: '#9CA3AF', glow: 'shadow-gray-400/30' };
    };

    const createMarkerHtml = (presence) => {
        const isMe = presence.user_id === currentUserId;
        const colors = getMarkerColor(presence.visibility_mode, presence.user_id);
        const size = isMe ? 16 : 12;
        const haloClass = isMe ? 'animate-ping' : 'animate-pulse';

        return `
            <div class="relative flex items-center justify-center" style="width:${size}px;height:${size}px;">
                <div class="absolute inset-0 rounded-full opacity-40 ${haloClass}"
                     style="background:${colors.fill}; animation-duration:${isMe ? '2s' : '3s'};"></div>
                <div class="relative rounded-full border-2 border-white shadow-lg ${colors.glow}"
                     style="width:${size}px;height:${size}px;background:${colors.fill};"></div>
            </div>
        `;
    };

    const animateMarkerTo = (marker, targetLatLng) => {
        const startLatLng = marker.getLatLng();
        const startTime = performance.now();

        const animate = (now) => {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / MARKER_ANIMATION_MS, 1);
            // easeOutQuad
            const ease = 1 - (1 - progress) * (1 - progress);

            const lat = startLatLng.lat + (targetLatLng.lat - startLatLng.lat) * ease;
            const lng = startLatLng.lng + (targetLatLng.lng - startLatLng.lng) * ease;
            marker.setLatLng([lat, lng]);

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    };

    /* ── Layer management ──────────────────────────────── */
    const ensureLayer = () => {
        if (!presenceLayer.value && mapInstance.value) {
            presenceLayer.value = L.layerGroup().addTo(mapInstance.value);
        }
        return presenceLayer.value;
    };

    const updateOrCreateMarker = (presence) => {
        if (presence.user_id === currentUserId.value) return;

        const layer = ensureLayer();
        if (!layer) return;

        let marker = presences.value.get(presence.user_id)?.marker;
        const latLng = [presence.latitude, presence.longitude];

        if (!marker) {
            const icon = L.divIcon({
                className: 'user-presence-marker',
                html: createMarkerHtml(presence),
                iconSize: [20, 20],
                iconAnchor: [10, 10],
            });

            marker = L.marker(latLng, { icon, zIndexOffset: 500 });
            marker.addTo(layer);

            marker.on('click', (e) => {
                e.originalEvent?.stopPropagation();
                if (typeof options.onUserClick === 'function') {
                    options.onUserClick(presence);
                }
            });

            // Popup
            const name = presence.user_name ?? 'Enregistreur anonyme';
            const distanceText = presence.distance_meters
                ? `${Math.round(presence.distance_meters)}m`
                : presence.last_seen_at ?? '';

            marker.bindPopup(`
                <div class="text-xs text-arbor-cream font-medium">${name}</div>
                <div class="text-[10px] text-arbor-sage/70">${distanceText}</div>
            `, {
                closeButton: false,
                className: 'arbor-popup',
                offset: [0, -8],
            });
        } else {
            animateMarkerTo(marker, { lat: presence.latitude, lng: presence.longitude });
            // Update icon if visibility changed
            const icon = marker.getElement()?.querySelector('.relative');
            if (icon) {
                icon.innerHTML = createMarkerHtml(presence);
            }
        }

        presences.value.set(presence.user_id, {
            ...presence,
            marker,
            lastUpdated: Date.now(),
        });
    };

    const removeMarker = (userId) => {
        const data = presences.value.get(userId);
        if (data?.marker) {
            presenceLayer.value?.removeLayer(data.marker);
        }
        presences.value.delete(userId);
    };

    /* ── Fetching ───────────────────────────────────────── */
    const fetchPresences = async () => {
        if (!mapInstance.value) return;

        const bounds = mapInstance.value.getBounds();
        const params = new URLSearchParams({
            'bounds[south]': bounds.getSouth(),
            'bounds[north]': bounds.getNorth(),
            'bounds[west]': bounds.getWest(),
            'bounds[east]': bounds.getEast(),
        });

        try {
            const res = await fetch(`/api/map/presence?${params.toString()}`, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const geojson = await res.json();

            const activeIds = new Set();
            geojson.features?.forEach((feature) => {
                const p = feature.properties;
                const coords = feature.geometry.coordinates;
                const presence = {
                    user_id: p.user_id,
                    user_name: p.user_name,
                    latitude: coords[1],
                    longitude: coords[0],
                    visibility_mode: p.visibility_mode,
                    last_seen_at: p.last_seen_at,
                };
                activeIds.add(p.user_id);
                updateOrCreateMarker(presence);
            });

            // Remove stale markers not in current bounds
            for (const [userId] of presences.value) {
                if (!activeIds.has(userId)) {
                    removeMarker(userId);
                }
            }
        } catch (e) {
            console.error('Failed to fetch presences:', e);
        }
    };

    /* ── Echo / WebSocket ───────────────────────────────── */
    const subscribeEcho = () => {
        if (echoChannel.value) return;

        echoChannel.value = echo.channel('presence.map');

        echoChannel.value.listen('.presence.updated', (data) => {
            const presence = {
                user_id: data.user_id,
                user_name: data.visibility_mode === 'public_zone' ? data.user_name : null,
                latitude: data.latitude,
                longitude: data.longitude,
                visibility_mode: data.visibility_mode,
                last_seen_at: 'à l\'instant',
            };
            updateOrCreateMarker(presence);
        });

        echoChannel.value.listen('.presence.left', (data) => {
            removeMarker(data.user_id);
        });

        isConnected.value = true;
    };

    const unsubscribeEcho = () => {
        if (echoChannel.value) {
            echoChannel.value.stopListening('.presence.updated');
            echoChannel.value.stopListening('.presence.left');
            echo.leaveChannel('presence.map');
            echoChannel.value = null;
        }
        isConnected.value = false;
    };

    /* ── Lifecycle ──────────────────────────────────────── */
    onMounted(() => {
        subscribeEcho();

        // Wait for map to be ready before starting fetch
        const startFetching = () => {
            if (mapInstance.value) {
                fetchPresences();
                fetchInterval.value = setInterval(fetchPresences, PRESENCE_FETCH_MS);
            } else {
                setTimeout(startFetching, 200);
            }
        };
        startFetching();
    });

    onUnmounted(() => {
        unsubscribeEcho();
        if (fetchInterval.value) {
            clearInterval(fetchInterval.value);
        }
        if (presenceLayer.value) {
            mapInstance.value?.removeLayer(presenceLayer.value);
            presenceLayer.value = null;
        }
        presences.value.clear();
    });

    return {
        presences,
        isConnected,
        fetchPresences,
    };
}
