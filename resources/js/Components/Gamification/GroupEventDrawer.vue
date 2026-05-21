<script setup>
import { computed } from 'vue';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    event: { type: Object, default: null },
    isParticipant: { type: Boolean, default: false },
    canCheckIn: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'join', 'leave', 'checkIn']);

const eventTypeLabel = computed(() => {
    const map = {
        dawn_chorus: 'Dawn Chorus',
        soundwalk: 'Soundwalk',
        night_ambience: 'Ambiance nocturne',
        freestyle: 'Session libre',
    };
    return map[props.event?.event_type] ?? 'Événement';
});

const eventTypeColor = computed(() => {
    const map = {
        dawn_chorus: 'text-amber-400 bg-amber-400/10',
        soundwalk: 'text-sky-400 bg-sky-400/10',
        night_ambience: 'text-violet-400 bg-violet-400/10',
        freestyle: 'text-arbor-emerald bg-arbor-emerald/10',
    };
    return map[props.event?.event_type] ?? 'text-arbor-sage bg-arbor-sage/10';
});

const timeUntil = computed(() => {
    if (!props.event?.scheduled_at) return '';
    const diff = new Date(props.event.scheduled_at) - Date.now();
    if (diff < 0) return 'En cours';
    const mins = Math.round(diff / 60000);
    if (mins < 60) return `Dans ${mins}min`;
    const hrs = Math.round(mins / 60);
    return `Dans ${hrs}h`;
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
    >
        <div
            v-if="isOpen && event"
            class="absolute top-4 right-4 bottom-4 w-80 max-w-[calc(100vw-2rem)] z-drawer bg-arbor-night/95 backdrop-blur-xl border border-white/5 rounded-2xl shadow-2xl overflow-hidden flex flex-col p-5"
        >
            <div class="flex items-center justify-between mb-4">
                <span class="text-[10px] px-2 py-1 rounded-md font-medium" :class="eventTypeColor">
                    {{ eventTypeLabel }}
                </span>
                <button @click="emit('close')" aria-label="Fermer" class="min-w-[44px] min-h-[44px] flex items-center justify-center text-arbor-sage/50 hover:text-arbor-cream">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <h2 class="text-lg font-semibold text-arbor-cream mb-1">{{ event.title }}</h2>
            <p class="text-xs text-arbor-sage/70 mb-4">{{ event.description }}</p>

            <div class="flex items-center gap-3 text-xs text-arbor-sage/60 mb-4">
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ timeUntil }}
                </span>
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ event.participants_count ?? 0 }} / {{ event.max_participants }}
                </span>
            </div>

            <div class="mt-auto space-y-2">
                <button
                    v-if="!isParticipant"
                    class="w-full py-2.5 rounded-xl bg-arbor-emerald text-arbor-night font-semibold text-sm hover:bg-arbor-emerald/90 transition-colors"
                    @click="emit('join')"
                >
                    Rejoindre
                </button>

                <button
                    v-if="isParticipant && canCheckIn"
                    class="w-full py-2.5 rounded-xl bg-arbor-amber text-arbor-night font-semibold text-sm hover:bg-arbor-amber/90 transition-colors"
                    @click="emit('checkIn')"
                >
                    Check-in
                </button>

                <button
                    v-if="isParticipant && !canCheckIn"
                    class="w-full py-2.5 rounded-xl bg-white/5 text-arbor-cream font-medium text-sm hover:bg-white/10 transition-colors"
                    @click="emit('leave')"
                >
                    Quitter
                </button>
            </div>
        </div>
    </Transition>
</template>
