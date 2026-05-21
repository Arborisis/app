<script setup>
import { computed } from 'vue';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    user: { type: Object, default: null },
    distance: { type: Number, default: null },
});

const emit = defineEmits(['close', 'greet', 'share', 'invite']);

const displayDistance = computed(() => {
    if (props.distance == null) return null;
    if (props.distance < 1000) return `${Math.round(props.distance)}m`;
    return `${(props.distance / 1000).toFixed(1)}km`;
});

const isNearby = computed(() => props.distance !== null && props.distance <= 500);
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="isOpen && user"
            class="fixed inset-x-0 bottom-0 z-drawer md:absolute md:top-4 md:right-4 md:bottom-auto md:left-auto md:w-80 md:translate-y-0"
        >
            <div class="bg-arbor-night/95 backdrop-blur-xl border-t md:border border-white/10 md:rounded-2xl shadow-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-arbor-emerald/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-arbor-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-arbor-cream">{{ user.name || 'Enregistreur anonyme' }}</h3>
                            <p v-if="displayDistance" class="text-xs text-arbor-sage/70">{{ displayDistance }} de toi</p>
                        </div>
                    </div>
                    <button @click="emit('close')" aria-label="Fermer" class="min-w-[44px] min-h-[44px] flex items-center justify-center text-arbor-sage/50 hover:text-arbor-cream">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-2">
                    <button
                        :disabled="!isNearby"
                        class="w-full py-2.5 rounded-xl bg-arbor-emerald text-arbor-night font-semibold text-sm hover:bg-arbor-emerald/90 transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        @click="emit('greet')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Saluer
                    </button>

                    <button
                        :disabled="!isNearby"
                        class="w-full py-2.5 rounded-xl bg-white/5 text-arbor-cream font-medium text-sm hover:bg-white/10 transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        @click="emit('share')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Partager un spot
                    </button>

                    <button
                        class="w-full py-2.5 rounded-xl bg-white/5 text-arbor-cream font-medium text-sm hover:bg-white/10 transition-colors flex items-center justify-center gap-2"
                        @click="emit('invite')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Inviter à enregistrer
                    </button>
                </div>

                <p v-if="!isNearby" class="mt-3 text-[11px] text-arbor-sage/50 text-center">
                    Rapproche-toi à moins de 500m pour interagir.
                </p>
            </div>
        </div>
    </Transition>
</template>
