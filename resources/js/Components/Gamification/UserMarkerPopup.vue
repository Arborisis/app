<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, default: 'Enregistreur anonyme' },
    distance: { type: Number, default: null },
    lastSeen: { type: String, default: '' },
    visibilityMode: { type: String, default: 'approximate' },
    isFriend: { type: Boolean, default: false },
});

const displayDistance = computed(() => {
    if (props.distance == null) return null;
    if (props.distance < 1000) return `${Math.round(props.distance)}m`;
    return `${(props.distance / 1000).toFixed(1)}km`;
});

const modeLabel = computed(() => {
    switch (props.visibilityMode) {
        case 'public_zone': return 'Public';
        case 'friends_only': return 'Ami';
        case 'approximate': return 'Approximatif';
        default: return '';
    }
});

const modeColor = computed(() => {
    switch (props.visibilityMode) {
        case 'public_zone': return 'text-amber-400 bg-amber-400/10';
        case 'friends_only': return 'text-sky-400 bg-sky-400/10';
        default: return 'text-arbor-sage bg-arbor-sage/10';
    }
});
</script>

<template>
    <div class="min-w-[160px] p-1">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full" :class="isFriend ? 'bg-sky-400' : 'bg-amber-400'" />
            <span class="text-sm font-semibold text-arbor-cream">{{ name }}</span>
        </div>

        <div class="flex items-center gap-2 mb-2">
            <span class="text-[10px] px-1.5 py-0.5 rounded-md font-medium" :class="modeColor">
                {{ modeLabel }}
            </span>
            <span v-if="displayDistance" class="text-[10px] text-arbor-sage/70">
                {{ displayDistance }}
            </span>
        </div>

        <p v-if="lastSeen" class="text-[10px] text-arbor-sage/70 italic">
            {{ lastSeen }}
        </p>

        <div class="mt-2 pt-2 border-t border-white/5 flex gap-2">
            <button
                class="text-[10px] px-2 py-1 rounded-md bg-arbor-emerald/15 text-arbor-emerald hover:bg-arbor-emerald/25 transition-colors font-medium"
                @click.prevent="$emit('greet')"
            >
                Saluer
            </button>
            <button
                class="text-[10px] px-2 py-1 rounded-md bg-white/5 text-arbor-sage hover:bg-white/10 transition-colors font-medium"
                @click.prevent="$emit('profile')"
            >
                Profil
            </button>
        </div>
    </div>
</template>
