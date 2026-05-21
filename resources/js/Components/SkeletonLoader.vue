<script setup>
const props = defineProps({
    type: {
        type: String,
        default: 'card', // 'card' | 'text' | 'avatar' | 'waveform'
    },
    lines: {
        type: Number,
        default: 3,
    },
    class: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div v-if="type === 'card'" class="glass-card p-6 space-y-4 animate-pulse" :class="class">
        <div class="h-48 bg-arbor-charcoal rounded-xl" />
        <div class="h-4 bg-arbor-charcoal rounded w-3/4" />
        <div class="h-3 bg-arbor-charcoal rounded w-1/2" />
    </div>

    <div v-else-if="type === 'text'" class="space-y-3" :class="class">
        <div
            v-for="n in lines"
            :key="n"
            class="h-3 bg-arbor-charcoal rounded skeleton"
            :class="n === lines ? 'w-2/3' : 'w-full'"
        />
    </div>

    <div v-else-if="type === 'avatar'" class="flex items-center gap-3" :class="class">
        <div class="w-10 h-10 rounded-full bg-arbor-charcoal skeleton" />
        <div class="space-y-2 flex-1">
            <div class="h-3 bg-arbor-charcoal rounded w-24" />
            <div class="h-2 bg-arbor-charcoal rounded w-16" />
        </div>
    </div>

    <div v-else-if="type === 'waveform'" class="flex items-end gap-[2px] h-8" :class="class">
        <div
            v-for="n in 24"
            :key="n"
            class="w-[3px] bg-arbor-charcoal rounded-full"
            :style="{ height: `${20 + Math.random() * 80}%` }"
        />
    </div>

    <div v-else class="bg-arbor-charcoal rounded skeleton" :class="class" />
</template>
