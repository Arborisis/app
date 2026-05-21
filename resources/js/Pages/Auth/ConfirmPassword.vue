<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};

const mounted = ref(false);

onMounted(() => {
    setTimeout(() => {
        mounted.value = true;
    }, 50);
});
</script>

<template>
    <GuestLayout>
        <Head title="Confirmer le mot de passe" />

        <div class="relative min-h-[calc(100vh-4rem)] flex items-center justify-center overflow-hidden pt-16 py-12">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div
                    class="absolute -top-20 -right-20 w-[500px] h-[500px] rounded-full bg-arbor-moss/10 blur-[100px] transition-all duration-1000"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                />
                <div
                    class="absolute -bottom-20 -left-20 w-[600px] h-[600px] rounded-full bg-arbor-emerald/5 blur-[120px] transition-all duration-1000 delay-200"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                />
                <div
                    class="absolute top-1/2 left-1/4 w-72 h-72 rounded-full bg-arbor-amber/5 blur-[80px] transition-all duration-1000 delay-300"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                />

                <svg class="absolute top-24 right-[12%] w-28 h-28 text-arbor-moss/10 animate-pulse-slow" viewBox="0 0 100 100" fill="currentColor"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.8s ease-out 0.4s"
                >
                    <path d="M50 5 C20 5 5 30 5 55 C5 80 25 95 50 95 C75 95 95 80 95 55 C95 30 80 5 50 5 Z M50 85 C30 85 15 70 15 55 C15 35 30 20 50 20 C70 20 85 35 85 55 C85 70 70 85 50 85 Z" />
                </svg>

                <svg class="absolute bottom-24 left-[12%] w-20 h-20 text-arbor-emerald/10 animate-pulse-slow" viewBox="0 0 100 100" fill="currentColor"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="animation-delay: 2s; transition: all 0.8s ease-out 0.5s"
                >
                    <path d="M50 10 Q30 40 10 50 Q30 60 50 90 Q70 60 90 50 Q70 40 50 10 Z" />
                </svg>

                <div class="absolute inset-0 opacity-[0.02]"
                    style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.3) 1px, transparent 0); background-size: 40px 40px;"
                />
            </div>

            <div class="relative z-10 w-full max-w-md mx-auto px-4 sm:px-6"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.7s ease-out 0.2s"
            >
                <div class="glass-card p-8 sm:p-10 relative overflow-hidden"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
                    style="transition: all 0.6s ease-out 0.3s"
                >
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-1 bg-gradient-to-r from-transparent via-arbor-emerald/40 to-transparent" />

                    <div class="text-center mb-8"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s ease-out 0.4s"
                    >
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-arbor-moss/20 mb-5 ring-1 ring-arbor-emerald/20"
                            :class="mounted ? 'scale-100' : 'scale-90'"
                            style="transition: transform 0.5s ease-out 0.5s"
                        >
                            <svg class="w-7 h-7 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        <h1 class="font-display text-3xl font-bold text-arbor-cream mb-2">
                            Zone sécurisée
                        </h1>
                        <p class="text-arbor-sage text-sm">
                            Confirmez votre identité pour continuer
                        </p>
                    </div>

                    <div class="mb-6 text-sm text-arbor-sage leading-relaxed"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                        style="transition: all 0.5s ease-out 0.5s"
                    >
                        Cette zone de l'application est sécurisée. Veuillez confirmer votre mot de passe avant de continuer.
                    </div>

                    <form @submit.prevent="submit" class="space-y-5"
                        :class="mounted ? 'opacity-100' : 'opacity-0'"
                        style="transition: opacity 0.6s ease-out 0.55s"
                    >
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
                                autofocus
                                placeholder="••••••••"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="flex justify-end pt-2"
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                            style="transition: all 0.5s ease-out 0.7s"
                        >
                            <PrimaryButton
                                :class="{ 'opacity-50 cursor-wait': form.processing }"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Vérification...</span>
                                <span v-else>Confirmer</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
