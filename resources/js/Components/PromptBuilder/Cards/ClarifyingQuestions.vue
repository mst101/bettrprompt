<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import Card from '@/Components/Card.vue';
import type { ClarifyingQuestion } from '@/Components/PromptBuilder/Cards/clarifyingQuestion';
import AnsweredList from '@/Components/PromptBuilder/Cards/ClarifyingQuestions/AnsweredList.vue';
import BulkQuestions from '@/Components/PromptBuilder/Cards/ClarifyingQuestions/BulkQuestions.vue';
import QuestionAnsweringForm from '@/Components/PromptBuilder/QuestionAnsweringForm.vue';
import VisitorLimitModal from '@/Components/VisitorLimitModal.vue';
import type { PromptRunResource } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, inject, nextTick, ref, watch, watchEffect } from 'vue';

interface Props {
    promptRun: PromptRunResource;
    uiComplexity?: 'simple' | 'advanced';
    visitorHasCompletedPrompts?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    visitorHasCompletedPrompts: false,
    uiComplexity: 'advanced',
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const openRegisterModal = inject<() => void>('openRegisterModal');
const showVisitorLimitModal = ref(false);

const allQuestions = computed<ClarifyingQuestion[]>(() => {
    const raw =
        (props.promptRun.frameworkQuestions as ClarifyingQuestion[] | null) ??
        [];

    return raw
        .filter(Boolean)
        .map((item) =>
            typeof item === 'string'
                ? { question: item, required: true }
                : { ...item },
        );
});

const optionalQuestions = computed<ClarifyingQuestion[]>(() =>
    allQuestions.value.filter((q) => q.required === false),
);

// Questions are already sorted by the backend (required first)
const questions = computed<ClarifyingQuestion[]>(() => {
    return allQuestions.value;
});

const answers = ref<(string | null)[]>([]);
const currentIndex = ref(0);
const currentAnswerDraft = ref('');
const showAllQuestions = ref(false);
const isEditingAnswers = ref(false);
const isSubmitting = ref(false);
const submitError = ref<string | null>(null);
const shouldFocusBulkQuestions = ref(false);
const shouldFocusEditButton = ref(false);
const questionFormRef = ref<InstanceType<typeof QuestionAnsweringForm> | null>(
    null,
);
const bulkQuestionsRef = ref<InstanceType<typeof BulkQuestions> | null>(null);
const editAnswersButtonRef = ref<InstanceType<typeof ButtonSecondary> | null>(
    null,
);

// Watch for bulk questions ref and focus first textarea when available
watchEffect(() => {
    if (shouldFocusBulkQuestions.value && bulkQuestionsRef.value) {
        bulkQuestionsRef.value.focusFirstTextarea();
        shouldFocusBulkQuestions.value = false;
    }
});

// Watch for edit button ref and focus when available
watchEffect(() => {
    if (shouldFocusEditButton.value && editAnswersButtonRef.value) {
        editAnswersButtonRef.value.focus();
        shouldFocusEditButton.value = false;
    }
});

const hasQuestions = computed(() => questions.value.length > 0);
const currentQuestion = computed(
    () => questions.value[currentIndex.value] ?? null,
);
const atLastQuestion = computed(
    () => currentIndex.value >= questions.value.length - 1,
);

const currentAnswer = computed({
    get: () => currentAnswerDraft.value,
    set: (value: string) => {
        currentAnswerDraft.value = value;
    },
});

const normalizeAnswer = (value: string | null | undefined) => {
    if (value === undefined || value === null) return null;
    const trimmed = value.trim();
    return trimmed.length ? trimmed : null;
};

const hydrateAnswers = () => {
    const existing = props.promptRun.clarifyingAnswers ?? [];
    // Always use allQuestions for the answers array to maintain index consistency
    answers.value = allQuestions.value.map((_, idx) => {
        const value = existing[idx];
        return normalizeAnswer(
            typeof value === 'string' ? value : (value ?? null),
        );
    });

    const nextIndexFromServer =
        props.promptRun.currentQuestionIndex ?? answers.value.length;
    const firstPending = answers.value.findIndex((answer) => answer === null);
    const startIndex =
        firstPending === -1
            ? Math.min(
                  nextIndexFromServer,
                  Math.max(allQuestions.value.length - 1, 0),
              )
            : firstPending;

    currentIndex.value = Math.max(0, startIndex);

    // Sync the draft with the current answer
    currentAnswerDraft.value = answers.value[currentIndex.value] ?? '';

    isEditingAnswers.value = false;
    showAllQuestions.value = false;
    submitError.value = null;
};

watch(
    () => [
        props.promptRun.id,
        props.promptRun.frameworkQuestions,
        props.promptRun.clarifyingAnswers,
        props.promptRun.currentQuestionIndex,
    ],
    hydrateAnswers,
    { immediate: true },
);

// Check if any answers have changed from their original values
const clarifyingAnswersHaveChanged = computed(() => {
    const original =
        (props.promptRun.clarifyingAnswers as (string | null)[]) ?? [];
    return answers.value.some((current, idx) => {
        const originalAnswer = normalizeAnswer(original[idx]);
        return current !== originalAnswer;
    });
});

const goBack = async () => {
    if (currentIndex.value > 0) {
        currentIndex.value -= 1;
        currentAnswerDraft.value = answers.value[currentIndex.value] ?? '';
        // Focus the previous question's textarea
        await nextTick();
        questionFormRef.value?.focus();
    }
};

const goNext = () => {
    if (currentIndex.value < questions.value.length - 1) {
        currentIndex.value += 1;
        currentAnswerDraft.value = answers.value[currentIndex.value] ?? '';
    }
};

const saveAnswer = async (questionIndex: number, value: string | null) => {
    isSubmitting.value = true;
    submitError.value = null;

    try {
        const response = await axios.post(
            route('prompt-builder.answer', props.promptRun.id),
            {
                question_index: questionIndex,
                answer: value,
            },
        );

        const updated =
            (response.data?.clarifying_answers as (string | null)[]) ?? [];
        // Answers array is always in original question order from the server
        answers.value = allQuestions.value.map(
            (_, idx) => normalizeAnswer(updated[idx]) ?? null,
        );

        // Update draft to match saved answer
        currentAnswerDraft.value = answers.value[questionIndex] ?? '';
    } catch (error: unknown) {
        const axiosError = error as {
            response?: { data?: { error?: { message?: string } } };
            message?: string;
        };

        submitError.value =
            axiosError?.response?.data?.error?.message ||
            axiosError?.message ||
            'Failed to save answer. Please try again.';
        throw error;
    } finally {
        isSubmitting.value = false;
    }
};

const skipQuestion = async () => {
    await saveAnswer(currentIndex.value, null);

    if (atLastQuestion.value) {
        // If skipping the last question, submit all answers
        submitAllAnswers();
    } else {
        goNext();
        // Focus the next question's textarea
        await nextTick();
        questionFormRef.value?.focus();
    }
};

const clearCurrentAnswer = () => {
    currentAnswerDraft.value = '';
};

const submitAnswer = async () => {
    const answer = normalizeAnswer(currentAnswer.value);
    await saveAnswer(currentIndex.value, answer);

    if (atLastQuestion.value) {
        submitAllAnswers();
    } else {
        goNext();
        // Focus the next question's textarea
        await nextTick();
        questionFormRef.value?.focus();
    }
};

const submitAllAnswersEarly = async () => {
    // Save current answer first, then submit all answers
    const answer = normalizeAnswer(currentAnswer.value);
    await saveAnswer(currentIndex.value, answer);
    await submitAllAnswers();
};

const startEditingAnswers = () => {
    isEditingAnswers.value = true;
    showAllQuestions.value = true;
    shouldFocusBulkQuestions.value = true;
};

const cancelEditingAnswers = () => {
    isEditingAnswers.value = false;
    showAllQuestions.value = false;
    shouldFocusEditButton.value = true;
};

const submitAllAnswers = async () => {
    if (!questions.value.length) return;

    isSubmitting.value = true;
    submitError.value = null;

    const payload = answers.value.map((value) => normalizeAnswer(value));

    try {
        await axios.post(route('prompt-builder.generate', props.promptRun.id), {
            question_answers: payload,
        });

        window.scrollTo(0, 0);
        router.reload({ only: ['promptRun'] });
    } catch (error: unknown) {
        const axiosError = error as {
            response?: { data?: { error?: { message?: string } } };
            message?: string;
        };

        submitError.value =
            axiosError?.response?.data?.error?.message ||
            axiosError?.message ||
            'Failed to submit answers';
    } finally {
        isSubmitting.value = false;
    }
};

const submitEditedAnswers = () => {
    if (!questions.value.length) return;

    // Check if unregistered visitor has completed prompts
    if (!user.value && props.visitorHasCompletedPrompts) {
        showVisitorLimitModal.value = true;
        return;
    }

    isSubmitting.value = true;
    submitError.value = null;

    const payload = answers.value.map((value) => normalizeAnswer(value));

    router.post(
        route('prompt-builder.create-child-from-answers', {
            parentPromptRun: props.promptRun.id,
        }),
        { clarifying_answers: payload },
        {
            onSuccess: () => {
                window.scrollTo(0, 0);
            },
            onError: () => {
                submitError.value =
                    'Failed to optimise with edited answers. Please try again.';
            },
            onFinish: () => {
                isSubmitting.value = false;
                isEditingAnswers.value = false;
            },
        },
    );
};

const handleRegister = () => {
    showVisitorLimitModal.value = false;
    if (openRegisterModal) {
        openRegisterModal();
    }
};

// Focus the first textarea - useful when switching to this tab
const focus = async () => {
    await nextTick();
    if (shouldShowQuestionForm.value) {
        questionFormRef.value?.focus();
    } else if (showAllQuestions.value || isEditingAnswers.value) {
        bulkQuestionsRef.value?.focusFirstTextarea();
    }
};

// Expose focus method for parent components
defineExpose({ focus });

// Check if we're in the generation workflow stage (2_processing, 2_completed, or 2_failed)
const isInGenerationStage = computed(() => {
    const stage = props.promptRun.workflowStage;
    return (
        stage === '2_processing' ||
        stage === '2_completed' ||
        stage === '2_failed'
    );
});

const hasSubmittedAnswers = computed(() => {
    // If we're in generation stage, answers have definitely been submitted
    if (isInGenerationStage.value) return true;

    // Otherwise check if all answers are filled (including skipped = null)
    if (!questions.value.length) return false;
    if (answers.value.length < questions.value.length) return false;

    // Check if we've addressed all questions (even if some are null/skipped)
    // The key is that the array is complete, not that all values are non-null
    return answers.value.length === questions.value.length;
});

const shouldShowQuestionForm = computed(
    () =>
        hasQuestions.value &&
        currentQuestion.value &&
        !showAllQuestions.value &&
        !hasSubmittedAnswers.value &&
        !isInGenerationStage.value,
);

const bulkSubmitLabel = computed(() =>
    isEditingAnswers.value
        ? 'Optimise Prompt with Edited Answers'
        : 'Submit All Answers',
);

const hasOptionalQuestions = computed(() => optionalQuestions.value.length > 0);

const optionalQuestionsLabel = computed(() => {
    const count = optionalQuestions.value.length;
    return `${count} optional question${count !== 1 ? 's' : ''} <span class="text-xs">(to improve your prompt)</span>`;
});
</script>

<template>
    <VisitorLimitModal
        :show="showVisitorLimitModal"
        @close="showVisitorLimitModal = false"
        @register="handleRegister"
    />

    <Card class="space-y-6">
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-start"
        >
            <h2
                class="sr-only text-lg font-semibold text-indigo-900 sm:not-sr-only"
            >
                Clarifying Questions
            </h2>

            <div
                v-if="hasQuestions"
                class="flex w-full flex-col gap-2 sm:w-auto sm:items-end sm:text-right"
            >
                <div
                    v-if="!hasSubmittedAnswers"
                    class="flex flex-col gap-2 sm:flex-row sm:space-x-2"
                >
                    <ButtonSecondary
                        id="show-all-questions"
                        type="button"
                        :underline="true"
                        @click="showAllQuestions = !showAllQuestions"
                    >
                        {{
                            showAllQuestions
                                ? 'One at a time'
                                : 'Show all questions'
                        }}
                    </ButtonSecondary>
                </div>
                <div v-if="hasSubmittedAnswers" class="w-full sm:w-auto">
                    <ButtonSecondary
                        v-if="!isEditingAnswers"
                        ref="editAnswersButtonRef"
                        type="button"
                        :disabled="isSubmitting"
                        class="w-full sm:w-auto"
                        @click="startEditingAnswers"
                    >
                        Edit Answers
                    </ButtonSecondary>
                    <div
                        v-else
                        class="flex flex-col gap-2 sm:flex-row sm:items-center"
                    >
                        <ButtonSecondary
                            type="button"
                            :disabled="isSubmitting"
                            class="w-full sm:w-auto"
                            @click="cancelEditingAnswers"
                        >
                            Cancel
                        </ButtonSecondary>
                        <ButtonPrimary
                            type="button"
                            :disabled="
                                isSubmitting || !clarifyingAnswersHaveChanged
                            "
                            :loading="isSubmitting"
                            class="w-full sm:w-auto"
                            @click="submitEditedAnswers"
                        >
                            Optimise Prompt with Edited Answers
                        </ButtonPrimary>
                    </div>
                </div>
            </div>
        </div>

        <p
            v-if="promptRun.questionRationale && uiComplexity === 'advanced'"
            class="text-sm text-indigo-800"
        >
            {{ promptRun.questionRationale }}
        </p>

        <AnsweredList
            v-if="isInGenerationStage && !isEditingAnswers"
            :questions="allQuestions"
            :answers="promptRun.clarifyingAnswers"
        />

        <!-- One-at-a-time Question Form -->
        <QuestionAnsweringForm
            v-if="shouldShowQuestionForm"
            ref="questionFormRef"
            :key="`question-${currentIndex}`"
            v-model:answer="currentAnswer"
            :question="currentQuestion"
            :current-question-number="currentIndex + 1"
            :total-questions="questions.length"
            :is-submitting="isSubmitting"
            :can-go-back="currentIndex > 0"
            :show-all="showAllQuestions"
            @submit="submitAnswer"
            @submit-all="submitAllAnswersEarly"
            @skip="skipQuestion"
            @go-back="goBack"
            @clear="clearCurrentAnswer"
            @toggle-show-all="showAllQuestions = !showAllQuestions"
        />

        <BulkQuestions
            v-else-if="hasQuestions && (showAllQuestions || isEditingAnswers)"
            ref="bulkQuestionsRef"
            :questions="allQuestions"
            :answers="answers"
            :has-optional-questions="hasOptionalQuestions"
            :optional-questions-label="optionalQuestionsLabel"
            :is-submitting="isSubmitting"
            :submit-label="bulkSubmitLabel"
            :show-back="!isEditingAnswers"
            :back-label="isEditingAnswers ? 'Cancel edit' : undefined"
            @update:answer="
                (index: number, value: string) => (answers[index] = value)
            "
            @submit-all="
                isEditingAnswers ? submitEditedAnswers() : submitAllAnswers()
            "
            @back="
                isEditingAnswers
                    ? cancelEditingAnswers()
                    : (showAllQuestions = false)
            "
        />

        <div
            v-if="submitError"
            class="rounded-md border border-red-100 bg-red-50 p-3 text-sm text-red-700"
        >
            {{ submitError }}
        </div>
    </Card>
</template>
