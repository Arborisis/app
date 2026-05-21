<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    initialLat: { type: Number, default: null },
    initialLng: { type: Number, default: null },
});

const emit = defineEmits(['submit', 'cancel']);

const form = ref({
    title: '',
    description: '',
    latitude: props.initialLat ?? '',
    longitude: props.initialLng ?? '',
    category: '',
    tags: [],
    difficulty_level: 1,
    nature_sensitivity_level: 'normal',
    recommended_time: '',
    audio_environment_type: '',
});

const tagInput = ref('');

watch(() => props.initialLat, (val) => {
    if (val !== null && val !== undefined) {
        form.value.latitude = val;
    }
});

watch(() => props.initialLng, (val) => {
    if (val !== null && val !== undefined) {
        form.value.longitude = val;
    }
});

const categories = [
    { value: 'birds', label: 'Oiseaux' },
    { value: 'forest', label: 'Forêt' },
    { value: 'water', label: 'Eau' },
    { value: 'insects', label: 'Insectes' },
    { value: 'wind', label: 'Vent' },
    { value: 'night_ambience', label: 'Ambiance nocturne' },
    { value: 'meeting_point', label: 'Point de rencontre' },
    { value: 'quiet_spot', label: 'Spot calme' },
    { value: 'educational_zone', label: 'Zone pédagogique' },
    { value: 'other', label: 'Autre' },
];

const sensitivityLevels = [
    { value: 'normal', label: 'Normal' },
    { value: 'fragile', label: 'Fragile' },
    { value: 'sensitive_species', label: 'Espèce sensible' },
    { value: 'private', label: 'Privé' },
    { value: 'dangerous', label: 'Dangereux' },
];

const isSubmitting = ref(false);
const errors = ref({});
const locationError = ref('');

const addTag = () => {
    const tag = tagInput.value.trim();
    if (tag && !form.value.tags.includes(tag) && form.value.tags.length < 10) {
        form.value.tags.push(tag);
    }
    tagInput.value = '';
};

const removeTag = (tag) => {
    form.value.tags = form.value.tags.filter(t => t !== tag);
};

const handleSubmit = async () => {
    isSubmitting.value = true;
    errors.value = {};

    try {
        emit('submit', { ...form.value });
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
    } finally {
        isSubmitting.value = false;
    }
};

const fillCurrentLocation = () => {
    locationError.value = '';
    if (!navigator.geolocation) {
        locationError.value = 'La géolocalisation n\'est pas supportée par votre navigateur.';
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.value.latitude = position.coords.latitude;
            form.value.longitude = position.coords.longitude;
        },
        () => {
            locationError.value = 'Impossible d\'obtenir votre position. Vérifiez les permissions de votre navigateur ou cliquez sur la carte.';
        },
        { enableHighAccuracy: false, maximumAge: 60000, timeout: 10000 }
    );
};

const showSensitiveWarning = computed(() => {
    return form.value.nature_sensitivity_level !== 'normal';
});
</script>

<template>
    <form @submit.prevent="handleSubmit" class="space-y-5">
        <div>
            <label class="block text-xs font-medium text-arbor-sage/70 mb-1.5">Titre du lieu</label>
            <input
                v-model="form.title"
                type="text"
                required
                minlength="3"
                maxlength="255"
                class="w-full bg-arbor-glass border border-arbor-glass-border rounded-xl px-4 py-2.5 text-base text-arbor-cream placeholder:text-arbor-sage/60 focus:outline-none focus:border-arbor-emerald/50 focus:ring-1 focus:ring-arbor-emerald/20 transition-colors"
                placeholder="Ex: Clairière aux chouettes"
            />
            <p v-if="errors.title" class="text-xs text-rose-400 mt-1">{{ errors.title[0] }}</p>
        </div>

        <div>
            <label class="block text-xs font-medium text-arbor-sage/70 mb-1.5">Description</label>
            <textarea
                v-model="form.description"
                rows="3"
                maxlength="5000"
                class="w-full bg-arbor-glass border border-arbor-glass-border rounded-xl px-4 py-2.5 text-base text-arbor-cream placeholder:text-arbor-sage/60 focus:outline-none focus:border-arbor-emerald/50 focus:ring-1 focus:ring-arbor-emerald/20 transition-colors resize-none"
                placeholder="Décrivez ce lieu, ce qu'on y entend, comment y accéder..."
            />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-arbor-sage/70 mb-1.5">Latitude</label>
                <input
                    v-model="form.latitude"
                    type="number"
                    step="any"
                    required
                    class="w-full bg-arbor-glass border border-arbor-glass-border rounded-xl px-4 py-2.5 text-base text-arbor-cream focus:outline-none focus:border-arbor-emerald/50 transition-colors"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-arbor-sage/70 mb-1.5">Longitude</label>
                <input
                    v-model="form.longitude"
                    type="number"
                    step="any"
                    required
                    class="w-full bg-arbor-glass border border-arbor-glass-border rounded-xl px-4 py-2.5 text-base text-arbor-cream focus:outline-none focus:border-arbor-emerald/50 transition-colors"
                />
            </div>
        </div>
        <div v-if="form.latitude === '' && form.longitude === ''" class="flex flex-col gap-2 mt-1">
            <button
                type="button"
                @click="fillCurrentLocation"
                class="text-xs text-arbor-emerald hover:text-arbor-emerald/80 underline underline-offset-2 text-left"
            >
                📍 Utiliser ma position actuelle
            </button>
            <p v-if="locationError" class="text-[11px] text-amber-300/80">{{ locationError }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-arbor-sage/70 mb-1.5">Catégorie</label>
                <select
                    v-model="form.category"
                    required
                    class="w-full bg-arbor-glass border border-arbor-glass-border rounded-xl px-4 py-2.5 text-sm text-arbor-cream focus:outline-none focus:border-arbor-emerald/50 transition-colors appearance-none"
                >
                    <option value="" disabled>Choisir...</option>
                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-arbor-sage/70 mb-1.5">Difficulté (1-5)</label>
                <input
                    v-model.number="form.difficulty_level"
                    type="number"
                    min="1"
                    max="5"
                    class="w-full bg-arbor-glass border border-arbor-glass-border rounded-xl px-4 py-2.5 text-base text-arbor-cream focus:outline-none focus:border-arbor-emerald/50 transition-colors"
                />
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-arbor-sage/70 mb-1.5">Niveau de sensibilité naturelle</label>
            <select
                v-model="form.nature_sensitivity_level"
                class="w-full bg-arbor-glass border border-arbor-glass-border rounded-xl px-4 py-2.5 text-sm text-arbor-cream focus:outline-none focus:border-arbor-emerald/50 transition-colors appearance-none"
            >
                <option v-for="level in sensitivityLevels" :key="level.value" :value="level.value">{{ level.label }}</option>
            </select>
            <p v-if="showSensitiveWarning" class="text-[11px] text-amber-300/80 mt-1.5">
                ⚠️ Les coordonnées exactes seront masquées et remplacées par une position approximative.
            </p>
        </div>

        <div>
            <label class="block text-xs font-medium text-arbor-sage/70 mb-1.5">Tags</label>
            <div class="flex flex-wrap gap-1.5 mb-2">
                <span
                    v-for="tag in form.tags"
                    :key="tag"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-arbor-emerald/10 text-arbor-emerald text-xs"
                >
                    {{ tag }}
                    <button type="button" @click="removeTag(tag)" class="hover:text-arbor-cream">×</button>
                </span>
            </div>
            <div class="flex gap-2">
                <input
                    v-model="tagInput"
                    type="text"
                    maxlength="50"
                    class="flex-1 bg-arbor-glass border border-arbor-glass-border rounded-xl px-4 py-2 text-base text-arbor-cream placeholder:text-arbor-sage/60 focus:outline-none focus:border-arbor-emerald/50 transition-colors"
                    placeholder="Ajouter un tag..."
                    @keydown.enter.prevent="addTag"
                />
                <button
                    type="button"
                    @click="addTag"
                    class="px-3 py-2 rounded-xl bg-arbor-glass text-arbor-sage text-xs hover:bg-white/10 transition-colors"
                >
                    + Ajouter
                </button>
            </div>
        </div>

        <div class="pt-2 flex gap-3">
            <button
                type="submit"
                :disabled="isSubmitting"
                class="flex-1 py-2.5 rounded-xl bg-arbor-emerald text-arbor-night font-semibold text-sm hover:bg-arbor-emerald/90 transition-colors disabled:opacity-50"
            >
                {{ isSubmitting ? 'Envoi...' : 'Proposer ce lieu' }}
            </button>
            <button
                type="button"
                @click="emit('cancel')"
                class="px-5 py-2.5 rounded-xl bg-arbor-glass text-arbor-sage text-sm hover:bg-white/10 transition-colors"
            >
                Annuler
            </button>
        </div>
    </form>
</template>
