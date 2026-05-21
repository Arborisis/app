<script setup>
const props = defineProps({
    modelValue: { type: String, default: 'invisible' },
});

const emit = defineEmits(['update:modelValue']);

const modes = [
    { value: 'invisible', label: 'Invisible', desc: 'Personne ne te voit' },
    { value: 'approximate', label: 'Approximative', desc: 'Position floutée à ~100m' },
    { value: 'friends_only', label: 'Amis', desc: 'Visible uniquement par tes amis' },
    { value: 'public_zone', label: 'Publique', desc: 'Visible par tous dans la zone' },
];

const selectMode = (mode) => {
    emit('update:modelValue', mode);
};
</script>

<template>
    <div class="space-y-2">
        <p class="text-xs font-medium text-arbor-sage/70 mb-2">Mode de visibilité</p>
        <button
            v-for="mode in modes"
            :key="mode.value"
            @click="selectMode(mode.value)"
            class="w-full flex items-center gap-3 p-3 rounded-xl border transition-colors text-left"
            :class="modelValue === mode.value
                ? 'border-arbor-emerald/40 bg-arbor-emerald/5'
                : 'border-white/5 bg-white/5 hover:bg-white/10'"
        >
            <div
                class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0"
                :class="modelValue === mode.value ? 'border-arbor-emerald' : 'border-white/20'"
            >
                <div v-if="modelValue === mode.value" class="w-2 h-2 rounded-full bg-arbor-emerald" />
            </div>
            <div>
                <p class="text-sm text-arbor-cream">{{ mode.label }}</p>
                <p class="text-[11px] text-arbor-sage/70">{{ mode.desc }}</p>
            </div>
        </button>
    </div>
</template>
