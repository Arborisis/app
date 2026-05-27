<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    stats: Object,
});

const isVisible = ref(false);
const sectionRef = ref(null);
const animatedStats = ref({
    sounds: 0,
    creators: 0,
    countries: 0,
    hours: 0,
});

const statsConfig = [
    { key: 'sounds', label: 'Enregistrements', suffix: '', icon: '🔊' },
    { key: 'creators', label: 'Créateurs', suffix: '', icon: '👤' },
    { key: 'countries', label: 'Pays couverts', suffix: '', icon: '🌍' },
    { key: 'hours', label: 'Heures d\'écoute', suffix: 'M', icon: '⏱' },
];

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
    const targets = {
        sounds: props.stats?.sounds || 12847,
        creators: props.stats?.creators || 847,
        countries: props.stats?.countries || 156,
        hours: 2.1,
    };

    const duration = 2000;
    const start = performance.now();

    const step = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);

        animatedStats.value.sounds = Math.floor(targets.sounds * eased);
        animatedStats.value.creators = Math.floor(targets.creators * eased);
        animatedStats.value.countries = Math.floor(targets.countries * eased);
        animatedStats.value.hours = Math.round(targets.hours * eased * 10) / 10;

        if (progress < 1) {
            requestAnimationFrame(step);
        }
    };

    requestAnimationFrame(step);
};

const formatNumber = (num) => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'k';
    }
    return num.toString();
};
</script>

<template>
    <section ref="sectionRef" class="relative py-24 bg-[#050A08] border-y border-[#FFFFFF08]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
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
                        {{ formatNumber(animatedStats[stat.key]) }}{{ stat.suffix }}
                    </div>
                    <div class="text-sm text-[#8FA69E] uppercase tracking-widest">
                        {{ stat.label }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
