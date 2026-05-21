<script setup>
const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const statusConfig = {
    pending: {
        text: 'Ce point est en attente de modération. Il ne sera visible publiquement qu\'après validation.',
        color: 'text-amber-300',
        bg: 'bg-amber-500/10',
        border: 'border-amber-500/20',
    },
    approved: {
        text: 'Ce point a été validé par la modération.',
        color: 'text-emerald-300',
        bg: 'bg-emerald-500/10',
        border: 'border-emerald-500/20',
    },
    rejected: {
        text: 'Ce point a été rejeté par la modération.',
        color: 'text-rose-300',
        bg: 'bg-rose-500/10',
        border: 'border-rose-500/20',
    },
    hidden: {
        text: 'Ce point a été masqué par la modération.',
        color: 'text-slate-300',
        bg: 'bg-slate-500/10',
        border: 'border-slate-500/20',
    },
};

const config = statusConfig[props.status] || statusConfig.pending;
</script>

<template>
    <div class="rounded-xl border p-3 text-xs" :class="[config.bg, config.border, config.color]">
        {{ config.text }}
    </div>
</template>
