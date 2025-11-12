<script setup lang="ts">
import ContainerPage from '@/Components/ContainerPage.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
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

    <HeaderPage title="Profile" />

    <ContainerPage spacing>
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
    </ContainerPage>
</template>
