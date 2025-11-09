<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdatePersonalityTypeForm from './Partials/UpdatePersonalityTypeForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineOptions({
    layout: AppLayout,
});

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    personalityTypes: Record<string, string>;
}>();
</script>

<template>
    <Head title="Profile" />

    <header class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Profile
            </h2>
        </div>
    </header>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8">
                <UpdatePersonalityTypeForm
                    :personality-types="personalityTypes"
                    class="max-w-xl"
                />
            </div>

            <div class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </div>
</template>
