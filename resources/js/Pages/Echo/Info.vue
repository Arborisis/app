<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const echoRate = 10;

const splits = [
    { label: 'Créateur', percent: 70, color: 'bg-arbor-emerald', desc: 'Reçu directement par l\'auteur du son' },
    { label: 'Infrastructure', percent: 20, color: 'bg-arbor-moss', desc: 'Maintenance de la plateforme et stockage' },
    { label: 'Communauté', percent: 10, color: 'bg-arbor-amber', desc: 'Fonds dédié aux projets collectifs' },
];

const principles = [
    { title: 'Pas une cryptomonnaie', desc: 'ECHO ne peut pas être échangé contre de la monnaie réelle en dehors de la plateforme.' },
    { title: 'Pas un investissement', desc: 'Aucune promesse de rendement. Les crédits servent uniquement à soutenir les créateurs.' },
    { title: 'Transactions atomiques', desc: 'Chaque transfert est immédiat et irréversible, garanti par un journal immuable.' },
];
</script>

<template>
    <Head title="Le système ECHO" />

    <GuestLayout>
        <div class="relative min-h-screen bg-arbor-night">
            <!-- Background -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-hero-glow opacity-50" />
                <div class="absolute top-40 right-20 w-72 h-72 bg-arbor-amber/5 rounded-full blur-3xl" />
            </div>

            <div class="relative z-10">
                <!-- Hero -->
                <section class="pt-32 pb-20 section-padding">
                    <div class="max-w-4xl mx-auto text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-arbor-amber/15 mb-8 animate-glow-pulse">
                            <span class="text-3xl font-display font-bold text-arbor-amber">E</span>
                        </div>
                        <h1 class="font-display text-5xl sm:text-6xl font-bold text-arbor-cream leading-tight mb-6">
                            Le système <span class="text-arbor-amber">ECHO</span>
                        </h1>
                        <p class="text-arbor-sage text-lg max-w-2xl mx-auto leading-relaxed">
                            Des crédits internes pour soutenir directement les enregistreurs de sons naturels.
                            Simple, transparent, sans intermédiaire opaque.
                        </p>
                    </div>
                </section>

                <!-- Conversion -->
                <section class="pb-20 section-padding">
                    <div class="max-w-3xl mx-auto">
                        <div class="glass-card p-10 text-center relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-40 h-40 bg-arbor-amber/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2" />
                            <div class="relative z-10">
                                <p class="text-arbor-sage text-sm uppercase tracking-wider mb-4">Taux de conversion</p>
                                <div class="flex items-center justify-center gap-6 mb-6">
                                    <div class="text-center">
                                        <div class="font-mono text-4xl text-arbor-cream">1 €</div>
                                        <div class="text-arbor-sage text-xs mt-1">euro</div>
                                    </div>
                                    <div class="w-12 h-px bg-arbor-glass-border relative">
                                        <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-arbor-amber/30 border border-arbor-amber" />
                                    </div>
                                    <div class="text-center">
                                        <div class="font-mono text-4xl text-arbor-amber">{{ echoRate }}</div>
                                        <div class="text-arbor-sage text-xs mt-1">ECHO</div>
                                    </div>
                                </div>
                                <p class="text-arbor-sage text-sm">
                                    Achetez des crédits par carte bancaire via Stripe. Ils sont crédités instantanément sur votre portefeuille.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Repartition -->
                <section class="pb-20 section-padding">
                    <div class="max-w-5xl mx-auto">
                        <div class="text-center mb-12">
                            <h2 class="font-display text-3xl font-bold text-arbor-cream mb-3">Répartition de chaque don</h2>
                            <p class="text-arbor-sage">Chaque ECHO envoyé est divisé de manière transparente.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div
                                v-for="(split, index) in splits"
                                :key="split.label"
                                class="glass-card p-8 text-center hover:bg-white/10 transition-all duration-300 hover-lift"
                                :style="`animation: fadeInUp 0.6s ease-out forwards; animation-delay: ${index * 0.15}s; opacity: 0;`"
                            >
                                <div class="font-mono text-5xl font-medium mb-3" :class="split.color.replace('bg-', 'text-')">
                                    {{ split.percent }}%
                                </div>
                                <h3 class="text-arbor-cream font-medium mb-2">{{ split.label }}</h3>
                                <p class="text-arbor-sage text-sm">{{ split.desc }}</p>
                            </div>
                        </div>

                        <!-- Visual bar -->
                        <div class="mt-10 flex h-4 rounded-full overflow-hidden glass-card p-0">
                            <div class="bg-arbor-emerald h-full" style="width: 70%" />
                            <div class="bg-arbor-moss h-full" style="width: 20%" />
                            <div class="bg-arbor-amber h-full" style="width: 10%" />
                        </div>
                        <div class="flex justify-between mt-3 text-xs text-arbor-sage px-1">
                            <span class="text-arbor-emerald">70 % créateur</span>
                            <span class="text-arbor-moss">20 % infra</span>
                            <span class="text-arbor-amber">10 % communauté</span>
                        </div>
                    </div>
                </section>

                <!-- Principles -->
                <section class="pb-20 section-padding bg-arbor-deep/30 border-y border-arbor-glass-border">
                    <div class="max-w-4xl mx-auto">
                        <h2 class="font-display text-3xl font-bold text-arbor-cream text-center mb-12">Nos engagements</h2>
                        <div class="space-y-6">
                            <div
                                v-for="(principle, index) in principles"
                                :key="principle.title"
                                class="flex items-start gap-5 p-6 rounded-2xl bg-arbor-charcoal/30 border border-arbor-fog/30 hover:border-arbor-moss/30 transition-colors"
                                :style="`animation: slideUp 0.5s ease-out forwards; animation-delay: ${index * 0.1}s; opacity: 0;`"
                            >
                                <div class="w-10 h-10 rounded-xl bg-arbor-emerald/15 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-arbor-cream font-medium mb-1">{{ principle.title }}</h3>
                                    <p class="text-arbor-sage text-sm leading-relaxed">{{ principle.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- CTA -->
                <section class="py-24 section-padding">
                    <div class="max-w-2xl mx-auto text-center">
                        <h2 class="font-display text-3xl font-bold text-arbor-cream mb-4">Prêt à soutenir ?</h2>
                        <p class="text-arbor-sage mb-8">
                            Créez un compte, achetez des crédits ECHO et commencez à soutenir les enregistreurs dont vous appréciez le travail.
                        </p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Link v-if="$page.props.auth.user" href="/wallet" class="btn-amber text-base px-8 py-4">
                                Accéder à mon portefeuille
                            </Link>
                            <template v-else>
                                <Link href="/register" class="btn-primary text-base px-8 py-4">
                                    Créer un compte
                                </Link>
                                <Link href="/login" class="btn-secondary text-base px-8 py-4">
                                    J'ai déjà un compte
                                </Link>
                            </template>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </GuestLayout>
</template>
