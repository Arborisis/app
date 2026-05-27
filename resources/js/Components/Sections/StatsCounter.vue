<script setup>
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
        validator(value) {
            return value && typeof value === 'object';
        }
    },
});

const isVisible = ref(false);
const sectionRef = ref(null);
const animationProgress = ref(0);

// Strictly use backend data only — no fallbacks, no hardcoded values
const statsConfig = computed(() => {
    const config = [];
    
    if (props.stats.sounds !== undefined) {
        config.push({ key: 'sounds', label: 'Enregistrements', value: props.stats.sounds });
    }
    if (props.stats.creators !== undefined) {
        config.push({ key: 'creators', label: 'Créateurs', value: props.stats.creators });
    }
    if (props.stats.countries !== undefined) {
        config.push({ key: 'countries', label: 'Pays couverts', value: props.stats.countries });
    }
    if (props.stats.hours !== undefined) {
        config.push({ key: 'hours', label: 'Heures d\'écoute', value: props.stats.hours });
    }
    if (props.stats.plays !== undefined) {
        config.push({ key: 'plays', label: 'Écoutes totales', value: props.stats.plays });
    }
    
    return config;
});

const animatedValue = (key) => {
    const stat = statsConfig.value.find(s => s.key === key);
    if (!stat) return 0;
    return Math.floor(stat.value * animationProgress.value);
};

onMounted(() => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    isVisible.value = true;
                    animateStats();
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.3 }
    );

    if (sectionRef.value) {
        observer.observe(sectionRef.value);
    }
});

const animateStats = () => {
    const duration = 2000;
    const start = performance.now();

    const step = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        animationProgress.value = 1 - Math.pow(1 - progress, 3); // easeOutCubic

        if (progress < 1) {
            requestAnimationFrame(step);
        }
    };

    requestAnimationFrame(step);
};

const formatNumber = (num) => {
    if (num === undefined || num === null) return '—';
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'k';
    }
    return num.toLocaleString('fr-FR');
};
</script>

<template>
    <section ref="sectionRef" class="relative py-24 bg-[#050A08] border-y border-[#FFFFFF08]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Error state if no stats -->
            <div v-if="statsConfig.length === 0" class="text-center py-8">
                <p class="text-[#5A6B65] text-sm">Chargement des statistiques...</p>
            </div>
            
            <!-- Stats grid -->
            <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
                <div
                    v-for="(stat, index) in statsConfig"
                    :key="stat.key"
                    class="text-center"
                    :style="{
                        opacity: isVisible ? 1 : 0,
                        transform: isVisible ? 'translateY(0)' : 'translateY(20px)',
                        transition: `all 0.6s ease-out ${index * 0.15}s`,
                    }"
                >
                    <div class="text-5xl lg:text-6xl font-semibold text-[#34D399] tracking-tight mb-2">
                        {{ formatNumber(animatedValue(stat.key)) }}
                    </div>
                    <div class="text-sm text-[#8FA69E] uppercase tracking-widest">
                        {{ stat.label }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
