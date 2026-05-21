<script setup>
const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

function formatTime(value) {
    if (!value) return '--:--';
    return new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}

function typeLabel(type) {
    return {
        live: 'Direct',
        podcast: 'Podcast',
        flash: 'Flash',
        emission: 'Émission',
        schedule: 'Sélection',
    }[type] || 'Radio';
}
</script>

<template>
    <section class="mt-10">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 class="font-display text-2xl font-semibold text-arbor-cream">Programme du jour</h2>
                <p class="mt-1 text-sm text-arbor-sage">Shows, sélections et temps forts récents.</p>
            </div>
        </div>

        <div v-if="items.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <article
                v-for="item in items"
                :key="`${item.type}-${item.id}-${item.starts_at}`"
                class="min-h-[132px] rounded-lg border border-arbor-glass-border bg-arbor-deep/70 p-4"
            >
                <div class="mb-3 flex items-center justify-between gap-2">
                    <span class="font-mono text-xs text-arbor-sage">{{ formatTime(item.starts_at) }}</span>
                    <span class="rounded-full border border-arbor-emerald/30 bg-arbor-emerald/10 px-2 py-0.5 text-[11px] font-medium text-arbor-emerald">
                        {{ typeLabel(item.type) }}
                    </span>
                </div>
                <h3 class="line-clamp-2 text-sm font-semibold leading-snug text-arbor-cream">
                    {{ item.title }}
                </h3>
                <p v-if="item.description" class="mt-2 line-clamp-2 text-xs leading-relaxed text-arbor-sage">
                    {{ item.description }}
                </p>
            </article>
        </div>

        <div v-else class="rounded-lg border border-arbor-glass-border bg-arbor-deep/70 p-5 text-sm text-arbor-sage">
            Le programme est en cours de composition.
        </div>
    </section>
</template>
