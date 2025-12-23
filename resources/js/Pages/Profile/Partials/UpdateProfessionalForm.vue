<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import CollapsibleSection from '@/Components/Base/CollapsibleSection.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useNotification } from '@/Composables/ui/useNotification';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    professionalData: {
        jobTitle: string | null;
        industry: string | null;
        experienceLevel: string | null;
        companySize: string | null;
    };
}

const props = defineProps<Props>();
const { success, error } = useNotification();

const experienceLevelOptions = [
    { value: 'entry', label: 'Entry Level' },
    { value: 'mid', label: 'Mid-Level' },
    { value: 'senior', label: 'Senior' },
    { value: 'expert', label: 'Expert/Principal' },
];

const companySizeOptions = [
    { value: 'solo', label: 'Freelancer/Solo' },
    { value: 'small', label: 'Small (2-10 people)' },
    { value: 'medium', label: 'Medium (11-100 people)' },
    { value: 'large', label: 'Large (100-1000 people)' },
    { value: 'enterprise', label: 'Enterprise (1000+ people)' },
];

const form = useForm({
    jobTitle: props.professionalData.jobTitle || '',
    industry: props.professionalData.industry || '',
    experienceLevel: props.professionalData.experienceLevel || '',
    companySize: props.professionalData.companySize || '',
});

watch(
    () => form.recentlySuccessful,
    (value) => {
        if (value) {
            success('Professional information updated successfully');
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
    form.patch(route('profile.professional.update'), {
        preserveScroll: true,
    });
};

const hasProfessionalData = computed(() => {
    return !!(
        props.professionalData.jobTitle ||
        props.professionalData.industry ||
        props.professionalData.experienceLevel ||
        props.professionalData.companySize
    );
});

const { confirm } = useAlert();

const clearProfessional = async () => {
    const confirmed = await confirm(
        'Are you sure you want to clear all professional information?',
        'Clear Professional Context',
        { confirmButtonStyle: 'danger', confirmText: 'Clear' },
    );

    if (confirmed) {
        const clearForm = useForm({});
        clearForm.delete(route('profile.professional.clear'), {
            preserveScroll: true,
            onSuccess: () => {
                success('Professional information cleared successfully');
                // Clear the form fields
                form.jobTitle = '';
                form.industry = '';
                form.experienceLevel = '';
                form.companySize = '';
            },
            onError: () => {
                error('Failed to clear professional information');
            },
        });
    }
};
</script>

<template>
    <CollapsibleSection
        title="Professional Context"
        subtitle="Tell us about your professional background to help optimise prompts for your role."
        data-testid="professional"
        icon="building-office-2"
    >
        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <!-- Job Title -->
                <FormInput
                    id="job-title"
                    v-model="form.jobTitle"
                    label="Job Title"
                    placeholder="e.g., Software Engineer, Product Manager"
                    :error="form.errors.jobTitle"
                    help-text="Your current or primary job title"
                />

                <!-- Industry -->
                <FormInput
                    id="industry"
                    v-model="form.industry"
                    label="Industry"
                    placeholder="e.g., Technology"
                    :error="form.errors.industry"
                    help-text="Your industry or sector"
                />

                <!-- Experience Level -->
                <FormSelect
                    id="experience-level"
                    v-model="form.experienceLevel"
                    label="Experience Level"
                    :options="experienceLevelOptions"
                    :error="form.errors.experienceLevel"
                    placeholder="Select your level"
                    show-placeholder
                />

                <!-- Company Size -->
                <FormSelect
                    id="company-size"
                    v-model="form.companySize"
                    label="Company Size"
                    :options="companySizeOptions"
                    :error="form.errors.companySize"
                    placeholder="Select company size"
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
                    Save Professional Context
                </ButtonPrimary>

                <ButtonTrash
                    v-if="hasProfessionalData"
                    id="clear-professional-form"
                    label="Clear"
                    @clear="clearProfessional"
                />
            </div>
        </form>
    </CollapsibleSection>
</template>
