<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    items: Array,
    counts: Object,
});

const activeCategory = ref('all');

const categories = [
    { key: 'all', label: 'Tout', accent: 'text-arbor-cream' },
    { key: 'podcast', label: 'Podcasts', accent: 'text-violet-300' },
    { key: 'flash', label: 'Flash info', accent: 'text-cyan-300' },
    { key: 'emission', label: 'Émissions', accent: 'text-rose-300' },
];

const typeMeta = {
    podcast: {
        label: 'Podcast',
        pill: 'bg-violet-500/15 text-violet-200 border-violet-400/20',
        line: 'bg-violet-300',
    },
    flash: {
        label: 'Flash info',
        pill: 'bg-cyan-500/15 text-cyan-200 border-cyan-400/20',
        line: 'bg-cyan-300',
    },
    emission: {
        label: 'Émission',
        pill: 'bg-rose-500/15 text-rose-200 border-rose-400/20',
        line: 'bg-rose-300',
    },
};

const filteredItems = computed(() => {
    if (activeCategory.value === 'all') {
        return props.items ?? [];
    }

    return (props.items ?? []).filter((item) => item.show_type === activeCategory.value);
});

const featuredItem = computed(() => props.items?.[0] ?? null);

const formatDuration = (seconds) => {
    if (!seconds) return '--:--';
    const total = Math.round(seconds);
    const mins = Math.floor(total / 60);
    const secs = total % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const formatDate = (iso) => {
    if (!iso) return 'Non daté';

    return new Date(iso).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const metaFor = (type) => typeMeta[type] ?? typeMeta.podcast;
</script>

<template>
    <Head title="Podcasts, flash info et émissions" />

    <GuestLayout>
        <div class="pt-24 pb-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <section class="mb-10 grid gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-end">
                    <div>
                        <div class="mb-5 flex flex-wrap items-center gap-3">
                            <Link
                                :href="route('radio.index')"
                                class="inline-flex items-center gap-2 rounded-lg border border-arbor-glass-border bg-arbor-glass px-3 py-2 text-sm text-arbor-sage transition hover:text-arbor-cream"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7" />
                                </svg>
                                Radio en direct
                            </Link>
                            <span class="rounded-full border border-arbor-emerald/25 bg-arbor-emerald/10 px-3 py-1 text-xs uppercase tracking-[0.22em] text-arbor-emerald">
                            Archives radio
                            </span>
                        </div>

                        <h1 class="font-display text-4xl font-semibold leading-tight text-arbor-cream sm:text-5xl">
                            Podcasts, flash info et émissions
                        </h1>
                        <p class="mt-5 max-w-2xl text-base leading-7 text-arbor-sage">
                            Les formats générés d'Arborisis Radio sont regroupés ici : capsules longues, bulletins courts et émissions animées.
                        </p>
                    </div>

                    <div class="rounded-lg border border-arbor-glass-border bg-arbor-deep p-5">
                        <p class="text-xs uppercase tracking-[0.24em] text-arbor-sage">Derniere publication</p>
                        <template v-if="featuredItem">
                            <div class="mt-4 flex items-start gap-4">
                                <span class="mt-1 h-12 w-1 rounded-full" :class="metaFor(featuredItem.show_type).line"></span>
                                <div class="min-w-0">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium" :class="metaFor(featuredItem.show_type).pill">
                                        {{ metaFor(featuredItem.show_type).label }}
                                    </span>
                                    <h2 class="mt-3 line-clamp-2 font-display text-2xl font-semibold text-arbor-cream">
                                        {{ featuredItem.title }}
                                    </h2>
                                    <p class="mt-2 text-sm text-arbor-sage">
                                        {{ formatDate(featuredItem.published_at) }} · {{ formatDuration(featuredItem.duration) }}
                                    </p>
                                </div>
                            </div>
                        </template>
                        <p v-else class="mt-4 text-sm text-arbor-sage">
                            Aucun format publié pour le moment.
                        </p>
                    </div>
                </section>

                <div class="mb-8 flex gap-2 overflow-x-auto rounded-lg border border-arbor-glass-border bg-arbor-night/70 p-1">
                    <button
                        v-for="category in categories"
                        :key="category.key"
                        type="button"
                        @click="activeCategory = category.key"
                        class="whitespace-nowrap rounded-md px-4 py-2 text-sm font-medium transition"
                        :class="activeCategory === category.key
                            ? 'bg-arbor-cream text-arbor-night'
                            : 'text-arbor-sage hover:bg-arbor-glass hover:text-arbor-cream'"
                    >
                        <span>{{ category.label }}</span>
                        <span class="ml-2 font-mono text-xs" :class="activeCategory === category.key ? 'text-arbor-night/70' : category.accent">
                            {{ counts?.[category.key] ?? 0 }}
                        </span>
                    </button>
                </div>

                <section v-if="filteredItems.length" class="grid gap-4">
                    <article
                        v-for="item in filteredItems"
                        :key="item.id"
                        class="grid gap-4 rounded-lg border border-arbor-glass-border bg-arbor-glass p-5 md:grid-cols-[1fr_280px] md:items-center"
                    >
                        <div class="min-w-0">
                            <div class="mb-3 flex flex-wrap items-center gap-3">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium" :class="metaFor(item.show_type).pill">
                                    {{ metaFor(item.show_type).label }}
                                </span>
                                <span class="text-xs text-arbor-sage">{{ formatDate(item.published_at) }}</span>
                                <span class="font-mono text-xs text-arbor-sage">{{ formatDuration(item.duration) }}</span>
                            </div>

                            <h2 class="line-clamp-2 font-display text-2xl font-semibold text-arbor-cream">
                                {{ item.title }}
                            </h2>
                            <p v-if="item.theme" class="mt-1 text-sm text-arbor-emerald">
                                {{ item.theme }}
                            </p>
                            <p v-if="item.description" class="mt-3 line-clamp-3 text-sm leading-6 text-arbor-sage">
                                {{ item.description }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-arbor-glass-border bg-arbor-night/55 p-3">
                            <audio
                                :src="item.audio_url"
                                controls
                                preload="none"
                                class="h-10 w-full"
                            />
                        </div>
                    </article>
                </section>

                <section v-else class="rounded-lg border border-arbor-glass-border bg-arbor-glass p-8 text-center">
                    <p class="font-display text-2xl text-arbor-cream">Aucun contenu dans cette catégorie</p>
                    <p class="mt-2 text-sm text-arbor-sage">Les prochaines générations quotidiennes apparaîtront ici après publication.</p>
                </section>
            </div>
        </div>
    </GuestLayout>
</template>
