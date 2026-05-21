<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    creator: {
        type: Object,
        required: true,
    },
    featuredSound: {
        type: Object,
        default: null,
    },
});

const formatNumber = (num) => {
    if (!num) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
    return num.toString();
};
</script>

<template>
    <div class="creator-card group cursor-pointer transition-all duration-200 hover:-translate-y-0.5">
        <div class="flex items-center gap-4">
            <!-- Avatar -->
            <div class="shrink-0">
                <div class="w-16 h-16 rounded-full bg-arbor-moss/20 flex items-center justify-center text-xl font-display font-semibold text-arbor-emerald ring-2 ring-arbor-glass-border group-hover:ring-arbor-emerald/30 transition-all">
                    <img
                        v-if="creator.avatar_url"
                        :src="creator.avatar_url"
                        :alt="`Avatar de ${creator.name}`"
                        class="w-full h-full rounded-full object-cover"
                        loading="lazy"
                    />
                    <span v-else>{{ creator.name.charAt(0).toUpperCase() }}</span>
                </div>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
                <Link
                    :href="route('creators.show', creator.slug)"
                    class="block font-medium text-arbor-cream truncate group-hover:text-arbor-emerald transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-arbor-emerald/50 focus-visible:ring-offset-2 focus-visible:ring-offset-arbor-night rounded"
                >
                    {{ creator.name }}
                </Link>
                <p v-if="creator.location" class="text-xs text-arbor-sage truncate mt-0.5">
                    {{ creator.location }}
                </p>
                <div class="flex items-center gap-3 mt-2 text-xs text-arbor-sage/70">
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                        {{ formatNumber(creator.sounds_count || 0) }} sons
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ formatNumber(creator.total_plays || 0) }} écoutes
                    </span>
                </div>
            </div>

            <!-- Featured sound mini -->
            <div v-if="featuredSound" class="hidden sm:block shrink-0 w-24">
                <Link
                    :href="route('sounds.show', featuredSound.slug)"
                    class="block relative aspect-square rounded-xl overflow-hidden bg-arbor-charcoal group/sound cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-arbor-emerald/50 focus-visible:ring-offset-2 focus-visible:ring-offset-arbor-night"
                >
                    <img
                        v-if="featuredSound.cover_url"
                        :src="featuredSound.cover_url"
                        :alt="featuredSound.title"
                        class="w-full h-full object-cover group-hover/sound:scale-110 transition-transform duration-300"
                        loading="lazy"
                    />
                    <div class="absolute inset-0 bg-arbor-night/40 flex items-center justify-center opacity-0 group-hover/sound:opacity-100 transition-opacity">
                        <svg class="w-6 h-6 text-arbor-cream" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>
