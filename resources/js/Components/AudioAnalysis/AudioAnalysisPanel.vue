<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    analysis: {
        type: Object,
        default: null,
    },
    sound: {
        type: Object,
        required: true,
    },
    isOwner: {
        type: Boolean,
        default: false,
    },
});

const isRetrying = ref(false);

const status = computed(() => props.analysis?.status ?? 'no_analysis');

const statusConfig = {
    pending: { label: 'En attente', color: 'text-amber-400', bg: 'bg-amber-400/10', icon: '⏳' },
    queued: { label: 'En file d\'attente', color: 'text-sky-400', bg: 'bg-sky-400/10', icon: '📥' },
    processing: { label: 'Analyse en cours', color: 'text-emerald-400', bg: 'bg-emerald-400/10', icon: '🔬' },
    completed: { label: 'Analyse terminée', color: 'text-teal-400', bg: 'bg-teal-400/10', icon: '✓' },
    failed: { label: 'Échec', color: 'text-rose-400', bg: 'bg-rose-400/10', icon: '✕' },
    no_analysis: { label: 'Aucune analyse', color: 'text-arbor-sage', bg: 'bg-arbor-glass', icon: '−' },
};

const currentStatus = computed(() => statusConfig[status.value] ?? statusConfig.no_analysis);

const qualityGradient = computed(() => {
    const label = props.analysis?.quality_label;
    switch (label) {
        case 'excellent': return 'from-emerald-500 to-teal-400';
        case 'good': return 'from-teal-500 to-cyan-400';
        case 'medium': return 'from-amber-500 to-yellow-400';
        case 'poor': return 'from-orange-500 to-red-400';
        case 'unusable': return 'from-red-600 to-rose-500';
        default: return 'from-arbor-moss to-arbor-sage';
    }
});

const qualityPercent = computed(() => {
    const map = { excellent: 100, good: 80, medium: 50, poor: 25, unusable: 5 };
    return map[props.analysis?.quality_label] ?? 0;
});

const canRetry = computed(() => {
    return props.isOwner && ['failed', 'no_analysis'].includes(status.value);
});

const retryAnalysis = () => {
    if (!canRetry.value) return;
    isRetrying.value = true;
    router.post(route('api.sounds.analysis.retry', props.sound.id), {}, {
        preserveScroll: true,
        onFinish: () => { isRetrying.value = false; },
        onSuccess: () => { window.location.reload(); },
    });
};

const formatDuration = (s) => {
    if (!s) return '—';
    const m = Math.floor(s / 60);
    const sec = Math.floor(s % 60);
    return `${m}:${sec.toString().padStart(2, '0')}`;
};

const confidenceColor = (c) => {
    if (c >= 0.8) return 'bg-emerald-500';
    if (c >= 0.6) return 'bg-teal-500';
    if (c >= 0.4) return 'bg-amber-500';
    return 'bg-orange-500';
};
</script>

<template>
    <div class="glass-card p-6 space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg"
                     :class="currentStatus.bg">
                    <span>{{ currentStatus.icon }}</span>
                </div>
                <div>
                    <h3 class="font-semibold text-arbor-cream">Analyse Audio</h3>
                    <p class="text-xs" :class="currentStatus.color">
                        {{ currentStatus.label }}
                    </p>
                </div>
            </div>
            <button
                v-if="canRetry"
                @click="retryAnalysis"
                :disabled="isRetrying"
                class="px-4 py-2 rounded-lg bg-arbor-emerald/10 text-arbor-emerald text-sm hover:bg-arbor-emerald/20 transition-colors disabled:opacity-50"
            >
                {{ isRetrying ? 'Relance…' : 'Relancer' }}
            </button>
        </div>

        <!-- Loading / Pending states -->
        <div v-if="['pending', 'queued', 'processing'].includes(status)" class="py-8 text-center">
            <div class="inline-flex items-center gap-2 text-arbor-sage text-sm">
                <svg class="animate-spin w-4 h-4 text-arbor-emerald" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span v-if="status === 'processing'">L'analyse est en cours sur nos serveurs…</span>
                <span v-else-if="status === 'queued'">File d'attente — l'analyse démarrera sous peu.</span>
                <span v-else>En attente de traitement.</span>
            </div>
        </div>

        <!-- Failed state -->
        <div v-else-if="status === 'failed'" class="py-4 text-center">
            <p class="text-rose-400 text-sm">L'analyse a échoué. Vous pouvez réessayer plus tard.</p>
            <p v-if="analysis?.error_message" class="text-xs text-arbor-sage mt-1 font-mono">
                {{ analysis.error_message }}
            </p>
        </div>

        <!-- No analysis -->
        <div v-else-if="status === 'no_analysis'" class="py-4 text-center">
            <p class="text-arbor-sage text-sm">Aucune analyse n'est disponible pour ce son.</p>
        </div>

        <!-- Completed state -->
        <div v-else-if="status === 'completed'" class="space-y-6">
            <!-- Metadata badges -->
            <div class="flex flex-wrap gap-2">
                <span v-if="analysis.duration_seconds" class="px-2.5 py-1 rounded-md bg-arbor-glass text-xs text-arbor-sage">
                    {{ formatDuration(analysis.duration_seconds) }}
                </span>
                <span v-if="analysis.format" class="px-2.5 py-1 rounded-md bg-arbor-glass text-xs text-arbor-sage uppercase">
                    {{ analysis.format }}
                </span>
                <span v-if="analysis.sample_rate" class="px-2.5 py-1 rounded-md bg-arbor-glass text-xs text-arbor-sage">
                    {{ (analysis.sample_rate / 1000).toFixed(1) }} kHz
                </span>
                <span v-if="analysis.channels" class="px-2.5 py-1 rounded-md bg-arbor-glass text-xs text-arbor-sage">
                    {{ analysis.channels === 1 ? 'Mono' : 'Stéréo' }}
                </span>
            </div>

            <!-- Quality bar -->
            <div v-if="analysis.quality_label">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs text-arbor-sage">Qualité d'enregistrement</span>
                    <span class="text-xs font-medium capitalize" :class="currentStatus.color">
                        {{ analysis.quality_label }}
                    </span>
                </div>
                <div class="h-1.5 rounded-full bg-arbor-glass overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r transition-[width] duration-700"
                         :class="qualityGradient"
                         :style="`width: ${qualityPercent}%`"></div>
                </div>
            </div>

            <!-- Spectrogram -->
            <div v-if="analysis.spectrogram_url">
                <p class="text-xs text-arbor-sage mb-2">Spectrogramme</p>
                <div class="rounded-xl overflow-hidden bg-arbor-deep border border-arbor-glass-border">
                    <img :src="analysis.spectrogram_url" alt="Spectrogramme"
                         class="w-full h-auto object-cover" loading="lazy" />
                </div>
            </div>

            <!-- BirdNET detections -->
            <div v-if="analysis.birdnet_detections?.length > 0">
                <p class="text-xs text-arbor-sage mb-3">
                    Espèces principales du résumé automatique
                </p>
                <div class="space-y-2">
                    <div v-for="(det, i) in analysis.birdnet_detections" :key="i"
                         class="flex items-center gap-3 p-3 rounded-lg bg-arbor-glass/50 border border-arbor-glass-border/50">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-arbor-deep text-sm">🐦</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-arbor-cream truncate">{{ det.common_name }}</p>
                            <p v-if="det.scientific_name" class="text-xs text-arbor-sage truncate italic">{{ det.scientific_name }}</p>
                        </div>
                        <div v-if="det.confidence !== null && det.confidence !== undefined" class="w-20">
                            <div class="h-1.5 rounded-full bg-arbor-glass overflow-hidden">
                                <div class="h-full rounded-full transition-[width]"
                                     :class="confidenceColor(det.confidence)"
                                     :style="`width: ${Math.round(det.confidence * 100)}%`"></div>
                            </div>
                            <p class="text-[10px] text-arbor-sage text-right mt-0.5">{{ Math.round(det.confidence * 100) }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suggested tags -->
            <div v-if="analysis.suggested_tags?.length > 0">
                <p class="text-xs text-arbor-sage mb-2">Tags suggérés</p>
                <div class="flex flex-wrap gap-2">
                    <span v-for="tag in analysis.suggested_tags" :key="tag"
                          class="px-2.5 py-1 rounded-md bg-arbor-emerald/10 text-arbor-emerald text-xs">
                        {{ tag }}
                    </span>
                </div>
            </div>

            <!-- Exports -->
            <div class="flex flex-wrap gap-3 pt-2 border-t border-arbor-glass-border/50">
                <a v-if="analysis.features_url" :href="analysis.features_url" target="_blank"
                   class="text-xs text-arbor-sage hover:text-arbor-emerald transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Features JSON
                </a>
                <a v-if="analysis.birdnet_url" :href="analysis.birdnet_url" target="_blank"
                   class="text-xs text-arbor-sage hover:text-arbor-emerald transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    BirdNET
                </a>
                <a v-if="analysis.summary_url" :href="analysis.summary_url" target="_blank"
                   class="text-xs text-arbor-sage hover:text-arbor-emerald transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Résumé
                </a>
            </div>
        </div>
    </div>
</template>
