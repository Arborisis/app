<script setup>
import { reactive } from 'vue';

const emit = defineEmits(['analyze']);

const props = defineProps({
    isLoading: { type: Boolean, default: false },
});

const params = reactive({
    n_fft: 2048,
    hop_length: 512,
    frequency_scale: 'linear',
    n_mels: 128,
    n_mfcc: 13,
    preprocessing: {
        filter_type: null,
        cutoff: 1000,
        normalize: true,
        normalize_method: 'peak',
        vad: false,
    },
    visualization_types: ['stft', 'mel', 'mfcc'],
});

const submit = () => {
    emit('analyze', { ...params });
};

const nfftOptions = [512, 1024, 2048, 4096];
const scaleOptions = [
    { value: 'linear', label: 'Linéaire' },
    { value: 'log', label: 'Logarithmique' },
    { value: 'mel', label: 'Mel' },
];
const filterOptions = [
    { value: null, label: 'Aucun' },
    { value: 'lowpass', label: 'Passe-bas' },
    { value: 'highpass', label: 'Passe-haut' },
    { value: 'bandpass', label: 'Passe-bande' },
];
const vizOptions = [
    { value: 'stft', label: 'STFT' },
    { value: 'mel', label: 'Mel' },
    { value: 'mfcc', label: 'MFCC' },
];

const toggleViz = (type) => {
    const idx = params.visualization_types.indexOf(type);
    if (idx > -1) {
        params.visualization_types.splice(idx, 1);
    } else {
        params.visualization_types.push(type);
    }
};
</script>

<template>
    <div class="glass-card p-6 space-y-5">
        <h3 class="font-semibold text-arbor-cream text-sm">Paramètres d'analyse</h3>

        <div class="space-y-4">
            <!-- Window size -->
            <div>
                <label class="block text-xs text-arbor-sage mb-1">Taille FFT (n_fft)</label>
                <select v-model="params.n_fft" class="w-full bg-arbor-deep border border-arbor-glass-border rounded-lg px-3 py-2 text-sm text-arbor-cream focus:outline-none focus:border-arbor-emerald">
                    <option v-for="opt in nfftOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
            </div>

            <!-- Hop length -->
            <div>
                <label class="block text-xs text-arbor-sage mb-1">Hop length</label>
                <input v-model.number="params.hop_length" type="number" min="64" max="4096" step="64"
                    class="w-full bg-arbor-deep border border-arbor-glass-border rounded-lg px-3 py-2 text-base text-arbor-cream focus:outline-none focus:border-arbor-emerald">
            </div>

            <!-- Frequency scale -->
            <div>
                <label class="block text-xs text-arbor-sage mb-1">Échelle fréquentielle</label>
                <select v-model="params.frequency_scale" class="w-full bg-arbor-deep border border-arbor-glass-border rounded-lg px-3 py-2 text-sm text-arbor-cream focus:outline-none focus:border-arbor-emerald">
                    <option v-for="opt in scaleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
            </div>

            <!-- n_mels -->
            <div>
                <label class="block text-xs text-arbor-sage mb-1">Nombre de bands Mel</label>
                <input v-model.number="params.n_mels" type="number" min="32" max="256"
                    class="w-full bg-arbor-deep border border-arbor-glass-border rounded-lg px-3 py-2 text-base text-arbor-cream focus:outline-none focus:border-arbor-emerald">
            </div>

            <!-- n_mfcc -->
            <div>
                <label class="block text-xs text-arbor-sage mb-1">Nombre de MFCC</label>
                <input v-model.number="params.n_mfcc" type="number" min="5" max="40"
                    class="w-full bg-arbor-deep border border-arbor-glass-border rounded-lg px-3 py-2 text-base text-arbor-cream focus:outline-none focus:border-arbor-emerald">
            </div>

            <!-- Preprocessing -->
            <div class="border-t border-arbor-glass-border pt-4">
                <h4 class="text-xs text-arbor-sage mb-3">Prétraitement</h4>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-arbor-cream">Normalisation</span>
                        <button @click="params.preprocessing.normalize = !params.preprocessing.normalize"
                            :class="params.preprocessing.normalize ? 'bg-arbor-emerald' : 'bg-arbor-fog'"
                            class="w-10 h-5 rounded-full relative transition-colors">
                            <span :class="params.preprocessing.normalize ? 'translate-x-5' : 'translate-x-0.5'"
                                class="absolute top-0.5 w-4 h-4 bg-white rounded-full transition-transform" />
                        </button>
                    </div>

                    <div v-if="params.preprocessing.normalize">
                        <label class="block text-xs text-arbor-sage mb-1">Méthode</label>
                        <select v-model="params.preprocessing.normalize_method" class="w-full bg-arbor-deep border border-arbor-glass-border rounded-lg px-3 py-2 text-sm text-arbor-cream focus:outline-none focus:border-arbor-emerald">
                            <option value="peak">Peak</option>
                            <option value="rms">RMS</option>
                            <option value="zscore">Z-Score</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-arbor-sage mb-1">Filtre</label>
                        <select v-model="params.preprocessing.filter_type" class="w-full bg-arbor-deep border border-arbor-glass-border rounded-lg px-3 py-2 text-sm text-arbor-cream focus:outline-none focus:border-arbor-emerald">
                            <option v-for="opt in filterOptions" :key="opt.value ?? 'null'" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>

                    <div v-if="params.preprocessing.filter_type">
                        <label class="block text-xs text-arbor-sage mb-1">Fréquence de coupure (Hz)</label>
                        <input v-model.number="params.preprocessing.cutoff" type="number" min="10"
                            class="w-full bg-arbor-deep border border-arbor-glass-border rounded-lg px-3 py-2 text-base text-arbor-cream focus:outline-none focus:border-arbor-emerald">
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-arbor-cream">VAD (détection voix)</span>
                        <button @click="params.preprocessing.vad = !params.preprocessing.vad"
                            :class="params.preprocessing.vad ? 'bg-arbor-emerald' : 'bg-arbor-fog'"
                            class="w-10 h-5 rounded-full relative transition-colors">
                            <span :class="params.preprocessing.vad ? 'translate-x-5' : 'translate-x-0.5'"
                                class="absolute top-0.5 w-4 h-4 bg-white rounded-full transition-transform" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Visualizations -->
            <div class="border-t border-arbor-glass-border pt-4">
                <h4 class="text-xs text-arbor-sage mb-3">Visualisations</h4>
                <div class="flex flex-wrap gap-2">
                    <button v-for="opt in vizOptions" :key="opt.value"
                        @click="toggleViz(opt.value)"
                        :class="params.visualization_types.includes(opt.value) ? 'bg-arbor-emerald/20 text-arbor-emerald border-arbor-emerald/30' : 'bg-arbor-glass text-arbor-sage border-arbor-glass-border'"
                        class="px-3 py-1.5 rounded-lg text-xs border transition-colors">
                        {{ opt.label }}
                    </button>
                </div>
            </div>
        </div>

        <button @click="submit" :disabled="isLoading"
            class="w-full py-2.5 rounded-lg bg-arbor-emerald text-arbor-night font-medium text-sm hover:bg-arbor-emerald-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
            <svg v-if="isLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <span>{{ isLoading ? 'Analyse en cours...' : 'Lancer l\'analyse' }}</span>
        </button>
    </div>
</template>
