<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: {
        type: Array,
        required: true,
        // Each item: { label: string, href?: string }
    },
});
</script>

<template>
    <nav aria-label="Fil d'Ariane" class="mb-6">
        <ol class="flex items-center gap-2 text-sm text-arbor-sage">
            <li
                v-for="(item, index) in items"
                :key="index"
                class="flex items-center gap-2"
            >
                <span v-if="index > 0" aria-hidden="true" class="text-arbor-sage/30">/</span>
                <Link
                    v-if="item.href && index < items.length - 1"
                    :href="item.href"
                    class="hover:text-arbor-emerald transition-colors"
                >
                    {{ item.label }}
                </Link>
                <span v-else class="text-arbor-cream">
                    {{ item.label }}
                </span>
            </li>
        </ol>
    </nav>
</template>
