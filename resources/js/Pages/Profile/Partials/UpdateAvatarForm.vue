<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    avatarUrl: {
        type: String,
        default: null,
    },
});

const user = usePage().props.auth.user;
const fileInput = ref(null);
const previewUrl = ref(null);

const form = useForm({
    avatar: null,
});

const displayUrl = computed(() => previewUrl.value ?? props.avatarUrl);

const onFileChange = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    form.avatar = file;

    const reader = new FileReader();
    reader.onload = (e) => {
        previewUrl.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const submit = () => {
    if (!form.avatar) return;

    form.post(route('profile.avatar'), {
        forceFormData: true,
        onSuccess: () => {
            previewUrl.value = null;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const triggerFileInput = () => {
    fileInput.value?.click();
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-arbor-cream">
                Photo de profil
            </h2>
            <p class="mt-1 text-sm text-arbor-sage">
                Choisissez une image pour votre profil public.
            </p>
        </header>

        <div class="mt-6 flex items-center gap-6">
            <div
                class="relative w-24 h-24 rounded-full overflow-hidden ring-4 ring-arbor-glass-border/50 bg-arbor-moss/30 flex items-center justify-center shrink-0 cursor-pointer group"
                @click="triggerFileInput"
            >
                <img
                    v-if="displayUrl"
                    :src="displayUrl"
                    :alt="user.name"
                    class="w-full h-full object-cover"
                />
                <span v-else class="text-3xl font-display font-bold text-arbor-emerald">
                    {{ user.name?.charAt(0)?.toUpperCase() ?? '?' }}
                </span>
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>

            <div class="flex-1">
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="onFileChange"
                />
                <button
                    type="button"
                    @click="triggerFileInput"
                    class="text-sm text-arbor-emerald hover:text-arbor-emerald/80 underline underline-offset-2"
                >
                    Choisir une image
                </button>
                <p class="text-xs text-arbor-sage/60 mt-1">
                    JPG, PNG. Max 2 Mo.
                </p>
                <p v-if="form.errors.avatar" class="text-xs text-rose-400 mt-1">
                    {{ form.errors.avatar }}
                </p>
            </div>
        </div>

        <div v-if="form.avatar" class="mt-4 flex items-center gap-4">
            <PrimaryButton :disabled="form.processing" @click="submit">
                {{ form.processing ? 'Envoi...' : 'Enregistrer la photo' }}
            </PrimaryButton>
            <button
                type="button"
                class="text-sm text-arbor-sage hover:text-arbor-cream"
                @click="previewUrl = null; form.avatar = null; if (fileInput) fileInput.value = ''"
            >
                Annuler
            </button>
        </div>
    </section>
</template>
