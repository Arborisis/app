<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import ForestSceneUltra from '@/Components/Three/ForestSceneUltra.vue';
import { useScrollAnimation } from '@/Composables/useScrollAnimation.js';

const props = defineProps({
    stats: Object,
});

const heroRef = ref(null);
const isLoaded = ref(false);
const scrollProgress = ref(0);

const { createPin, createReveal, createParallax, cleanup } = useScrollAnimation();

onMounted(() => {
    // Trigger entrance animation
    setTimeout(() => {
        isLoaded.value = true;
    }, 100);

    // Track scroll progress for Three.js
    const handleScroll = () => {
        const scrollY = window.scrollY;
        const windowHeight = window.innerHeight;
        scrollProgress.value = Math.min(scrollY / windowHeight, 1);
    };
    window.addEventListener('scroll', handleScroll, { passive: true });

    // GSAP animations
    if (heroRef.value) {
        createPin(heroRef.value, {
            start: 'top top',
            end: '+=150%',
            pinSpacing: true,
        });
    }

    // Reveal animations for content
    const revealElements = document.querySelectorAll('.gsap-reveal');
    revealElements.forEach((el) => {
        createReveal(el, {
            y: 80,
            duration: 1.2,
            start: 'top 90%',
        });
    });

    // Parallax for background elements
    const parallaxElements = document.querySelectorAll('.gsap-parallax');
    parallaxElements.forEach((el) => {
        createParallax(el, {
            speed: 0.3,
            start: 'top bottom',
            end: 'bottom top',
        });
    });

    onUnmounted(() => {
        window.removeEventListener('scroll', handleScroll);
        cleanup();
    });
});
</script>

<template>
    <section ref="heroRef" class="relative h-screen overflow-hidden bg-[#050A08]">
        <!-- Three.js Forest Background -->
        <ForestSceneUltra :scroll-progress="scrollProgress" class="absolute inset-0 z-0" />
        
        <!-- Gradient overlays for depth -->
        <div class="absolute inset-0 bg-gradient-to-t from-[#050A08] via-transparent to-[#050A08]/60 z-[1]" />
        <div class="absolute inset-0 bg-gradient-to-r from-[#050A08]/80 via-transparent to-[#050A08]/80 z-[1]" />
        
        <!-- Vignette -->
        <div 
            class="absolute inset-0 pointer-events-none z-[2]"
            style="background: radial-gradient(ellipse at center, transparent 0%, transparent 50%, rgba(5,10,8,0.9) 100%);"
        />
        
        <!-- Content -->
        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-full flex items-center">
            <div class="max-w-3xl">
                <!-- Label -->
                <div 
                    class="mb-6 transition-all duration-1000 ease-out"
                    :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                >
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-[#34D399]/20 bg-[#34D399]/5 text-[#34D399] text-sm font-mono tracking-wider uppercase">
                        <span class="w-2 h-2 rounded-full bg-[#34D399] animate-pulse" />
                        Atlas acoustique du vivant
                    </span>
                </div>
                
                <!-- Title -->
                <h1 
                    class="text-6xl sm:text-7xl lg:text-8xl font-semibold text-[#E8F0EC] leading-[0.95] tracking-[-0.04em] mb-8 transition-all duration-1000 delay-200 ease-out"
                    :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'"
                >
                    Le vivant<br />
                    <span class="text-[#34D399]">s'écoute.</span>
                </h1>
                
                <!-- Description -->
                <p 
                    class="text-lg sm:text-xl text-[#8FA69E] leading-relaxed max-w-xl mb-10 transition-all duration-1000 delay-300 ease-out"
                    :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                >
                    Explorez les sons naturels du monde comme des traces vivantes : lieux, espèces, saisons et mémoires audio capturés par une communauté de créateurs naturalistes.
                </p>
                
                <!-- CTAs -->
                <div 
                    class="flex flex-col sm:flex-row gap-4 mb-12 transition-all duration-1000 delay-500 ease-out"
                    :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                >
                    <Link 
                        href="/map" 
                        class="group inline-flex items-center justify-center gap-3 px-8 py-4 bg-[#34D399] text-[#050A08] rounded-xl font-medium text-base hover:bg-[#6EE7B7] transition-all duration-300 hover:shadow-[0_0_40px_rgba(52,211,153,0.3)] hover:-translate-y-0.5"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        Explorer la carte
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </Link>
                    
                    <Link 
                        :href="route('sounds.create')" 
                        class="group inline-flex items-center justify-center gap-3 px-8 py-4 border border-[#FFFFFF14] text-[#E8F0EC] rounded-xl font-medium text-base hover:border-[#34D399]/30 hover:bg-[#34D399]/5 transition-all duration-300"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                        </svg>
                        Publier une trace
                    </Link>
                </div>
                
                <!-- Stats — strictly from backend -->
                <div 
                    v-if="stats && (stats.sounds !== undefined || stats.creators !== undefined || stats.countries !== undefined)"
                    class="flex gap-8 transition-all duration-1000 delay-700 ease-out"
                    :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                >
                    <div v-if="stats.sounds !== undefined">
                        <div class="text-3xl font-semibold text-[#E8F0EC] tracking-tight">{{ stats.sounds.toLocaleString('fr-FR') }}</div>
                        <div class="text-xs text-[#5A6B65] uppercase tracking-widest mt-1">Sons</div>
                    </div>
                    <div v-if="stats.sounds !== undefined && stats.creators !== undefined" class="w-px bg-[#FFFFFF0A]" />
                    <div v-if="stats.creators !== undefined">
                        <div class="text-3xl font-semibold text-[#E8F0EC] tracking-tight">{{ stats.creators.toLocaleString('fr-FR') }}</div>
                        <div class="text-xs text-[#5A6B65] uppercase tracking-widest mt-1">Créateurs</div>
                    </div>
                    <div v-if="stats.creators !== undefined && stats.countries !== undefined" class="w-px bg-[#FFFFFF0A]" />
                    <div v-if="stats.countries !== undefined">
                        <div class="text-3xl font-semibold text-[#E8F0EC] tracking-tight">{{ stats.countries.toLocaleString('fr-FR') }}</div>
                        <div class="text-xs text-[#5A6B65] uppercase tracking-widest mt-1">Pays</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scroll indicator -->
        <div 
            class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 flex flex-col items-center gap-2 transition-all duration-1000 delay-1000"
            :class="isLoaded ? 'opacity-100' : 'opacity-0'"
        >
            <span class="text-[10px] uppercase tracking-[0.2em] text-[#5A6B65]">Scroll</span>
            <div class="w-px h-10 bg-gradient-to-b from-[#34D399] to-transparent animate-pulse" />
        </div>
    </section>
</template>