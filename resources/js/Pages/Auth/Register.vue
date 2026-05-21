<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const mounted = ref(false);

onMounted(() => {
    setTimeout(() => {
        mounted.value = true;
    }, 50);
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Rejoindre" />

        <div class="relative min-h-[calc(100vh-4rem)] flex items-center justify-center overflow-hidden pt-16 py-12">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div
                    class="absolute -top-20 -left-20 w-[500px] h-[500px] rounded-full bg-arbor-moss/10 blur-[100px] transition-all duration-1000"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                />
                <div
                    class="absolute -bottom-20 -right-20 w-[600px] h-[600px] rounded-full bg-arbor-emerald/5 blur-[120px] transition-all duration-1000 delay-200"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                />
                <div
                    class="absolute top-1/2 right-1/4 w-72 h-72 rounded-full bg-arbor-amber/5 blur-[80px] transition-all duration-1000 delay-300"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                />

                <svg class="absolute top-24 left-[12%] w-28 h-28 text-arbor-moss/10 animate-pulse-slow" viewBox="0 0 100 100" fill="currentColor"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.8s ease-out 0.4s"
                >
                    <path d="M50 5 C20 5 5 30 5 55 C5 80 25 95 50 95 C75 95 95 80 95 55 C95 30 80 5 50 5 Z M50 85 C30 85 15 70 15 55 C15 35 30 20 50 20 C70 20 85 35 85 55 C85 70 70 85 50 85 Z" />
                </svg>

                <svg class="absolute bottom-24 right-[12%] w-20 h-20 text-arbor-emerald/10 animate-pulse-slow" viewBox="0 0 100 100" fill="currentColor"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="animation-delay: 2s; transition: all 0.8s ease-out 0.5s"
                >
                    <path d="M50 10 Q30 40 10 50 Q30 60 50 90 Q70 60 90 50 Q70 40 50 10 Z" />
                </svg>

                <div class="absolute top-[30%] right-[10%] flex items-end gap-1 h-12"
                    :class="mounted ? 'opacity-100' : 'opacity-0'"
                    style="transition: opacity 1s ease-out 0.6s"
                >
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 15px; animation-delay: 0s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 35px; animation-delay: 0.1s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 25px; animation-delay: 0.2s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 45px; animation-delay: 0.3s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 20px; animation-delay: 0.15s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 30px; animation-delay: 0.25s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 10px; animation-delay: 0.05s" />
                </div>

                <div class="absolute inset-0 opacity-[0.02]"
                    style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.3) 1px, transparent 0); background-size: 40px 40px;"
                />
            </div>

            <div class="relative z-10 w-full max-w-md mx-auto px-4 sm:px-6"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.7s ease-out 0.2s"
            >
                <div class="glass-card-glow p-8 sm:p-10 relative overflow-hidden"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
                    style="transition: all 0.6s ease-out 0.3s"
                >
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-1 bg-gradient-to-r from-transparent via-arbor-emerald/40 to-transparent" />
                    <div class="organic-blob w-40 h-40 bg-arbor-emerald/10 -top-10 -right-10" />

                    <div class="text-center mb-8"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s ease-out 0.4s"
                    >
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-arbor-moss/20 mb-5 ring-1 ring-arbor-emerald/20"
                            :class="mounted ? 'scale-100' : 'scale-90'"
                            style="transition: transform 0.5s ease-out 0.5s"
                        >
                            <svg class="w-7 h-7 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <h1 class="font-display text-3xl font-bold text-arbor-cream mb-2">
                            Rejoindre Arborisis
                        </h1>
                        <p class="text-arbor-sage text-sm">
                            Créez votre compte pour partager les sons de la nature
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4"
                        :class="mounted ? 'opacity-100' : 'opacity-0'"
                        style="transition: opacity 0.6s ease-out 0.5s"
                    >
                        <div
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                            style="transition: all 0.5s ease-out 0.55s"
                        >
                            <InputLabel for="name" value="Nom d'utilisateur" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="Votre nom"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                            style="transition: all 0.5s ease-out 0.6s"
                        >
                            <InputLabel for="email" value="Adresse e-mail" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="vous@exemple.com"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                            style="transition: all 0.5s ease-out 0.65s"
                        >
                            <InputLabel for="password" value="Mot de passe" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                            style="transition: all 0.5s ease-out 0.7s"
                        >
                            <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <InputError class="mt-2" :message="form.errors.password_confirmation" />
                        </div>

                        <div class="pt-2"
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                            style="transition: all 0.5s ease-out 0.75s"
                        >
                            <PrimaryButton
                                class="w-full justify-center py-3.5 text-base"
                                :class="{ 'opacity-50 cursor-wait': form.processing }"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Création du compte...</span>
                                <span v-else>Créer un compte</span>
                            </PrimaryButton>
                        </div>
                    </form>

                    <div class="relative my-6"
                        :class="mounted ? 'opacity-100' : 'opacity-0'"
                        style="transition: opacity 0.5s ease-out 0.8s"
                    >
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-arbor-glass-border" />
                        </div>
                        <div class="relative flex justify-center text-xs">
                            <span class="bg-arbor-deep px-3 text-arbor-sage/60">ou</span>
                        </div>
                    </div>

                    <div class="text-center"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                        style="transition: all 0.5s ease-out 0.85s"
                    >
                        <p class="text-sm text-arbor-sage">
                            Déjà membre ?
                            <Link
                                :href="route('login')"
                                class="text-arbor-emerald hover:text-arbor-emerald-dark font-medium transition-colors hover:underline underline-offset-4 ml-1"
                            >
                                Se connecter
                            </Link>
                        </p>
                    </div>
                </div>

                <div class="mt-6 text-center"
                    :class="mounted ? 'opacity-100' : 'opacity-0'"
                    style="transition: opacity 0.5s ease-out 0.95s"
                >
                    <Link href="/" class="text-sm text-arbor-sage hover:text-arbor-cream transition-colors inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour à l'accueil
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
