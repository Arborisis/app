<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    initialIdeas: {
        type: Array,
        default: () => []
    },
    initialTheme: {
        type: String,
        default: null
    },
    initialCompletedCount: {
        type: Number,
        default: 0
    },
    initialTotalCount: {
        type: Number,
        default: 0
    }
});

const ideas = ref(props.initialIdeas);
const theme = ref(props.initialTheme);
const completedCount = ref(props.initialCompletedCount);
const totalCount = ref(props.initialTotalCount);
const loading = ref(false);
const error = ref(null);

const progressPercentage = computed(() => {
    if (totalCount.value <= 0) return 0;
    return Math.round((completedCount.value / totalCount.value) * 100);
});

const difficultyConfig = {
    easy: { label: 'Facile', color: 'text-arbor-emerald bg-arbor-emerald/15 border-arbor-emerald/30' },
    medium: { label: 'Moyen', color: 'text-arbor-amber bg-arbor-amber/15 border-arbor-amber/30' },
    hard: { label: 'Avancé', color: 'text-rose-400 bg-rose-400/15 border-rose-400/30' },
};

const getDifficultyStyle = (difficulty) => {
    return difficultyConfig[difficulty] ?? difficultyConfig.easy;
};

const getTimeIcon = (timeOfDay) => {
    const icons = {
        'matin': 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707',
        'midi': 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707',
        'après-midi': 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707',
        'soir': 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z',
        'nuit': 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z',
        'toute la journée': 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707',
    };
    return icons[timeOfDay] || icons['toute la journée'];
};

const fetchIdeas = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get(route('api.sound-ideas.index'));
        ideas.value = response.data.ideas.map(idea => ({
            ...idea,
            status: idea.status || 'pending',
        }));
        theme.value = response.data.theme;
        completedCount.value = response.data.completed_count;
        totalCount.value = response.data.total_count;
    } catch (err) {
        error.value = 'Impossible de charger les idées du jour.';
        console.error('Failed to fetch sound ideas:', err);
    } finally {
        loading.value = false;
    }
};

const toggleIdea = async (idea) => {
    if (idea._processing) return;
    idea._processing = true;

    const previousStatus = idea.status;

    try {
        const response = await axios.post(route('api.sound-ideas.toggle', idea.id));
        idea.status = response.data.status;
        completedCount.value = response.data.completed_count;
    } catch (err) {
        idea.status = previousStatus;
        console.error('Failed to toggle idea:', err);
    } finally {
        idea._processing = false;
    }
};

const dismissIdea = async (idea) => {
    if (idea._processing) return;
    idea._processing = true;

    const previousStatus = idea.status;

    try {
        const response = await axios.post(route('api.sound-ideas.dismiss', idea.id));
        idea.status = response.data.status;
    } catch (err) {
        idea.status = previousStatus;
        console.error('Failed to dismiss idea:', err);
    } finally {
        idea._processing = false;
    }
};

onMounted(() => {
    if (ideas.value.length === 0) {
        fetchIdeas();
    }
});
</script>

<template>
    <div class="glass-card p-6 lg:p-8">
        <div class="flex items-start justify-between mb-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-arbor-emerald/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h2 class="font-display text-2xl font-semibold text-arbor-cream">
                        Idées du jour
                    </h2>
                </div>
                <p v-if="theme" class="text-arbor-sage text-sm italic">
                    {{ theme }}
                </p>
                <p v-else class="text-arbor-sage text-sm">
                    Suggestions d'enregistrement générées par IA
                </p>
            </div>

            <div v-if="totalCount > 0" class="text-right">
                <div class="font-display text-2xl font-semibold text-arbor-cream">
                    {{ completedCount }}<span class="text-arbor-sage text-lg">/{{ totalCount }}</span>
                </div>
                <div class="text-arbor-sage text-xs">
                    complétées
                </div>
            </div>
        </div>

        <!-- Progress bar -->
        <div v-if="totalCount > 0" class="mb-6">
            <div class="h-1.5 bg-arbor-charcoal rounded-full overflow-hidden">
                <div
                    class="h-full bg-gradient-to-r from-arbor-emerald to-arbor-moss rounded-full transition-transform duration-500 ease-out"
                    :style="{ width: `${progressPercentage}%` }"
                />
            </div>
        </div>

        <!-- Loading state -->
        <div v-if="loading && ideas.length === 0" class="space-y-3">
            <div v-for="n in 4" :key="n" class="p-4 rounded-xl bg-arbor-charcoal/40 border border-arbor-fog/30 animate-pulse">
                <div class="h-4 bg-arbor-charcoal rounded w-3/4 mb-3" />
                <div class="h-3 bg-arbor-charcoal rounded w-full mb-2" />
                <div class="h-3 bg-arbor-charcoal rounded w-2/3" />
            </div>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="text-center py-8">
            <p class="text-arbor-sage text-sm">{{ error }}</p>
            <button
                @click="fetchIdeas"
                class="mt-3 text-arbor-emerald text-sm hover:underline"
            >
                Réessayer
            </button>
        </div>

        <!-- Empty state -->
        <div v-else-if="ideas.length === 0" class="text-center py-8">
            <div class="w-12 h-12 rounded-xl bg-arbor-moss/10 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-arbor-moss/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
            </div>
            <p class="text-arbor-sage text-sm">Aucune idée disponible pour aujourd'hui.</p>
        </div>

        <!-- Ideas list -->
        <div v-else class="space-y-3">
            <div
                v-for="idea in ideas"
                :key="idea.id"
                class="group relative p-4 rounded-xl border transition-colors duration-300"
                :class="{
                    'bg-arbor-emerald/5 border-arbor-emerald/30': idea.status === 'completed',
                    'bg-arbor-charcoal/40 border-arbor-fog/30 hover:border-arbor-moss/40': idea.status !== 'completed',
                    'opacity-50': idea.status === 'dismissed',
                }"
            >
                <div class="flex items-start gap-3">
                    <!-- Checkbox -->
                    <button
                        @click="toggleIdea(idea)"
                        aria-label="Marquer comme complété"
                        class="mt-0.5 shrink-0 min-w-[44px] min-h-[44px] rounded-lg border-2 flex items-center justify-center transition-colors duration-200"
                        :class="{
                            'bg-arbor-emerald border-arbor-emerald': idea.status === 'completed',
                            'border-arbor-sage/40 hover:border-arbor-emerald': idea.status !== 'completed',
                        }"
                        :disabled="idea._processing"
                    >
                        <svg
                            v-if="idea.status === 'completed'"
                            class="w-3.5 h-3.5 text-arbor-night"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <h3
                                class="font-medium text-sm"
                                :class="idea.status === 'completed' ? 'text-arbor-emerald line-through' : 'text-arbor-cream'"
                            >
                                {{ idea.title }}
                            </h3>
                            <span
                                class="text-[10px] font-medium px-2 py-0.5 rounded-full border"
                                :class="getDifficultyStyle(idea.difficulty).color"
                            >
                                {{ getDifficultyStyle(idea.difficulty).label }}
                            </span>
                        </div>

                        <p
                            class="text-sm leading-relaxed mb-2"
                            :class="idea.status === 'completed' ? 'text-arbor-sage/60' : 'text-arbor-sage'"
                        >
                            {{ idea.description }}
                        </p>

                        <!-- Meta -->
                        <div class="flex items-center gap-3 flex-wrap">
                            <span
                                v-if="idea.time_of_day"
                                class="flex items-center gap-1 text-xs text-arbor-sage/70"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTimeIcon(idea.time_of_day)" />
                                </svg>
                                {{ idea.time_of_day }}
                            </span>

                            <span
                                v-if="idea.weather_context"
                                class="text-xs text-arbor-sage/70"
                            >
                                {{ idea.weather_context }}
                            </span>

                            <div v-if="idea.tags && idea.tags.length" class="flex items-center gap-1">
                                <span
                                    v-for="tag in idea.tags"
                                    :key="tag"
                                    class="text-[10px] px-1.5 py-0.5 rounded bg-arbor-charcoal text-arbor-sage/80"
                                >
                                    {{ tag }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Dismiss button -->
                    <button
                        v-if="idea.status !== 'dismissed' && idea.status !== 'completed'"
                        @click="dismissIdea(idea)"
                        class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity text-arbor-sage/50 hover:text-arbor-sage min-w-[44px] min-h-[44px] flex items-center justify-center"
                        title="Ignorer cette idée"
                        aria-label="Ignorer cette idée"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
