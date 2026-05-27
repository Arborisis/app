<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    creators: {
        type: Array,
        required: true,
    },
});

const isVisible = ref(false);
const sectionRef = ref(null);

// Strictly use backend data — no fallbacks for content
const hasCreators = computed(() => props.creators && props.creators.length > 0);

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
</script>

<template>
    <section ref="sectionRef" class="relative py-32 bg-[#0B0F0E]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6 mb-16">
                <div>
                    <span class="text-[#34D399] text-sm font-mono tracking-wider uppercase mb-4 block">
                        Communauté
                    </span>
                    <h2 class="text-4xl sm:text-5xl font-semibold text-[#E8F0EC] tracking-tight">
                        Les créateurs<br />
                        <span class="text-[#34D399]">en avant.</span>
                    </h2>
                </div>
                <Link
                    href="/creators"
                    class="group inline-flex items-center gap-2 px-6 py-3 border border-[#FFFFFF14] text-[#E8F0EC] rounded-xl hover:border-[#34D399]/30 hover:bg-[#34D399]/5 transition-all duration-300 shrink-0"
                >
                    Tous les créateurs
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </Link>
            </div>

            <!-- Loading / Empty state -->
            <div v-if="!hasCreators" class="text-center py-16">
                <div class="w-16 h-16 rounded-2xl bg-[#111815] flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-[#5A6B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="text-lg text-[#E8F0EC] mb-2">La communauté grandit</h3>
                <p class="text-[#8FA69E] max-w-sm mx-auto mb-6">
                    Rejoignez les premiers créateurs à partager leurs enregistrements naturels.
                </p>
                <Link :href="route('register')" class="inline-flex items-center gap-2 px-6 py-3 bg-[#34D399] text-[#050A08] rounded-xl font-medium hover:bg-[#6EE7B7] transition-colors">
                    Créer un compte
                </Link>
            </div>

            <!-- Creators grid — only render if backend provides creators -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="(creator, index) in creators.slice(0, 4)"
                    :key="creator.id"
                    class="group relative bg-[#111815] border border-[#FFFFFF08] rounded-2xl p-6 hover:border-[#34D399]/20 transition-all duration-500 hover:shadow-[0_0_40px_rgba(52,211,153,0.08)]"
                    :style="{
                        opacity: isVisible ? 1 : 0,
                        transform: isVisible ? 'translateY(0)' : 'translateY(30px)',
                        transition: `all 0.6s ease-out ${index * 0.1}s`,
                    }"
                >
                    <!-- Avatar -->
                    <div class="relative mb-4">
                        <div class="w-20 h-20 rounded-full bg-[#1A211E] border-2 border-[#FFFFFF08] overflow-hidden mx-auto group-hover:border-[#34D399]/30 transition-colors">
                            <img
                                v-if="creator.avatar_url || creator.avatar"
                                :src="creator.avatar_url || creator.avatar"
                                :alt="creator.name"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-2xl text-[#5A6B65]">
                                {{ creator.name?.charAt(0) || '?' }}
                            </div>
                        </div>
                        <div v-if="creator.sounds_count !== undefined" class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-[#34D399] flex items-center justify-center text-[10px] text-[#050A08] font-bold">
                            {{ creator.sounds_count }}
                        </div>
                    </div>

                    <!-- Info — all from backend -->
                    <div class="text-center">
                        <h3 class="text-lg font-medium text-[#E8F0EC] mb-1 group-hover:text-[#34D399] transition-colors">
                            {{ creator.name }}
                        </h3>
                        <p v-if="creator.sounds_count !== undefined || creator.total_plays !== undefined" class="text-sm text-[#8FA69E] mb-4">
                            <span v-if="creator.sounds_count !== undefined">{{ creator.sounds_count }} sons</span>
                            <span v-if="creator.sounds_count !== undefined && creator.total_plays !== undefined"> · </span>
                            <span v-if="creator.total_plays !== undefined">{{ creator.total_plays }} écoutes</span>
                        </p>
                        <Link
                            :href="`/creators/${creator.slug || creator.id}`"
                            class="inline-flex items-center gap-1 text-sm text-[#34D399] hover:text-[#6EE7B7] transition-colors"
                        >
                            Voir le profil
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
