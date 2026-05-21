<script setup>
import { computed } from 'vue';
import SensitivityWarning from './SensitivityWarning.vue';
import ModerationInfoBox from './ModerationInfoBox.vue';

const props = defineProps({
    point: {
        type: Object,
        default: null,
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'visit']);

const categoryColors = {
    birds: '#FBBF24',
    forest: '#34D399',
    water: '#60A5FA',
    insects: '#A3E635',
    wind: '#C7D2FE',
    night_ambience: '#A78BFA',
    meeting_point: '#F472B6',
    quiet_spot: '#8FA68E',
    educational_zone: '#38BDF8',
    other: '#9CA3AF',
};

const categoryLabel = computed(() => {
    if (!props.point) return '';
    const labels = {
        birds: 'Oiseaux',
        forest: 'Forêt',
        water: 'Eau',
        insects: 'Insectes',
        wind: 'Vent',
        night_ambience: 'Ambiance nocturne',
        meeting_point: 'Point de rencontre',
        quiet_spot: 'Spot calme',
        educational_zone: 'Zone pédagogique',
        other: 'Autre',
    };
    return labels[props.point.category_value] || props.point.category;
});

const categoryColor = computed(() => {
    if (!props.point) return '#9CA3AF';
    return categoryColors[props.point.category_value] || '#9CA3AF';
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
    >
        <div
            v-if="isOpen && point"
            class="absolute top-4 right-4 bottom-4 w-80 z-map bg-arbor-night/95 backdrop-blur-xl border border-white/5 rounded-2xl shadow-2xl overflow-hidden flex flex-col"
        >
            <!-- Header -->
            <div class="relative h-40 shrink-0">
                <div
                    v-if="point.cover_image"
                    class="absolute inset-0 bg-cover bg-center"
                    :style="{ backgroundImage: `url(${point.cover_image})` }"
                />
                <div
                    v-else
                    class="absolute inset-0 flex items-center justify-center"
                    :style="{ background: `linear-gradient(135deg, ${categoryColor}15, ${categoryColor}05)` }"
                >
                    <svg class="w-16 h-16 opacity-20" :style="{ color: categoryColor }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-arbor-night via-arbor-night/40 to-transparent" />

                <button
                    @click="emit('close')"
                    aria-label="Fermer"
                    class="absolute top-3 right-3 min-w-[44px] min-h-[44px] rounded-full bg-black/30 backdrop-blur-sm flex items-center justify-center text-white/70 hover:text-white hover:bg-black/50 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="absolute bottom-3 left-4 right-4">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wider"
                        :style="{ background: categoryColor + '20', color: categoryColor, border: '1px solid ' + categoryColor + '30' }"
                    >
                        {{ categoryLabel }}
                    </span>
                    <h2 class="text-lg font-semibold text-arbor-cream mt-1.5 leading-tight">{{ point.title }}</h2>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                <SensitivityWarning
                    :level="point.nature_sensitivity_level"
                    :warning-text="point.nature_sensitivity_warning"
                />

                <ModerationInfoBox
                    v-if="point.moderation_status !== 'approved'"
                    :status="point.moderation_status"
                />

                <p v-if="point.description" class="text-sm text-arbor-sage/80 leading-relaxed">
                    {{ point.description }}
                </p>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-white/5 rounded-lg p-3">
                        <p class="text-arbor-sage/50 mb-1">Difficulté</p>
                        <div class="flex gap-0.5">
                            <span
                                v-for="i in 5"
                                :key="i"
                                class="w-4 h-1.5 rounded-full"
                                :class="i <= point.difficulty_level ? 'bg-arbor-emerald' : 'bg-white/10'"
                            />
                        </div>
                    </div>
                    <div class="bg-white/5 rounded-lg p-3">
                        <p class="text-arbor-sage/50 mb-1">Moment</p>
                        <p class="text-arbor-cream">{{ point.recommended_time || 'Toute la journée' }}</p>
                    </div>
                </div>

                <div v-if="point.tags?.length" class="flex flex-wrap gap-1.5">
                    <span
                        v-for="tag in point.tags"
                        :key="tag"
                        class="px-2 py-0.5 rounded-md bg-white/5 text-[10px] text-arbor-sage/70 border border-white/5"
                    >
                        {{ tag }}
                    </span>
                </div>

                <div class="pt-3 border-t border-white/5">
                    <p class="text-xs text-arbor-sage/50">
                        Proposé par <span class="text-arbor-sage/80">{{ point.user?.name || 'Anonyme' }}</span>
                    </p>
                    <p class="text-[10px] text-arbor-sage/30 mt-0.5">{{ point.created_at }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="shrink-0 p-4 border-t border-white/5 space-y-2">
                <button
                    @click="emit('visit')"
                    class="w-full py-2.5 rounded-xl bg-arbor-emerald text-arbor-night font-semibold text-sm hover:bg-arbor-emerald/90 transition-colors flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Visiter ce lieu
                </button>
                <button
                    class="w-full py-2 rounded-xl bg-white/5 text-arbor-sage text-xs hover:bg-white/10 transition-colors"
                >
                    Signaler un problème
                </button>
            </div>
        </div>
    </Transition>
</template>
