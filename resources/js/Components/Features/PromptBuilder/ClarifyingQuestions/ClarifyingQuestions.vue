<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import ButtonText from '@/Components/Base/Button/ButtonText.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import VisitorLimitModal from '@/Components/Common/VisitorLimitModal.vue';
import QuestionAnsweringForm from '@/Components/Features/PromptBuilder/Forms/QuestionAnsweringForm.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import { analyticsService } from '@/services/analytics';
import type { PromptRunResource } from '@/Types';
import type { ClarifyingQuestion } from '@/Types/models/ClarifyingQuestion';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, inject, nextTick, ref, watch, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import AnsweredList from './AnsweredList.vue';
import BulkQuestions from './BulkQuestions.vue';

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
const { t } = useI18n({ useScope: 'global' });
const { countryRoute } = useCountryRoute();

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

// ClarifyingQuestions are already sorted by the backend (required first)
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
const showQuestionRationale = ref(false);
const rationaleContainerRef = ref<HTMLDivElement | null>(null);
const rationaleMaxHeight = ref('0px');
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

// Watch for rationale visibility changes and measure height
watch(showQuestionRationale, async () => {
    await nextTick();
    if (rationaleContainerRef.value) {
        if (showQuestionRationale.value) {
            rationaleMaxHeight.value =
                rationaleContainerRef.value.scrollHeight + 'px';
        } else {
            rationaleMaxHeight.value = '0px';
        }
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
            countryRoute('prompt-builder.answer', {
                promptRun: props.promptRun.id,
            }),
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

        // Track analytics for question answered
        analyticsService.track({
            name: 'question_answered',
            properties: {
                question_index: questionIndex,
                question_id:
                    questions.value[questionIndex]?.id ?? `Q${questionIndex}`,
                answer_length: value?.length ?? 0,
                prompt_run_id: props.promptRun.id,
                total_questions: questions.value.length,
                answered_count: answers.value.filter((a) => a !== null).length,
            },
        });

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
            t('promptBuilder.components.clarifyingQuestions.errors.save');
        throw error;
    } finally {
        isSubmitting.value = false;
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
        await axios.post(
            countryRoute('prompt-builder.generate', {
                promptRun: props.promptRun.id,
            }),
            {
                question_answers: payload,
            },
        );

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
            t('promptBuilder.components.clarifyingQuestions.errors.submit');
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
        countryRoute('prompt-builder.create-child-from-answers', {
            parentPromptRun: props.promptRun.id,
        }),
        { clarifying_answers: payload },
        {
            onSuccess: () => {
                window.scrollTo(0, 0);
            },
            onError: () => {
                submitError.value = t(
                    'promptBuilder.components.clarifyingQuestions.errors.edit',
                );
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

// State machine: Determine the current view mode based on workflow stage and UI state
type ViewMode = 'answering' | 'reviewing' | 'editing' | 'generating';

const viewMode = computed<ViewMode>(() => {
    // No questions = nothing to show
    if (!hasQuestions.value) return 'answering';

    const stage = props.promptRun.workflowStage;

    // Stage 2 (generation workflow) - answers have been submitted
    if (stage.startsWith('2_')) {
        if (stage === '2_processing') return 'generating';
        if (isEditingAnswers.value) return 'editing';
        return 'reviewing';
    }

    // Stage 1 (analysis workflow) or earlier - user is answering questions
    return 'answering';
});

// Derived computed properties based on viewMode
const shouldShowQuestionForm = computed(() => {
    return viewMode.value === 'answering' && !showAllQuestions.value;
});

const shouldShowBulkQuestions = computed(() => {
    return (
        (viewMode.value === 'answering' && showAllQuestions.value) ||
        viewMode.value === 'editing'
    );
});

const shouldShowAnsweredList = computed(() => {
    return viewMode.value === 'reviewing';
});

// Focus the first textarea - useful when switching to this tab
const focus = async () => {
    await nextTick();
    if (shouldShowQuestionForm.value) {
        questionFormRef.value?.focus();
    } else if (shouldShowBulkQuestions.value) {
        bulkQuestionsRef.value?.focusFirstTextarea();
    }
};

// Expose focus method for parent components
defineExpose({ focus });

const bulkSubmitLabel = computed(() =>
    viewMode.value === 'editing'
        ? t('promptBuilder.components.clarifyingQuestions.submitEdited')
        : t('promptBuilder.components.clarifyingQuestions.submitAll'),
);

const hasOptionalQuestions = computed(() => optionalQuestions.value.length > 0);

const optionalQuestionsLabel = computed(() => {
    const count = optionalQuestions.value.length;
    return count === 1
        ? t('promptBuilder.components.clarifyingQuestions.optionalQuestion', {
              count,
          })
        : t('promptBuilder.components.clarifyingQuestions.optionalQuestions', {
              count,
          });
});

const backLabel = computed(() =>
    viewMode.value === 'editing'
        ? t('promptBuilder.components.clarifyingQuestions.cancelEdit')
        : undefined,
);
</script>

<template>
    <VisitorLimitModal
        :show="showVisitorLimitModal"
        @close="showVisitorLimitModal = false"
        @register="handleRegister"
    />

    <Card class="space-y-6" data-testid="clarifying-questions">
        <div
            class="mb-2 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-start"
        >
            <h2
                class="sr-only text-lg font-semibold text-indigo-900 sm:not-sr-only"
            >
                {{ $t('promptBuilder.components.clarifyingQuestions.title') }}
            </h2>

            <div
                v-if="hasQuestions"
                class="flex w-full flex-col gap-2 sm:w-auto sm:items-end sm:text-right"
            >
                <!-- Show "Show all questions" toggle when in answering mode -->
                <div
                    v-if="viewMode === 'answering'"
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
                                ? $t(
                                      'promptBuilder.components.clarifyingQuestions.singleQuestion',
                                  )
                                : $t(
                                      'promptBuilder.components.clarifyingQuestions.allQuestions',
                                  )
                        }}
                    </ButtonSecondary>
                </div>

                <!-- Show edit controls when reviewing answers -->
                <div
                    v-if="viewMode === 'reviewing' || viewMode === 'editing'"
                    class="w-full sm:w-auto"
                >
                    <ButtonSecondary
                        v-if="viewMode === 'reviewing'"
                        ref="editAnswersButtonRef"
                        type="button"
                        :disabled="isSubmitting"
                        class="w-full sm:w-auto"
                        @click="startEditingAnswers"
                    >
                        <DynamicIcon name="edit" class="mr-2 -ml-1 h-4 w-4" />
                        {{
                            $t(
                                'promptBuilder.components.clarifyingQuestions.edit',
                            )
                        }}
                    </ButtonSecondary>
                    <div
                        v-else-if="viewMode === 'editing'"
                        class="flex flex-col gap-2 sm:flex-row sm:items-center"
                    >
                        <ButtonSecondary
                            type="button"
                            :disabled="isSubmitting"
                            class="w-full sm:w-auto"
                            @click="cancelEditingAnswers"
                        >
                            {{ $t('common.buttons.cancel') }}
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
                            {{
                                $t(
                                    'promptBuilder.components.clarifyingQuestions.submitEdited',
                                )
                            }}
                        </ButtonPrimary>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="promptRun.questionRationale && uiComplexity === 'advanced'">
            <ButtonText
                id="show-question-rationale"
                type="button"
                class="-ml-1 flex items-center gap-2 text-sm font-medium text-indigo-700 no-underline! hover:text-indigo-900"
                @click="showQuestionRationale = !showQuestionRationale"
            >
                {{
                    showQuestionRationale
                        ? $t(
                              'promptBuilder.components.clarifyingQuestions.rationale.hide',
                          )
                        : $t(
                              'promptBuilder.components.clarifyingQuestions.rationale.show',
                          )
                }}
                <DynamicIcon
                    :name="
                        showQuestionRationale ? 'chevron-down' : 'chevron-right'
                    "
                    class="h-4 w-4"
                />
            </ButtonText>
            <div
                ref="rationaleContainerRef"
                class="overflow-hidden transition-all duration-300 ease-in-out"
                :style="{ maxHeight: rationaleMaxHeight }"
            >
                <p class="pt-2 text-sm text-indigo-800">
                    {{ promptRun.questionRationale }}
                </p>
            </div>
        </div>

        <!-- Reviewing mode: Show answered list -->
        <AnsweredList
            v-if="shouldShowAnsweredList"
            :questions="allQuestions"
            :answers="promptRun.clarifyingAnswers"
        />

        <!-- Answering mode: One-at-a-time Question Form -->
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
            @go-back="goBack"
            @clear="clearCurrentAnswer"
            @toggle-show-all="showAllQuestions = !showAllQuestions"
        />

        <!-- Answering mode (bulk) or Editing mode: Bulk ClarifyingQuestions -->
        <BulkQuestions
            v-if="shouldShowBulkQuestions"
            ref="bulkQuestionsRef"
            :questions="allQuestions"
            :answers="answers"
            :saved-answers="
                allQuestions.map((_, idx) => {
                    const val = (promptRun.clarifyingAnswers ?? [])[idx];
                    return normalizeAnswer(
                        typeof val === 'string' ? val : (val ?? null),
                    );
                })
            "
            :has-optional-questions="hasOptionalQuestions"
            :optional-questions-label="optionalQuestionsLabel"
            :is-submitting="isSubmitting"
            :submit-label="bulkSubmitLabel"
            :show-back="viewMode === 'answering'"
            :back-label="backLabel"
            :is-edit-mode="viewMode === 'editing'"
            @update:answer="
                (index: number, value: string) => (answers[index] = value)
            "
            @save-answer="
                (index: number, value: string) => saveAnswer(index, value)
            "
            @submit-all="
                viewMode === 'editing'
                    ? submitEditedAnswers()
                    : submitAllAnswers()
            "
            @back="
                viewMode === 'editing'
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
