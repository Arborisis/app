<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const soundWalks = ref([]);
const loading = ref(true);

async function fetchSoundWalks() {
    try {
        const res = await fetch('/api/sound-walks', {
            headers: { Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            soundWalks.value = data.features ?? [];
        }
    } catch (e) {
        console.warn('Failed to load sound walks', e);
    } finally {
        loading.value = false;
    }
}

onMounted(fetchSoundWalks);

function formatDuration(minutes) {
    if (!minutes) return '-';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h > 0) return `${h}h ${m}min`;
    return `${m} min`;
}
</script>

<template>
    <Head title="Balades field recording" />
    <GuestLayout>
        <div class="min-h-screen bg-arbor-night">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16">
                <div class="mb-10">
                    <h1 class="font-display text-3xl md:text-4xl font-bold text-arbor-cream">
                        Balades field recording
                    </h1>
                    <p class="mt-3 text-arbor-sage max-w-2xl">
                        Explore les itinéraires sonores partagés par la communauté Arborisis. Chaque balade est un parcours de points d'écoute dans la nature.
                    </p>
                </div>

                <div v-if="loading" class="text-arbor-sage text-sm">Chargement…</div>

                <div v-else-if="soundWalks.length === 0" class="text-arbor-sage text-sm">
                    Aucune balade disponible pour le moment.
                </div>

                <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="walk in soundWalks"
                        :key="walk.properties.id"
                        :href="`/sound-walks/${walk.properties.slug}`"
                        class="group block rounded-[12px] border border-arbor-mineral/15 bg-arbor-ink/40 p-5 transition hover:border-arbor-firefly/30 hover:bg-arbor-ink/60"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="font-display text-lg font-semibold text-arbor-cream group-hover:text-arbor-firefly transition">
                                {{ walk.properties.title }}
                            </h2>
                            <span
                                v-if="walk.properties.moderation_status === 'pending'"
                                class="shrink-0 rounded-[6px] bg-amber-500/10 px-2 py-0.5 text-[10px] text-amber-400"
                            >
                                En attente
                            </span>
                        </div>
                        <p v-if="walk.properties.description" class="mt-2 text-sm text-arbor-sage line-clamp-2">
                            {{ walk.properties.description }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2 text-xs text-arbor-sage">
                            <span v-if="walk.properties.estimated_duration_minutes" class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                {{ formatDuration(walk.properties.estimated_duration_minutes) }}
                            </span>
                            <span v-if="walk.properties.difficulty_level" class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                {{ walk.properties.difficulty_level }}/5
                            </span>
                            <span v-if="walk.properties.waypoints_count" class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ walk.properties.waypoints_count }} arrêts
                            </span>
                        </div>
                        <div v-if="walk.properties.user" class="mt-3 pt-3 border-t border-arbor-mineral/10 text-xs text-arbor-sage">
                            par {{ walk.properties.user.name }}
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
