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
    <Head title="Profile" />

    <HeaderPage title="Profile" />

    <ContainerPage spacing>
        <!-- Profile Completion Progress -->
        <div
            class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
        >
            <ProfileCompletion :percentage="profileCompletion" />
        </div>

        <!-- Basic Information -->
        <div
            class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
        >
            <UpdateProfileInformationForm
                :must-verify-email="mustVerifyEmail"
                :status="status"
                class="max-w-xl"
            />
        </div>

        <!-- Personality Type -->
        <div
            class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
        >
            <UpdatePersonalityTypeForm :personality-types="personalityTypes" />
        </div>

        <!-- UI Complexity -->
        <div
            class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
        >
            <UpdateUiComplexityForm
                :ui-complexity="uiComplexity"
                class="max-w-xl"
            />
        </div>

        <!-- Location & Language -->
        <div
            class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
        >
            <UpdateLocationForm
                :location-data="locationData"
                :countries="countries"
                :currencies="currencies"
                :languages="languages"
                class="max-w-2xl"
            />
        </div>

        <!-- Professional Context -->
        <div
            class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
        >
            <UpdateProfessionalForm
                :professional-data="professionalData"
                class="max-w-2xl"
            />
        </div>

        <!-- Team & Work Context -->
        <div
            class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
        >
            <UpdateTeamForm :team-data="teamData" class="max-w-2xl" />
        </div>

        <!-- Budget Preferences -->
        <div
            class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
        >
            <UpdateBudgetForm :budget-data="budgetData" class="max-w-2xl" />
        </div>

        <!-- Tools & Technologies -->
        <div
            class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
        >
            <UpdateToolsForm :tools-data="toolsData" class="max-w-4xl" />
        </div>

        <!-- Password -->
        <div
            class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
        >
            <UpdatePasswordForm class="max-w-xl" />
        </div>

        <!-- Delete Account -->
        <div
            class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8 dark:bg-indigo-50"
        >
            <DeleteUserForm class="max-w-xl" />
        </div>
    </ContainerPage>
</template>
