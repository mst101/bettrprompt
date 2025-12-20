<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import { useForm } from '@inertiajs/vue3';

interface Props {
    teamData: {
        teamSize: string | null;
        teamRole: string | null;
        workMode: string | null;
    };
}

const props = defineProps<Props>();

const teamSizeOptions = [
    { value: 'solo', label: 'Just Me (Solo)' },
    { value: 'small', label: 'Small (2-5 people)' },
    { value: 'medium', label: 'Medium (6-20 people)' },
    { value: 'large', label: 'Large (20+ people)' },
];

const teamRoleOptions = [
    { value: 'individual', label: 'Individual Contributor' },
    { value: 'lead', label: 'Team Lead' },
    { value: 'manager', label: 'Manager' },
    { value: 'director', label: 'Director' },
    { value: 'executive', label: 'Executive' },
];

const workModeOptions = [
    { value: 'office', label: 'Office (On-Site)' },
    { value: 'hybrid', label: 'Hybrid' },
    { value: 'remote', label: 'Remote' },
    { value: 'freelance', label: 'Freelance' },
];

const form = useForm({
    teamSize: props.teamData.teamSize || '',
    teamRole: props.teamData.teamRole || '',
    workMode: props.teamData.workMode || '',
});

const submit = () => {
    form.patch(route('profile.team.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-indigo-900">
                Team & Work Context
            </h2>

            <p class="mt-1 text-sm text-indigo-600">
                Share information about your team structure and work
                environment.
            </p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <div class="space-y-6 sm:grid sm:grid-cols-2 sm:gap-6 sm:space-y-0">
                <!-- Team Size -->
                <FormSelect
                    id="team-size"
                    v-model="form.teamSize"
                    label="Team Size"
                    :options="teamSizeOptions"
                    :error="form.errors.teamSize"
                    placeholder="Select team size"
                    show-placeholder
                />

                <!-- Team Role -->
                <FormSelect
                    id="team-role"
                    v-model="form.teamRole"
                    label="Your Role"
                    :options="teamRoleOptions"
                    :error="form.errors.teamRole"
                    placeholder="Select your role"
                    show-placeholder
                />

                <!-- Work Mode -->
                <FormSelect
                    id="work-mode"
                    v-model="form.workMode"
                    label="Work Mode"
                    :options="workModeOptions"
                    :error="form.errors.workMode"
                    placeholder="Select work mode"
                    show-placeholder
                />
            </div>

            <div class="flex items-center gap-4">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    Save Team Context
                </ButtonPrimary>
            </div>
        </form>
    </section>
</template>
