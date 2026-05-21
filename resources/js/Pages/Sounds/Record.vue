<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AudioRecorder from '@/Components/Audio/AudioRecorder.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    categories: Array,
    environments: Array,
    selectedListeningPoint: Object,
});

const step = ref('record'); // 'record' | 'meta'
const recordedBlob = ref(null);
const recordedUrl = ref(null);

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

const coverPreview = ref(null);
const isDragging = ref(false);
const geoError = ref('');
const locating = ref(false);
const uploadProgress = ref(0);
const categorySelection = ref('');
const environmentSelection = ref(props.selectedListeningPoint?.environment_id ?? '');

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

const onConfirmRecording = (blob, url) => {
    recordedBlob.value = blob;
    recordedUrl.value = url;

    // Build filename with timestamp
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    const h = String(now.getHours()).padStart(2, '0');
    const min = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    const ext = blob.type.includes('mp4') || blob.type.includes('aac') ? 'm4a' : 'webm';
    const filename = `arborisis_record_${y}${m}${d}_${h}${min}${s}.${ext}`;

    form.audio_file = new File([blob], filename, { type: blob.type || 'audio/webm' });
    form.recorded_at = `${y}-${m}-${d}`;
    form.recorded_time = `${h}:${min}`;

    step.value = 'meta';
};

const handleCoverChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.cover_image = file;
        coverPreview.value = URL.createObjectURL(file);
    }
};

const getCurrentLocation = () => {
    if (!navigator.geolocation) {
        geoError.value = "La géolocalisation n'est pas supportée par ce navigateur.";
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
            geoError.value = "Impossible d'obtenir votre position. Vérifiez les permissions de votre navigateur.";
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
            coverPreview.value = null;
            uploadProgress.value = 0;
            step.value = 'record';
            recordedBlob.value = null;
            recordedUrl.value = null;
        },
    });
};

const goBackToRecord = () => {
    step.value = 'record';
    recordedBlob.value = null;
    recordedUrl.value = null;
    form.audio_file = null;
};
</script>

<template>
    <Head title="Enregistrer un son" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="atlas-kicker">Capture directe</p>
                <h2 class="font-display text-2xl font-semibold text-arbor-cream">
                    Enregistrer une trace sonore
                </h2>
            </div>
        </template>

        <div class="relative min-h-[calc(100vh-8rem)]">
            <!-- Step 1: Recorder -->
            <transition
                enter-active-class="transition duration-500 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-300 ease-in absolute inset-0"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-4"
            >
                <div v-if="step === 'record'" class="py-6">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <div class="trace-frame mb-8 p-6 text-center sm:p-8">
                            <div class="relative z-10">
                            <p class="atlas-kicker mb-4">Le terrain commence ici</p>
                            <h1 class="atlas-heading text-5xl sm:text-6xl">
                                Capturez l'instant.
                            </h1>
                            <p class="mx-auto mt-5 max-w-xl text-sm leading-6 text-arbor-sage/80">
                                Choisissez votre source, écoutez le lieu, puis gardez uniquement les captures qui méritent d'entrer dans l'atlas.
                            </p>
                            <p v-if="selectedListeningPoint" class="mx-auto mt-4 max-w-xl rounded-lg border border-arbor-lichen/20 bg-arbor-lichen/10 px-4 py-3 text-sm text-arbor-cream">
                                Nouvelle prise pour <span class="font-semibold text-arbor-lichen">{{ selectedListeningPoint.title }}</span>.
                            </p>
                            </div>
                        </div>

                        <div class="trace-frame overflow-hidden">
                            <div class="relative z-10">
                            <AudioRecorder @confirm="onConfirmRecording" />
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Step 2: Metadata form -->
            <transition
                enter-active-class="transition duration-500 ease-out delay-200"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-300 ease-in absolute inset-0"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
            >
                <div v-if="step === 'meta'" class="py-8">
                    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <p class="atlas-kicker">Contexte de terrain</p>
                                <h2 class="font-display text-3xl font-semibold text-arbor-cream">
                                    Décrivez votre capture
                                </h2>
                                <p class="mt-1 text-sm text-arbor-sage/70">
                                    Vous pouvez revenir en arrière pour réenregistrer.
                                </p>
                            </div>
                            <button
                                class="btn-secondary text-sm"
                                @click="goBackToRecord"
                            >
                                <svg class="mr-1.5 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Retour
                            </button>
                        </div>

                        <form @submit.prevent="submit" class="space-y-8">
                            <input type="hidden" v-model="form.listening_point_id" />
                            <input type="hidden" v-model="form.create_new_listening_point" />

                            <div v-if="selectedListeningPoint" class="trace-frame border-arbor-lichen/25 bg-arbor-lichen/10 p-5">
                                <div class="relative z-10">
                                    <p class="atlas-kicker mb-2">Point d'écoute prérempli</p>
                                    <h3 class="font-display text-2xl font-semibold text-arbor-cream">
                                        {{ selectedListeningPoint.title }}
                                    </h3>
                                    <p class="mt-1 text-sm text-arbor-sage">
                                        La capture sera attachée à ce point après publication.
                                    </p>
                                </div>
                            </div>

                            <!-- Audio preview (read-only) -->
                            <div class="trace-frame p-6">
                                <div class="relative z-10">
                                <InputLabel value="Enregistrement" />
                                <div class="mt-3 flex items-center gap-4 rounded-lg border border-arbor-mineral/10 bg-arbor-deep/40 px-4 py-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-arbor-lichen/10">
                                        <svg class="h-5 w-5 text-arbor-lichen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm text-arbor-cream">
                                            {{ form.audio_file?.name || 'Enregistrement' }}
                                        </p>
                                        <p class="text-xs text-arbor-sage/70">
                                            {{ form.audio_file?.type?.includes('mp4') || form.audio_file?.type?.includes('aac') ? 'M4A' : 'WebM' }}
                                            — {{ (form.audio_file?.size / 1024 / 1024).toFixed(2) }} Mo
                                        </p>
                                    </div>
                                    <audio :src="recordedUrl" controls class="h-8 w-32 sm:w-48" />
                                </div>
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
                                        Public approximé
                                    </span>
                                </div>
                                <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                                    class="mb-2 flex items-center gap-1.5 text-sm text-arbor-lichen transition-colors hover:text-arbor-firefly disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <svg v-if="locating" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span v-if="locating">Localisation...</span>
                                    <span v-else>Utiliser ma position actuelle</span>
                                </button>
                                <div v-if="geoError" class="mb-4 flex items-center gap-1.5 text-sm text-red-400">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ geoError }}
                                </div>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                                        class="rounded border-arbor-glass-border bg-arbor-deep text-arbor-lichen focus:ring-arbor-lichen"
                                    />
                                    <label for="is_sensitive_location" class="text-sm text-arbor-sage">
                                        Ce lieu est sensible — afficher une localisation approximative uniquement
                                    </label>
                                </div>
                                </div>
                            </div>

                            <!-- Recording Details -->
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
                                        class="relative mb-3 aspect-square w-48 overflow-hidden rounded-xl"
                                    >
                                        <img :src="coverPreview" class="h-full w-full object-cover" />
                                        <button
                                            type="button"
                                            @click="coverPreview = null; form.cover_image = null"
                                            class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-arbor-night/80 text-arbor-cream transition-colors hover:bg-red-500/80"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <label class="inline-flex cursor-pointer items-center rounded-xl border border-arbor-glass-border bg-arbor-glass px-4 py-2 text-sm text-arbor-cream transition-colors hover:bg-white/10">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Choisir une image
                                        <input type="file" accept="image/*" class="hidden" @change="handleCoverChange" />
                                    </label>
                                    <p class="mt-2 text-xs text-arbor-sage">JPG, PNG, WebP — max 10 Mo</p>
                                </div>
                                <InputError :message="form.errors.cover_image" />
                            </div>

                            <!-- Submit -->
                            <div class="flex items-center justify-end gap-4 border-t border-arbor-glass-border pt-4">
                                <button
                                    type="button"
                                    class="text-sm text-arbor-sage transition-colors hover:text-arbor-cream"
                                    @click="goBackToRecord"
                                >
                                    Annuler
                                </button>
                                <div class="w-full max-w-xs">
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="btn-primary w-full disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <span v-if="form.processing">Publication en cours...</span>
                                        <span v-else>Publier le son</span>
                                    </button>
                                    <div v-if="form.processing && uploadProgress > 0" class="mt-3">
                                        <div class="h-1.5 overflow-hidden rounded-full bg-arbor-glass">
                                            <div
                                                class="h-full rounded-full bg-gradient-to-r from-arbor-lichen to-arbor-firefly transition-all duration-200"
                                                :style="`width: ${uploadProgress}%`"
                                            />
                                        </div>
                                        <p class="mt-1 text-right text-xs text-arbor-sage">{{ Math.round(uploadProgress) }}%</p>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </transition>
        </div>
    </AuthenticatedLayout>
</template>
