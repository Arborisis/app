<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ImmersivePlayer from '@/Components/Player/ImmersivePlayer.vue';
import Spectrogram from '@/Components/Audio/Spectrogram.vue';
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    sound: {
        type: Object,
        required: true,
    },
    relatedSounds: {
        type: Array,
        default: () => [],
    },
});

const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(props.sound.duration || 0);
const showSpectrogram = ref(false);

const togglePlay = () => {
    isPlaying.value = !isPlaying.value;
};

const handleSeek = (time) => {
    currentTime.value = time;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDuration = (seconds) => {
    if (!seconds || seconds <= 0) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};
</script>

<template>
    <Head :title="`${sound.title} — Arborisis`" />
    
    <GuestLayout>
        <!-- Hero with cover image -->
        <section class="relative h-[60vh] min-h-[500px] overflow-hidden">
            <div 
                v-if="sound.cover_url"
                class="absolute inset-0 bg-cover bg-center"
                :style="{ backgroundImage: `url(${sound.cover_url})` }"
            />
            <div v-else class="absolute inset-0 bg-[#0B0F0E]" />
            
            <!-- Gradient overlays -->
            <div class="absolute inset-0 bg-gradient-to-t from-[#050A08] via-[#050A08]/60 to-transparent" />
            <div class="absolute inset-0 bg-gradient-to-r from-[#050A08]/80 to-transparent" />
            
            <!-- Content -->
            <div class="relative z-10 h-full flex items-end">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full pb-12">
                    <span class="text-[#34D399] text-sm font-mono tracking-wider uppercase mb-4 block">
                        {{ sound.category?.name || 'Field Recording' }}
                    </span>
                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-semibold text-[#E8F0EC] mb-4">
                        {{ sound.title }}
                    </h1>
                    <div class="flex items-center gap-6 text-[#8FA69E]">
                        <Link 
                            :href="`/creators/${sound.user?.slug || sound.user?.id}`"
                            class="flex items-center gap-3 hover:text-[#34D399] transition-colors"
                        >
                            <img 
                                v-if="sound.user?.avatar_url"
                                :src="sound.user.avatar_url"
                                :alt="sound.user.name"
                                class="w-10 h-10 rounded-full object-cover"
                            />
                            <div v-else class="w-10 h-10 rounded-full bg-[#1A211E] flex items-center justify-center text-[#5A6B65]">
                                {{ sound.user?.name?.charAt(0) || '?' }}
                            </div>
                            <span>{{ sound.user?.name || 'Anonyme' }}</span>
                        </Link>
                        <span v-if="sound.location_name || sound.location">·</span>
                        <span v-if="sound.location_name || sound.location">{{ sound.location_name || sound.location }}</span>
                        <span>·</span>
                        <span>{{ formatDate(sound.created_at) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Player section -->
        <section class="sticky top-0 z-40 bg-[#050A08]/95 backdrop-blur-xl border-b border-[#FFFFFF08]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <ImmersivePlayer
                    :sound="sound"
                    :is-playing="isPlaying"
                    :current-time="currentTime"
                    :duration="duration"
                    @play="togglePlay"
                    @pause="togglePlay"
                    @seek="handleSeek"
                />
            </div>
        </section>

        <!-- Spectrogram toggle -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <button 
                @click="showSpectrogram = !showSpectrogram"
                class="flex items-center gap-2 text-[#8FA69E] hover:text-[#34D399] transition-colors mb-4"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                {{ showSpectrogram ? 'Masquer' : 'Afficher' }} le spectrogramme
            </button>
            
            <Spectrogram
                v-if="showSpectrogram"
                :audio-url="sound.audio_url"
                :is-playing="isPlaying"
                :current-time="currentTime"
            />
        </section>

        <!-- Metadata -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Left column: Description and details -->
                <div class="lg:col-span-2">
                    <h2 class="text-2xl font-semibold text-[#E8F0EC] mb-6">À propos de cet enregistrement</h2>
                    <p class="text-[#8FA69E] leading-relaxed mb-8">
                        {{ sound.description || 'Aucune description disponible.' }}
                    </p>
                    
                    <!-- Audio features -->
                    <div v-if="sound.audio_features" class="bg-[#111815] rounded-2xl p-6 border border-[#FFFFFF08]">
                        <h3 class="text-lg font-medium text-[#E8F0EC] mb-4">Caractéristiques audio</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <div class="text-sm text-[#5A6B65] mb-1">Fréquence dominante</div>
                                <div class="text-[#34D399] font-mono">{{ Math.round(sound.audio_features.spectral_centroid || 0) }} Hz</div>
                            </div>
                            <div>
                                <div class="text-sm text-[#5A6B65] mb-1">Bande passante</div>
                                <div class="text-[#34D399] font-mono">{{ Math.round(sound.audio_features.bandwidth || 0) }} Hz</div>
                            </div>
                            <div>
                                <div class="text-sm text-[#5A6B65] mb-1">RMS</div>
                                <div class="text-[#34D399] font-mono">{{ (sound.audio_features.rms || 0).toFixed(3) }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-[#5A6B65] mb-1">Zero-crossing</div>
                                <div class="text-[#34D399] font-mono">{{ Math.round(sound.audio_features.zero_crossing_rate || 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right column: Stats and actions -->
                <div>
                    <div class="bg-[#111815] rounded-2xl p-6 border border-[#FFFFFF08] mb-6">
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="text-center">
                                <div class="text-2xl font-semibold text-[#34D399]">{{ sound.play_count?.toLocaleString('fr-FR') || 0 }}</div>
                                <div class="text-xs text-[#5A6B65] uppercase tracking-wider">Écoutes</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-semibold text-[#34D399]">{{ sound.like_count?.toLocaleString('fr-FR') || 0 }}</div>
                                <div class="text-xs text-[#5A6B65] uppercase tracking-wider">J'aime</div>
                            </div>
                        </div>
                        
                        <button class="w-full py-3 bg-[#34D399] text-[#050A08] rounded-xl font-medium hover:bg-[#6EE7B7] transition-colors mb-3">
                            J'aime
                        </button>
                        <button class="w-full py-3 border border-[#FFFFFF14] text-[#E8F0EC] rounded-xl hover:border-[#34D399]/30 hover:bg-[#34D399]/5 transition-colors">
                            Partager
                        </button>
                    </div>
                    
                    <!-- Equipment info -->
                    <div v-if="sound.equipment" class="bg-[#111815] rounded-2xl p-6 border border-[#FFFFFF08]">
                        <h3 class="text-lg font-medium text-[#E8F0EC] mb-4">Équipement</h3>
                        <div class="space-y-3">
                            <div v-if="sound.equipment.microphone">
                                <div class="text-sm text-[#5A6B65]">Microphone</div>
                                <div class="text-[#E8F0EC]">{{ sound.equipment.microphone }}</div>
                            </div>
                            <div v-if="sound.equipment.recorder">
                                <div class="text-sm text-[#5A6B65]">Enregistreur</div>
                                <div class="text-[#E8F0EC]">{{ sound.equipment.recorder }}</div>
                            </div>
                            <div v-if="sound.equipment.windshield">
                                <div class="text-sm text-[#5A6B65]">Pare-brise</div>
                                <div class="text-[#E8F0EC]">{{ sound.equipment.windshield }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Related sounds -->
        <section v-if="relatedSounds.length > 0" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h2 class="text-2xl font-semibold text-[#E8F0EC] mb-8">Enregistrements similaires</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="related in relatedSounds.slice(0, 3)"
                    :key="related.id"
                    :href="`/sounds/${related.slug}`"
                    class="group bg-[#111815] border border-[#FFFFFF08] rounded-2xl overflow-hidden hover:border-[#34D399]/20 transition-all duration-500"
                >
                    <div class="aspect-[16/10] bg-[#0B0F0E] relative overflow-hidden">
                        <img 
                            v-if="related.cover_url"
                            :src="related.cover_url"
                            :alt="related.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-[#111815] to-transparent" />
                    </div>
                    <div class="p-5">
                        <h3 class="text-lg font-medium text-[#E8F0EC] group-hover:text-[#34D399] transition-colors">{{ related.title }}</h3>
                        <p class="text-sm text-[#8FA69E]">{{ related.user?.name }} · {{ related.location_name || related.location }}</p>
                    </div>
                </Link>
            </div>
        </section>
    </GuestLayout>
</template>
