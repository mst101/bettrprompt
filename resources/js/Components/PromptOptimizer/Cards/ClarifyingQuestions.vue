<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ClarifyingAnswersEdit from '@/Components/PromptOptimizer/ClarifyingAnswersEdit.vue';
import QuestionAnsweringForm from '@/Components/PromptOptimizer/QuestionAnsweringForm.vue';
import { usePromptAnswering } from '@/Composables/usePromptAnswering';
import type { PromptRunResource } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Props {
    promptRun: PromptRunResource;
    currentQuestion?: string | null;
    progress?: { answered: number; total: number };
}

const props = defineProps<Props>();

const page = usePage<{
    flash: {
        previous_answer?: string | null;
    };
}>();

// Edit mode state for clarifying answers
const isEditingAnswers = ref(false);

// Initialize form for editing answers (convert nulls to empty strings)
const initialAnswers =
    props.promptRun.clarifyingAnswers?.map((answer) => answer ?? '') ?? [];

const answersEditForm = useForm({
    clarifying_answers: initialAnswers,
});

const startEditingAnswers = () => {
    isEditingAnswers.value = true;
    // Reset form to current values when starting edit
    answersEditForm.clarifying_answers =
        props.promptRun.clarifyingAnswers?.map((answer) => answer ?? '') ?? [];
};

const cancelEditingAnswers = () => {
    isEditingAnswers.value = false;
    answersEditForm.reset();
    answersEditForm.clearErrors();
};

const submitEditedAnswers = () => {
    answersEditForm.post(
        route('prompt-optimizer.create-child-from-answers', {
            parentPromptRun: props.promptRun.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditingAnswers.value = false;
                // Redirect happens automatically via controller
            },
        },
    );
};

// Show all questions mode
const showAllQuestions = ref(false);

const toggleShowAll = () => {
    showAllQuestions.value = !showAllQuestions.value;
};

// Question answering composable with pre-population from parent
const {
    answerForm,
    isSubmitting,
    submitAnswer,
    skipQuestion,
    goBackToPreviousQuestion,
    clearAnswer,
} = usePromptAnswering(props.promptRun.id);

// Get the current answer if it exists (for going back or pre-population)
const getCurrentAnswer = (): string | null => {
    // First check if we have a previousAnswer from going back (via flash)
    const flashPreviousAnswer = page.props.flash.previous_answer;
    if (flashPreviousAnswer !== undefined && flashPreviousAnswer !== null) {
        return flashPreviousAnswer;
    }

    // Otherwise check clarifyingAnswers array
    if (!props.promptRun.clarifyingAnswers || !props.progress) return null;

    // Current question index is progress.answered (0-based)
    const currentIndex = props.progress.answered;
    const answer = props.promptRun.clarifyingAnswers[currentIndex];

    return answer ?? null;
};

// Pre-populate answer if similar question exists in parent
const findSimilarAnswer = (currentQuestion: string): string | null => {
    if (!props.promptRun.parent) return null;

    const parentQuestions = props.promptRun.parent.frameworkQuestions;
    const parentAnswers = props.promptRun.parent.clarifyingAnswers;

    if (!parentQuestions || !parentAnswers) return null;

    // Find exact match or similar question
    const index = parentQuestions.findIndex((q) => q === currentQuestion);
    if (index !== -1 && parentAnswers[index]) {
        return parentAnswers[index];
    }

    return null;
};

// Watch for current question changes and pre-populate if available
watch(
    () => props.currentQuestion,
    (newQuestion) => {
        if (newQuestion) {
            // First check if we have a current answer (e.g., when going back)
            const currentAnswer = getCurrentAnswer();
            if (currentAnswer) {
                answerForm.answer = currentAnswer;
                return;
            }

            // Otherwise, try to find a similar answer from parent
            if (!answerForm.answer) {
                const similarAnswer = findSimilarAnswer(newQuestion);
                if (similarAnswer) {
                    answerForm.answer = similarAnswer;
                }
            }
        }
    },
    { immediate: true },
);

// Reset edit mode when navigating to different prompt run
watch(
    () => props.promptRun.id,
    () => {
        isEditingAnswers.value = false;
        answersEditForm.reset();
        answersEditForm.clearErrors();
    },
);

const isAnsweringQuestions = computed(
    () =>
        props.promptRun.workflowStage === 'framework_selected' ||
        props.promptRun.workflowStage === 'answering_questions',
);

const isCompleted = computed(
    () => props.promptRun.workflowStage === 'completed',
);
</script>

<template>
    <!-- Question Answering Interface (for in-progress runs) -->
    <QuestionAnsweringForm
        v-if="isAnsweringQuestions && currentQuestion && !showAllQuestions"
        v-model:answer="answerForm.answer"
        :question="currentQuestion"
        :current-question-number="progress ? progress.answered + 1 : 0"
        :total-questions="progress ? progress.total : 0"
        :is-submitting="isSubmitting"
        :can-go-back="progress ? progress.answered > 0 : false"
        :has-error="!!answerForm.errors.answer"
        :error-message="answerForm.errors.answer"
        :show-all="showAllQuestions"
        @submit="submitAnswer"
        @skip="skipQuestion"
        @go-back="goBackToPreviousQuestion"
        @clear="clearAnswer"
        @toggle-show-all="toggleShowAll"
    />

    <!-- All Questions View (for completed runs) -->
    <div
        v-else-if="isCompleted"
        class="overflow-hidden rounded-lg bg-white shadow-xs"
    >
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                    Clarifying Questions
                </h3>
                <div v-if="!isEditingAnswers" class="flex items-center gap-2">
                    <ButtonSecondary type="button" @click="startEditingAnswers">
                        Edit Answers
                    </ButtonSecondary>
                </div>
                <div v-else class="flex items-center gap-2">
                    <ButtonSecondary
                        type="button"
                        :disabled="answersEditForm.processing"
                        @click="cancelEditingAnswers"
                    >
                        Cancel
                    </ButtonSecondary>
                    <ButtonPrimary
                        type="button"
                        :loading="answersEditForm.processing"
                        @click="submitEditedAnswers"
                    >
                        Optimise Prompt with Edited Answers
                    </ButtonPrimary>
                </div>
            </div>

            <ClarifyingAnswersEdit
                v-if="isEditingAnswers"
                :prompt-run="promptRun"
                :form="answersEditForm"
            />

            <div v-else class="space-y-3">
                <div
                    v-for="(question, index) in promptRun.frameworkQuestions"
                    :key="index"
                    class="border-b border-gray-200 pb-3 last:border-b-0"
                >
                    <div class="flex items-start">
                        <span
                            class="mt-0.5 mr-2 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-medium text-indigo-800"
                        >
                            {{ index + 1 }}
                        </span>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                {{ question }}
                            </p>
                            <div
                                v-if="
                                    promptRun.clarifyingAnswers &&
                                    promptRun.clarifyingAnswers[index] !==
                                        null &&
                                    promptRun.clarifyingAnswers[index] !==
                                        undefined
                                "
                                class="mt-2 rounded-md bg-gray-50 p-3"
                            >
                                <p
                                    class="text-sm whitespace-break-spaces text-gray-700"
                                >
                                    {{ promptRun.clarifyingAnswers[index] }}
                                </p>
                            </div>
                            <div v-else class="mt-2 rounded-md bg-gray-50 p-3">
                                <p class="text-sm text-gray-500 italic">
                                    [Skipped]
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
