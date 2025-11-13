<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import Card from '@/Components/Card.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import FormCheckboxGroup from '@/Components/FormCheckboxGroup.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import LikertScale from '@/Components/LikertScale.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

interface FeedbackData {
    experienceLevel: number;
    usefulness: number;
    recommendationLikelihood: number;
    suggestions: string | null;
    desiredFeatures: string[];
    desiredFeaturesOther: string | null;
    createdAt: string;
    updatedAt: string;
}

interface Props {
    feedback: FeedbackData;
}

const isEditing = ref(false);

const form = useForm({
    experienceLevel: props.feedback.experienceLevel,
    usefulness: props.feedback.usefulness,
    recommendationLikelihood: props.feedback.recommendationLikelihood,
    suggestions: props.feedback.suggestions || '',
    desiredFeatures: props.feedback.desiredFeatures || [],
    desiredFeaturesOther: props.feedback.desiredFeaturesOther || '',
});

const featureOptions = [
    {
        value: 'templates',
        label: 'Prompt templates library',
        description:
            'Pre-built templates for common use cases (code review, content writing, data analysis, etc.)',
    },
    {
        value: 'compare',
        label: 'Compare prompt versions side-by-side',
        description:
            'Visual comparison tool showing differences between prompt versions and their effectiveness',
    },
    {
        value: 'api-integration',
        label: 'Integration with ChatGPT/Claude APIs',
        description:
            'Test prompts directly with AI models, see real responses, and iterate within the app',
    },
    {
        value: 'collaboration',
        label: 'Team collaboration features',
        description:
            'Share prompts within your organisation, add comments, track versions, and manage permissions',
    },
    {
        value: 'model-specific',
        label: 'AI model-specific optimisation',
        description:
            'Tailor prompts for specific models (GPT-4, Claude, Gemini) with their unique formatting and preferences',
    },
    {
        value: 'other',
        label: 'Other',
        description: "Something else? Let us know what you'd like to see!",
    },
];

const submit = () => {
    form.put(route('feedback.update'), {
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Your Feedback" />

    <HeaderPage title="Your Feedback" />

    <ContainerPage>
        <Card>
            <div class="mb-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Thank you for your feedback!
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            You can update your responses at any time.
                        </p>
                    </div>
                    <ButtonSecondary
                        v-if="!isEditing"
                        type="button"
                        @click="isEditing = true"
                    >
                        Edit Responses
                    </ButtonSecondary>
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    Last updated: {{ formatDate(feedback.updatedAt) }}
                </p>
            </div>

            <form class="space-y-8" @submit.prevent="submit">
                <!-- Question 1: Experience Level -->
                <div class="mt-8">
                    <label class="mb-4 block text-sm font-medium text-gray-900">
                        1. How experienced are you with AI tools like ChatGPT or
                        Claude?
                    </label>
                    <LikertScale
                        v-model="form.experienceLevel"
                        left-label="Novice"
                        right-label="Experienced"
                        :disabled="!isEditing || form.processing"
                    />
                    <p
                        v-if="form.errors.experienceLevel"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.experienceLevel }}
                    </p>
                </div>

                <!-- Question 2: Usefulness -->
                <div class="mt-16">
                    <label class="mb-4 block text-sm font-medium text-gray-900">
                        2. How useful was the app for improving your prompt?
                    </label>
                    <LikertScale
                        v-model="form.usefulness"
                        left-label="Not useful"
                        right-label="Extremely useful"
                        :disabled="!isEditing || form.processing"
                    />
                    <p
                        v-if="form.errors.usefulness"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.usefulness }}
                    </p>
                </div>

                <!-- Question 3: Recommendation Likelihood -->
                <div class="mt-16">
                    <label class="mb-4 block text-sm font-medium text-gray-900">
                        3. How likely are you to recommend this app to a friend
                        or colleague?
                    </label>
                    <LikertScale
                        v-model="form.recommendationLikelihood"
                        left-label="Very unlikely"
                        right-label="Very likely"
                        :disabled="!isEditing || form.processing"
                    />
                    <p
                        v-if="form.errors.recommendationLikelihood"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.recommendationLikelihood }}
                    </p>
                </div>

                <!-- Question 4: Suggestions -->
                <div class="mt-16">
                    <FormTextarea
                        id="suggestions"
                        v-model="form.suggestions"
                        label="4. What's one thing you'd change or improve about the app?"
                        :error="form.errors.suggestions"
                        :disabled="!isEditing || form.processing"
                        placeholder="Mention any steps you found confusing or features you'd like to see next."
                        :rows="5"
                    />
                </div>

                <!-- Question 5: Desired Features -->
                <div class="mt-16">
                    <label class="mb-4 block text-sm font-medium text-gray-900">
                        5. Which features would you most want to see added next?
                        <span class="font-normal text-gray-600"
                            >(Select all that apply)</span
                        >
                    </label>
                    <FormCheckboxGroup
                        v-model="form.desiredFeatures"
                        v-model:other-value="form.desiredFeaturesOther"
                        :options="featureOptions"
                        :disabled="!isEditing || form.processing"
                        :error="form.errors.desiredFeatures"
                    />
                </div>

                <!-- Submit Buttons -->
                <div
                    v-if="isEditing"
                    class="flex items-center justify-end gap-3"
                >
                    <ButtonSecondary
                        type="button"
                        :disabled="form.processing"
                        @click="
                            () => {
                                isEditing = false;
                                form.reset();
                                form.clearErrors();
                            }
                        "
                    >
                        Cancel
                    </ButtonSecondary>
                    <ButtonPrimary
                        type="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        Update Feedback
                    </ButtonPrimary>
                </div>
            </form>
        </Card>
    </ContainerPage>
</template>
