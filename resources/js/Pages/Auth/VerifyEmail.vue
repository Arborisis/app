<script setup>
import { computed, ref, onMounted } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);

const mounted = ref(false);

onMounted(() => {
    setTimeout(() => {
        mounted.value = true;
    }, 50);
});
</script>

<template>
    <GuestLayout>
        <Head title="Vérification de l'email" />

        <div class="relative min-h-[calc(100vh-4rem)] flex items-center justify-center overflow-hidden pt-16 py-12">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <h1 class="font-display text-3xl font-bold text-arbor-cream mb-2">
                            Vérifiez votre email
                        </h1>
                        <p class="text-arbor-sage text-sm">
                            Une dernière étape avant de rejoindre la communauté
                        </p>
                    </div>

                    <div class="mb-6 text-sm text-arbor-sage leading-relaxed"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                        style="transition: all 0.5s ease-out 0.5s"
                    >
                        Merci de votre inscription ! Avant de commencer, veuillez vérifier votre adresse e-mail en cliquant sur le lien que nous venons de vous envoyer. Si vous n'avez pas reçu l'e-mail, nous vous en enverrons un autre avec plaisir.
                    </div>

                    <div
                        v-if="verificationLinkSent"
                        class="mb-6 p-3 rounded-lg bg-arbor-emerald/10 border border-arbor-emerald/20 text-sm text-arbor-emerald text-center"
                        :class="mounted ? 'opacity-100' : 'opacity-0'"
                        style="transition: opacity 0.5s ease-out 0.45s"
                    >
                        Un nouveau lien de vérification a été envoyé à l'adresse e-mail que vous avez fournie lors de l'inscription.
                    </div>

                    <form @submit.prevent="submit" class="space-y-5"
                        :class="mounted ? 'opacity-100' : 'opacity-0'"
                        style="transition: opacity 0.6s ease-out 0.55s"
                    >
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <PrimaryButton
                                :class="{ 'opacity-50 cursor-wait': form.processing }"
                                :disabled="form.processing"
                                class="w-full sm:w-auto justify-center py-3 px-6"
                            >
                                <span v-if="form.processing">Envoi en cours...</span>
                                <span v-else>Renvoyer l'e-mail de vérification</span>
                            </PrimaryButton>

                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="text-sm text-arbor-sage hover:text-arbor-cream transition-colors underline underline-offset-4"
                            >
                                Se déconnecter
                            </Link>
                        </div>
                    </form>
                </div>

                <div class="mt-6 text-center"
                    :class="mounted ? 'opacity-100' : 'opacity-0'"
                    style="transition: opacity 0.5s ease-out 0.8s"
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
