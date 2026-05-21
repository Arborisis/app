<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const mounted = ref(false);

onMounted(() => {
    setTimeout(() => {
        mounted.value = true;
    }, 50);
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Connexion" />

        <div class="relative min-h-[calc(100vh-4rem)] flex items-center justify-center overflow-hidden pt-16">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div
                    class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full bg-arbor-moss/10 blur-[100px] transition-all duration-1000"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                />
                <div
                    class="absolute -bottom-40 -left-40 w-[600px] h-[600px] rounded-full bg-arbor-emerald/5 blur-[120px] transition-all duration-1000 delay-200"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                />
                <div
                    class="absolute top-1/3 left-1/4 w-72 h-72 rounded-full bg-arbor-amber/5 blur-[80px] transition-all duration-1000 delay-300"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                />

                <svg class="absolute top-20 right-[15%] w-32 h-32 text-arbor-moss/10 animate-pulse-slow transition-all duration-800" viewBox="0 0 100 100" fill="currentColor"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition-delay: 0.4s"
                >
                    <path d="M50 5 C20 5 5 30 5 55 C5 80 25 95 50 95 C75 95 95 80 95 55 C95 30 80 5 50 5 Z M50 85 C30 85 15 70 15 55 C15 35 30 20 50 20 C70 20 85 35 85 55 C85 70 70 85 50 85 Z" />
                </svg>

                <svg class="absolute bottom-32 left-[10%] w-24 h-24 text-arbor-emerald/10 animate-pulse-slow" viewBox="0 0 100 100" fill="currentColor" style="animation-delay: 2s"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                >
                    <path d="M50 10 Q30 40 10 50 Q30 60 50 90 Q70 60 90 50 Q70 40 50 10 Z" />
                </svg>

                <div class="absolute bottom-[20%] right-[8%] flex items-end gap-1 h-16"
                    :class="mounted ? 'opacity-100' : 'opacity-0'"
                    style="transition: opacity 1s ease-out 0.6s"
                >
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 20px; animation-delay: 0s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 40px; animation-delay: 0.1s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 30px; animation-delay: 0.2s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 50px; animation-delay: 0.3s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 25px; animation-delay: 0.15s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 35px; animation-delay: 0.25s" />
                    <div class="w-0.5 bg-arbor-emerald/20 rounded-full animate-wave" style="height: 15px; animation-delay: 0.05s" />
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                        </div>
                        <h1 class="font-display text-3xl font-bold text-arbor-cream mb-2">
                            Bon retour
                        </h1>
                        <p class="text-arbor-sage text-sm">
                            Connectez-vous pour explorer les sons de la nature
                        </p>
                    </div>

                    <div v-if="status" class="mb-6 p-3 rounded-lg bg-arbor-emerald/10 border border-arbor-emerald/20 text-sm text-arbor-emerald text-center"
                        :class="mounted ? 'opacity-100' : 'opacity-0'"
                        style="transition: opacity 0.5s ease-out 0.45s"
                    >
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5"
                        :class="mounted ? 'opacity-100' : 'opacity-0'"
                        style="transition: opacity 0.6s ease-out 0.5s"
                    >
                        <div
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                            style="transition: all 0.5s ease-out 0.55s"
                        >
                            <InputLabel for="email" value="Adresse e-mail" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="vous@exemple.com"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                            style="transition: all 0.5s ease-out 0.6s"
                        >
                            <InputLabel for="password" value="Mot de passe" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="flex items-center justify-between"
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                            style="transition: all 0.5s ease-out 0.65s"
                        >
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <Checkbox name="remember" v-model:checked="form.remember" />
                                <span class="text-sm text-arbor-sage group-hover:text-arbor-cream transition-colors">Se souvenir de moi</span>
                            </label>

                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm text-arbor-emerald hover:text-arbor-emerald-dark transition-colors hover:underline underline-offset-4"
                            >
                                Mot de passe oublié ?
                            </Link>
                        </div>

                        <div class="pt-2"
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                            style="transition: all 0.5s ease-out 0.7s"
                        >
                            <PrimaryButton
                                class="w-full justify-center py-3.5 text-base"
                                :class="{ 'opacity-50 cursor-wait': form.processing }"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Connexion...</span>
                                <span v-else>Se connecter</span>
                            </PrimaryButton>
                        </div>
                    </form>

                    <div class="relative my-6"
                        :class="mounted ? 'opacity-100' : 'opacity-0'"
                        style="transition: opacity 0.5s ease-out 0.75s"
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
                        style="transition: all 0.5s ease-out 0.8s"
                    >
                        <p class="text-sm text-arbor-sage">
                            Pas encore de compte ?
                            <Link
                                :href="route('register')"
                                class="text-arbor-emerald hover:text-arbor-emerald-dark font-medium transition-colors hover:underline underline-offset-4 ml-1"
                            >
                                Rejoindre Arborisis
                            </Link>
                        </p>
                    </div>
                </div>

                <div class="mt-6 text-center"
                    :class="mounted ? 'opacity-100' : 'opacity-0'"
                    style="transition: opacity 0.5s ease-out 0.9s"
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
