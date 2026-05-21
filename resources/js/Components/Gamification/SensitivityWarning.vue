<script setup>
import { computed } from 'vue';

const props = defineProps({
    level: {
        type: String,
        default: 'normal',
    },
    warningText: {
        type: String,
        default: null,
    },
});

const isSensitive = computed(() => {
    return props.level !== 'normal';
});

const levelConfig = computed(() => {
    const configs = {
        fragile: {
            icon: '🌿',
            title: 'Lieu fragile',
            color: 'text-amber-300',
            bg: 'bg-amber-500/10',
            border: 'border-amber-500/20',
        },
        sensitive_species: {
            icon: '🦉',
            title: 'Espèce sensible',
            color: 'text-rose-300',
            bg: 'bg-rose-500/10',
            border: 'border-rose-500/20',
        },
        private: {
            icon: '🔒',
            title: 'Lieu privé',
            color: 'text-slate-300',
            bg: 'bg-slate-500/10',
            border: 'border-slate-500/20',
        },
        dangerous: {
            icon: '⚠️',
            title: 'Zone dangereuse',
            color: 'text-orange-300',
            bg: 'bg-orange-500/10',
            border: 'border-orange-500/20',
        },
    };

    return configs[props.level] || null;
});
</script>

<template>
    <div
        v-if="isSensitive && levelConfig"
        class="rounded-xl border p-4 flex items-start gap-3"
        :class="[levelConfig.bg, levelConfig.border]"
    >
        <span class="text-xl shrink-0 mt-0.5">{{ levelConfig.icon }}</span>
        <div>
            <p class="font-medium text-sm" :class="levelConfig.color">
                {{ levelConfig.title }}
            </p>
            <p class="text-xs mt-1 opacity-80 leading-relaxed" :class="levelConfig.color">
                {{ warningText || 'Ce lieu est protégé : sa position exacte reste confidentielle.' }}
            </p>
        </div>
    </div>
</template>
