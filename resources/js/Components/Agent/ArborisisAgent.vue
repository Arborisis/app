<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const isOpen = ref(false);
const isSending = ref(false);
const input = ref('');
const conversationId = ref(null);
const scrollContainer = ref(null);
const page = usePage();
const userLocation = ref(null);
const locationError = ref(null);
const pollInterval = ref(null);
const activeJobId = ref(null);

function clearPoll() {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
        pollInterval.value = null;
    }
}

function requestLocation() {
    if (!navigator.geolocation) {
        locationError.value = 'Géolocalisation non supportée par ce navigateur.';
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (position) => {
            userLocation.value = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy,
            };
            locationError.value = null;
        },
        (err) => {
            locationError.value = 'Impossible d’obtenir la position. Vérifie les permissions.';
            console.warn('[Sylve] geolocation error', err);
        },
        { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
    );
}

function clearLocation() {
    userLocation.value = null;
    locationError.value = null;
}

const currentUser = computed(() => page.props.auth?.user ?? null);
const firstName = computed(() => currentUser.value?.name?.split(' ')?.[0] ?? null);
const storageKey = computed(() => `<redacted>:sylve:${currentUser.value?.id ?? 'guest'}`);

const messages = ref([
    {
        role: 'assistant',
        content: firstName.value
            ? `Bonjour ${firstName.value}, je suis Sylve. Je peux t’aider à publier, explorer, analyser tes sons et consulter les données Arborisis en direct.`
            : 'Je suis Sylve, l’agent Arborisis. Je peux consulter les données publiques du site pour répondre sur les sons, la carte, la radio et les métriques scientifiques.',
        sources: [],
    },
]);

const suggestions = computed(() => {
    const base = currentUser.value
        ? [
            'Que me conseilles-tu maintenant ?',
            'Aide-moi à publier un son',
            'Analyse mon parcours Arborisis',
        ]
        : [
            'Qu’est-ce qui passe sur la radio ?',
            'Montre-moi les données scientifiques',
            'Comment rejoindre Arborisis ?',
        ];

    if (userLocation.value) {
        return [
            'Crée un point d\'intérêt ici',
            'Propose-moi une balade field recording',
            'Que puis-je enregistrer près d’ici ?',
            'Y a-t-il des événements de field recording à proximité ?',
            ...base,
        ];
    }

    return base;
});

const quickLinks = computed(() => {
    const links = [
        { label: 'Explorer', href: '/sounds' },
        { label: 'Carte', href: '/map' },
        { label: 'Radio', href: '/radio' },
        { label: 'Stats', href: '/scientific-stats' },
    ];

    if (currentUser.value) {
        return [
            { label: 'Dashboard', href: '/dashboard' },
            { label: 'Publier', href: '/sounds/create' },
            { label: 'Enregistrer', href: '/record' },
            ...links.slice(1),
        ];
    }

    return [
        ...links,
        { label: 'Contribuer', href: '/register' },
    ];
});

const history = computed(() => messages.value
    .filter((message) => ['user', 'assistant'].includes(message.role))
    .slice(-10)
    .map((message) => ({
        role: message.role,
        content: message.content,
    })));

function persistState() {
    try {
        window.localStorage.setItem(storageKey.value, JSON.stringify({
            conversation_id: conversationId.value,
            active_job_id: activeJobId.value,
            messages: messages.value.slice(-30),
            location: userLocation.value,
        }));
    } catch (e) {
        // localStorage can be unavailable in private contexts.
    }
}

function restoreState() {
    try {
        const raw = window.localStorage.getItem(storageKey.value);
        if (!raw) return;

        const saved = JSON.parse(raw);
        if (Array.isArray(saved.messages) && saved.messages.length) {
            messages.value = saved.messages
                .filter((message) => ['user', 'assistant'].includes(message.role) && typeof message.content === 'string')
                .slice(-30);
        }
        conversationId.value = typeof saved.conversation_id === 'string' ? saved.conversation_id : null;
        activeJobId.value = typeof saved.active_job_id === 'string' ? saved.active_job_id : null;
        userLocation.value = saved.location && Number.isFinite(saved.location.lat) && Number.isFinite(saved.location.lng)
            ? saved.location
            : null;
    } catch (e) {
        window.localStorage.removeItem(storageKey.value);
    }
}

function toggle() {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        nextTick(() => scrollToBottom());
    }
}

function scrollToBottom() {
    if (scrollContainer.value) {
        scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight;
    }
}

async function sendMessage(text = null) {
    const content = (text ?? input.value).trim();

    if (!content || isSending.value) {
        return;
    }

    clearPoll();
    const priorHistory = history.value;
    input.value = '';
    messages.value.push({ role: 'user', content, sources: [] });
    isSending.value = true;
    persistState();
    await nextTick();
    scrollToBottom();

    let jobId = null;

    try {
        const response = await fetch('/api/ai-agent/chat', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            body: JSON.stringify({
                message: content,
                conversation_id: conversationId.value,
                history: priorHistory,
                location: userLocation.value,
                page: {
                    url: window.location.pathname + window.location.search,
                    title: document.title,
                    section: sectionFromPath(window.location.pathname),
                    available_actions: quickLinks.value,
                },
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        jobId = data.job_id;

        if (!jobId) {
            throw new Error('Missing job_id');
        }
    } catch (error) {
        messages.value.push({
            role: 'assistant',
            content: 'Je n’arrive pas à joindre l’agent pour le moment.',
            sources: [],
        });
        isSending.value = false;
        await nextTick();
        scrollToBottom();
        return;
    }

    activeJobId.value = jobId;
    persistState();
    startPolling(jobId);
}

function startPolling(jobId) {
    clearPoll();
    isSending.value = true;
    activeJobId.value = jobId;
    persistState();

    const maxAttempts = 180;
    let attempts = 0;
    pollInterval.value = setInterval(async () => {
        attempts++;

        if (attempts > maxAttempts) {
            clearPoll();
            isSending.value = false;
            activeJobId.value = null;
            messages.value.push({
                role: 'assistant',
                content: 'L’agent met trop de temps à répondre. Réessaie dans quelques instants.',
                sources: [],
            });
            persistState();
            await nextTick();
            scrollToBottom();
            return;
        }

        try {
            const res = await fetch(`/api/ai-agent/status/${jobId}`, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            if (!res.ok) {
                return;
            }

            const statusData = await res.json();

            if (statusData.status === 'completed') {
                clearPoll();
                isSending.value = false;
                activeJobId.value = null;
                conversationId.value = statusData.conversation_id ?? conversationId.value;
                appendAssistantResult(statusData);
                persistState();
                await nextTick();
                scrollToBottom();
            } else if (statusData.status === 'failed') {
                clearPoll();
                isSending.value = false;
                activeJobId.value = null;
                messages.value.push({
                    role: 'assistant',
                    content: statusData.error ?? 'L’agent a rencontré un problème.',
                    sources: [],
                });
                persistState();
                await nextTick();
                scrollToBottom();
            }
            // Sinon status === 'processing', on continue le poll
        } catch (e) {
            // Ignorer les erreurs réseau temporaires pendant le polling
        }
    }, 2000);
}

function appendAssistantResult(statusData) {
    const content = statusData.answer ?? 'Je n’ai pas pu répondre correctement.';
    const alreadyPresent = messages.value.some((message) =>
        message.role === 'assistant'
        && message.content === content
        && message.job_id === statusData.job_id
    );

    if (alreadyPresent) {
        return;
    }

    messages.value.push({
        role: 'assistant',
        content,
        sources: statusData.sources ?? [],
        tool_calls: statusData.tool_calls ?? [],
        job_id: statusData.job_id,
    });
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderInlineMarkdown(value) {
    return escapeHtml(value)
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/\*([^*\n]+)\*/g, '<em>$1</em>');
}

function isTableSeparator(line) {
    return /^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?\s*$/.test(line);
}

function splitTableRow(line) {
    return line
        .trim()
        .replace(/^\|/, '')
        .replace(/\|$/, '')
        .split('|')
        .map((cell) => cell.trim());
}

function renderTable(lines, startIndex) {
    const header = splitTableRow(lines[startIndex]);
    const body = [];
    let index = startIndex + 2;

    while (index < lines.length && /^\s*\|.*\|\s*$/.test(lines[index])) {
        body.push(splitTableRow(lines[index]));
        index++;
    }

    const headHtml = header
        .map((cell) => `<th>${renderInlineMarkdown(cell)}</th>`)
        .join('');
    const bodyHtml = body
        .map((row) => `<tr>${row.map((cell) => `<td>${renderInlineMarkdown(cell)}</td>`).join('')}</tr>`)
        .join('');

    return {
        html: `<div class="sylve-table-wrap"><table><thead><tr>${headHtml}</tr></thead><tbody>${bodyHtml}</tbody></table></div>`,
        nextIndex: index,
    };
}

function renderMarkdown(content) {
    const lines = String(content ?? '').split(/\r?\n/);
    const html = [];
    let paragraph = [];
    let list = [];

    const flushParagraph = () => {
        if (!paragraph.length) return;
        html.push(`<p>${renderInlineMarkdown(paragraph.join(' '))}</p>`);
        paragraph = [];
    };

    const flushList = () => {
        if (!list.length) return;
        html.push(`<ul>${list.map((item) => `<li>${renderInlineMarkdown(item)}</li>`).join('')}</ul>`);
        list = [];
    };

    for (let index = 0; index < lines.length; index++) {
        const rawLine = lines[index];
        const line = rawLine.trim();

        if (!line) {
            flushParagraph();
            flushList();
            continue;
        }

        if (/^\s*\|.*\|\s*$/.test(rawLine) && lines[index + 1] && isTableSeparator(lines[index + 1])) {
            flushParagraph();
            flushList();
            const table = renderTable(lines, index);
            html.push(table.html);
            index = table.nextIndex - 1;
            continue;
        }

        const heading = line.match(/^(#{1,4})\s+(.+)$/);
        if (heading) {
            flushParagraph();
            flushList();
            const level = Math.min(heading[1].length + 2, 4);
            html.push(`<h${level}>${renderInlineMarkdown(heading[2])}</h${level}>`);
            continue;
        }

        const listItem = line.match(/^[-*]\s+(.+)$/);
        if (listItem) {
            flushParagraph();
            list.push(listItem[1]);
            continue;
        }

        flushList();
        paragraph.push(line);
    }

    flushParagraph();
    flushList();

    return html.join('');
}

function toolCards(message) {
    return (message.tool_calls ?? [])
        .filter((tc) => tc?.result?.ok || tc?.ok)
        .map((tc) => ({
            ...tc,
            label: tc.label ?? labelForTool(tc.tool),
            summary: tc.summary ?? summaryForTool(tc),
        }))
        .filter((tc) => tc.label || tc.summary || tc.result?.point || tc.result?.sound_walk);
}

function labelForTool(tool) {
    return {
        'create_<redacted>_point': 'Point créé',
        create_sound_walk: 'Balade créée',
        resolve_sound_walk_route: 'Itinéraire vérifié',
        get_radio_now_playing: 'Radio en direct',
        get_radio_programme: 'Programme radio',
        get_scientific_stats: 'Données scientifiques',
        get_user_field_recording_brief: 'Mémoire personnelle',
        get_field_session_plan: 'Plan de sortie',
        search_public_sounds: 'Recherche sons',
        get_map_sounds: 'Carte sonore',
    }[tool] ?? 'Action Sylve';
}

function summaryForTool(tc) {
    if (tc.result?.message) return tc.result.message;
    if (tc.tool === 'resolve_sound_walk_route') return 'Route piétonne vérifiée avec les données disponibles.';
    if (tc.result?.path) return `Données consultées: ${tc.result.path}`;
    return 'Données consultées.';
}

function sectionFromPath(path) {
    if (path.startsWith('/sounds')) return 'sounds';
    if (path.startsWith('/map') || path.startsWith('/<redacted>-map')) return 'map';
    if (path.startsWith('/radio')) return 'radio';
    if (path.startsWith('/scientific-stats')) return 'scientific_stats';
    if (path.startsWith('/dashboard')) return 'dashboard';
    if (path.startsWith('/chat')) return 'community_chat';
    if (path.startsWith('/profile')) return 'profile';

    return 'public_site';
}

onMounted(() => {
    restoreState();
    if (activeJobId.value) {
        startPolling(activeJobId.value);
    }
});

onBeforeUnmount(() => {
    clearPoll();
});
</script>

<template>
    <div class="fixed bottom-5 right-4 z-agent sm:bottom-6 sm:right-6">
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-3 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-3 opacity-0"
        >
            <section
                v-if="isOpen"
                class="mb-3 flex h-[min(650px,calc(100vh-7rem))] w-[calc(100vw-2rem)] max-w-[440px] flex-col overflow-hidden rounded-[8px] border border-arbor-mineral/20 bg-arbor-forest/96 text-arbor-cream shadow-[0_24px_90px_rgba(0,0,0,0.46)] backdrop-blur-2xl"
                aria-label="Agent Arborisis"
            >
                <header class="flex items-center justify-between border-b border-arbor-mineral/15 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="grid h-9 w-9 place-items-center rounded-[8px] bg-arbor-emerald/15 text-arbor-firefly">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 3c4.5 2.9 7 6.2 7 9.8a7 7 0 1 1-14 0C5 9.2 7.5 5.9 12 3Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 9v8m-3-3 3 3 3-3" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-display text-lg leading-none text-arbor-cream">
                                {{ firstName ? `Sylve pour ${firstName}` : 'Sylve' }}
                            </p>
                            <p class="mt-1 text-xs text-arbor-sage">
                                {{ currentUser ? 'Assistant de ton espace Arborisis' : 'Agent Arborisis' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            v-if="currentUser"
                            type="button"
                            class="grid h-9 w-9 place-items-center rounded-[8px] text-arbor-sage transition hover:bg-white/10 hover:text-arbor-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-arbor-firefly/60"
                            :aria-label="userLocation ? 'Désactiver la localisation' : 'Partager ma position'"
                            :title="userLocation ? 'Position partagée — cliquer pour révoquer' : 'Partager ma position pour découvrir les points à proximité'"
                            @click="userLocation ? clearLocation() : requestLocation()"
                        >
                            <svg
                                class="h-5 w-5"
                                :class="userLocation ? 'text-arbor-firefly' : ''"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    v-if="userLocation"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                />
                                <path
                                    v-if="userLocation"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                                <path
                                    v-else
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M12 2a7 7 0 00-7 7c0 2.38 1.19 4.47 3 5.74V17a2 2 0 002 2h4a2 2 0 002-2v-2.26c1.81-1.27 3-3.36 3-5.74a7 7 0 00-7-7zM9 21h6"
                                />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="grid h-9 w-9 place-items-center rounded-[8px] text-arbor-sage transition hover:bg-white/10 hover:text-arbor-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-arbor-firefly/60"
                            aria-label="Fermer l’agent"
                            @click="toggle"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </button>
                    </div>
                </header>

                <nav class="flex gap-2 overflow-x-auto border-b border-arbor-mineral/10 px-4 py-2" aria-label="Actions rapides Sylve">
                    <Link
                        v-for="link in quickLinks"
                        :key="link.href"
                        :href="link.href"
                        class="shrink-0 rounded-[8px] border border-arbor-mineral/15 bg-arbor-night/45 px-3 py-1.5 text-xs text-arbor-sage transition hover:border-arbor-firefly/40 hover:text-arbor-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-arbor-firefly/50"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <div ref="scrollContainer" class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-4">
                    <div
                        v-for="(message, index) in messages"
                        :key="index"
                        class="flex"
                        :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[86%] rounded-[8px] px-3.5 py-3 text-sm leading-6"
                            :class="message.role === 'user'
                                ? 'bg-arbor-emerald text-arbor-night'
                                : 'border border-arbor-mineral/12 bg-arbor-ink/64 text-arbor-cream'"
                        >
                            <div
                                class="sylve-markdown"
                                :class="message.role === 'user' ? 'sylve-markdown-user' : 'sylve-markdown-assistant'"
                                v-html="renderMarkdown(message.content)"
                            />

                            <!-- Preview cards pour ressources créées par l'agent -->
                            <div v-if="toolCards(message).length" class="mt-3 space-y-2">
                                <div
                                    v-for="(tc, tcIndex) in toolCards(message)"
                                    :key="tcIndex"
                                    class="rounded-[8px] border border-arbor-firefly/25 bg-arbor-firefly/10 p-3"
                                >
                                    <div v-if="tc.tool === 'create_<redacted>_point' && tc.result.point" class="flex items-center gap-3">
                                        <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-arbor-firefly/20 text-arbor-firefly">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-arbor-cream">{{ tc.result.point.title }}</p>
                                            <p class="text-xs text-arbor-sage">Point créé · {{ tc.result.point.moderation_status === 'pending' ? 'En attente de modération' : 'Approuvé' }}</p>
                                        </div>
                                        <Link
                                            :href="`/<redacted>-map`"
                                            class="ml-auto shrink-0 rounded-[6px] bg-arbor-firefly/20 px-2.5 py-1 text-xs text-arbor-firefly transition hover:bg-arbor-firefly/30"
                                        >
                                            Voir la carte
                                        </Link>
                                    </div>
                                    <div v-else-if="tc.tool === 'create_sound_walk' && tc.result.sound_walk" class="flex items-center gap-3">
                                        <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-arbor-firefly/20 text-arbor-firefly">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0121 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-arbor-cream">{{ tc.result.sound_walk.title }}</p>
                                            <p class="text-xs text-arbor-sage">Balade {{ tc.result.sound_walk.waypoints_count }} arrêts · {{ tc.result.sound_walk.moderation_status === 'pending' ? 'En attente' : 'Approuvée' }}</p>
                                        </div>
                                        <Link
                                            :href="`/sound-walks/${tc.result.sound_walk.slug}`"
                                            class="ml-auto shrink-0 rounded-[6px] bg-arbor-firefly/20 px-2.5 py-1 text-xs text-arbor-firefly transition hover:bg-arbor-firefly/30"
                                        >
                                            Voir
                                        </Link>
                                    </div>
                                    <div v-else class="flex items-start gap-3">
                                        <div class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-arbor-firefly/20 text-arbor-firefly">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.5 11 14l4-5" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-arbor-cream">{{ tc.label }}</p>
                                            <p class="text-xs leading-5 text-arbor-sage">{{ tc.summary }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="message.sources?.length" class="mt-3 flex flex-wrap gap-1.5">
                                <span
                                    v-for="source in message.sources"
                                    :key="source"
                                    class="rounded-[6px] border border-arbor-emerald/20 bg-arbor-emerald/10 px-2 py-0.5 text-[11px] text-arbor-firefly"
                                >
                                    {{ source }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="isSending" class="flex justify-start">
                        <div class="flex items-center gap-2 rounded-[8px] border border-arbor-mineral/12 bg-arbor-ink/64 px-3.5 py-3 text-sm text-arbor-sage">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-arbor-firefly"></span>
                            Sylve consulte Arborisis
                        </div>
                    </div>
                </div>

                <div class="border-t border-arbor-mineral/15 p-3">
                    <p v-if="locationError" class="mb-2 text-xs text-red-400/90">
                        {{ locationError }}
                    </p>
                    <div class="mb-2 flex gap-2 overflow-x-auto pb-1">
                        <button
                            v-for="suggestion in suggestions"
                            :key="suggestion"
                            type="button"
                            class="shrink-0 rounded-[8px] border border-arbor-mineral/15 bg-arbor-ink/50 px-3 py-1.5 text-xs text-arbor-sage transition hover:border-arbor-emerald/35 hover:text-arbor-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-arbor-firefly/50"
                            @click="sendMessage(suggestion)"
                        >
                            {{ suggestion }}
                        </button>
                    </div>
                    <form class="flex items-end gap-2" @submit.prevent="sendMessage()">
                        <textarea
                            v-model="input"
                            rows="1"
                            maxlength="4000"
                            class="max-h-28 min-h-[44px] flex-1 resize-none rounded-[8px] border-arbor-mineral/20 bg-arbor-night/70 text-sm text-arbor-cream placeholder:text-arbor-sage/70 focus:border-arbor-firefly focus:ring-arbor-firefly"
                            placeholder="Demander à Sylve..."
                            @keydown.enter.exact.prevent="sendMessage()"
                        ></textarea>
                        <button
                            type="submit"
                            :disabled="isSending || !input.trim()"
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-[8px] bg-arbor-firefly text-arbor-night transition hover:bg-arbor-emerald disabled:cursor-not-allowed disabled:opacity-45 focus:outline-none focus-visible:ring-2 focus-visible:ring-arbor-firefly/60 focus-visible:ring-offset-2 focus-visible:ring-offset-arbor-night"
                            aria-label="Envoyer"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m5 12 14-7-4 14-3-6-7-1Z" />
                            </svg>
                        </button>
                    </form>
                </div>
            </section>
        </transition>

        <button
            type="button"
            class="ml-auto flex h-14 items-center gap-3 rounded-[8px] border border-arbor-emerald/30 bg-arbor-forest/92 px-4 text-arbor-cream shadow-[0_18px_60px_rgba(0,0,0,0.42)] backdrop-blur-xl transition hover:border-arbor-firefly/60 hover:bg-arbor-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-arbor-firefly/70 focus-visible:ring-offset-2 focus-visible:ring-offset-arbor-night"
            :aria-expanded="isOpen"
            aria-label="Ouvrir l’agent Arborisis"
            @click="toggle"
        >
            <span class="grid h-9 w-9 place-items-center rounded-[8px] bg-arbor-emerald/15 text-arbor-firefly">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 13.5C4 8.8 7.8 5 12.5 5S21 8.8 21 13.5 17.2 22 12.5 22c-1.2 0-2.4-.3-3.4-.7L4 22l1.1-4.4A8.4 8.4 0 0 1 4 13.5Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 13h.01M12.5 13h.01M16 13h.01" />
                </svg>
            </span>
            <span class="hidden text-left sm:block">
                <span class="block text-sm font-semibold leading-none">{{ firstName ? `Sylve, ${firstName}` : 'Sylve' }}</span>
                <span class="mt-1 block text-xs text-arbor-sage">{{ currentUser ? 'Ton agent' : 'Agent IA' }}</span>
            </span>
        </button>
    </div>
</template>

<style scoped>
.sylve-markdown {
    overflow-wrap: anywhere;
}

.sylve-markdown :deep(p) {
    margin: 0;
}

.sylve-markdown :deep(p + p),
.sylve-markdown :deep(p + ul),
.sylve-markdown :deep(ul + p),
.sylve-markdown :deep(.sylve-table-wrap + p),
.sylve-markdown :deep(p + .sylve-table-wrap) {
    margin-top: 0.7rem;
}

.sylve-markdown :deep(h3),
.sylve-markdown :deep(h4) {
    margin: 0.9rem 0 0.35rem;
    font-size: 0.78rem;
    font-weight: 750;
    letter-spacing: 0;
    line-height: 1.25;
    text-transform: uppercase;
}

.sylve-markdown :deep(h3:first-child),
.sylve-markdown :deep(h4:first-child) {
    margin-top: 0;
}

.sylve-markdown :deep(ul) {
    margin: 0;
    padding-left: 1rem;
}

.sylve-markdown :deep(li + li) {
    margin-top: 0.25rem;
}

.sylve-markdown :deep(strong) {
    font-weight: 750;
}

.sylve-markdown :deep(em) {
    font-style: italic;
}

.sylve-markdown :deep(code) {
    border-radius: 5px;
    padding: 0.08rem 0.28rem;
    font-family: "JetBrains Mono Variable", "JetBrains Mono", monospace;
    font-size: 0.78em;
}

.sylve-markdown-assistant :deep(h3),
.sylve-markdown-assistant :deep(h4),
.sylve-markdown-assistant :deep(strong) {
    color: #e9f8d7;
}

.sylve-markdown-assistant :deep(code) {
    background: rgba(255, 255, 255, 0.08);
    color: #d7f6b5;
}

.sylve-markdown-user :deep(code) {
    background: rgba(7, 26, 22, 0.16);
    color: inherit;
}

.sylve-markdown :deep(.sylve-table-wrap) {
    max-width: 100%;
    overflow-x: auto;
    border: 1px solid rgba(233, 248, 215, 0.12);
    border-radius: 8px;
}

.sylve-markdown :deep(table) {
    width: 100%;
    min-width: 340px;
    border-collapse: collapse;
    font-size: 0.78rem;
    line-height: 1.45;
}

.sylve-markdown :deep(th),
.sylve-markdown :deep(td) {
    border-bottom: 1px solid rgba(233, 248, 215, 0.1);
    padding: 0.42rem 0.5rem;
    text-align: left;
    vertical-align: top;
}

.sylve-markdown :deep(th) {
    background: rgba(255, 255, 255, 0.06);
    color: #e9f8d7;
    font-weight: 700;
}

.sylve-markdown :deep(tr:last-child td) {
    border-bottom: 0;
}
</style>
