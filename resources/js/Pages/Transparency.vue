<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
</script>

<template>
    <Head title="Transparence ECHO" />
    <GuestLayout>
        <div class="pt-24 pb-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="text-center mb-16">
                    <h1 class="font-display text-4xl md:text-5xl font-bold text-arbor-cream mb-6">
                        Transparence ECHO
                    </h1>
                    <p class="text-arbor-sage text-lg max-w-2xl mx-auto leading-relaxed">
                        ECHO est le système de crédits interne d'Arborisis. Nous croyons en une transparence totale sur son fonctionnement et sa répartition.
                    </p>
                </div>

                <!-- What ECHO is NOT -->
                <section class="mb-16">
                    <div class="glass-card p-8 border-l-4 border-arbor-emerald">
                        <h2 class="font-display text-2xl font-bold text-arbor-cream mb-6">Ce qu'ECHO n'est pas</h2>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-red-500/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-arbor-cream mb-1">Pas une cryptomonnaie</h3>
                                    <p class="text-arbor-sage text-sm">ECHO n'est pas un actif numérique spéculatif. Il n'a pas de valeur sur les marchés externes et ne peut pas être échangé contre des devises réelles en dehors de la plateforme.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-red-500/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-arbor-cream mb-1">Pas un investissement</h3>
                                    <p class="text-arbor-sage text-sm">Acheter des ECHO ne constitue pas un placement financier. Les crédits sont destinés à soutenir les créateurs et accéder au contenu premium.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- How it works -->
                <section class="mb-16">
                    <h2 class="font-display text-2xl font-bold text-arbor-cream mb-8">Comment fonctionne ECHO</h2>

                    <div class="space-y-6">
                        <div class="glass-card p-6 flex items-start gap-6">
                            <div class="w-12 h-12 rounded-full bg-arbor-emerald/10 flex items-center justify-center shrink-0 text-arbor-emerald font-bold text-lg">1</div>
                            <div>
                                <h3 class="font-semibold text-arbor-cream mb-2">Achat de crédits</h3>
                                <p class="text-arbor-sage text-sm leading-relaxed">Les utilisateurs achètent des ECHO via Stripe en toute sécurité. 1€ = 10 ECHO. Les paiements sont sécurisés et conformes au RGPD.</p>
                            </div>
                        </div>

                        <div class="glass-card p-6 flex items-start gap-6">
                            <div class="w-12 h-12 rounded-full bg-arbor-emerald/10 flex items-center justify-center shrink-0 text-arbor-emerald font-bold text-lg">2</div>
                            <div>
                                <h3 class="font-semibold text-arbor-cream mb-2">Don aux créateurs</h3>
                                <p class="text-arbor-sage text-sm leading-relaxed">Les utilisateurs peuvent donner des ECHO aux créateurs pour les remercier de leur travail. Chaque don est immédiatement crédité sur le wallet du créateur.</p>
                            </div>
                        </div>

                        <div class="glass-card p-6 flex items-start gap-6">
                            <div class="w-12 h-12 rounded-full bg-arbor-emerald/10 flex items-center justify-center shrink-0 text-arbor-emerald font-bold text-lg">3</div>
                            <div>
                                <h3 class="font-semibold text-arbor-cream mb-2">Conversion en euros</h3>
                                <p class="text-arbor-sage text-sm leading-relaxed">Les créateurs peuvent demander un retrait de leurs ECHO accumulés. Les retraits sont traités sous 5 jours ouvrés par virement bancaire.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Revenue split -->
                <section class="mb-16">
                    <h2 class="font-display text-2xl font-bold text-arbor-cream mb-8">Répartition des revenus</h2>

                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="glass-card p-6 text-center">
                            <div class="text-4xl font-display font-bold text-arbor-emerald mb-2">70%</div>
                            <h3 class="font-semibold text-arbor-cream mb-2">Créateur</h3>
                            <p class="text-arbor-sage text-sm">Directement vers le wallet du créateur pour récompenser son travail de field recording.</p>
                        </div>

                        <div class="glass-card p-6 text-center">
                            <div class="text-4xl font-display font-bold text-arbor-sage mb-2">20%</div>
                            <h3 class="font-semibold text-arbor-cream mb-2">Infrastructure</h3>
                            <p class="text-arbor-sage text-sm">Stockage S3, bande passante, serveurs et maintenance technique de la plateforme.</p>
                        </div>

                        <div class="glass-card p-6 text-center">
                            <div class="text-4xl font-display font-bold text-arbor-moss mb-2">10%</div>
                            <h3 class="font-semibold text-arbor-cream mb-2">Fonds communautaire</h3>
                            <p class="text-arbor-sage text-sm">Réinvesti dans des projets de conservation de la nature et des événements communautaires.</p>
                        </div>
                    </div>
                </section>

                <!-- Transparency promises -->
                <section class="mb-16">
                    <h2 class="font-display text-2xl font-bold text-arbor-cream mb-8">Nos engagements</h2>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="glass-card p-6">
                            <div class="flex items-center gap-3 mb-3">
                                <svg class="w-6 h-6 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="font-semibold text-arbor-cream">Journal immuable</h3>
                            </div>
                            <p class="text-arbor-sage text-sm">Toutes les transactions ECHO sont enregistrées dans un journal immuable. Une fois créée, une transaction ne peut ni être modifiée ni supprimée.</p>
                        </div>

                        <div class="glass-card p-6">
                            <div class="flex items-center gap-3 mb-3">
                                <svg class="w-6 h-6 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <h3 class="font-semibold text-arbor-cream">Sécurité</h3>
                            </div>
                            <p class="text-arbor-sage text-sm">Toutes les transactions sont atomiques. En cas d'erreur, aucun crédit n'est débité ou crédité de manière partielle.</p>
                        </div>

                        <div class="glass-card p-6">
                            <div class="flex items-center gap-3 mb-3">
                                <svg class="w-6 h-6 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <h3 class="font-semibold text-arbor-cream">Transparence</h3>
                            </div>
                            <p class="text-arbor-sage text-sm">Chaque utilisateur peut consulter l'historique complet de ses transactions dans son portefeuille.</p>
                        </div>

                        <div class="glass-card p-6">
                            <div class="flex items-center gap-3 mb-3">
                                <svg class="w-6 h-6 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                </svg>
                                <h3 class="font-semibold text-arbor-cream">Équité</h3>
                            </div>
                            <p class="text-arbor-sage text-sm">Le même taux de conversion s'applique à tous les créateurs, sans discrimination ni privilège.</p>
                        </div>
                    </div>
                </section>

                <!-- CTA -->
                <section class="text-center">
                    <div class="glass-card p-8">
                        <h2 class="font-display text-2xl font-bold text-arbor-cream mb-4">Rejoignez l'aventure</h2>
                        <p class="text-arbor-sage mb-6 max-w-lg mx-auto">
                            Soutenez les créateurs de sons de la nature et participez à la préservation de notre patrimoine sonore.
                        </p>
                        <div class="flex items-center justify-center gap-4">
                            <Link
                                href="/sounds"
                                class="btn-primary px-6 py-3"
                            >
                                Explorer les sons
                            </Link>
                            <Link
                                v-if="$page.props.auth.user"
                                href="/wallet"
                                class="px-6 py-3 rounded-xl border border-arbor-glass-border text-arbor-cream hover:bg-arbor-glass transition-colors"
                            >
                                Mon portefeuille
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </GuestLayout>
</template>
