<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    creators: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const search = ref(props.filters.search || '');
let searchTimeout = null;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('creators.index'), { search: value }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300);
});

const getAvatarUrl = (creator) => {
    return creator?.avatar_url || creator?.profile?.avatar_url || creator?.profile?.avatarUrl || null;
};

const getInitials = (name) => {
    return name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() || '?';
};
</script>

<template>
    <Head title="Créateurs" />

    <GuestLayout>
        <div class="relative min-h-screen bg-arbor-night">
            <!-- Background -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-hero-glow opacity-40" />
            </div>

            <div class="relative z-10 pt-28 pb-24 section-padding">
                <div class="max-w-6xl mx-auto">
                    <!-- Header -->
                    <div class="text-center mb-12 animate-fade-in">
                        <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-bold text-arbor-cream leading-tight mb-4">
                            Les enregistreurs de la nature
                        </h1>
                        <p class="text-arbor-sage text-lg max-w-xl mx-auto">
                            Découvrez les créateurs qui capturent et partagent les sons du monde vivant.
                        </p>
                    </div>

                    <!-- Search -->
                    <div class="max-w-md mx-auto mb-12 animate-slide-up">
                        <div class="relative">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-arbor-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher un créateur..."
                                class="w-full bg-arbor-charcoal/50 border border-arbor-fog/50 rounded-xl pl-12 pr-4 py-3 text-arbor-cream placeholder-arbor-sage/50 focus:border-arbor-moss/50 focus:ring-1 focus:ring-arbor-moss/30 outline-none transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Grid -->
                    <div v-if="creators.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Link
                            v-for="(creator, index) in creators.data"
                            :key="creator.id"
                            :href="route('creators.show', creator.slug)"
                            class="glass-card p-6 group hover:bg-white/10 transition-transform duration-300 hover-lift"
                            :style="`animation: fadeInUp 0.5s ease-out forwards; animation-delay: ${index * 0.06}s; opacity: 0;`"
                        >
                            <div class="flex items-center gap-4 mb-4">
                                <div class="relative">
                                    <div v-if="getAvatarUrl(creator)" class="w-16 h-16 rounded-2xl overflow-hidden bg-arbor-deep">
                                        <img :src="getAvatarUrl(creator)" class="w-full h-full object-cover" loading="lazy" :alt="creator.name" />
                                    </div>
                                    <div v-else class="w-16 h-16 rounded-2xl bg-arbor-moss/20 flex items-center justify-center text-arbor-moss-light font-display text-xl">
                                        {{ getInitials(creator.name) }}
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-arbor-emerald border-2 border-arbor-night flex items-center justify-center">
                                        <svg class="w-3 h-3 text-arbor-night" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-arbor-cream font-semibold text-lg group-hover:text-arbor-emerald transition-colors truncate">
                                        {{ creator.name }}
                                    </h3>
                                    <p class="text-arbor-sage text-sm">{{ creator.public_sounds_count }} son{{ creator.public_sounds_count > 1 ? 's' : '' }}</p>
                                </div>
                            </div>
                            <p class="text-arbor-sage text-sm leading-relaxed line-clamp-2">
                                {{ creator.profile?.bio || 'Aucune bio pour le moment.' }}
                            </p>
                        </Link>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-20 glass-card animate-fade-in">
                        <div class="w-16 h-16 rounded-2xl bg-arbor-moss/10 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-arbor-moss/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="font-display text-xl text-arbor-cream mb-2">Aucun créateur trouvé</h3>
                        <p class="text-arbor-sage text-sm">Essayez une autre recherche.</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="creators.links && creators.links.length > 3" class="flex justify-center gap-2 mt-10">
                        <Link
                            v-for="link in creators.links"
                            :key="link.label"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1 rounded-lg text-sm transition-colors"
                            :class="link.active ? 'bg-arbor-emerald/20 text-arbor-emerald' : 'text-arbor-sage hover:bg-arbor-charcoal/50'"
                            preserve-state
                        />
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
