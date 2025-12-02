<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import Card from '@/Components/Card.vue';
import type {
    PreAnalysisQuestion,
    PromptRunResource,
} from '@/types/resources/PromptRunResource';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    promptRun: PromptRunResource;
}

const props = defineProps<Props>();

const questions = computed<PreAnalysisQuestion[]>(
    () => props.promptRun.preAnalysisQuestions ?? [],
);

// Initialize answers object with empty strings for each question
const answers = ref<Record<string, string>>({});

const isSubmitting = ref(false);
const submitError = ref<string | null>(null);

// Check if all questions have been answered
const allAnswered = computed(() => {
    return questions.value.every((question) => {
        const answer = answers.value[question.id];
        return answer && answer.trim().length > 0;
    });
});

const submitAnswers = () => {
    if (!allAnswered.value || isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    submitError.value = null;

    router.post(
        route('prompt-builder.pre-analysis-answers', props.promptRun.id),
        { answers: answers.value },
        {
            preserveScroll: true,
            onError: (errors) => {
                submitError.value =
                    (errors as Record<string, string>)?.message ||
                    'Failed to submit answers. Please try again.';
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};
</script>

<template>
    <Card class="space-y-6">
        <div>
            <h2 class="mb-2 text-lg font-semibold text-indigo-900">
                Quick Clarification
            </h2>
            <p
                v-if="promptRun.preAnalysisReasoning"
                class="text-sm text-indigo-600"
            >
                {{ promptRun.preAnalysisReasoning }}
            </p>
        </div>

        <div v-for="question in questions" :key="question.id" class="space-y-3">
            <label class="block text-sm font-medium text-indigo-900">
                {{ question.question }}
            </label>

            <!-- Multiple choice questions -->
            <div v-if="question.type === 'choice'" class="space-y-2">
                <label
                    v-for="option in question.options"
                    :key="option.value"
                    class="flex cursor-pointer items-start rounded-lg border border-indigo-200 p-3 transition-colors hover:bg-indigo-50"
                    :class="{
                        'border-indigo-500 bg-indigo-50':
                            answers[question.id] === option.value,
                    }"
                >
                    <input
                        v-model="answers[question.id]"
                        type="radio"
                        :name="question.id"
                        :value="option.value"
                        class="mt-0.5 h-4 w-4 border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span class="ml-3 text-sm text-indigo-900">{{
                        option.label
                    }}</span>
                </label>
            </div>

            <!-- Yes/No questions -->
            <div v-else-if="question.type === 'yes_no'" class="space-y-2">
                <label
                    v-for="option in question.options"
                    :key="option.value"
                    class="flex cursor-pointer items-start rounded-lg border border-indigo-200 p-3 transition-colors hover:bg-indigo-50"
                    :class="{
                        'border-indigo-500 bg-indigo-50':
                            answers[question.id] === option.value,
                    }"
                >
                    <input
                        v-model="answers[question.id]"
                        type="radio"
                        :name="question.id"
                        :value="option.value"
                        class="mt-0.5 h-4 w-4 border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span class="ml-3 text-sm text-indigo-900">{{
                        option.label
                    }}</span>
                </label>
            </div>

            <!-- Text input questions -->
            <div v-else-if="question.type === 'text'">
                <textarea
                    v-model="answers[question.id]"
                    rows="3"
                    class="block w-full rounded-lg border border-indigo-200 px-3 py-2 text-sm text-indigo-900 placeholder-indigo-300 focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Type your answer here..."
                ></textarea>
            </div>
        </div>

        <div
            v-if="submitError"
            class="rounded-md border border-red-100 bg-red-50 p-3 text-sm text-red-700"
        >
            {{ submitError }}
        </div>

        <div class="flex justify-end">
            <ButtonPrimary
                type="button"
                :disabled="!allAnswered || isSubmitting"
                :loading="isSubmitting"
                @click="submitAnswers"
            >
                Continue
            </ButtonPrimary>
        </div>
    </Card>
</template>
