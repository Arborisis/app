<script setup>
const props = defineProps({
    isOpen: { type: Boolean, default: false },
    pointTitle: { type: String, default: '' },
    xpGained: { type: Number, default: 10 },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="emit('close')" />

            <div class="relative bg-arbor-night border border-white/10 rounded-2xl p-8 max-w-sm w-full text-center shadow-2xl">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-arbor-emerald/10 flex items-center justify-center">
                    <svg class="w-8 h-8 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h3 class="text-lg font-semibold text-arbor-cream mb-1">
                    Visite enregistrée
                </h3>
                <p class="text-sm text-arbor-sage/70 mb-4">
                    Tu as découvert un nouvel écho naturel.
                </p>

                <div class="bg-white/5 rounded-xl p-4 mb-6">
                    <p class="text-xs text-arbor-sage/70 mb-1">Lieu visité</p>
                    <p class="text-sm font-medium text-arbor-cream">{{ pointTitle }}</p>
                </div>

                <div class="flex items-center justify-center gap-2 mb-6">
                    <span class="text-xs text-arbor-sage/70">+</span>
                    <span class="text-2xl font-bold text-arbor-emerald">{{ xpGained }}</span>
                    <span class="text-xs text-arbor-sage/70">XP</span>
                </div>

                <button
                    @click="emit('close')"
                    class="w-full py-2.5 rounded-xl bg-arbor-emerald text-arbor-night font-semibold text-sm hover:bg-arbor-emerald/90 transition-colors"
                >
                    Continuer l'exploration
                </button>
            </div>
        </div>
    </Transition>
</template>
