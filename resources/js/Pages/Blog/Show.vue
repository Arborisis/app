<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BlogPostContent from '@/Components/Blog/BlogPostContent.vue';

const props = defineProps({
    post: { type: Object, required: true },
    relatedSounds: { type: Array, default: () => [] },
    relatedCreators: { type: Array, default: () => [] },
    previousPost: { type: Object, default: null },
    nextPost: { type: Object, default: null },
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
};

const getAvatarUrl = (creator) => {
    return creator?.creator?.avatar_url || creator?.creator?.profile?.avatar_url || creator?.creator?.profile?.avatarUrl || null;
};

const getInitials = (name) => {
    return name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() || '?';
};
</script>

<template>
    <Head :title="post.title" />

    <GuestLayout>
        <div class="relative min-h-screen bg-arbor-night">
            <!-- Background glow -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-hero-glow opacity-30" />
            </div>

            <div class="relative z-10 pt-28 pb-24 section-padding">
                <div class="max-w-4xl mx-auto">
                    <!-- Back link -->
                    <div class="mb-8 animate-fade-in">
                        <Link
                            href="/blog"
                            class="inline-flex items-center text-arbor-sage hover:text-arbor-emerald transition-colors text-sm"
                        >
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Toutes les chroniques
                        </Link>
                    </div>

                    <!-- Header -->
                    <header class="mb-12 animate-fade-in">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="text-xs font-medium text-arbor-emerald bg-arbor-emerald/10 px-2.5 py-1 rounded-full">
                                Chronique
                            </span>
                            <span class="text-xs text-arbor-sage">
                                {{ formatDate(post.published_at) }}
                            </span>
                        </div>
                        <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl font-bold text-arbor-cream leading-tight mb-6">
                            {{ post.title }}
                        </h1>
                        <p v-if="post.subtitle" class="text-arbor-sage text-lg sm:text-xl leading-relaxed mb-8">
                            {{ post.subtitle }}
                        </p>
                        <!-- Excerpt card -->
                        <div v-if="post.excerpt" class="glass-card p-6 border-l-2 border-arbor-emerald/30 rounded-r-xl">
                            <p class="text-arbor-cream/70 text-base leading-relaxed italic">
                                {{ post.excerpt }}
                            </p>
                        </div>
                    </header>

                    <!-- Cover image -->
                    <div v-if="post.cover_image" class="mb-12 rounded-2xl overflow-hidden animate-slide-up">
                        <img
                            :src="post.cover_image"
                            :alt="post.title"
                            class="w-full aspect-[21/9] object-cover"
                        />
                    </div>

                    <!-- Content + Sidebar -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                        <!-- Main content -->
                        <div class="lg:col-span-8 animate-slide-up">
                            <BlogPostContent :content="post.content" />

                            <!-- Navigation -->
                            <div class="mt-16 pt-8 border-t border-arbor-glass-border">
                                <div class="flex flex-col sm:flex-row justify-between gap-4">
                                    <Link
                                        v-if="previousPost"
                                        :href="route('blog.show', previousPost.slug)"
                                        class="group flex items-start gap-3 text-left"
                                    >
                                        <svg class="w-5 h-5 text-arbor-sage group-hover:text-arbor-emerald transition-colors mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                        </svg>
                                        <div>
                                            <span class="text-xs text-arbor-sage block mb-1">Chronique précédente</span>
                                            <span class="text-arbor-cream font-medium group-hover:text-arbor-emerald transition-colors line-clamp-2">
                                                {{ previousPost.title }}
                                            </span>
                                        </div>
                                    </Link>
                                    <div v-else />

                                    <Link
                                        v-if="nextPost"
                                        :href="route('blog.show', nextPost.slug)"
                                        class="group flex items-start gap-3 text-right sm:flex-row-reverse"
                                    >
                                        <svg class="w-5 h-5 text-arbor-sage group-hover:text-arbor-emerald transition-colors mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                        <div>
                                            <span class="text-xs text-arbor-sage block mb-1">Chronique suivante</span>
                                            <span class="text-arbor-cream font-medium group-hover:text-arbor-emerald transition-colors line-clamp-2">
                                                {{ nextPost.title }}
                                            </span>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <aside class="lg:col-span-4 space-y-8 animate-slide-up" style="animation-delay: 0.1s;">
                            <!-- Related sounds -->
                            <div v-if="relatedSounds.length > 0" class="glass-card p-5">
                                <h3 class="font-display text-sm font-semibold text-arbor-cream uppercase tracking-wider mb-4">
                                    Sons mentionnés
                                </h3>
                                <div class="space-y-3">
                                    <Link
                                        v-for="related in relatedSounds"
                                        :key="related.sound_id"
                                        :href="route('sounds.show', related.sound.slug)"
                                        class="flex items-center gap-3 group"
                                    >
                                        <div class="w-12 h-12 rounded-lg bg-arbor-moss/20 flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5 text-arbor-moss" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm text-arbor-cream group-hover:text-arbor-emerald transition-colors truncate">
                                                {{ related.sound.title }}
                                            </p>
                                            <p class="text-xs text-arbor-sage truncate">
                                                {{ related.mention_context }}
                                            </p>
                                        </div>
                                    </Link>
                                </div>
                            </div>

                            <!-- Related creators -->
                            <div v-if="relatedCreators.length > 0" class="glass-card p-5">
                                <h3 class="font-display text-sm font-semibold text-arbor-cream uppercase tracking-wider mb-4">
                                    Créateurs mentionnés
                                </h3>
                                <div class="space-y-3">
                                    <Link
                                        v-for="related in relatedCreators"
                                        :key="related.user_id"
                                        :href="route('creators.show', related.creator.slug)"
                                        class="flex items-center gap-3 group"
                                    >
                                        <div class="w-10 h-10 rounded-full bg-arbor-moss/20 flex items-center justify-center shrink-0 overflow-hidden">
                                            <img
                                                v-if="getAvatarUrl(related)"
                                                :src="getAvatarUrl(related)"
                                                :alt="related.name"
                                                class="w-full h-full object-cover"
                                            />
                                            <span v-else class="text-xs text-arbor-moss font-medium">
                                                {{ getInitials(related.name) }}
                                            </span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm text-arbor-cream group-hover:text-arbor-emerald transition-colors truncate">
                                                {{ related.name }}
                                            </p>
                                            <p class="text-xs text-arbor-sage truncate">
                                                {{ related.mention_context }}
                                            </p>
                                        </div>
                                    </Link>
                                </div>
                            </div>

                            <!-- Keywords -->
                            <div v-if="post.ai_metadata?.keywords?.length" class="glass-card p-5">
                                <h3 class="font-display text-sm font-semibold text-arbor-cream uppercase tracking-wider mb-4">
                                    Mots-clés
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="keyword in post.ai_metadata.keywords"
                                        :key="keyword"
                                        class="text-xs text-arbor-sage bg-arbor-glass px-2.5 py-1 rounded-full"
                                    >
                                        {{ keyword }}
                                    </span>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
