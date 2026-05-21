<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PlayerProgressCard from '@/Components/Gamification/PlayerProgressCard.vue';
import ActiveQuests from '@/Components/Gamification/ActiveQuests.vue';
import AchievementMedalShelf from '@/Components/Gamification/AchievementMedalShelf.vue';
import DailySoundIdeas from '@/Components/SoundIdeas/DailySoundIdeas.vue';
import { computed, onMounted, ref, defineAsyncComponent } from 'vue';

// Lazy load heavy Three.js component
const WaveformScene = defineAsyncComponent(() => import('@/Components/Three/WaveformScene.vue'));

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalSounds: 0,
            totalPlays: 0,
            totalLikes: 0,
            totalFollowers: 0,
        })
    },
    recentSounds: {
        type: Array,
        default: () => []
    },
    activities: {
        type: Array,
        default: () => []
    },
    echoBalance: {
        type: Number,
        default: 0
    },
    gamification: {
        type: Object,
        default: () => ({
            level: 1,
            xp_total: 0,
            xp_for_next_level: 100,
            xp_progress: 0,
            xp_needed: 100,
            progress_percentage: 0,
            current_streak: 0,
            longest_streak: 0,
            quests: [],
            achievements: [],
            medals: [],
            quests_completed: 0,
            achievements_unlocked: 0,
            medals_unlocked: 0,
        })
    },
    dailySoundIdeas: {
        type: Object,
        default: () => ({
            ideas: [],
            theme: null,
            completed_count: 0,
            total_count: 0,
        })
    },
});

const activeTab = ref('overview');

const tabs = [
    { id: 'overview', label: 'Aperçu' },
    { id: 'progression', label: 'Progression' },
    { id: 'activity', label: 'Activité' },
];

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 6) return 'Bonne nuit';
    if (hour < 12) return 'Bon matin';
    if (hour < 18) return 'Bon après-midi';
    return 'Bonsoir';
});

const animatedStats = ref({
    totalSounds: 0,
    totalPlays: 0,
    totalLikes: 0,
    totalFollowers: 0,
});

const statsAnimated = ref(false);
const prefersReducedMotion = ref(false);

const animateCount = (target, key, duration = 1200) => {
    if (prefersReducedMotion.value) {
        animatedStats.value[key] = target;
        return;
    }
    const start = performance.now();
    const from = 0;
    const to = target;
    const step = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        animatedStats.value[key] = Math.floor(from + (to - from) * eased);
        if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
};

onMounted(() => {
    prefersReducedMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    setTimeout(() => {
        statsAnimated.value = true;
        animateCount(props.stats.totalSounds, 'totalSounds');
        animateCount(props.stats.totalPlays, 'totalPlays');
        animateCount(props.stats.totalLikes, 'totalLikes');
        animateCount(props.stats.totalFollowers, 'totalFollowers');
    }, 300);
});

const formatDuration = (seconds) => {
    if (!seconds) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const formatDate = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);

    if (diff < 60) return 'À l\'instant';
    if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`;
    if (diff < 604800) return `Il y a ${Math.floor(diff / 86400)} j`;

    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
    });
};

const formatNumber = (num) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
    return num.toString();
};

const quickActions = computed(() => {
    const actions = [
        {
            label: 'Nouvel enregistrement',
            description: 'Publier un son',
            href: '/sounds/create',
            icon: 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
            color: 'emerald',
        },
        {
            label: 'Explorer la carte',
            description: 'Découvrir des sons',
            href: '/map',
            icon: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 7m0 13V7m0 0L9 7',
            color: 'moss',
        },
        {
            label: 'Mon profil',
            description: 'Gérer mon compte',
            href: '/profile',
            icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            color: 'sage',
        },
    ];

    const latestSound = props.recentSounds[0];
    if (latestSound) {
        actions.unshift({
            label: 'Analyser mon dernier son',
            description: latestSound.title,
            href: route('sounds.analysis.show', latestSound.id),
            icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            color: 'amber',
        });
    }

    return actions;
});

const getActivityIcon = (type) => {
    const icons = {
        like: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        play: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        follow: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
        comment: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    };
    return icons[type] || icons.play;
};

const getActivityColor = (type) => {
    const colors = {
        like: 'text-rose-400 bg-rose-400/15',
        play: 'text-arbor-emerald bg-arbor-emerald/15',
        follow: 'text-arbor-amber bg-arbor-amber/15',
        comment: 'text-sky-400 bg-sky-400/15',
    };
    return colors[type] || colors.play;
};

const getMiniWaveform = (seed) => {
    return Array.from({ length: 12 }, (_, i) => {
        const base = 20 + Math.abs(Math.sin(seed + i * 0.8)) * 60;
        return Math.max(8, Math.min(100, base));
    });
};
</script>

<template>
    <Head title="Studio" />

    <AuthenticatedLayout>
        <div class="relative min-h-screen bg-arbor-night">
            <!-- Ambient Background -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-hero-glow opacity-40" />
                <div class="absolute bottom-0 left-0 right-0 h-48 opacity-10">
                    <WaveformScene />
                </div>
                <div class="absolute inset-0 opacity-[0.015]" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 256 256%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.9%22 numOctaves=%224%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E'); background-repeat: repeat;"
                />
            </div>

            <!-- Main Content -->
            <div class="relative z-10">
                <!-- Hero Welcome Section -->
                <section class="pt-24 pb-8 section-padding">
                    <div class="max-w-7xl mx-auto">
                        <div class="animate-fade-in">
                            <div class="mb-2">
                                <span class="text-arbor-sage text-sm font-medium tracking-wide uppercase">
                                    {{ greeting }}
                                </span>
                            </div>
                            <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-semibold text-arbor-cream leading-tight mb-4">
                                {{ $page.props.auth.user.name }}
                            </h1>
                            <p class="text-arbor-sage text-lg max-w-xl leading-relaxed">
                                Bienvenue dans votre studio. Voici ce qui se passe avec vos enregistrements.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Stats Row -->
                <section class="pb-8 section-padding">
                    <div class="max-w-7xl mx-auto">
                        <div v-if="!statsAnimated" class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                            <div v-for="n in 4" :key="n" class="stat-card">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-arbor-charcoal skeleton" />
                                    <div class="h-3 bg-arbor-charcoal skeleton rounded w-20" />
                                </div>
                                <div class="h-8 bg-arbor-charcoal skeleton rounded w-16" />
                            </div>
                        </div>
                        <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                            <div class="stat-card group">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-arbor-emerald/15 flex items-center justify-center transition-transform group-hover:scale-110">
                                        <svg class="w-5 h-5 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                        </svg>
                                    </div>
                                    <span class="text-arbor-sage text-xs font-medium uppercase tracking-wider">Enregistrements</span>
                                </div>
                                <div class="animate-count-up">
                                    <span class="font-display text-3xl lg:text-4xl font-semibold text-arbor-cream">
                                        {{ formatNumber(animatedStats.totalSounds) }}
                                    </span>
                                </div>
                            </div>

                            <div class="stat-card group">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-arbor-moss/20 flex items-center justify-center transition-transform group-hover:scale-110">
                                        <svg class="w-5 h-5 text-arbor-moss-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <span class="text-arbor-sage text-xs font-medium uppercase tracking-wider">Écoutes</span>
                                </div>
                                <div class="animate-count-up">
                                    <span class="font-display text-3xl lg:text-4xl font-semibold text-arbor-cream">
                                        {{ formatNumber(animatedStats.totalPlays) }}
                                    </span>
                                </div>
                            </div>

                            <div class="stat-card group">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-rose-500/15 flex items-center justify-center transition-transform group-hover:scale-110">
                                        <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    </div>
                                    <span class="text-arbor-sage text-xs font-medium uppercase tracking-wider">J'aime</span>
                                </div>
                                <div class="animate-count-up">
                                    <span class="font-display text-3xl lg:text-4xl font-semibold text-arbor-cream">
                                        {{ formatNumber(animatedStats.totalLikes) }}
                                    </span>
                                </div>
                            </div>

                            <div class="stat-card group">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-arbor-amber/15 flex items-center justify-center transition-transform group-hover:scale-110">
                                        <svg class="w-5 h-5 text-arbor-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <span class="text-arbor-sage text-xs font-medium uppercase tracking-wider">Abonnés</span>
                                </div>
                                <div class="animate-count-up">
                                    <span class="font-display text-3xl lg:text-4xl font-semibold text-arbor-cream">
                                        {{ formatNumber(animatedStats.totalFollowers) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Tabs -->
                <section class="pb-8 section-padding">
                    <div class="max-w-7xl mx-auto">
                        <div class="flex items-center gap-1 border-b border-arbor-glass-border">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                class="px-4 py-3 text-sm font-medium transition-all duration-200 relative cursor-pointer rounded-lg hover:bg-arbor-glass/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-arbor-emerald/50 focus-visible:ring-offset-2 focus-visible:ring-offset-arbor-night"
                                :class="activeTab === tab.id ? 'text-arbor-emerald' : 'text-arbor-sage hover:text-arbor-cream'"
                            >
                                {{ tab.label }}
                                <span
                                    v-if="activeTab === tab.id"
                                    class="absolute bottom-0 left-2 right-2 h-0.5 bg-arbor-emerald rounded-full transition-all duration-300"
                                />
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Tab Content: Overview -->
                <section v-if="activeTab === 'overview'" class="pb-24 section-padding">
                    <div class="max-w-7xl mx-auto">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <!-- Left Column (2/3) -->
                            <div class="lg:col-span-2 space-y-8">
                                <DailySoundIdeas
                                    :initial-ideas="dailySoundIdeas.ideas"
                                    :initial-theme="dailySoundIdeas.theme"
                                    :initial-completed-count="dailySoundIdeas.completed_count"
                                    :initial-total-count="dailySoundIdeas.total_count"
                                />

                                <!-- Recent Sounds -->
                                <div class="glass-card-glow p-6 lg:p-8">
                                    <div class="flex items-center justify-between mb-6">
                                        <div>
                                            <h2 class="font-display text-2xl font-semibold text-arbor-cream">
                                                Vos enregistrements
                                            </h2>
                                            <p class="text-arbor-sage text-sm mt-1">
                                                Les sons que vous avez partagés avec la communauté
                                            </p>
                                        </div>
                                        <Link
                                            href="/sounds/create"
                                            class="btn-primary text-sm px-4 py-2"
                                        >
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Publier
                                        </Link>
                                    </div>

                                    <div v-if="recentSounds.length > 0">
                                        <div class="space-y-4">
                                            <Link
                                                v-for="(sound, index) in recentSounds.slice(0, 5)"
                                                :key="sound.id"
                                                :href="route('sounds.show', sound.slug)"
                                                class="flex items-center gap-4 p-4 rounded-xl bg-arbor-charcoal/50 border border-arbor-fog/50 hover:border-arbor-moss/50 hover:bg-arbor-charcoal transition-colors duration-300 group"
                                                :style="`animation: slideInRight 0.4s ease-out forwards; animation-delay: ${index * 0.08}s; opacity: 0;`"
                                            >
                                                <!-- Cover -->
                                                <div class="relative w-16 h-16 rounded-xl overflow-hidden shrink-0 bg-arbor-deep">
                                                    <div
                                                        v-if="sound.cover_url"
                                                        class="absolute inset-0 bg-cover bg-center"
                                                        :style="`background-image: url(${sound.cover_url})`"
                                                    />
                                                    <div v-else class="absolute inset-0 flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-arbor-moss/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                                        </svg>
                                                    </div>
                                                    <div class="sound-card-overlay rounded-xl" aria-hidden="true">
                                                        <div class="w-8 h-8 rounded-full bg-arbor-emerald/90 flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-arbor-night ml-0.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path d="M8 5v14l11-7z" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Info -->
                                                <div class="flex-1 min-w-0">
                                                    <h3 class="font-medium text-arbor-cream truncate group-hover:text-arbor-emerald transition-colors">
                                                        {{ sound.title }}
                                                    </h3>
                                                    <p class="text-sm text-arbor-sage truncate">
                                                        {{ sound.category?.name || 'Sans catégorie' }}
                                                    </p>
                                                </div>

                                                <!-- Mini waveform -->
                                                <div class="hidden sm:flex items-end gap-[2px] h-6 opacity-40 group-hover:opacity-70 transition-opacity">
                                                    <div
                                                        v-for="(h, i) in getMiniWaveform(sound.id)"
                                                        :key="i"
                                                        class="w-[2px] bg-arbor-emerald rounded-full"
                                                        :style="{ height: `${h}%` }"
                                                    />
                                                </div>

                                                <!-- Stats -->
                                                <div class="hidden sm:flex items-center gap-4 text-sm text-arbor-sage shrink-0">
                                                    <span class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        {{ formatNumber(sound.play_count || 0) }}
                                                    </span>
                                                    <span class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                        </svg>
                                                        {{ formatNumber(sound.like_count || 0) }}
                                                    </span>
                                                    <span class="text-xs">
                                                        {{ formatDuration(sound.duration) }}
                                                    </span>
                                                </div>
                                            </Link>
                                        </div>
                                    </div>

                                    <div v-else class="text-center py-16">
                                        <div class="w-16 h-16 rounded-2xl bg-arbor-moss/10 flex items-center justify-center mx-auto mb-4 transition-transform hover:scale-110">
                                            <svg class="w-8 h-8 text-arbor-moss/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                            </svg>
                                        </div>
                                        <h3 class="font-display text-xl text-arbor-cream mb-2">Aucun enregistrement</h3>
                                        <p class="text-arbor-sage text-sm mb-6">Partagez votre premier son naturel avec la communauté.</p>
                                        <Link href="/sounds/create" class="btn-primary">
                                            Publier un son
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column (1/3) -->
                            <div class="space-y-8">
                                <!-- ECHO Balance -->
                                <div class="glass-card-glow p-6 relative overflow-hidden">
                                    <div class="absolute top-0 right-0 w-32 h-32 bg-arbor-amber/10 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2" />
                                    <div class="relative z-10">
                                        <div class="flex items-center gap-2 mb-4">
                                            <div class="w-8 h-8 rounded-lg bg-arbor-amber/20 flex items-center justify-center">
                                                <span class="font-mono text-sm font-medium text-arbor-amber">E</span>
                                            </div>
                                            <span class="text-arbor-sage text-xs font-medium uppercase tracking-wider">Solde ECHO</span>
                                        </div>
                                        <div class="font-mono text-3xl font-medium text-arbor-amber mb-2 relative">
                                            {{ echoBalance.toLocaleString('fr-FR') }}
                                            <div v-if="echoBalance > 0" class="absolute inset-0 shimmer-text opacity-20 pointer-events-none" />
                                        </div>
                                        <p class="text-arbor-sage text-xs">
                                            Crédits disponibles
                                        </p>
                                        <div class="mt-4 pt-4 border-t border-arbor-glass-border">
                                            <Link href="/wallet" class="text-arbor-amber text-sm hover:underline inline-flex items-center gap-1 group">
                                                Voir l'historique
                                                <svg class="w-3 h-3 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                </svg>
                                            </Link>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="glass-card-glow p-6">
                                    <h3 class="font-display text-lg font-semibold text-arbor-cream mb-4">
                                        Actions rapides
                                    </h3>
                                    <div class="space-y-3">
                                        <Link
                                            v-for="action in quickActions"
                                            :key="action.label"
                                            :href="action.href"
                                            class="flex items-center gap-4 p-3 rounded-xl hover:bg-arbor-charcoal/50 transition-colors group"
                                        >
                                            <div
                                                class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-110"
                                                :class="{
                                                    'bg-arbor-emerald/15 text-arbor-emerald': action.color === 'emerald',
                                                    'bg-arbor-moss/20 text-arbor-moss-light': action.color === 'moss',
                                                    'bg-arbor-sage/15 text-arbor-sage': action.color === 'sage',
                                                    'bg-arbor-amber/15 text-arbor-amber': action.color === 'amber',
                                                }"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="action.icon" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-arbor-cream text-sm font-medium group-hover:text-arbor-emerald transition-colors">
                                                    {{ action.label }}
                                                </div>
                                                <div class="text-arbor-sage text-xs">{{ action.description }}</div>
                                            </div>
                                        </Link>
                                    </div>
                                </div>

                                <!-- Tip Card -->
                                <div class="glass-card-glow p-6 bg-gradient-to-br from-arbor-moss/10 to-transparent">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-arbor-emerald/20 flex items-center justify-center shrink-0 mt-0.5">
                                            <svg class="w-4 h-4 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-arbor-cream text-sm font-medium mb-1">Astuce</h4>
                                            <p class="text-arbor-sage text-xs leading-relaxed">
                                                Ajoutez une photo de couverture à vos enregistrements pour augmenter les écoutes de 40%.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Tab Content: Progression -->
                <section v-if="activeTab === 'progression'" class="pb-24 section-padding">
                    <div class="max-w-7xl mx-auto">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <div class="lg:col-span-1">
                                <PlayerProgressCard
                                    :level="gamification.level"
                                    :xp-total="gamification.xp_total"
                                    :xp-for-next-level="gamification.xp_for_next_level"
                                    :xp-progress="gamification.xp_progress"
                                    :xp-needed="gamification.xp_needed"
                                    :progress-percentage="gamification.progress_percentage"
                                    :current-streak="gamification.current_streak"
                                    :longest-streak="gamification.longest_streak"
                                    :quests-completed="gamification.quests_completed"
                                    :achievements-unlocked="gamification.achievements_unlocked"
                                    :medals-unlocked="gamification.medals_unlocked"
                                />
                            </div>
                            <div class="lg:col-span-2">
                                <ActiveQuests :quests="gamification.quests" />
                            </div>
                        </div>
                        <div class="mt-8">
                            <AchievementMedalShelf
                                :achievements="gamification.achievements"
                                :medals="gamification.medals"
                            />
                        </div>
                    </div>
                </section>

                <!-- Tab Content: Activity -->
                <section v-if="activeTab === 'activity'" class="pb-24 section-padding">
                    <div class="max-w-7xl mx-auto">
                        <div class="glass-card p-6 lg:p-8 max-w-3xl">
                            <h2 class="font-display text-2xl font-semibold text-arbor-cream mb-6">
                                Activité récente
                            </h2>

                            <div v-if="activities.length > 0" class="space-y-4">
                                <div
                                    v-for="(activity, index) in activities"
                                    :key="activity.id"
                                    class="flex items-start gap-4 p-4 rounded-xl hover:bg-arbor-charcoal/30 transition-colors"
                                    :style="`animation: slideInRight 0.4s ease-out forwards; animation-delay: ${index * 0.06}s; opacity: 0;`"
                                >
                                    <div
                                        class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform hover:scale-110"
                                        :class="getActivityColor(activity.type)"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="getActivityIcon(activity.type)" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-arbor-cream text-sm">
                                            <span class="font-medium">{{ activity.user?.name }}</span>
                                            {{ activity.description }}
                                        </p>
                                        <p class="text-arbor-sage text-xs mt-1">
                                            {{ formatDate(activity.created_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-12">
                                <p class="text-arbor-sage text-sm">Aucune activité récente à afficher.</p>
                                <p class="text-arbor-sage/70 text-xs mt-2">Votre histoire commence ici. Publiez ou écoutez votre premier son.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
