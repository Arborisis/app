<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    profile: {
        type: Object,
        default: null,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    bio: props.profile?.bio ?? '',
    location: props.profile?.location ?? '',
    website: props.profile?.website ?? '',
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-arbor-cream">
                Informations du profil
            </h2>

            <p class="mt-1 text-sm text-arbor-sage">
                Mettez à jour les informations de votre compte et votre adresse e-mail.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Nom" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Adresse e-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="bio" value="Bio" />
                <textarea
                    id="bio"
                    v-model="form.bio"
                    rows="3"
                    maxlength="1000"
                    class="mt-1 block w-full rounded-lg border border-arbor-fog/30 bg-arbor-charcoal/50 px-4 py-2.5 text-base text-arbor-cream placeholder:text-arbor-sage/60 focus:border-arbor-emerald/50 focus:outline-none focus:ring-1 focus:ring-arbor-emerald/20 transition-colors resize-none"
                    placeholder="Parlez-nous de vous, de votre passion pour le field recording..."
                />
                <InputError class="mt-2" :message="form.errors.bio" />
            </div>

            <div>
                <InputLabel for="location" value="Localisation" />
                <TextInput
                    id="location"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.location"
                    autocomplete="off"
                    placeholder="Ex: Bretagne, France"
                />
                <InputError class="mt-2" :message="form.errors.location" />
            </div>

            <div>
                <InputLabel for="website" value="Site web" />
                <TextInput
                    id="website"
                    type="url"
                    class="mt-1 block w-full"
                    v-model="form.website"
                    autocomplete="url"
                    placeholder="https://..."
                />
                <InputError class="mt-2" :message="form.errors.website" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-arbor-cream">
                    Votre adresse e-mail n'est pas vérifiée.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-arbor-sage underline hover:text-arbor-cream focus:outline-none focus:ring-2 focus:ring-arbor-emerald focus:ring-offset-2 focus:ring-offset-arbor-night"
                    >
                        Cliquez ici pour renvoyer l'e-mail de vérification.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-arbor-emerald"
                >
                    Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-arbor-emerald"
                    >
                        Modifications enregistrées.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
