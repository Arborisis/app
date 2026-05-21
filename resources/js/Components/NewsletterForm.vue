<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
});

function submit() {
    form.post(route('newsletter.subscribe'), {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
}
</script>

<template>
    <div>
        <form @submit.prevent="submit" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <input
                    v-model="form.email"
                    type="email"
                    required
                    placeholder="votre@email.com"
                    class="w-full rounded-xl bg-arbor-glass/30 border border-arbor-glass-border px-4 py-3 text-base text-arbor-cream placeholder:text-arbor-sage/60 focus:outline-none focus:ring-2 focus:ring-arbor-emerald/40 focus:border-arbor-emerald/40 transition-colors"
                />
                <div v-if="form.errors.email" class="absolute -bottom-5 left-0 text-xs text-red-400">
                    {{ form.errors.email }}
                </div>
            </div>
            <button
                type="submit"
                :disabled="form.processing"
                class="shrink-0 px-6 py-3 rounded-xl bg-gradient-to-r from-arbor-moss to-arbor-moss-light text-white text-sm font-medium hover:shadow-lg hover:shadow-arbor-moss/20 transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            >
                <span v-if="form.processing">Inscription...</span>
                <span v-else>S'inscrire</span>
            </button>
        </form>

        <div
            v-if="$page.props.flash?.success"
            class="mt-4 p-3 rounded-xl bg-arbor-emerald/10 border border-arbor-emerald/20 text-arbor-emerald text-sm"
        >
            {{ $page.props.flash.success }}
        </div>
    </div>
</template>
