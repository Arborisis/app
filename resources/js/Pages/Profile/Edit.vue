<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import DiscordConnectionForm from './Partials/DiscordConnectionForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateAvatarForm from './Partials/UpdateAvatarForm.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    discord: {
        type: Object,
        default: null,
    },
    profile: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Profil" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl font-semibold text-arbor-cream">
                Paramètres du profil
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="glass-card p-6 sm:p-8">
                    <UpdateAvatarForm :avatar-url="profile?.avatarUrl" />
                </div>

                <div class="glass-card p-6 sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :profile="profile"
                        class="max-w-xl"
                    />
                </div>

                <div class="glass-card p-6 sm:p-8">
                    <DiscordConnectionForm :discord="discord" />
                </div>

                <div class="glass-card p-6 sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="glass-card p-6 sm:p-8 border-red-500/20">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
