<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-arbor-emerald text-start text-base font-medium text-arbor-cream bg-arbor-emerald/10 focus:outline-none focus:text-arbor-cream focus:bg-arbor-emerald/15 focus:border-arbor-emerald-dark transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-arbor-sage hover:text-arbor-cream hover:bg-arbor-glass hover:border-arbor-sage/50 focus:outline-none focus:text-arbor-cream focus:bg-arbor-glass focus:border-arbor-sage/50 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
