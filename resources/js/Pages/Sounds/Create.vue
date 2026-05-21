<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    categories: Array,
    environments: Array,
    selectedListeningPoint: Object,
});

const form = useForm({
    audio_file: null,
    title: '',
    description: '',
    recorded_at: '',
    recorded_time: '',
    latitude: props.selectedListeningPoint?.public_latitude ?? '',
    longitude: props.selectedListeningPoint?.public_longitude ?? '',
    location_name: props.selectedListeningPoint?.title ?? '',
    is_sensitive_location: false,
    tags: '',
    category_id: '',
    new_category_name: '',
    environment_id: props.selectedListeningPoint?.environment_id ?? '',
    new_environment_name: '',
    equipment: '',
    license: 'all_rights_reserved',
    visibility: 'public',
    cover_image: null,
    listening_point_id: props.selectedListeningPoint?.id ?? '',
    create_new_listening_point: false,
});

const audioPreview = ref(null);
const coverPreview = ref(null);
const isDragging = ref(false);
const geoError = ref('');
const locating = ref(false);
const uploadProgress = ref(0);
const categorySelection = ref('');
const environmentSelection = ref(props.selectedListeningPoint?.environment_id ?? '');

const completionItems = computed(() => [
    { label: 'Audio', done: Boolean(form.audio_file) },
    { label: 'Titre', done: Boolean(form.title) },
    { label: 'Lieu', done: Boolean(form.latitude && form.longitude) },
    { label: 'Droits', done: Boolean(form.license && form.visibility) },
]);

const licenses = [
    { value: 'all_rights_reserved', label: 'Tous droits réservés' },
    { value: 'cc_by', label: 'CC BY' },
    { value: 'cc_by_sa', label: 'CC BY-SA' },
    { value: 'cc_by_nc', label: 'CC BY-NC' },
    { value: 'cc0', label: 'CC0 (Domaine public)' },
];

const visibilities = [
    { value: 'public', label: 'Public' },
    { value: 'followers', label: 'Abonnés uniquement' },
    { value: 'private', label: 'Privé' },
];

const selectCategory = (value) => {
    categorySelection.value = value;
    form.category_id = value === '__new' ? '' : value;
    if (value !== '__new') {
        form.new_category_name = '';
    }
};

const selectEnvironment = (value) => {
    environmentSelection.value = value;
    form.environment_id = value === '__new' ? '' : value;
    if (value !== '__new') {
        form.new_environment_name = '';
    }
};

const handleAudioChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.audio_file = file;
        audioPreview.value = URL.createObjectURL(file);
    }
};

const handleCoverChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.cover_image = file;
        coverPreview.value = URL.createObjectURL(file);
    }
};

const handleDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('audio/')) {
        form.audio_file = file;
        audioPreview.value = URL.createObjectURL(file);
    }
};

const getCurrentLocation = () => {
    if (!navigator.geolocation) {
        geoError.value = 'La géolocalisation n\'est pas supportée par ce navigateur.';
        return;
    }
    locating.value = true;
    geoError.value = '';
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude.toFixed(6);
            form.longitude = position.coords.longitude.toFixed(6);
            locating.value = false;
        },
        () => {
            geoError.value = 'Impossible d\'obtenir votre position. Vérifiez les permissions de votre navigateur.';
            locating.value = false;
        }
    );
};

const submit = () => {
    uploadProgress.value = 0;
    form.post(route('sounds.store'), {
        forceFormData: true,
        onProgress: (progress) => {
            uploadProgress.value = progress.percentage;
        },
        onSuccess: () => {
            form.reset();
            audioPreview.value = null;
            coverPreview.value = null;
            uploadProgress.value = 0;
        },
    });
};
</script>

<template>
    <Head title="Publier un son" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="atlas-kicker">Nouvelle archive</p>
                <h2 class="font-display text-2xl font-semibold text-arbor-cream">
                    Publier une trace sonore
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-6xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.78fr_1.22fr] lg:px-8">
                <aside class="space-y-5">
                    <div class="trace-frame p-6">
                        <div class="relative z-10">
                            <p class="atlas-kicker mb-4">Carnet de terrain</p>
                            <h1 class="atlas-heading text-4xl">Une capsule du vivant.</h1>
                            <p class="mt-5 text-sm leading-6 text-arbor-sage">
                                Ajoutez le fichier, son contexte, puis une localisation. Les coordonnées publiques seront protégées selon les règles de confidentialité Arborisis.
                            </p>
                        </div>
                    </div>

                    <div class="trace-frame p-5">
                        <div class="relative z-10">
                            <p class="mb-4 text-xs font-medium uppercase tracking-[0.16em] text-arbor-sage">Progression</p>
                            <div class="space-y-3">
                                <div
                                    v-for="item in completionItems"
                                    :key="item.label"
                                    class="flex items-center justify-between rounded-lg border border-arbor-mineral/10 bg-arbor-mist/[0.035] px-3 py-2 text-sm"
                                >
                                    <span class="text-arbor-sage">{{ item.label }}</span>
                                    <span
                                        class="h-2.5 w-2.5 rounded-full"
                                        :class="item.done ? 'bg-arbor-firefly shadow-firefly' : 'bg-arbor-mineral/20'"
                                    ></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-arbor-lichen/20 bg-arbor-lichen/10 p-4 text-sm leading-6 text-arbor-sage">
                        Les lieux sensibles doivent rester approximés. Ne publiez jamais d’espèces fragiles avec coordonnées exactes.
                    </div>
                </aside>

                <div>
                <!-- Recorder promo banner -->
                <div class="mb-8 rounded-lg border border-arbor-lichen/20 bg-arbor-lichen/5 p-5 backdrop-blur-sm">
                    <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-display text-xl font-semibold text-arbor-cream">
                                Enregistrez directement depuis votre appareil
                            </h3>
                            <p class="mt-1 text-sm text-arbor-sage/70">
                                Utilisez le micro de votre téléphone ou une interface externe, sans quitter Arborisis.
                            </p>
                        </div>
                        <Link
                            :href="route('sounds.record', selectedListeningPoint ? { listening_point_id: selectedListeningPoint.id } : {})"
                            class="btn-primary inline-flex shrink-0 items-center gap-2 text-sm"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                            Ouvrir l'enregistreur
                        </Link>
                    </div>
                </div>

                    <form @submit.prevent="submit" class="space-y-8">
                    <input type="hidden" v-model="form.listening_point_id" />
                    <input type="hidden" v-model="form.create_new_listening_point" />

                    <div v-if="selectedListeningPoint" class="trace-frame border-arbor-lichen/25 bg-arbor-lichen/10 p-5">
                        <div class="relative z-10">
                            <p class="atlas-kicker mb-2">Nouvelle prise liée</p>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-display text-2xl font-semibold text-arbor-cream">
                                        {{ selectedListeningPoint.title }}
                                    </h3>
                                    <p class="mt-1 text-sm text-arbor-sage">
                                        Point prérempli · coordonnées publiques approximées à {{ selectedListeningPoint.public_accuracy_meters }} m.
                                    </p>
                                </div>
                                <Link
                                    :href="route('listening-points.show', selectedListeningPoint.slug)"
                                    class="text-sm text-arbor-lichen hover:text-arbor-firefly"
                                >
                                    Voir le point
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Audio Upload -->
                    <div class="trace-frame p-5 sm:p-6">
                        <div class="relative z-10">
                        <InputLabel value="Fichier audio *" />
                        <div
                            class="mt-2 border border-dashed rounded-lg p-8 text-center transition-colors"
                            :class="isDragging ? 'border-arbor-firefly bg-arbor-firefly/5' : 'border-arbor-mineral/15 hover:border-arbor-lichen/40'"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop="handleDrop"
                        >
                            <div v-if="!audioPreview">
                                <div class="poetic-empty-icon flex items-center justify-center">
                                    <svg class="relative w-8 h-8 text-arbor-lichen/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                    </svg>
                                </div>
                                <p class="text-arbor-sage text-sm mb-2">
                                    Glissez-déposez votre fichier audio ici, ou
                                    <label class="text-arbor-lichen cursor-pointer hover:underline">
                                        parcourez
                                        <input type="file" accept="audio/mpeg,audio/wav,audio/flac,audio/mp4,audio/x-m4a" class="hidden" @change="handleAudioChange" />
                                    </label>
                                </p>
                                <p class="text-xs text-arbor-sage/70">MP3, WAV, FLAC, M4A — max 500 Mo</p>
                            </div>
                            <div v-else>
                                <audio :src="audioPreview" controls class="w-full" />
                                <button
                                    type="button"
                                    @click="audioPreview = null; form.audio_file = null"
                                    class="mt-3 text-sm text-arbor-sage hover:text-red-400 transition-colors"
                                >
                                    Supprimer le fichier
                                </button>
                            </div>
                        </div>
                        <InputError :message="form.errors.audio_file" />
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="trace-frame p-5 sm:p-6">
                        <div class="relative z-10">
                        <InputLabel for="title" value="Titre *" />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="mt-2 block w-full"
                            placeholder="Ex: Aube dans la forêt de Fontainebleau"
                            required
                        />
                        <InputError :message="form.errors.title" />
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="trace-frame p-5 sm:p-6">
                        <div class="relative z-10">
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="mt-2 block w-full rounded-xl border-arbor-glass-border bg-arbor-deep text-arbor-cream shadow-sm focus:border-arbor-emerald focus:ring-arbor-emerald"
                            placeholder="Décrivez le contexte de l'enregistrement, ce que vous entendez, l'ambiance..."
                        />
                        <InputError :message="form.errors.description" />
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="trace-frame p-6">
                        <div class="relative z-10">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <p class="atlas-kicker mb-1">Lieu d'écoute</p>
                                <h3 class="font-display text-2xl font-semibold text-arbor-cream">Localisation *</h3>
                            </div>
                            <span class="rounded-full border border-arbor-firefly/20 bg-arbor-firefly/10 px-3 py-1 text-[11px] text-arbor-firefly">
                                Publique approximée
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel for="latitude" value="Latitude" />
                                <TextInput
                                    id="latitude"
                                    v-model="form.latitude"
                                    type="text"
                                    class="mt-2 block w-full"
                                    placeholder="48.8566"
                                    required
                                />
                                <InputError :message="form.errors.latitude" />
                            </div>
                            <div>
                                <InputLabel for="longitude" value="Longitude" />
                                <TextInput
                                    id="longitude"
                                    v-model="form.longitude"
                                    type="text"
                                    class="mt-2 block w-full"
                                    placeholder="2.3522"
                                    required
                                />
                                <InputError :message="form.errors.longitude" />
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="getCurrentLocation"
                            :disabled="locating"
                            class="text-sm text-arbor-lichen hover:text-arbor-firefly flex items-center gap-1.5 mb-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="locating" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span v-if="locating">Localisation...</span>
                            <span v-else>Utiliser ma position actuelle</span>
                        </button>
                        <div v-if="geoError" class="mb-4 text-sm text-red-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ geoError }}
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="location_name" value="Nom du lieu" />
                                <TextInput
                                    id="location_name"
                                    v-model="form.location_name"
                                    type="text"
                                    class="mt-2 block w-full"
                                    placeholder="Ex: Forêt de Fontainebleau"
                                />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <input
                                id="is_sensitive_location"
                                v-model="form.is_sensitive_location"
                                type="checkbox"
                                class="rounded border-arbor-glass-border bg-arbor-deep text-arbor-emerald focus:ring-arbor-emerald"
                            />
                            <label for="is_sensitive_location" class="text-sm text-arbor-sage">
                                Ce lieu est sensible — afficher une localisation approximative uniquement
                            </label>
                        </div>
                        </div>
                    </div>

                    <!-- Recording Details -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="recorded_at" value="Date d'enregistrement" />
                            <TextInput
                                id="recorded_at"
                                v-model="form.recorded_at"
                                type="date"
                                class="mt-2 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel for="recorded_time" value="Heure d'enregistrement" />
                            <TextInput
                                id="recorded_time"
                                v-model="form.recorded_time"
                                type="time"
                                class="mt-2 block w-full"
                            />
                        </div>
                    </div>

                    <!-- Category & Environment -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="category_id" value="Catégorie" />
                            <select
                                id="category_id"
                                v-model="categorySelection"
                                @change="selectCategory($event.target.value)"
                                class="mt-2 block w-full rounded-xl border-arbor-glass-border bg-arbor-deep text-arbor-cream shadow-sm focus:border-arbor-emerald focus:ring-arbor-emerald"
                            >
                                <option value="">Choisir une catégorie</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                                <option value="__new">Créer une nouvelle catégorie</option>
                            </select>
                            <TextInput
                                v-if="categorySelection === '__new'"
                                id="new_category_name"
                                v-model="form.new_category_name"
                                type="text"
                                class="mt-3 block w-full"
                                placeholder="Ex: Chiroptères, geais, ambiance de canopée"
                            />
                            <InputError :message="form.errors.category_id || form.errors.new_category_name" />
                        </div>
                        <div>
                            <InputLabel for="environment_id" value="Environnement" />
                            <select
                                id="environment_id"
                                v-model="environmentSelection"
                                @change="selectEnvironment($event.target.value)"
                                class="mt-2 block w-full rounded-xl border-arbor-glass-border bg-arbor-deep text-arbor-cream shadow-sm focus:border-arbor-emerald focus:ring-arbor-emerald"
                            >
                                <option value="">Choisir un environnement</option>
                                <option v-for="env in environments" :key="env.id" :value="env.id">
                                    {{ env.name }}
                                </option>
                                <option value="__new">Créer un nouvel environnement</option>
                            </select>
                            <TextInput
                                v-if="environmentSelection === '__new'"
                                id="new_environment_name"
                                v-model="form.new_environment_name"
                                type="text"
                                class="mt-3 block w-full"
                                placeholder="Ex: ripisylve, vieille haie, friche ferroviaire"
                            />
                            <InputError :message="form.errors.environment_id || form.errors.new_environment_name" />
                        </div>
                    </div>

                    <!-- Equipment & Tags -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="equipment" value="Matériel utilisé" />
                            <TextInput
                                id="equipment"
                                v-model="form.equipment"
                                type="text"
                                class="mt-2 block w-full"
                                placeholder="Ex: Zoom H6 + Sennheiser MKH 416"
                            />
                        </div>
                        <div>
                            <InputLabel for="tags" value="Tags (séparés par des virgules)" />
                            <TextInput
                                id="tags"
                                v-model="form.tags"
                                type="text"
                                class="mt-2 block w-full"
                                placeholder="Ex: oiseaux, matin, printemps"
                            />
                        </div>
                    </div>

                    <!-- License & Visibility -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="license" value="Licence *" />
                            <select
                                id="license"
                                v-model="form.license"
                                class="mt-2 block w-full rounded-xl border-arbor-glass-border bg-arbor-deep text-arbor-cream shadow-sm focus:border-arbor-emerald focus:ring-arbor-emerald"
                                required
                            >
                                <option v-for="lic in licenses" :key="lic.value" :value="lic.value">
                                    {{ lic.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.license" />
                        </div>
                        <div>
                            <InputLabel for="visibility" value="Visibilité *" />
                            <select
                                id="visibility"
                                v-model="form.visibility"
                                class="mt-2 block w-full rounded-xl border-arbor-glass-border bg-arbor-deep text-arbor-cream shadow-sm focus:border-arbor-emerald focus:ring-arbor-emerald"
                                required
                            >
                                <option v-for="vis in visibilities" :key="vis.value" :value="vis.value">
                                    {{ vis.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.visibility" />
                        </div>
                    </div>

                    <!-- Cover Image -->
                    <div>
                        <InputLabel value="Image de couverture" />
                        <div class="mt-2">
                            <div
                                v-if="coverPreview"
                                class="relative w-48 aspect-square rounded-xl overflow-hidden mb-3"
                            >
                                <img :src="coverPreview" class="w-full h-full object-cover" />
                                <button
                                    type="button"
                                    @click="coverPreview = null; form.cover_image = null"
                                    class="absolute top-2 right-2 w-8 h-8 rounded-full bg-arbor-night/80 flex items-center justify-center text-arbor-cream hover:bg-red-500/80 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <label class="inline-flex items-center px-4 py-2 rounded-xl border border-arbor-glass-border bg-arbor-glass text-sm text-arbor-cream cursor-pointer hover:bg-white/10 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Choisir une image
                                <input type="file" accept="image/*" class="hidden" @change="handleCoverChange" />
                            </label>
                            <p class="text-xs text-arbor-sage mt-2">JPG, PNG, WebP — max 10 Mo</p>
                        </div>
                        <InputError :message="form.errors.cover_image" />
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-arbor-glass-border">
                        <Link
                            href="/sounds"
                            class="text-sm text-arbor-sage hover:text-arbor-cream transition-colors"
                        >
                            Annuler
                        </Link>
                        <div class="w-full max-w-xs">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed w-full"
                            >
                                <span v-if="form.processing">Publication en cours...</span>
                                <span v-else>Publier le son</span>
                            </button>
                            <!-- Upload progress bar -->
                            <div v-if="form.processing && uploadProgress > 0" class="mt-3">
                                <div class="h-1.5 bg-arbor-glass rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-gradient-to-r from-arbor-lichen to-arbor-firefly rounded-full transition-all duration-200"
                                        :style="`width: ${uploadProgress}%`"
                                    />
                                </div>
                                <p class="text-xs text-arbor-sage mt-1 text-right">{{ Math.round(uploadProgress) }}%</p>
                            </div>
                        </div>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
