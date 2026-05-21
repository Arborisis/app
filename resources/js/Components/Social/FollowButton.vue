<script setup>
import { ref } from 'vue';

const props = defineProps({
    userId: Number,
    initialFollowing: Boolean,
    size: {
        type: String,
        default: 'md',
    },
});

const following = ref(props.initialFollowing);
const loading = ref(false);

const toggle = async () => {
    if (loading.value) return;
    loading.value = true;

    const isFollowing = following.value;
    const url = isFollowing
        ? route('follows.destroy', props.userId)
        : route('follows.store', props.userId);

    try {
        const response = await fetch(url, {
            method: isFollowing ? 'DELETE' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            following.value = data.following;
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const sizeClasses = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base',
};
</script>

<template>
    <button
        @click="toggle"
        :disabled="loading"
        class="rounded-lg font-medium transition-colors disabled:opacity-50"
        :class="[
            sizeClasses[size],
            following
                ? 'bg-arbor-glass text-arbor-sage hover:bg-white/10 border border-arbor-glass'
                : 'bg-arbor-emerald text-arbor-night hover:bg-arbor-emerald-dark',
        ]"
    >
        {{ following ? 'Suivi' : 'Suivre' }}
    </button>
</template>
