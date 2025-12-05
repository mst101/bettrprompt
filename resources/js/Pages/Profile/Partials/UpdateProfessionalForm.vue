<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
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
    job_title: props.professionalData.jobTitle || '',
    industry: props.professionalData.industry || '',
    experience_level: props.professionalData.experienceLevel || '',
    company_size: props.professionalData.companySize || '',
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
            <h2 class="text-lg font-medium text-gray-900">
                Professional Context
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Tell us about your professional background to help optimise
                prompts for your role.
            </p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <!-- Job Title -->
                <FormInput
                    id="job_title"
                    v-model="form.job_title"
                    label="Job Title"
                    placeholder="e.g., Software Engineer, Product Manager"
                    :error="form.errors.job_title"
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
                    id="experience_level"
                    v-model="form.experience_level"
                    label="Experience Level"
                    :options="experienceLevelOptions"
                    :error="form.errors.experience_level"
                    placeholder="Select your level"
                    show-placeholder
                />

                <!-- Company Size -->
                <FormSelect
                    id="company_size"
                    v-model="form.company_size"
                    label="Company Size"
                    :options="companySizeOptions"
                    :error="form.errors.company_size"
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
