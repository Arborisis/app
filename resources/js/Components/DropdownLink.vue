<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-sm leading-5 text-arbor-sage transition duration-150 ease-in-out hover:bg-arbor-glass hover:text-arbor-cream focus:bg-arbor-glass focus:text-arbor-cream focus:outline-none"
    >
        <slot />
    </Link>
</template>
