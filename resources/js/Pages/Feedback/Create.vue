<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormCheckboxGroup from '@/Components/Base/Form/FormCheckboxGroup.vue';
import FormTextarea from '@/Components/Base/Form/FormTextarea.vue';
import LikertScale from '@/Components/Base/LikertScale.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    experienceLevel: null,
    usefulness: null,
    usageIntent: null,
    suggestions: '',
    desiredFeatures: [] as string[],
    desiredFeaturesOther: '',
});

const hasErrors = computed(() => Object.keys(form.errors).length > 0);

const featureOptions = [
    {
        value: 'document-upload',
        label: 'Upload documents and/or images',
        description:
            'Upload files and images to provide context for prompt generation, e.g., analyse documents or refine prompts based on visual content',
    },
    {
        value: 'templates',
        label: 'Prompt templates library',
        description:
            'Pre-built templates for common use cases (content writing, data analysis, code review, etc.)',
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
    form.post(route('feedback.store'), {
        onSuccess: () => {
            // Redirect will be handled by controller
        },
    });
};
</script>

<template>
    <Head title="Feedback" />

    <HeaderPage title="Feedback" />

    <ContainerPage>
        <Card>
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900">
                    We'd love to hear from you!
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Your feedback will help us decide whether to improve this
                    project - or to abandon it!<br />
                    Please be honest.
                </p>
            </div>

            <!-- Error Alert -->
            <div
                v-if="hasErrors"
                class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4"
                role="alert"
            >
                <div class="flex items-start gap-3">
                    <DynamicIcon
                        name="exclamation-circle"
                        class="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                    />
                    <div class="flex-1">
                        <h3 class="font-semibold text-red-900">
                            Please correct the errors below
                        </h3>
                        <ul
                            class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700"
                        >
                            <li
                                v-if="form.errors.experienceLevel"
                                key="experience-level"
                            >
                                <strong>Experience level:</strong>
                                {{ form.errors.experienceLevel }}
                            </li>
                            <li v-if="form.errors.usefulness" key="usefulness">
                                <strong>Usefulness:</strong>
                                {{ form.errors.usefulness }}
                            </li>
                            <li
                                v-if="form.errors.usageIntent"
                                key="usage-intent"
                            >
                                <strong>Usage likelihood:</strong>
                                {{ form.errors.usageIntent }}
                            </li>
                            <li
                                v-if="form.errors.suggestions"
                                key="suggestions"
                            >
                                <strong>Suggestions:</strong>
                                {{ form.errors.suggestions }}
                            </li>
                            <li
                                v-if="form.errors.desiredFeatures"
                                key="desired-features"
                            >
                                <strong>Features:</strong>
                                {{ form.errors.desiredFeatures }}
                            </li>
                            <li
                                v-if="form.errors.desiredFeaturesOther"
                                key="desired-features-other"
                            >
                                <strong>Feature description:</strong>
                                {{ form.errors.desiredFeaturesOther }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <form class="space-y-8" @submit.prevent="submit">
                <!-- Question 1: Experience Level -->
                <div
                    class="mt-8 rounded-lg p-4 transition"
                    :class="
                        form.errors.experienceLevel
                            ? 'border-2 border-red-300 bg-red-50'
                            : ''
                    "
                >
                    <label class="mb-4 block text-sm font-medium text-gray-900">
                        1. How experienced are you with AI tools like ChatGPT or
                        Claude?
                    </label>
                    <LikertScale
                        v-model="form.experienceLevel"
                        left-label="Novice"
                        right-label="Experienced"
                        :disabled="form.processing"
                    />
                    <p
                        v-if="form.errors.experienceLevel"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.experienceLevel }}
                    </p>
                </div>

                <!-- Question 2: Usefulness -->
                <div
                    class="mt-16 rounded-lg p-4 transition"
                    :class="
                        form.errors.usefulness
                            ? 'border-2 border-red-300 bg-red-50'
                            : ''
                    "
                >
                    <label class="mb-4 block text-sm font-medium text-gray-900">
                        2. How useful was the app for improving your prompt?
                    </label>
                    <LikertScale
                        v-model="form.usefulness"
                        left-label="Not useful"
                        right-label="Extremely useful"
                        :disabled="form.processing"
                    />
                    <p
                        v-if="form.errors.usefulness"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.usefulness }}
                    </p>
                </div>

                <!-- Question 3: Usage Intent -->
                <div
                    class="mt-16 rounded-lg p-4 transition"
                    :class="
                        form.errors.usageIntent
                            ? 'border-2 border-red-300 bg-red-50'
                            : ''
                    "
                >
                    <label class="mb-4 block text-sm font-medium text-gray-900">
                        3. How likely are you to use this app the next time you
                        need to work with an AI assistant?
                    </label>
                    <LikertScale
                        v-model="form.usageIntent"
                        left-label="Very unlikely"
                        right-label="Very likely"
                        :disabled="form.processing"
                    />
                    <p
                        v-if="form.errors.usageIntent"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.usageIntent }}
                    </p>
                </div>

                <!-- Question 4: Suggestions -->
                <div class="mt-16">
                    <FormTextarea
                        id="suggestions"
                        v-model="form.suggestions"
                        label="4. What's one thing you'd change or improve about the app?"
                        :error="form.errors.suggestions"
                        :disabled="form.processing"
                        placeholder="Mention any steps you found confusing or features you'd like to see next."
                        :rows="5"
                    />
                </div>

                <!-- Question 5: Desired Features -->
                <div
                    class="mt-16 rounded-lg p-4 transition"
                    :class="
                        form.errors.desiredFeatures ||
                        form.errors.desiredFeaturesOther
                            ? 'border-2 border-red-300 bg-red-50'
                            : ''
                    "
                >
                    <label class="mb-4 block text-sm font-medium text-gray-900">
                        5. Which features would you most want to see added next?
                        <span class="font-normal text-gray-600"
                            >(Select at least one)</span
                        >
                    </label>
                    <p class="mb-3 text-xs text-gray-600">
                        If you select "Other", please describe the feature you'd
                        like to see.
                    </p>
                    <FormCheckboxGroup
                        v-model="form.desiredFeatures"
                        v-model:other-value="form.desiredFeaturesOther"
                        :options="featureOptions"
                        :disabled="form.processing"
                        :error="form.errors.desiredFeatures"
                    />
                    <p
                        v-if="form.errors.desiredFeaturesOther"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.desiredFeaturesOther }}
                    </p>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end gap-3">
                    <ButtonSecondary
                        type="button"
                        :disabled="form.processing"
                        @click="$inertia.visit(route('prompt-builder.history'))"
                    >
                        Cancel
                    </ButtonSecondary>
                    <ButtonPrimary
                        type="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        Submit Feedback
                    </ButtonPrimary>
                </div>
            </form>
        </Card>
    </ContainerPage>
</template>
