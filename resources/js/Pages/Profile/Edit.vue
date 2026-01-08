<script setup lang="ts">
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import ProfileCompletion from '@/Components/Common/ProfileCompletion.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdateBudgetForm from './Partials/UpdateBudgetForm.vue';
import UpdateLocationForm from './Partials/UpdateLocationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdatePersonalityTypeForm from './Partials/UpdatePersonalityTypeForm.vue';
import UpdateProfessionalForm from './Partials/UpdateProfessionalForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateTeamForm from './Partials/UpdateTeamForm.vue';
import UpdateToolsForm from './Partials/UpdateToolsForm.vue';
import UpdateUiComplexityForm from './Partials/UpdateUiComplexityForm.vue';

interface LocationData {
    countryCode: string | null;
    countryName: string | null;
    region: string | null;
    city: string | null;
    timezone: string | null;
    currencyCode: string | null;
    languageCode: string | null;
    detectedAt: string | null;
    manuallySet: boolean;
}

interface ProfessionalData {
    jobTitle: string | null;
    industry: string | null;
    experienceLevel: string | null;
    companySize: string | null;
}

interface TeamData {
    teamSize: string | null;
    teamRole: string | null;
    workMode: string | null;
}

interface BudgetData {
    budgetConsciousness: string | null;
}

interface ToolsData {
    preferredTools: string[];
    primaryProgrammingLanguage: string | null;
}

interface SelectOption {
    value: string;
    label: string;
}

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    personalityTypes: Record<string, string>;
    uiComplexity: 'simple' | 'advanced';
    profileCompletion: number;
    locationData: LocationData;
    countries: SelectOption[];
    currencies: SelectOption[];
    languages: SelectOption[];
    professionalData: ProfessionalData;
    teamData: TeamData;
    budgetData: BudgetData;
    toolsData: ToolsData;
}>();

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head :title="$t('common.nav.profile')" />

    <HeaderPage :title="$t('common.nav.profile')" />

    <ContainerPage spacing>
        <!-- Profile Completion Progress -->

        <ProfileCompletion
            class="max-w-4xl space-y-2 bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
            :percentage="profileCompletion"
        />

        <!-- Basic Information -->
        <UpdateProfileInformationForm
            class="max-w-4xl space-y-2 bg-white shadow-sm sm:rounded-lg dark:bg-indigo-50"
            :must-verify-email="mustVerifyEmail"
            :status="status"
        />

        <!-- Personality Type -->
        <UpdatePersonalityTypeForm
            class="max-w-4xl space-y-2 bg-white shadow-sm sm:rounded-lg dark:bg-indigo-50"
            :personality-types="personalityTypes"
        />

        <!-- Location & Language -->
        <UpdateLocationForm
            class="max-w-4xl space-y-2 bg-white shadow-sm sm:rounded-lg dark:bg-indigo-50"
            :location-data="locationData"
            :countries="countries"
            :currencies="currencies"
            :languages="languages"
        />

        <!-- Professional Context -->
        <UpdateProfessionalForm
            class="max-w-4xl space-y-2 bg-white shadow-sm sm:rounded-lg dark:bg-indigo-50"
            :professional-data="professionalData"
        />

        <!-- Team & Work Context -->
        <UpdateTeamForm
            class="max-w-4xl space-y-2 bg-white shadow-sm sm:rounded-lg dark:bg-indigo-50"
            :team-data="teamData"
        />
        <!-- Budget Preferences -->
        <UpdateBudgetForm
            class="max-w-4xl space-y-2 bg-white shadow-sm sm:rounded-lg dark:bg-indigo-50"
            :budget-data="budgetData"
        />

        <!-- Tools & Technologies -->
        <UpdateToolsForm
            class="max-w-4xl space-y-2 bg-white shadow-sm sm:rounded-lg dark:bg-indigo-50"
            :tools-data="toolsData"
        />

        <!-- UI Complexity -->
        <UpdateUiComplexityForm
            class="max-w-4xl space-y-2 bg-white shadow-sm sm:rounded-lg dark:bg-indigo-50"
            :ui-complexity="uiComplexity"
        />

        <!-- Password -->
        <UpdatePasswordForm
            class="max-w-4xl space-y-2 bg-white shadow-sm sm:rounded-lg dark:bg-indigo-50"
        />

        <!-- Delete Account -->
        <DeleteUserForm
            class="max-w-4xl space-y-2 bg-white shadow-sm sm:rounded-lg dark:bg-indigo-50"
        />
    </ContainerPage>
</template>
