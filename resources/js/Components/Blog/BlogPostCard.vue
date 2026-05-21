<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    post: { type: Object, required: true },
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
</script>

<template>
    <Link
        :href="route('blog.show', post.slug)"
        class="group block glass-card hover:bg-white/10 transition-all duration-300 hover-lift overflow-hidden"
    >
        <div v-if="post.cover_image" class="aspect-[16/9] overflow-hidden">
            <img
                :src="post.cover_image"
                :alt="post.title"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
            />
        </div>
        <div v-else class="aspect-[16/9] bg-gradient-to-br from-arbor-moss/20 to-arbor-emerald/10 flex items-center justify-center">
            <svg class="w-12 h-12 text-arbor-moss/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
        </div>
        <div class="p-6">
            <div class="flex items-center gap-3 mb-3">
                <span class="text-xs font-medium text-arbor-emerald bg-arbor-emerald/10 px-2.5 py-1 rounded-full">
                    Chronique
                </span>
                <span class="text-xs text-arbor-sage">
                    {{ formatDate(post.published_at) }}
                </span>
            </div>
            <h2 class="font-display text-xl font-bold text-arbor-cream mb-2 group-hover:text-arbor-emerald transition-colors line-clamp-2">
                {{ post.title }}
            </h2>
            <p v-if="post.subtitle" class="text-arbor-sage text-sm leading-relaxed line-clamp-2 mb-3">
                {{ post.subtitle }}
            </p>
            <p v-else-if="post.excerpt" class="text-arbor-sage text-sm leading-relaxed line-clamp-3 mb-3">
                {{ post.excerpt }}
            </p>
            <div class="flex items-center text-arbor-emerald text-sm font-medium group-hover:translate-x-1 transition-transform">
                Lire la chronique
                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </div>
        </div>
    </Link>
</template>
