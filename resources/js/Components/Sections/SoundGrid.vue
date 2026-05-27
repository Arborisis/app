<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    sounds: {
        type: Array,
        required: true,
    },
});

const isVisible = ref(false);
const sectionRef = ref(null);

// Strictly use backend data — no fallbacks for content
const hasSounds = computed(() => props.sounds && props.sounds.length > 0);

onMounted(() => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    isVisible.value = true;
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1 }
    );

    if (sectionRef.value) {
        observer.observe(sectionRef.value);
    }
});

const formatDuration = (seconds) => {
    if (!seconds || seconds <= 0) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};
</script>

<template>
    <section ref="sectionRef" class="relative py-32 bg-[#0B0F0E]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6 mb-16">
                <div>
                    <span class="text-[#34D399] text-sm font-mono tracking-wider uppercase mb-4 block">
                        Fragments de territoire
                    </span>
                    <h2 class="text-4xl sm:text-5xl font-semibold text-[#E8F0EC] tracking-tight">
                        Des sons traités comme<br />
                        <span class="text-[#34D399]">des archives vivantes.</span>
                    </h2>
                </div>
                <Link
                    href="/sounds"
                    class="group inline-flex items-center gap-2 px-6 py-3 border border-[#FFFFFF14] text-[#E8F0EC] rounded-xl hover:border-[#34D399]/30 hover:bg-[#34D399]/5 transition-all duration-300 shrink-0"
                >
                    Tous les sons
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </Link>
            </div>

            <!-- Loading state -->
            <div v-if="!hasSounds" class="text-center py-16">
                <div class="w-16 h-16 rounded-2xl bg-[#111815] flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-[#5A6B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                </div>
                <h3 class="text-lg text-[#E8F0EC] mb-2">L'archive s'éveille</h3>
                <p class="text-[#8FA69E] max-w-sm mx-auto mb-6">
                    Les premiers enregistrements arrivent. Soyez parmi les premiers à capturer et partager les sons de la nature.
                </p>
                <Link :href="route('sounds.create')" class="inline-flex items-center gap-2 px-6 py-3 bg-[#34D399] text-[#050A08] rounded-xl font-medium hover:bg-[#6EE7B7] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                    </svg>
                    Enregistrer un son
                </Link>
            </div>

            <!-- Sound cards grid — only render if backend provides sounds -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="(sound, index) in sounds.slice(0, 6)"
                    :key="sound.id"
                    class="group relative bg-[#111815] border border-[#FFFFFF08] rounded-2xl overflow-hidden hover:border-[#34D399]/20 transition-all duration-500 hover:shadow-[0_0_40px_rgba(52,211,153,0.08)] hover:-translate-y-1"
                    :style="{
                        opacity: isVisible ? 1 : 0,
                        transform: isVisible ? 'translateY(0)' : 'translateY(30px)',
                        transition: `all 0.6s ease-out ${index * 0.1}s`,
                    }"
                >
                    <!-- Image / Waveform area -->
                    <div class="relative aspect-[16/10] bg-[#0B0F0E] overflow-hidden">
                        <!-- Waveform visualization — decorative, not data -->
                        <div class="absolute inset-0 flex items-center justify-center gap-[2px] px-8">
                            <div
                                v-for="i in 40"
                                :key="i"
                                class="w-[3px] rounded-full bg-[#34D399]/30 transition-all duration-300 group-hover:bg-[#34D399]/60"
                                :style="{
                                    height: `${20 + Math.sin(i * 0.5) * 30 + Math.random() * 20}%`,
                                }"
                            />
                        </div>

                        <!-- Play button -->
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <button class="w-14 h-14 rounded-full bg-[#34D399] text-[#050A08] flex items-center justify-center hover:scale-110 transition-transform shadow-[0_0_30px_rgba(52,211,153,0.4)]">
                                <svg class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Category badge — from backend only -->
                        <div v-if="sound.category?.name || sound.category" class="absolute top-4 left-4">
                            <span class="px-3 py-1 rounded-full bg-[#050A08]/60 backdrop-blur-sm text-xs font-medium text-[#34D399] border border-[#34D399]/20">
                                {{ sound.category?.name || sound.category }}
                            </span>
                        </div>
                    </div>

                    <!-- Info — all from backend -->
                    <div class="p-5">
                        <h3 class="text-lg font-medium text-[#E8F0EC] mb-2 group-hover:text-[#34D399] transition-colors">
                            {{ sound.title }}
                        </h3>
                        <p v-if="sound.location_name || sound.location" class="text-sm text-[#8FA69E] mb-4">
                            {{ sound.location_name || sound.location }}
                        </p>
                        <div class="flex items-center justify-between text-xs text-[#5A6B65]">
                            <div class="flex items-center gap-4">
                                <span v-if="sound.duration" class="font-mono">{{ formatDuration(sound.duration) }}</span>
                                <span v-if="sound.user_name || sound.user?.name">{{ sound.user_name || sound.user?.name }}</span>
                            </div>
                            <div v-if="sound.plays_count !== undefined" class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ sound.plays_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
