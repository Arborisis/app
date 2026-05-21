<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    soundId: Number,
    initialLiked: Boolean,
    initialCount: Number,
});

const liked = ref(props.initialLiked);
const count = ref(props.initialCount);
const loading = ref(false);

const toggle = async () => {
    if (loading.value) return;
    loading.value = true;

    const isLiked = liked.value;
    const url = isLiked
        ? route('likes.destroy', props.soundId)
        : route('likes.store', props.soundId);

    try {
        const response = await fetch(url, {
            method: isLiked ? 'DELETE' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            liked.value = data.liked;
            count.value = data.count;
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <button
        @click="toggle"
        :disabled="loading"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg transition-colors"
        :class="liked
            ? 'bg-arbor-emerald/20 text-arbor-emerald'
            : 'bg-arbor-glass text-arbor-sage hover:bg-white/10'"
    >
        <svg
            class="w-5 h-5 transition-transform"
            :class="{ 'scale-110': liked }"
            :fill="liked ? 'currentColor' : 'none'"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
            />
        </svg>
        <span class="text-sm font-medium">{{ count }}</span>
    </button>
</template>
