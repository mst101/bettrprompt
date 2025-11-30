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
import { computed, inject, nextTick, ref, watch } from 'vue';

interface Props {
    promptRun: PromptRunResource;
    visitorHasCompletedPrompts?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    visitorHasCompletedPrompts: false,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const openRegisterModal = inject<() => void>('openRegisterModal');
const showVisitorLimitModal = ref(false);

const questions = computed<ClarifyingQuestion[]>(() => {
    const raw =
        (props.promptRun.frameworkQuestions as ClarifyingQuestion[] | null) ??
        [];

    return raw
        .filter(Boolean)
        .map((item) =>
            typeof item === 'string' ? { question: item } : { ...item },
        );
});

const answers = ref<(string | null)[]>([]);
const currentIndex = ref(0);
const currentAnswerDraft = ref('');
const showAllQuestions = ref(false);
const isEditingAnswers = ref(false);
const isSubmitting = ref(false);
const submitError = ref<string | null>(null);
const questionFormRef = ref<InstanceType<typeof QuestionAnsweringForm> | null>(
    null,
);

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
    answers.value = questions.value.map((_, idx) => {
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
                  Math.max(questions.value.length - 1, 0),
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

const goBack = () => {
    if (currentIndex.value > 0) {
        currentIndex.value -= 1;
        currentAnswerDraft.value = answers.value[currentIndex.value] ?? '';
    }
};

const goNext = () => {
    if (currentIndex.value < questions.value.length - 1) {
        currentIndex.value += 1;
        currentAnswerDraft.value = answers.value[currentIndex.value] ?? '';
    }
};

const saveAnswer = async (index: number, value: string | null) => {
    isSubmitting.value = true;
    submitError.value = null;

    try {
        const response = await axios.post(
            route('prompt-builder.answer', props.promptRun.id),
            {
                question_index: index,
                answer: value,
            },
        );

        const updated =
            (response.data?.clarifying_answers as (string | null)[]) ?? [];
        answers.value = questions.value.map(
            (_, idx) => normalizeAnswer(updated[idx]) ?? null,
        );

        // Update draft to match saved answer
        currentAnswerDraft.value = answers.value[currentIndex.value] ?? '';
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
    goNext();
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

const startEditingAnswers = () => {
    isEditingAnswers.value = true;
    showAllQuestions.value = true;
};

const cancelEditingAnswers = () => {
    isEditingAnswers.value = false;
    showAllQuestions.value = false;
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
            preserveScroll: true,
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

const hasSubmittedAnswers = computed(() => {
    if (!questions.value.length) return false;
    if (answers.value.length < questions.value.length) return false;
    return answers.value.every(
        (answer) => answer !== null && answer !== undefined,
    );
});

const shouldShowQuestionForm = computed(
    () =>
        hasQuestions.value &&
        currentQuestion.value &&
        !showAllQuestions.value &&
        !hasSubmittedAnswers.value,
);

const bulkSubmitLabel = computed(() =>
    isEditingAnswers.value
        ? 'Optimise Prompt with Edited Answers'
        : 'Submit All Answers',
);
</script>

<template>
    <VisitorLimitModal
        :show="showVisitorLimitModal"
        @close="showVisitorLimitModal = false"
        @register="handleRegister"
    />

    <Card class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <h2 class="text-lg font-semibold text-gray-900">
                Clarifying Questions
            </h2>

            <div
                v-if="hasQuestions"
                class="flex flex-col items-end gap-2 text-right"
            >
                <ButtonSecondary
                    v-if="!hasSubmittedAnswers"
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
                <div v-if="hasSubmittedAnswers">
                    <ButtonSecondary
                        v-if="!isEditingAnswers"
                        type="button"
                        :disabled="isSubmitting"
                        @click="startEditingAnswers"
                    >
                        Edit Answers
                    </ButtonSecondary>
                    <div v-else class="flex items-center gap-2">
                        <ButtonSecondary
                            type="button"
                            :disabled="isSubmitting"
                            @click="cancelEditingAnswers"
                        >
                            Cancel
                        </ButtonSecondary>
                        <ButtonPrimary
                            type="button"
                            :disabled="isSubmitting"
                            :loading="isSubmitting"
                            @click="submitEditedAnswers"
                        >
                            Optimise Prompt with Edited Answers
                        </ButtonPrimary>
                    </div>
                </div>
            </div>
        </div>

        <p v-if="promptRun.questionRationale" class="text-sm text-gray-600">
            {{ promptRun.questionRationale }}
        </p>

        <AnsweredList
            v-if="hasSubmittedAnswers && !isEditingAnswers"
            :questions="questions"
            :answers="promptRun.clarifyingAnswers"
        />

        <!-- One-at-a-time Question Form -->
        <QuestionAnsweringForm
            v-if="shouldShowQuestionForm"
            ref="questionFormRef"
            :key="`question-${currentIndex}`"
            v-model:answer="currentAnswer"
            :question="currentQuestion.question"
            :current-question-number="currentIndex + 1"
            :total-questions="questions.length"
            :is-submitting="isSubmitting"
            :can-go-back="currentIndex > 0"
            :show-all="showAllQuestions"
            @submit="submitAnswer"
            @skip="skipQuestion"
            @go-back="goBack"
            @clear="clearCurrentAnswer"
            @toggle-show-all="showAllQuestions = !showAllQuestions"
        />

        <BulkQuestions
            v-else-if="hasQuestions && (showAllQuestions || isEditingAnswers)"
            :questions="questions"
            :answers="answers"
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

        <p v-else class="text-sm text-gray-600">
            No clarifying questions were generated for this prompt.
        </p>

        <div
            v-if="submitError"
            class="rounded-md border border-red-100 bg-red-50 p-3 text-sm text-red-700"
        >
            {{ submitError }}
        </div>
    </Card>
</template>
