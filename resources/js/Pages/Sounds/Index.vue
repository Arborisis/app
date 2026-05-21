<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { ref, computed } from 'vue';
import SoundCard from '@/Components/SoundCard.vue';

const props = defineProps({
    sounds: Object,
    categories: Array,
});

const selectedCategory = ref('');

const filteredSounds = computed(() => {
    if (!selectedCategory.value) return props.sounds.data;
    return props.sounds.data.filter(s => s.category_id === selectedCategory.value);
});

</script>

<template>
    <Head title="Sons naturels" />
    <GuestLayout>
        <div class="pt-24 pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="trace-frame mb-10 p-6 sm:p-8">
                    <div class="relative z-10 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                        <div>
                    <p class="atlas-kicker mb-4">Archives sonores</p>
                    <h1 class="atlas-heading text-5xl sm:text-6xl">
                        Sons naturels
                    </h1>
                    <p class="mt-5 text-arbor-sage max-w-xl leading-7">
                        Explorez les enregistrements géolocalisés et approximés de la communauté Arborisis.
                    </p>
                        </div>
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('sounds.create')"
                            class="btn-primary"
                        >
                            Publier une trace
                        </Link>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap gap-2 mb-8">
                    <button
                        @click="selectedCategory = ''"
                        class="rounded-full px-4 py-2 text-sm font-medium transition-colors duration-200"
                        :class="selectedCategory === '' ? 'bg-arbor-lichen/15 text-arbor-lichen border border-arbor-lichen/30 shadow-sm shadow-arbor-lichen/5' : 'bg-arbor-glass text-arbor-sage hover:bg-white/10 border border-transparent'"
                    >
                        Tous
                    </button>
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        @click="selectedCategory = selectedCategory === category.id ? '' : category.id"
                        class="rounded-full px-4 py-2 text-sm font-medium transition-colors duration-200"
                        :class="selectedCategory === category.id ? 'bg-arbor-lichen/15 text-arbor-lichen border border-arbor-lichen/30 shadow-sm shadow-arbor-lichen/5' : 'bg-arbor-glass text-arbor-sage hover:bg-white/10 border border-transparent'"
                    >
                        {{ category.name }}
                    </button>
                </div>

                <!-- Results count -->
                <div class="mb-6 text-sm text-arbor-sage">
                    <span class="text-arbor-lichen font-medium">{{ filteredSounds.length }}</span>
                    trace{{ filteredSounds.length > 1 ? 's' : '' }} sonore{{ filteredSounds.length > 1 ? 's' : '' }}
                </div>

                <!-- Sounds Grid -->
                <div v-if="filteredSounds.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <SoundCard
                        v-for="(sound, index) in filteredSounds"
                        :key="sound.id"
                        :sound="sound"
                        :style="`animation: fadeInUp 0.5s ease-out forwards; animation-delay: ${index * 0.06}s; opacity: 0;`"
                    />
                </div>

                <!-- Empty State -->
                <div v-else class="poetic-empty py-24">
                    <div class="poetic-empty-icon flex items-center justify-center">
                        <svg class="w-8 h-8 text-arbor-moss/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </div>
                    <h3 class="font-display text-2xl font-semibold text-arbor-cream mb-2">Aucune trace pour le moment</h3>
                    <p class="text-arbor-sage mb-6">Soyez le premier à publier un enregistrement naturel.</p>
                    <Link v-if="$page.props.auth.user" :href="route('sounds.create')" class="btn-primary">
                        Publier un son
                    </Link>
                    <Link v-else href="/register" class="btn-primary">
                        Créer un compte
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="sounds.links && sounds.links.length > 3" class="mt-12 flex justify-center">
                    <div class="flex items-center gap-2">
                        <template v-for="(link, index) in sounds.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-4 py-2 rounded-lg text-sm transition-colors"
                                :class="link.active ? 'bg-arbor-emerald/15 text-arbor-emerald border border-arbor-emerald/30' : 'text-arbor-sage hover:text-arbor-cream hover:bg-arbor-glass border border-transparent'"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-4 py-2 rounded-lg text-sm text-arbor-sage/70"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
