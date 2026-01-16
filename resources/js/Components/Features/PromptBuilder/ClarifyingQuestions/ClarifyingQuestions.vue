<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import ButtonText from '@/Components/Base/Button/ButtonText.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import VisitorLimitModal from '@/Components/Common/VisitorLimitModal.vue';
import QuestionAnsweringForm from '@/Components/Features/PromptBuilder/Forms/QuestionAnsweringForm.vue';
import PromptRating from '@/Components/Features/PromptBuilder/PromptRating.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import { analyticsService } from '@/services/analytics';
import type { PromptRunResource } from '@/Types';
import type { ClarifyingQuestion } from '@/Types/models/ClarifyingQuestion';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    computed,
    inject,
    nextTick,
    onBeforeUnmount,
    ref,
    watch,
    watchEffect,
} from 'vue';
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

// Load display mode preference from Inertia props, defaulting to one-at-a-time
const savedDisplayMode =
    (page.props.preferences?.question_display_mode as string | null) ??
    'one-at-a-time';
const showAllQuestions = ref(savedDisplayMode === 'show-all');
const isEditingAnswers = ref(false);
const isSubmitting = ref(false);
const submitError = ref<string | null>(null);
const shouldFocusBulkQuestions = ref(false);
const shouldFocusEditButton = ref(false);
const showQuestionRationale = ref(false);
const isSavingPreference = ref(false);
const rationaleContainerRef = ref<HTMLDivElement | null>(null);
const rationaleMaxHeight = ref('0px');
const questionFormRef = ref<InstanceType<typeof QuestionAnsweringForm> | null>(
    null,
);
const bulkQuestionsRef = ref<InstanceType<typeof BulkQuestions> | null>(null);
const editAnswersButtonRef = ref<InstanceType<typeof ButtonSecondary> | null>(
    null,
);

// Analytics tracking state for timing and display mode
const questionsFirstShownAt = ref<number | null>(null);
const questionShownTimestamps = ref<Map<number, number>>(new Map());
const questionsWithEventsFired = ref<Set<number>>(new Set());

// Check if questions_presented event has already fired for this prompt run in this session
const hasQuestionsPresentedEventFired = (
    promptRunId: string | number,
): boolean => {
    return (
        sessionStorage.getItem(`questions_presented_${promptRunId}`) === 'true'
    );
};

const markQuestionsPresentedEventFired = (
    promptRunId: string | number,
): void => {
    sessionStorage.setItem(`questions_presented_${promptRunId}`, 'true');
};

// Question rating state
const questionRatings = ref<
    Map<number, { rating: number | null; explanation: string | null }>
>(new Map());
const savingQuestionRatings = ref<Set<number>>(new Set());
const savedQuestionRatings = ref<Set<number>>(new Set());
const ratingsWithExplanationSubmitted = ref<Set<number>>(new Set());
const visibleThankYouMessages = ref<Set<number>>(new Set());
const thankYouTimeouts = ref<Map<number, ReturnType<typeof setTimeout>>>(
    new Map(),
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
    // Preserve the user's saved display mode preference
    showAllQuestions.value = savedDisplayMode === 'show-all';
    submitError.value = null;

    // Reset analytics tracking for new prompt run
    questionsWithEventsFired.value.clear();

    // Clear saved ratings for new prompt run
    savedQuestionRatings.value.clear();
    ratingsWithExplanationSubmitted.value.clear();

    // Load existing ratings from backend
    if (props.promptRun.questionRatings) {
        props.promptRun.questionRatings.forEach((savedRating) => {
            const index = savedRating.questionIndex;
            questionRatings.value.set(index, {
                rating: savedRating.rating,
                explanation: savedRating.explanation,
            });
            // Mark as saved (both star and explanation if present)
            savedQuestionRatings.value.add(index);
            // Mark explanation as submitted if it exists
            if (savedRating.explanation) {
                ratingsWithExplanationSubmitted.value.add(index);
            }
        });
    }
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

// Fire questions_presented event once per prompt run per session (persisted across page refreshes)
watch(
    () => questions.value,
    (newQuestions) => {
        // Fire only once per prompt run in this session (sessionStorage persists across refreshes)
        if (
            newQuestions.length > 0 &&
            !hasQuestionsPresentedEventFired(props.promptRun.id)
        ) {
            questionsFirstShownAt.value = Date.now();
            markQuestionsPresentedEventFired(props.promptRun.id);

            const displayMode = showAllQuestions.value
                ? 'show-all'
                : 'one-at-a-time';

            analyticsService.track({
                name: 'questions_presented',
                properties: {
                    prompt_run_id: props.promptRun.id,
                    question_ids: newQuestions.map((q) => q.id),
                    question_count: newQuestions.length,
                    display_mode: displayMode,
                    personality_type: props.promptRun.personality_type,
                    task_category: props.promptRun.task_category,
                },
            });

            // Initialise per-question timestamps (all shown at once for now)
            newQuestions.forEach((_, index) => {
                questionShownTimestamps.value.set(index, Date.now());
            });
        }
    },
    { immediate: true },
);

// Track individual question visibility in one-at-a-time mode
watch(
    () => currentIndex.value,
    (newIndex) => {
        if (!showAllQuestions.value && newIndex !== null) {
            // One-at-a-time mode: record timestamp when this question first appears
            if (!questionShownTimestamps.value.has(newIndex)) {
                questionShownTimestamps.value.set(newIndex, Date.now());
            }
        }
    },
);

// Save display mode preference when user toggles it
watch(
    () => showAllQuestions.value,
    async (newValue) => {
        isSavingPreference.value = true;
        try {
            const mode = newValue ? 'show-all' : 'one-at-a-time';
            await axios.patch(countryRoute('api.user.preferences.update'), {
                question_display_mode: mode,
            });

            // Track analytics event for display mode toggle
            analyticsService.track({
                name: 'question_display_mode_toggled',
                properties: {
                    prompt_run_id: props.promptRun.id,
                    new_display_mode: mode,
                    question_count: questions.value.length,
                    personality_type: props.promptRun.personality_type,
                    task_category: props.promptRun.task_category,
                },
            });
        } catch (error) {
            console.error('Failed to save display mode preference:', error);
        } finally {
            isSavingPreference.value = false;
        }
    },
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

        // Calculate time to answer and determine display mode
        const questionShownAt =
            questionShownTimestamps.value.get(questionIndex);
        const timeToAnswerMs = questionShownAt
            ? Date.now() - questionShownAt
            : null;
        const displayMode = showAllQuestions.value
            ? 'show-all'
            : 'one-at-a-time';

        // Track analytics based on whether answer was provided or skipped
        if (value !== null && value.trim().length > 0) {
            // Question was answered
            analyticsService.track({
                name: 'question_answered',
                properties: {
                    question_index: questionIndex,
                    question_id:
                        questions.value[questionIndex]?.id ??
                        `Q${questionIndex}`,
                    answer_length: value.length,
                    time_to_answer_ms: timeToAnswerMs,
                    display_mode: displayMode,
                    prompt_run_id: props.promptRun.id,
                    total_questions: questions.value.length,
                    answered_count: answers.value.filter((a) => a !== null)
                        .length,
                    question_category:
                        questions.value[questionIndex]?.category ?? null,
                },
            });
        } else {
            // Question was skipped (shown but not answered)
            analyticsService.track({
                name: 'question_skipped',
                properties: {
                    question_index: questionIndex,
                    question_id:
                        questions.value[questionIndex]?.id ??
                        `Q${questionIndex}`,
                    prompt_run_id: props.promptRun.id,
                    question_category:
                        questions.value[questionIndex]?.category ?? null,
                    personality_type: props.promptRun.personality_type,
                    display_mode: displayMode,
                    time_to_skip_ms: timeToAnswerMs,
                },
            });
        }

        // Mark that an analytics event has been fired for this question (to avoid duplicates in submitAllAnswers)
        questionsWithEventsFired.value.add(questionIndex);

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

const handleStarRatingSave = async (questionIndex: number, rating: number) => {
    // Update local state
    const existing = questionRatings.value.get(questionIndex) ?? {
        rating: null,
        explanation: null,
    };
    questionRatings.value.set(questionIndex, {
        ...existing,
        rating,
    });

    savingQuestionRatings.value.add(questionIndex);

    const question = questions.value[questionIndex];
    const questionId = question?.id ?? `Q${questionIndex}`;

    try {
        // Save to database
        await axios.post(
            route('api.questions.rate', {
                promptRun: props.promptRun.id,
                questionId,
            }),
            {
                rating,
                explanation: existing.explanation,
            },
        );

        // Mark as saved
        savedQuestionRatings.value.add(questionIndex);

        // Fire analytics event
        analyticsService.track({
            name: 'question_rated',
            properties: {
                prompt_run_id: props.promptRun.id,
                question_id: questionId,
                question_index: questionIndex,
                rating,
                has_explanation: !!existing.explanation,
                explanation_length: existing.explanation?.length ?? 0,
                question_category: question.category ?? null,
                was_answered: answers.value[questionIndex] !== null,
            },
        });
    } catch (error) {
        console.error('Failed to save question rating:', error);
        questionRatings.value.delete(questionIndex);
        savedQuestionRatings.value.delete(questionIndex);
    } finally {
        savingQuestionRatings.value.delete(questionIndex);
    }
};

const showThankYouMessageWithAutoHide = (questionIndex: number) => {
    // Clear any existing timeout for this question
    const existingTimeout = thankYouTimeouts.value.get(questionIndex);
    if (existingTimeout) {
        clearTimeout(existingTimeout);
    }

    // Show the thank you message
    visibleThankYouMessages.value.add(questionIndex);

    // Auto-hide after 4 seconds
    const timeout = setTimeout(() => {
        visibleThankYouMessages.value.delete(questionIndex);
        thankYouTimeouts.value.delete(questionIndex);
    }, 4000);

    thankYouTimeouts.value.set(questionIndex, timeout);
};

const handleQuestionExplanationSubmit = async (
    questionIndex: number,
    data: { rating: number; explanation: string | null },
) => {
    questionRatings.value.set(questionIndex, data);
    savingQuestionRatings.value.add(questionIndex);

    const question = questions.value[questionIndex];
    const questionId = question?.id ?? `Q${questionIndex}`;

    try {
        // Save to database
        await axios.post(
            route('api.questions.rate', {
                promptRun: props.promptRun.id,
                questionId,
            }),
            {
                rating: data.rating,
                explanation: data.explanation,
            },
        );

        // Mark explanation as submitted
        ratingsWithExplanationSubmitted.value.add(questionIndex);

        // Show thank you message with auto-hide
        showThankYouMessageWithAutoHide(questionIndex);

        // Fire analytics event (if rating wasn't already tracked)
        if (!savedQuestionRatings.value.has(questionIndex)) {
            analyticsService.track({
                name: 'question_rated',
                properties: {
                    prompt_run_id: props.promptRun.id,
                    question_id: questionId,
                    question_index: questionIndex,
                    rating: data.rating,
                    has_explanation: !!data.explanation,
                    explanation_length: data.explanation?.length ?? 0,
                    question_category: question.category ?? null,
                    was_answered: answers.value[questionIndex] !== null,
                },
            });
        }
    } catch (error) {
        console.error('Failed to save question explanation:', error);
        questionRatings.value.delete(questionIndex);
    } finally {
        savingQuestionRatings.value.delete(questionIndex);
    }
};

const handleQuestionRatingDraft = (
    questionIndex: number,
    update: { rating?: number | null; explanation?: string | null },
) => {
    const existing = questionRatings.value.get(questionIndex) ?? {
        rating: null,
        explanation: null,
    };

    questionRatings.value.set(questionIndex, {
        rating:
            update.rating !== undefined
                ? update.rating
                : (existing.rating ?? null),
        explanation:
            update.explanation !== undefined
                ? update.explanation
                : (existing.explanation ?? null),
    });
};

const submitAllAnswersEarly = async () => {
    // Save current answer first, then submit all answers
    const answer = normalizeAnswer(currentAnswer.value);
    await saveAnswer(currentIndex.value, answer);
    await submitAllAnswers();
};

const startEditingAnswers = () => {
    // Check if unregistered visitor has completed prompts
    if (!user.value && props.visitorHasCompletedPrompts) {
        showVisitorLimitModal.value = true;
        return;
    }

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

    // Track skipped questions before submitting (but only for questions that haven't already fired an event)
    const displayMode = showAllQuestions.value ? 'show-all' : 'one-at-a-time';
    questions.value.forEach((question, index) => {
        const wasShown = questionShownTimestamps.value.has(index);
        const wasAnswered =
            answers.value[index] !== null && answers.value[index] !== '';
        const eventAlreadyFired = questionsWithEventsFired.value.has(index);

        // If question was shown but NOT answered AND no event was fired yet → it was skipped
        // (In one-at-a-time mode, skip events are fired immediately in saveAnswer)
        // (In show-all mode, we fire them here only if the question was never explicitly submitted)
        if (wasShown && !wasAnswered && !eventAlreadyFired) {
            analyticsService.track({
                name: 'question_skipped',
                properties: {
                    question_index: index,
                    question_id: question.id,
                    prompt_run_id: props.promptRun.id,
                    question_category: question.category ?? null,
                    personality_type: props.promptRun.personality_type,
                    display_mode: displayMode,
                },
            });
        }
    });

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

// Clear thank you message when switching questions
watch(currentIndex, (newIndex, oldIndex) => {
    // Clear thank you message timeout when switching to a different question
    if (oldIndex !== undefined && oldIndex !== newIndex) {
        const timeout = thankYouTimeouts.value.get(oldIndex);
        if (timeout) {
            clearTimeout(timeout);
            thankYouTimeouts.value.delete(oldIndex);
        }
        visibleThankYouMessages.value.delete(oldIndex);
    }
});

// Clean up timeouts when component unmounts
onBeforeUnmount(() => {
    thankYouTimeouts.value.forEach((timeout) => {
        clearTimeout(timeout);
    });
    thankYouTimeouts.value.clear();
});
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

        <!-- Question Rating UI (one-at-a-time mode) -->
        <div
            v-if="
                shouldShowQuestionForm && currentQuestion && !showAllQuestions
            "
            class="mt-6 border-t border-indigo-200 pt-6"
        >
            <div class="flex flex-col items-center gap-3">
                <h4 class="text-sm font-medium text-indigo-700">
                    {{
                        $t(
                            'promptBuilder.components.clarifyingQuestions.rateQuestion',
                        )
                    }}
                </h4>
                <PromptRating
                    :model-value="
                        questionRatings.get(currentIndex)?.rating ?? null
                    "
                    :explanation="
                        questionRatings.get(currentIndex)?.explanation ?? null
                    "
                    :is-saved="savedQuestionRatings.has(currentIndex)"
                    size="sm"
                    :show-explanation="true"
                    :placeholder="
                        $t(
                            'promptBuilder.components.clarifyingQuestions.rateQuestionExplanationPlaceholder',
                        )
                    "
                    @update:model-value="
                        (rating) =>
                            handleQuestionRatingDraft(currentIndex, { rating })
                    "
                    @rate-immediately="
                        (rating) => handleStarRatingSave(currentIndex, rating)
                    "
                    @update:explanation="
                        (explanation) =>
                            handleQuestionRatingDraft(currentIndex, {
                                explanation,
                            })
                    "
                    @submit="
                        (data) =>
                            handleQuestionExplanationSubmit(currentIndex, data)
                    "
                />
                <p
                    v-if="
                        visibleThankYouMessages.has(currentIndex) &&
                        !savingQuestionRatings.has(currentIndex)
                    "
                    class="text-sm text-green-600"
                >
                    {{
                        $t(
                            'promptBuilder.components.clarifyingQuestions.rateQuestionThankYou',
                        )
                    }}
                </p>
                <p
                    v-if="savingQuestionRatings.has(currentIndex)"
                    class="text-sm text-indigo-500"
                >
                    {{ $t('common.labels.saving') }}
                </p>
            </div>
        </div>

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
            :question-ratings="questionRatings"
            :saved-question-ratings="savedQuestionRatings"
            @update:answer="
                (index: number, value: string) => (answers[index] = value)
            "
            @save-answer="
                (index: number, value: string) => saveAnswer(index, value)
            "
            @update-question-rating-draft="
                (data: {
                    index: number;
                    rating?: number | null;
                    explanation?: string | null;
                }) => handleQuestionRatingDraft(data.index, data)
            "
            @save-star-rating="
                (data: { index: number; rating: number }) =>
                    handleStarRatingSave(data.index, data.rating)
            "
            @submit-explanation="
                (data: {
                    index: number;
                    rating: number;
                    explanation: string | null;
                }) =>
                    handleQuestionExplanationSubmit(data.index, {
                        rating: data.rating,
                        explanation: data.explanation,
                    })
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
