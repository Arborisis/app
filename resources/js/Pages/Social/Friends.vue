<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FollowButton from '@/Components/Social/FollowButton.vue';

const props = defineProps({
    user: Object,
    list: Object,
});
</script>

<template>
    <Head :title="`Amis de ${user.name}`" />
    <GuestLayout>
        <div class="pt-24 pb-16">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <Link :href="route('creators.show', user.slug)" class="text-sm text-arbor-sage hover:text-arbor-cream flex items-center gap-1 mb-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Retour au profil
                    </Link>
                    <h1 class="font-display text-2xl font-bold text-arbor-cream">
                        Amis de {{ user.name }}
                    </h1>
                    <p class="text-arbor-sage text-sm mt-1">{{ list.total }} ami{{ list.total > 1 ? 's' : '' }}</p>
                </div>

                <div v-if="list.data.length > 0" class="space-y-3">
                    <div
                        v-for="person in list.data"
                        :key="person.id"
                        class="glass-card p-4 flex items-center gap-4 hover:bg-white/5 transition-colors"
                    >
                        <Link :href="route('creators.show', person.slug)" class="shrink-0">
                            <div class="w-12 h-12 rounded-full bg-arbor-moss/30 flex items-center justify-center overflow-hidden ring-2 ring-arbor-glass-border/50">
                                <img
                                    v-if="person.avatar_url"
                                    :src="person.avatar_url"
                                    :alt="person.name"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-lg font-display font-bold text-arbor-emerald">
                                    {{ person.name?.charAt(0)?.toUpperCase() ?? '?' }}
                                </span>
                            </div>
                        </Link>
                        <div class="flex-1 min-w-0">
                            <Link :href="route('creators.show', person.slug)" class="block">
                                <h3 class="font-semibold text-arbor-cream truncate hover:text-arbor-emerald transition-colors">{{ person.name }}</h3>
                            </Link>
                            <p v-if="person.profile?.bio" class="text-xs text-arbor-sage/70 truncate">{{ person.profile.bio }}</p>
                        </div>
                        <FollowButton
                            v-if="$page.props.auth.user && $page.props.auth.user.id !== person.id"
                            :user-id="person.id"
                            :initial-following="true"
                            size="sm"
                        />
                    </div>
                </div>

                <div v-else class="text-center py-12">
                    <p class="text-arbor-sage">Aucun ami pour le moment.</p>
                </div>

                <!-- Pagination -->
                <div v-if="list.links.length > 3" class="mt-6 flex justify-center">
                    <div class="flex gap-2">
                        <Link
                            v-for="(link, index) in list.links"
                            :key="index"
                            :href="link.url"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-sm transition-colors',
                                link.active
                                    ? 'bg-arbor-emerald text-arbor-night font-medium'
                                    : 'text-arbor-sage hover:bg-white/5',
                                !link.url && 'opacity-40 pointer-events-none',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
