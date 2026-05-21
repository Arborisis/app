<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    flash: Object,
    ticket: String,
    ticketData: Object,
});

const form = useForm({
    type: 'contact',
    name: '',
    email: '',
    subject: '',
    message: '',
});

const trackInput = ref(props.ticket ?? '');
const trackError = ref('');

const types = [
    { value: 'contact', label: 'Contact général', description: 'Pour toute question ou suggestion.' },
    { value: 'privacy', label: 'Données personnelles / RGPD', description: 'Exercer vos droits ou signaler un problème de confidentialité.' },
    { value: 'support', label: 'Support technique', description: 'Un bug, un problème de connexion ou d\'upload ?' },
];

const selectedTypeLabel = computed(() => {
    return types.find(t => t.value === form.type)?.label ?? '';
});

function submit() {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('subject', 'message'),
    });
}

function trackTicket() {
    trackError.value = '';
    const value = trackInput.value.trim().toUpperCase();

    if (!value) {
        router.get(route('contact'), {}, { preserveState: true });
        return;
    }

    const regex = /^ARB-\d{8}-[A-Z0-9]{5}$/;
    if (!regex.test(value)) {
        trackError.value = 'Le numéro de suivi doit être au format ARB-YYYYMMDD-XXXXX.';
        return;
    }

    router.get(route('contact'), { ticket: value }, { preserveState: true });
}

const statusBadgeClass = computed(() => {
    if (!props.ticketData) return '';
    switch (props.ticketData.status) {
        case 'new': return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
        case 'in_progress': return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
        case 'resolved': return 'bg-arbor-emerald/10 text-arbor-emerald border-arbor-emerald/20';
        case 'spam': return 'bg-red-500/10 text-red-400 border-red-500/20';
        default: return 'bg-arbor-glass/30 text-arbor-sage border-arbor-glass-border';
    }
});

function formatDate(iso) {
    return new Date(iso).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Contact" />

    <GuestLayout>
        <div class="relative min-h-screen bg-arbor-night">
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-hero-glow opacity-40" />
            </div>

            <div class="relative z-10">
                <!-- Hero -->
                <section class="pt-24 pb-12 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-3xl mx-auto text-center">
                        <h1 class="font-display text-4xl sm:text-5xl font-bold text-arbor-cream leading-tight mb-4">
                            Contactez-<span class="text-transparent bg-clip-text bg-gradient-to-r from-arbor-emerald to-arbor-moss">nous</span>
                        </h1>
                        <p class="text-arbor-sage text-lg max-w-2xl mx-auto leading-relaxed">
                            Une question, une demande RGPD ou un problème technique ? Nous vous répondrons dans les plus brefs délais.
                        </p>
                    </div>
                </section>

                <section class="pb-24 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl mx-auto space-y-10">
                        <!-- Ticket Tracking -->
                        <div class="p-6 rounded-2xl border border-arbor-glass-border bg-arbor-glass/20">
                            <h2 class="text-lg font-semibold text-arbor-cream mb-4">Suivre ma demande</h2>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <input
                                    v-model="trackInput"
                                    type="text"
                                    class="flex-1 rounded-xl bg-arbor-glass/30 border border-arbor-glass-border px-4 py-2.5 text-base text-arbor-cream placeholder:text-arbor-sage/60 focus:outline-none focus:ring-2 focus:ring-arbor-emerald/40 focus:border-arbor-emerald/40 transition-colors uppercase"
                                    placeholder="ARB-YYYYMMDD-XXXXX"
                                    @keydown.enter.prevent="trackTicket"
                                />
                                <button
                                    type="button"
                                    @click="trackTicket"
                                    class="btn-primary px-6 py-2.5 text-sm font-medium"
                                >
                                    Suivre
                                </button>
                            </div>
                            <div v-if="trackError" class="mt-2 text-sm text-red-400">{{ trackError }}</div>

                            <!-- Ticket found -->
                            <div v-if="ticketData" class="mt-6 space-y-4">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="text-sm font-mono text-arbor-sage">{{ ticketData.ticket_number }}</span>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                        :class="statusBadgeClass"
                                    >
                                        {{ ticketData.status_label }}
                                    </span>
                                    <span class="text-xs text-arbor-sage">{{ ticketData.type_label }}</span>
                                </div>

                                <div class="p-4 rounded-xl bg-arbor-glass/30 border border-arbor-glass-border">
                                    <h3 class="text-sm font-medium text-arbor-cream mb-1">{{ ticketData.subject }}</h3>
                                    <p class="text-sm text-arbor-sage whitespace-pre-wrap">{{ ticketData.message }}</p>
                                    <p class="text-xs text-arbor-sage/60 mt-2">Ouvert le {{ formatDate(ticketData.created_at) }}</p>
                                </div>

                                <div v-if="ticketData.replies.length > 0" class="space-y-3">
                                    <h4 class="text-sm font-medium text-arbor-cream">Réponses</h4>
                                    <div
                                        v-for="(reply, idx) in ticketData.replies"
                                        :key="idx"
                                        class="p-4 rounded-xl bg-arbor-glass/20 border border-arbor-glass-border"
                                    >
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-medium text-arbor-emerald">{{ reply.author }}</span>
                                            <span class="text-xs text-arbor-sage/60">{{ formatDate(reply.created_at) }}</span>
                                        </div>
                                        <p class="text-sm text-arbor-sage whitespace-pre-wrap">{{ reply.reply }}</p>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-arbor-sage/70">
                                    Aucune réponse pour le moment. Notre équipe vous répondra sous peu.
                                </div>
                            </div>

                            <!-- Ticket not found -->
                            <div v-else-if="ticket" class="mt-6 p-4 rounded-xl bg-red-500/5 border border-red-500/10 text-red-400 text-sm">
                                Aucun ticket trouvé avec le numéro <span class="font-mono">{{ ticket }}</span>.
                            </div>
                        </div>

                        <!-- Contact Form -->
                        <div>
                            <h2 class="text-lg font-semibold text-arbor-cream mb-4">Nouvelle demande</h2>

                            <!-- Success -->
                            <div
                                v-if="$page.props.flash?.success"
                                class="mb-6 p-4 rounded-xl bg-arbor-emerald/10 border border-arbor-emerald/20 text-arbor-emerald text-sm"
                            >
                                {{ $page.props.flash.success }}
                            </div>

                            <form @submit.prevent="submit" class="space-y-6">
                                <!-- Type -->
                                <div>
                                    <label class="block text-sm font-medium text-arbor-cream mb-3">Type de demande</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <button
                                            v-for="t in types"
                                            :key="t.value"
                                            type="button"
                                            @click="form.type = t.value"
                                            :class="[
                                                'text-left p-4 rounded-xl border transition-colors duration-200',
                                                form.type === t.value
                                                    ? 'border-arbor-emerald bg-arbor-emerald/10 text-arbor-emerald'
                                                    : 'border-arbor-glass-border bg-arbor-glass/30 text-arbor-sage hover:bg-white/10'
                                            ]"
                                        >
                                            <div class="font-medium text-sm">{{ t.label }}</div>
                                            <div class="text-xs mt-1 opacity-80 leading-snug">{{ t.description }}</div>
                                        </button>
                                    </div>
                                    <div v-if="form.errors.type" class="mt-2 text-sm text-red-400">{{ form.errors.type }}</div>
                                </div>

                                <!-- Name & Email -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-arbor-cream mb-1.5">Nom</label>
                                        <input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            required
                                            class="w-full rounded-xl bg-arbor-glass/30 border border-arbor-glass-border px-4 py-2.5 text-base text-arbor-cream placeholder:text-arbor-sage/60 focus:outline-none focus:ring-2 focus:ring-arbor-emerald/40 focus:border-arbor-emerald/40 transition-colors"
                                            placeholder="Votre nom"
                                        />
                                        <div v-if="form.errors.name" class="mt-1.5 text-sm text-red-400">{{ form.errors.name }}</div>
                                    </div>
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-arbor-cream mb-1.5">E-mail</label>
                                        <input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            required
                                            class="w-full rounded-xl bg-arbor-glass/30 border border-arbor-glass-border px-4 py-2.5 text-base text-arbor-cream placeholder:text-arbor-sage/60 focus:outline-none focus:ring-2 focus:ring-arbor-emerald/40 focus:border-arbor-emerald/40 transition-colors"
                                            placeholder="vous@exemple.com"
                                        />
                                        <div v-if="form.errors.email" class="mt-1.5 text-sm text-red-400">{{ form.errors.email }}</div>
                                    </div>
                                </div>

                                <!-- Subject -->
                                <div>
                                    <label for="subject" class="block text-sm font-medium text-arbor-cream mb-1.5">Sujet</label>
                                    <input
                                        id="subject"
                                        v-model="form.subject"
                                        type="text"
                                        required
                                        class="w-full rounded-xl bg-arbor-glass/30 border border-arbor-glass-border px-4 py-2.5 text-base text-arbor-cream placeholder:text-arbor-sage/60 focus:outline-none focus:ring-2 focus:ring-arbor-emerald/40 focus:border-arbor-emerald/40 transition-colors"
                                        placeholder="Sujet de votre message"
                                    />
                                    <div v-if="form.errors.subject" class="mt-1.5 text-sm text-red-400">{{ form.errors.subject }}</div>
                                </div>

                                <!-- Message -->
                                <div>
                                    <label for="message" class="block text-sm font-medium text-arbor-cream mb-1.5">Message</label>
                                    <textarea
                                        id="message"
                                        v-model="form.message"
                                        rows="5"
                                        required
                                        maxlength="5000"
                                        class="w-full rounded-xl bg-arbor-glass/30 border border-arbor-glass-border px-4 py-2.5 text-base text-arbor-cream placeholder:text-arbor-sage/60 focus:outline-none focus:ring-2 focus:ring-arbor-emerald/40 focus:border-arbor-emerald/40 transition-colors resize-none"
                                        placeholder="Décrivez votre demande en détail..."
                                    ></textarea>
                                    <div class="mt-1 flex justify-between">
                                        <span v-if="form.errors.message" class="text-sm text-red-400">{{ form.errors.message }}</span>
                                        <span class="text-xs text-arbor-sage ml-auto">{{ form.message.length }} / 5000</span>
                                    </div>
                                </div>

                                <!-- Submit -->
                                <div class="pt-2">
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="btn-primary w-full sm:w-auto px-8 py-3 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="form.processing">Envoi en cours...</span>
                                        <span v-else>Envoyer ma demande</span>
                                    </button>
                                </div>

                                <!-- Info -->
                                <p class="text-xs text-arbor-sage">
                                    En soumettant ce formulaire, vous acceptez que vos données soient traitées conformément à notre
                                    <a href="/privacy" class="text-arbor-emerald hover:underline">politique de confidentialité</a>.
                                    Vous recevrez un numéro de suivi unique.
                                </p>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </GuestLayout>
</template>
