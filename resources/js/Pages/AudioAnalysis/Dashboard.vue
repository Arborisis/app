<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import SpectrogramPanel from '@/Components/AudioAnalysis/SpectrogramPanel.vue';
import FeaturePanel from '@/Components/AudioAnalysis/FeaturePanel.vue';
import HeatmapViewer from '@/Components/AudioAnalysis/HeatmapViewer.vue';
import RealTimeAnalyzer from '@/Components/AudioAnalysis/RealTimeAnalyzer.vue';
import ExportPanel from '@/Components/AudioAnalysis/ExportPanel.vue';
import AnalysisControls from '@/Components/AudioAnalysis/AnalysisControls.vue';
import { useAudioAnalysis } from '@/Composables/useAudioAnalysis';

const props = defineProps({
    sound: Object,
    analysis: Object,
    isOwner: Boolean,
});

const {
    analysis: liveAnalysis,
    visualizations,
    isLoading,
    isAnalyzing,
    error,
    isPending,
    isCompleted,
    triggerAnalysis,
    exportData,
} = useAudioAnalysis(props.sound.id);

// Initialiser avec les données serveur
if (props.analysis) {
    liveAnalysis.value = props.analysis;
    visualizations.value = props.analysis.visualizations ?? [];
}

const handleAnalyze = (params) => {
    triggerAnalysis(params);
};

const handleExport = ({ analysisId, format }) => {
    exportData(analysisId, format);
};
</script>

<template>
    <Head :title="`Analyse — ${sound.title}`" />
    <GuestLayout>
        <div class="pt-24 pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center gap-2 text-sm text-arbor-sage mb-2">
                        <span>Analyse audio</span>
                        <span class="text-arbor-glass-border">/</span>
                        <span class="text-arbor-cream">{{ sound.title }}</span>
                    </div>
                    <h1 class="font-display text-2xl font-bold text-arbor-cream">
                        Tableau de bord d'analyse
                    </h1>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main visualizations -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Status alerts -->
                        <div v-if="isPending" class="glass-card p-6 flex items-center gap-4">
                            <svg class="animate-spin h-6 w-6 text-arbor-emerald" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            <div>
                                <p class="text-arbor-cream font-medium">Analyse en cours...</p>
                                <p class="text-sm text-arbor-sage">Les résultats apparaîtront automatiquement.</p>
                            </div>
                        </div>

                        <div v-if="error" class="glass-card p-4 border-red-500/30 bg-red-500/5">
                            <p class="text-red-400 text-sm">{{ error }}</p>
                        </div>

                        <SpectrogramPanel :visualizations="visualizations" />
                        <HeatmapViewer :visualizations="visualizations" />
                        <RealTimeAnalyzer :audio-url="sound.audio_url" :duration="sound.duration" />
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <AnalysisControls
                            v-if="isOwner"
                            :is-loading="isAnalyzing"
                            @analyze="handleAnalyze"
                        />

                        <FeaturePanel :features="liveAnalysis?.features ?? {}" />

                        <ExportPanel
                            v-if="isOwner && liveAnalysis?.id"
                            :analysis-id="liveAnalysis.id"
                            :visualizations="visualizations"
                            @export="handleExport"
                        />

                        <!-- Info -->
                        <div v-if="!isOwner" class="glass-card p-6">
                            <p class="text-sm text-arbor-sage">
                                Cet aperçu est limité. Le créateur a accès à l'analyse complète.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
