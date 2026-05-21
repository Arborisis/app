import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useAudioAnalysis(soundId) {
    const analysis = ref(null);
    const visualizations = ref([]);
    const isLoading = ref(false);
    const isAnalyzing = ref(false);
    const error = ref(null);

    const hasAnalysis = computed(() => analysis.value !== null);
    const isPending = computed(() => analysis.value?.status === 'pending');
    const isCompleted = computed(() => analysis.value?.status === 'completed');
    const isFailed = computed(() => analysis.value?.status === 'failed');

    const fetchAnalysis = async () => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await fetch(route('api.sounds.analysis.show', soundId), {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) throw new Error('Erreur lors du chargement de l\'analyse');

            const data = await response.json();
            analysis.value = data.analysis;
            visualizations.value = data.analysis?.visualizations ?? [];
        } catch (err) {
            error.value = err.message;
        } finally {
            isLoading.value = false;
        }
    };

    const triggerAnalysis = async (params = {}) => {
        isAnalyzing.value = true;
        error.value = null;

        try {
            await router.post(route('sounds.analysis.store', soundId), params, {
                preserveScroll: true,
                onSuccess: () => {
                    analysis.value = { status: 'pending' };
                },
                onError: (errors) => {
                    error.value = Object.values(errors).flat().join(', ');
                },
            });
        } catch (err) {
            error.value = err.message;
        } finally {
            isAnalyzing.value = false;
        }
    };

    const exportData = async (analysisId, format) => {
        const url = route('sounds.analysis.export', { analysis: analysisId, format });
        window.open(url, '_blank');
    };

    return {
        analysis,
        visualizations,
        isLoading,
        isAnalyzing,
        error,
        hasAnalysis,
        isPending,
        isCompleted,
        isFailed,
        fetchAnalysis,
        triggerAnalysis,
        exportData,
    };
}
