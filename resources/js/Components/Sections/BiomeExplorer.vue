<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const biomes = ref([
    {
        id: 'forest',
        title: 'La Forêt',
        subtitle: 'Chapitre I',
        description: 'Entrez dans le silence des bois anciens. Chaque pas révèle un nouveau chœur : chant des oiseaux, craquement des branches, murmure du vent dans les cimes.',
        image: '/images/biomes/forest.jpg',
        stats: { sounds: 4523, species: 128 },
        color: '#34D399',
    },
    {
        id: 'coast',
        title: 'La Côte',
        subtitle: 'Chapitre II',
        description: 'L\'océan en mémoire. Des vagues qui s\'écrasent sur les falaises aux marées qui recouvrent les estuaires — capturez la puissance brute de la mer.',
        image: '/images/biomes/coast.jpg',
        stats: { sounds: 2156, species: 89 },
        color: '#60A5FA',
    },
    {
        id: 'mountain',
        title: 'La Montagne',
        subtitle: 'Chapitre III',
        description: 'L\'altitude du son. Écoutez le vent traverser les crêtes alpines et les glaciers craquer sous le poids du temps.',
        image: '/images/biomes/mountain.jpg',
        stats: { sounds: 1876, species: 67 },
        color: '#A78BFA',
    },
    {
        id: 'desert',
        title: 'Le Désert',
        subtitle: 'Chapitre IV',
        description: 'Le silence absolu. Dans l\'immensité des dunes, chaque son devient précieux. Découvrez la beauté du minimalisme acoustique.',
        image: '/images/biomes/desert.jpg',
        stats: { sounds: 432, species: 34 },
        color: '#FBBF24',
    },
    {
        id: 'mangrove',
        title: 'La Mangrove',
        subtitle: 'Chapitre V',
        description: 'La nuit vivante. Entre terre et mer, les mangroves abritent des chorales d\'insectes et d\'amphibiens qui créent des symphonies nocturnes.',
        image: '/images/biomes/mangrove.jpg',
        stats: { sounds: 987, species: 156 },
        color: '#F472B6',
    },
]);

const activeBiome = ref(0);
const sectionRef = ref(null);

onMounted(() => {
    // Intersection observer for section visibility
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        },
        { threshold: 0.2 }
    );

    if (sectionRef.value) {
        observer.observe(sectionRef.value);
    }
});
</script>

<template>
    <section ref="sectionRef" class="relative py-32 bg-[#050A08] overflow-hidden">
        <!-- Section header -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-20">
            <div class="max-w-2xl">
                <span class="text-[#34D399] text-sm font-mono tracking-wider uppercase mb-4 block">
                    Explorer par biome
                </span>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-semibold text-[#E8F0EC] tracking-tight leading-tight">
                    Une balade à travers<br />
                    <span class="text-[#34D399]">les paysages sonores.</span>
                </h2>
            </div>
        </div>

        <!-- Biome cards - horizontal scroll on mobile, grid on desktop -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="(biome, index) in biomes"
                    :key="biome.id"
                    class="group relative aspect-[4/5] rounded-2xl overflow-hidden cursor-pointer transition-all duration-700"
                    :class="[
                        activeBiome === index ? 'ring-2 ring-[#34D399] ring-offset-2 ring-offset-[#050A08]' : '',
                        'hover:scale-[1.02]'
                    ]"
                    @mouseenter="activeBiome = index"
                >
                    <!-- Background image -->
                    <div
                        class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-110"
                        :style="{ backgroundImage: `url(${biome.image})` }"
                    />

                    <!-- Gradient overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-[#050A08] via-[#050A08]/40 to-transparent opacity-80 group-hover:opacity-90 transition-opacity duration-500" />

                    <!-- Glow effect on hover -->
                    <div
                        class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"
                        :style="{ background: `radial-gradient(circle at 50% 80%, ${biome.color}15, transparent 70%)` }"
                    />

                    <!-- Content -->
                    <div class="absolute inset-0 p-6 flex flex-col justify-end">
                        <!-- Chapter label -->
                        <span
                            class="text-xs font-mono tracking-widest uppercase mb-3 transition-colors duration-300"
                            :style="{ color: biome.color }"
                        >
                            {{ biome.subtitle }}
                        </span>

                        <!-- Title -->
                        <h3 class="text-2xl sm:text-3xl font-semibold text-[#E8F0EC] mb-3 tracking-tight">
                            {{ biome.title }}
                        </h3>

                        <!-- Description - shows on hover -->
                        <p class="text-sm text-[#8FA69E] leading-relaxed mb-4 opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition-all duration-500">
                            {{ biome.description }}
                        </p>

                        <!-- Stats -->
                        <div class="flex gap-6 opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition-all duration-500 delay-100">
                            <div>
                                <div class="text-lg font-semibold text-[#E8F0EC]">{{ biome.stats.sounds.toLocaleString() }}</div>
                                <div class="text-xs text-[#5A6B65] uppercase tracking-wider">Sons</div>
                            </div>
                            <div class="w-px bg-[#FFFFFF0A]" />
                            <div>
                                <div class="text-lg font-semibold text-[#E8F0EC]">{{ biome.stats.species }}</div>
                                <div class="text-xs text-[#5A6B65] uppercase tracking-wider">Espèces</div>
                            </div>
                        </div>

                        <!-- Explore link -->
                        <Link
                            :href="`/explore?biome=${biome.id}`"
                            class="inline-flex items-center gap-2 mt-4 text-sm font-medium transition-all duration-300 opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 delay-150"
                            :style="{ color: biome.color }"
                        >
                            Explorer
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.is-visible .biome-card {
    animation: slideUp 0.8s ease-out forwards;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
