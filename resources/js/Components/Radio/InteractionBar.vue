<script setup>
import { computed, ref } from 'vue';
import { useRadioSession } from '@/Composables/useRadioSession';

const props = defineProps({
    sound: Object,
    summary: {
        type: Object,
        default: () => ({ like: 0, heart: 0, leaf: 0, star: 0 }),
    },
});

const emit = defineEmits(['update:summary']);
const { sessionToken } = useRadioSession();
const busy = ref(false);
const copied = ref(false);

const soundId = computed(() => props.sound?.sound_id || props.sound?.id || null);
const shareDisabled = computed(() => !soundId.value);

async function post(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify(payload),
    });

    if (!response.ok) return null;

    return response.json();
}

async function like() {
    if (!soundId.value || busy.value) return;
    busy.value = true;
    const result = await post('/api/radio/interactions/like', {
        session_token: sessionToken,
        sound_id: soundId.value,
    });
    if (result?.summary) emit('update:summary', result.summary);
    busy.value = false;
}

async function react(type) {
    if (!soundId.value || busy.value) return;
    busy.value = true;
    const result = await post('/api/radio/interactions/react', {
        session_token: sessionToken,
        sound_id: soundId.value,
        reaction_type: type,
    });
    if (result?.summary) emit('update:summary', result.summary);
    busy.value = false;
}

async function share() {
    if (shareDisabled.value) return;
    const result = await post('/api/radio/interactions/share', {
        sound_id: soundId.value,
    });
    if (!result?.url) return;

    if (navigator.share) {
        await navigator.share({ title: result.title, text: result.text, url: result.url }).catch(() => {});
        return;
    }

    await navigator.clipboard.writeText(result.url);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 1800);
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-full bg-arbor-emerald px-4 py-2 text-sm font-semibold text-arbor-night transition hover:bg-arbor-emerald-dark disabled:opacity-50"
            :disabled="!soundId || busy"
            @click="like"
        >
            <span>J'aime</span>
            <span class="font-mono text-xs">{{ summary.like || 0 }}</span>
        </button>
        <button type="button" class="reaction-btn" :disabled="!soundId || busy" @click="react('heart')">
            <span>Coeur</span><span>{{ summary.heart || 0 }}</span>
        </button>
        <button type="button" class="reaction-btn" :disabled="!soundId || busy" @click="react('leaf')">
            <span>Feuille</span><span>{{ summary.leaf || 0 }}</span>
        </button>
        <button type="button" class="reaction-btn" :disabled="!soundId || busy" @click="react('star')">
            <span>Étoile</span><span>{{ summary.star || 0 }}</span>
        </button>
        <button type="button" class="reaction-btn" :disabled="shareDisabled" @click="share">
            {{ copied ? 'Lien copié' : 'Partager' }}
        </button>
    </div>
</template>

<style scoped>
.reaction-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 9999px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.06);
    padding: 0.5rem 0.8rem;
    font-size: 0.875rem;
    color: rgb(174 190 176);
    transition: color 150ms ease, background 150ms ease;
}

.reaction-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.1);
    color: rgb(244 238 220);
}

.reaction-btn:disabled {
    opacity: 0.5;
}
</style>
