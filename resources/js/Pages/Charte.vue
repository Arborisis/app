<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const sections = [
    {
        num: '01',
        title: 'Respect de la nature',
        desc: 'Les enregistrements doivent être réalisés sans perturbation des espèces et des écosystèmes. Il est interdit de stresser, capturer ou modifier le comportement animal pour obtenir un son. La discrétion et la patience sont les seules méthodes acceptables.',
        icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    },
    {
        num: '02',
        title: 'Confidentialité des lieux',
        desc: 'Les coordonnées GPS exactes des enregistrements ne sont jamais exposées publiquement. Arborisis affiche une localisation approximative pour protéger les sites sensibles, notamment ceux abritant des espèces menacées ou des écosystèmes fragiles.',
        icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    },
    {
        num: '03',
        title: 'Propriété intellectuelle',
        desc: 'Chaque créateur conserve l\'intégralité des droits sur ses enregistrements. En les publiant sur Arborisis, vous accordez une licence non exclusive de diffusion sur la plateforme. Vous pouvez retirer vos contenus à tout moment.',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        num: '04',
        title: 'Communauté bienveillante',
        desc: 'Le harcèlement, les discours de haine et les comportements toxiques sont strictement prohibés. Les commentaires et interactions doivent rester constructifs et respectueux. Les modérateurs peuvent suspendre tout compte en cas de manquement.',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
        num: '05',
        title: 'Transparence ECHO',
        desc: 'Le système de crédits ECHO est entièrement transparent. Chaque transaction est enregistrée dans un journal immuable. La répartition des dons (70 % créateur, 20 % infrastructure, 10 % fonds communautaire) est fixe et non négociable.',
        icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    },
    {
        num: '06',
        title: 'Qualité et authenticité',
        desc: 'Les sons publiés doivent être authentiques et non altérés de manière trompeuse. Les enregistrements de synthèse ou fortement modifiés doivent être clairement identifiés comme tels. Nous valorisons la documentation fidèle du monde sonore naturel.',
        icon: 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
    },
];
</script>

<template>
    <Head title="Charte" />

    <GuestLayout>
        <div class="relative min-h-screen bg-arbor-night">
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-hero-glow opacity-40" />
            </div>

            <div class="relative z-10">
                <!-- Hero -->
                <section class="pt-24 pb-16 section-padding">
                    <div class="max-w-4xl mx-auto text-center">
                        <h1 class="font-display text-5xl sm:text-6xl font-bold text-arbor-cream leading-tight mb-6">
                            Charte <span class="text-transparent bg-clip-text bg-gradient-to-r from-arbor-emerald to-arbor-moss">Arborisis</span>
                        </h1>
                        <p class="text-arbor-sage text-lg max-w-2xl mx-auto leading-relaxed">
                            Les règles qui guident notre communauté et garantissent la qualité,
                            l'intégrité et le respect de la nature sur la plateforme.
                        </p>
                    </div>
                </section>

                <!-- Sections -->
                <section class="py-20 section-padding">
                    <div class="max-w-4xl mx-auto space-y-6">
                        <div
                            v-for="(section, index) in sections"
                            :key="section.num"
                            class="glass-card p-8 lg:p-10 flex items-start gap-6 hover:bg-white/10 transition-all duration-300"
                            :style="`animation: slideUp 0.5s ease-out forwards; animation-delay: ${index * 0.08}s; opacity: 0;`"
                        >
                            <div class="shrink-0 hidden sm:block">
                                <div class="w-14 h-14 rounded-2xl bg-arbor-charcoal/70 border border-arbor-fog/40 flex items-center justify-center">
                                    <span class="font-mono text-lg text-arbor-emerald font-medium">{{ section.num }}</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-8 h-8 rounded-lg bg-arbor-moss/20 flex items-center justify-center sm:hidden">
                                        <svg class="w-4 h-4 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="section.icon" />
                                        </svg>
                                    </div>
                                    <h2 class="font-display text-xl font-semibold text-arbor-cream">{{ section.title }}</h2>
                                </div>
                                <p class="text-arbor-sage text-sm leading-relaxed">{{ section.desc }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Footer note -->
                <section class="py-20 section-padding border-t border-arbor-glass-border">
                    <div class="max-w-2xl mx-auto text-center">
                        <div class="w-12 h-12 rounded-xl bg-arbor-emerald/15 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="font-display text-xl text-arbor-cream mb-2">Ensemble, préservons l'écoute</h3>
                        <p class="text-arbor-sage text-sm">
                            Cette charte est vivante. Elle évolue avec la communauté et les retours de ses membres.
                            En vous inscrivant, vous vous engagez à la respecter.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </GuestLayout>
</template>
