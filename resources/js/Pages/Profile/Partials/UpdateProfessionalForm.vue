<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import FormInput from '@/Components/Base/Form/FormInput.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import { useForm } from '@inertiajs/vue3';

interface Props {
    professionalData: {
        jobTitle: string | null;
        industry: string | null;
        experienceLevel: string | null;
        companySize: string | null;
    };
}

const props = defineProps<Props>();

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

const submit = () => {
    form.patch(route('profile.professional.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-indigo-900">
                Professional Context
            </h2>

            <p class="mt-1 text-sm text-indigo-600">
                Tell us about your professional background to help optimise
                prompts for your role.
            </p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
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

            <div class="flex items-center gap-4">
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    Save Professional Context
                </ButtonPrimary>
            </div>
        </form>
    </section>
</template>
