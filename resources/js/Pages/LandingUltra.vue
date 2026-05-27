<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import HeroCinematic from '@/Components/Sections/HeroCinematic.vue';
import BiomeExplorer from '@/Components/Sections/BiomeExplorer.vue';
import SoundGrid from '@/Components/Sections/SoundGrid.vue';
import StatsCounter from '@/Components/Sections/StatsCounter.vue';
import CreatorsCarousel from '@/Components/Sections/CreatorsCarousel.vue';

const props = defineProps({
    stats: Object,
    featuredSounds: {
        type: Array,
        default: () => [],
    },
    featuredCreators: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Le vivant s'écoute — Arborisis" />
    <GuestLayout>
        <!-- Hero with Three.js forest -->
        <HeroCinematic :stats="stats" />

        <!-- Biome explorer -->
        <BiomeExplorer />

        <!-- Stats counter -->
        <StatsCounter :stats="stats" />

        <!-- Featured sounds -->
        <SoundGrid :sounds="featuredSounds" />

        <!-- Featured creators -->
        <CreatorsCarousel :creators="featuredCreators" />
    </GuestLayout>
</template>
