<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BlogPostCard from '@/Components/Blog/BlogPostCard.vue';

const props = defineProps({
    posts: { type: Object, default: () => ({ data: [] }) },
});
</script>

<template>
    <Head title="Chroniques du monde sonore" />

    <GuestLayout>
        <div class="relative min-h-screen bg-arbor-night">
            <!-- Background glow -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-hero-glow opacity-40" />
            </div>

            <div class="relative z-10 pt-28 pb-24 section-padding">
                <div class="max-w-6xl mx-auto">
                    <!-- Header -->
                    <div class="text-center mb-16 animate-fade-in">
                        <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-bold text-arbor-cream leading-tight mb-5">
                            Chroniques du monde sonore
                        </h1>
                        <div class="w-16 h-px bg-arbor-emerald/30 mx-auto mb-6"></div>
                        <p class="text-arbor-sage text-lg max-w-xl mx-auto leading-relaxed">
                            Récits, réflexions et découvertes sur l'art du field recording et l'écologie sonore.
                        </p>
                        <p class="text-arbor-sage/60 text-sm mt-3">
                            Chaque jour, une nouvelle chronique rédigée par notre intelligence artificielle naturaliste.
                        </p>
                    </div>

                    <!-- Grid -->
                    <div v-if="posts.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <BlogPostCard
                            v-for="(post, index) in posts.data"
                            :key="post.id"
                            :post="post"
                            :style="`animation: fadeInUp 0.5s ease-out forwards; animation-delay: ${index * 0.08}s; opacity: 0;`"
                        />
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-20 glass-card animate-fade-in">
                        <div class="w-16 h-16 rounded-2xl bg-arbor-moss/10 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-arbor-moss/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                        </div>
                        <h3 class="font-display text-xl text-arbor-cream mb-2">Aucune chronique pour le moment</h3>
                        <p class="text-arbor-sage text-sm">Les premières chroniques apparaîtront très bientôt.</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="posts.links && posts.links.length > 3" class="flex justify-center gap-2 mt-12">
                        <Link
                            v-for="link in posts.links"
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
