<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useNotification } from '@/Composables/ui/useNotification';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    teamData: {
        teamSize: string | null;
        teamRole: string | null;
        workMode: string | null;
    };
}

const props = defineProps<Props>();
const { success, error } = useNotification();

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

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success('Team context updated successfully');
        }
    },
);

watch(
    () => Object.keys(form.errors).length > 0,
    (hasErrors) => {
        if (hasErrors) {
            const errorMessage = Object.values(form.errors)[0];
            if (typeof errorMessage === 'string') {
                error(errorMessage);
            }
        }
    },
);

const submit = () => {
    form.patch(route('profile.team.update'), {
        preserveScroll: true,
    });
};

const hasTeamData = computed(() => {
    return !!(
        props.teamData.teamSize ||
        props.teamData.teamRole ||
        props.teamData.workMode
    );
});

const { confirm } = useAlert();

const clearTeam = async () => {
    const confirmed = await confirm(
        'Are you sure you want to clear all team and work information?',
        'Clear Team & Work Context',
        { confirmButtonStyle: 'danger', confirmText: 'Clear' },
    );

    if (confirmed) {
        const clearForm = useForm({});
        clearForm.delete(route('profile.team.clear'), {
            preserveScroll: true,
            onSuccess: () => {
                success('Team information cleared successfully');
                // Clear the form fields
                form.teamSize = '';
                form.teamRole = '';
                form.workMode = '';
            },
            onError: () => {
                error('Failed to clear team information');
            },
        });
    }
};
</script>

<template>
    <CollapsibleSection
        title="Team & Work Context"
        subtitle="Share information about your team structure and work environment."
    >
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

            <div class="flex flex-col items-center gap-4 sm:flex-row">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                    icon="download"
                >
                    Save Team Context
                </ButtonPrimary>

                <ButtonTrash
                    v-if="hasTeamData"
                    id="clear-team-form"
                    label="Clear"
                    @clear="clearTeam"
                />
            </div>
        </form>
    </CollapsibleSection>
</template>
