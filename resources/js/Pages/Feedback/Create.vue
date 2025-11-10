<script setup lang="ts">
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import LikertScale from '@/Components/LikertScale.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    experienceLevel: 4,
    usefulness: 5,
    suggestions: '',
});

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

    <header class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Feedback
            </h2>
        </div>
    </header>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <Card>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">
                        We'd love to hear from you!
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Your feedback helps us improve the Prompt Optimiser.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Question 1: Experience Level -->
                    <div class="mt-8">
                        <label
                            class="mb-4 block text-sm font-medium text-gray-900"
                        >
                            1. How experienced are you with AI tools like
                            ChatGPT or Claude?
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
                    <div class="mt-16">
                        <label
                            class="mb-4 block text-sm font-medium text-gray-900"
                        >
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

                    <!-- Question 3: Suggestions -->
                    <div class="mt-16">
                        <FormField
                            id="suggestions"
                            label="3. What's one thing you'd change or improve about the app?"
                            type="textarea"
                            v-model="form.suggestions"
                            :error="form.errors.suggestions"
                            :disabled="form.processing"
                            placeholder="Mention any steps you found confusing or features you'd like to see next."
                            :rows="5"
                        />
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-end gap-3">
                        <SecondaryButton
                            type="button"
                            @click="
                                $inertia.visit(
                                    route('prompt-optimizer.history'),
                                )
                            "
                            :disabled="form.processing"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            :disabled="form.processing"
                        >
                            Submit Feedback
                        </PrimaryButton>
                    </div>
                </form>
            </Card>
        </div>
    </div>
</template>
