<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    sent: { type: Object, default: () => ({ data: [] }) },
    received: { type: Object, default: () => ({ data: [] }) },
});

const activeTab = ref('sent');

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const getAvatarUrl = (user) => {
    return user?.avatar_url || user?.profile?.avatar_url || user?.profile?.avatarUrl || null;
};

const getInitials = (name) => {
    return name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() || '?';
};

const totalSent = props.sent.data.reduce((sum, d) => sum + parseFloat(d.amount), 0);
const totalReceived = props.received.data.reduce((sum, d) => sum + parseFloat(d.amount), 0);
</script>

<template>
    <Head title="Historique des dons" />

    <AuthenticatedLayout>
        <div class="relative min-h-screen bg-arbor-night">
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-hero-glow opacity-40" />
            </div>

            <div class="relative z-10 pt-24 pb-24 section-padding">
                <div class="max-w-4xl mx-auto">
                    <!-- Header -->
                    <div class="mb-10 animate-fade-in">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-arbor-emerald/15 flex items-center justify-center">
                                <svg class="w-4 h-4 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-arbor-sage text-sm font-medium uppercase tracking-wider">Historique</span>
                        </div>
                        <h1 class="font-display text-4xl sm:text-5xl font-semibold text-arbor-cream leading-tight">
                            Vos dons ECHO
                        </h1>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8 animate-slide-up">
                        <div class="glass-card p-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-arbor-amber/10 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2" />
                            <p class="text-arbor-sage text-xs uppercase tracking-wider mb-1">Total envoyé</p>
                            <p class="font-mono text-3xl text-arbor-amber">{{ totalSent.toLocaleString('fr-FR') }} <span class="text-lg">ECHO</span></p>
                        </div>
                        <div class="glass-card p-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-arbor-emerald/10 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2" />
                            <p class="text-arbor-sage text-xs uppercase tracking-wider mb-1">Total reçu</p>
                            <p class="font-mono text-3xl text-arbor-emerald">{{ totalReceived.toLocaleString('fr-FR') }} <span class="text-lg">ECHO</span></p>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="glass-card p-2 mb-6 inline-flex rounded-xl animate-slide-up" style="animation-delay: 0.1s">
                        <button
                            @click="activeTab = 'sent'"
                            class="px-6 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                            :class="activeTab === 'sent' ? 'bg-arbor-amber/20 text-arbor-amber' : 'text-arbor-sage hover:text-arbor-cream'"
                        >
                            Envoyés
                        </button>
                        <button
                            @click="activeTab = 'received'"
                            class="px-6 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                            :class="activeTab === 'received' ? 'bg-arbor-emerald/20 text-arbor-emerald' : 'text-arbor-sage hover:text-arbor-cream'"
                        >
                            Reçus
                        </button>
                    </div>

                    <!-- Sent List -->
                    <div v-if="activeTab === 'sent'" class="space-y-4 animate-fade-in">
                        <div
                            v-for="(donation, index) in sent.data"
                            :key="donation.id"
                            class="glass-card p-5 flex items-start gap-4 hover:bg-white/10 transition-colors"
                            :style="`animation: slideUp 0.4s ease-out forwards; animation-delay: ${index * 0.06}s; opacity: 0;`"
                        >
                            <!-- Recipient Avatar -->
                            <div class="shrink-0">
                                <div v-if="getAvatarUrl(donation.recipient)" class="w-12 h-12 rounded-xl overflow-hidden bg-arbor-deep">
                                    <img :src="getAvatarUrl(donation.recipient)" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-12 h-12 rounded-xl bg-arbor-moss/20 flex items-center justify-center text-arbor-moss-light font-medium text-sm">
                                    {{ getInitials(donation.recipient?.name) }}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-arbor-cream font-medium text-sm">
                                        À <span class="text-arbor-emerald">{{ donation.recipient?.name }}</span>
                                    </p>
                                    <span class="font-mono text-arbor-amber text-sm">-{{ donation.amount }} ECHO</span>
                                </div>
                                <p v-if="donation.message" class="text-arbor-sage text-sm mb-2 italic">
                                    "{{ donation.message }}"
                                </p>
                                <div class="flex items-center gap-3 text-xs text-arbor-sage">
                                    <span>{{ formatDate(donation.created_at) }}</span>
                                    <span v-if="donation.sound" class="px-2 py-0.5 rounded bg-arbor-charcoal/50">
                                        {{ donation.sound.title }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-if="sent.data.length === 0" class="text-center py-16 glass-card">
                            <p class="text-arbor-sage text-sm">Vous n'avez pas encore envoyé de dons.</p>
                        </div>

                        <!-- Pagination sent -->
                        <div v-if="sent.links && sent.links.length > 3" class="flex justify-center gap-2 mt-6">
                            <Link
                                v-for="link in sent.links"
                                :key="link.label"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 rounded-lg text-sm transition-colors"
                                :class="link.active ? 'bg-arbor-amber/20 text-arbor-amber' : 'text-arbor-sage hover:bg-arbor-charcoal/50'"
                                preserve-state
                            />
                        </div>
                    </div>

                    <!-- Received List -->
                    <div v-else class="space-y-4 animate-fade-in">
                        <div
                            v-for="(donation, index) in received.data"
                            :key="donation.id"
                            class="glass-card p-5 flex items-start gap-4 hover:bg-white/10 transition-colors"
                            :style="`animation: slideUp 0.4s ease-out forwards; animation-delay: ${index * 0.06}s; opacity: 0;`"
                        >
                            <!-- Donor Avatar -->
                            <div class="shrink-0">
                                <div v-if="getAvatarUrl(donation.donor)" class="w-12 h-12 rounded-xl overflow-hidden bg-arbor-deep">
                                    <img :src="getAvatarUrl(donation.donor)" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-12 h-12 rounded-xl bg-arbor-emerald/15 flex items-center justify-center text-arbor-emerald font-medium text-sm">
                                    {{ getInitials(donation.donor?.name) }}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-arbor-cream font-medium text-sm">
                                        De <span class="text-arbor-emerald">{{ donation.donor?.name }}</span>
                                    </p>
                                    <span class="font-mono text-arbor-emerald text-sm">+{{ donation.amount }} ECHO</span>
                                </div>
                                <p v-if="donation.message" class="text-arbor-sage text-sm mb-2 italic">
                                    "{{ donation.message }}"
                                </p>
                                <div class="flex items-center gap-3 text-xs text-arbor-sage">
                                    <span>{{ formatDate(donation.created_at) }}</span>
                                    <span v-if="donation.sound" class="px-2 py-0.5 rounded bg-arbor-charcoal/50">
                                        {{ donation.sound.title }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-if="received.data.length === 0" class="text-center py-16 glass-card">
                            <p class="text-arbor-sage text-sm">Vous n'avez pas encore reçu de dons.</p>
                        </div>

                        <!-- Pagination received -->
                        <div v-if="received.links && received.links.length > 3" class="flex justify-center gap-2 mt-6">
                            <Link
                                v-for="link in received.links"
                                :key="link.label"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 rounded-lg text-sm transition-colors"
                                :class="link.active ? 'bg-arbor-emerald/20 text-arbor-emerald' : 'text-arbor-sage hover:bg-arbor-charcoal/50'"
                                preserve-state
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
