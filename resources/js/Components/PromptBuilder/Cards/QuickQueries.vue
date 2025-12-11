<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import ButtonTrash from '@/Components/PromptBuilder/ButtonTrash.vue';
import QuestionNumber from '@/Components/PromptBuilder/QuestionNumber.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import type {
    PreAnalysisQuestion,
    PromptRunResource,
} from '@/types/resources/PromptRunResource';
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch, watchEffect } from 'vue';

interface Props {
    promptRun: PromptRunResource;
}

const props = defineProps<Props>();

const questions = computed<PreAnalysisQuestion[]>(
    () => props.promptRun.preAnalysisQuestions ?? [],
);

const answers = computed<Record<string, string>>(
    () => props.promptRun.preAnalysisAnswers ?? {},
);

const isEditing = ref(false);
const isSubmitting = ref(false);
const shouldFocusFirstAnswer = ref(false);
const shouldFocusEditButton = ref(false);
const currentAnswers = ref<Record<string, string>>({});
const otherResponses = ref<Record<string, string>>({});
const otherTextareaRefs = ref<
    Record<string, InstanceType<typeof FormTextarea>>
>({});
const firstAnswerRef = ref<HTMLElement | null>(null);
const editButtonRef = ref<InstanceType<typeof ButtonSecondary> | null>(null);
const submitError = ref<string | null>(null);

const form = useForm({
    answers: {} as Record<string, string>,
});

const { appendText } = useTextAppend();

const handleTranscription = (transcript: string, questionId: string) => {
    currentAnswers.value[questionId] = appendText(
        currentAnswers.value[questionId] || '',
        transcript,
    );
};

// Focus first answer when entering edit/submit mode
watchEffect(() => {
    if (shouldFocusFirstAnswer.value && firstAnswerRef.value) {
        // Try to focus directly if it's an input/textarea
        if (
            firstAnswerRef.value.tagName === 'INPUT' ||
            firstAnswerRef.value.tagName === 'TEXTAREA'
        ) {
            firstAnswerRef.value.focus();
        } else {
            // Otherwise, find the first focusable element within
            const focusable = firstAnswerRef.value.querySelector(
                'input, textarea, [tabindex]',
            ) as HTMLElement;
            if (focusable) {
                focusable.focus();
            }
        }
        shouldFocusFirstAnswer.value = false;
    }
});

// Focus edit button when exiting edit mode
watchEffect(() => {
    if (shouldFocusEditButton.value && editButtonRef.value) {
        editButtonRef.value.focus();
        shouldFocusEditButton.value = false;
    }
});

// Track previous "Other" selections to only focus when newly selected
const previousOtherSelections = ref<Set<string>>(new Set());

// Watch for changes in currentAnswers and focus the "Other" textarea only when newly selected
watch(
    currentAnswers,
    async () => {
        await nextTick();
        questions.value.forEach((question) => {
            const isCurrentlyOther = selectedOtherOption(question);
            const wasOther = previousOtherSelections.value.has(question.id);

            // Only focus if "Other" was just selected (wasn't selected before, but is now)
            if (isCurrentlyOther && !wasOther) {
                const textareaRef = otherTextareaRefs.value[question.id];
                if (textareaRef) {
                    const textarea = textareaRef.$el?.querySelector('textarea');
                    if (textarea) {
                        textarea.focus();
                    }
                }
                previousOtherSelections.value.add(question.id);
            } else if (!isCurrentlyOther && wasOther) {
                // Remove from tracking if no longer "Other"
                previousOtherSelections.value.delete(question.id);
            }
        });
    },
    { deep: true },
);

// Detect if an option represents "Other"
const isOtherOption = (label: string): boolean => {
    const lowerLabel = label.toLowerCase();
    return (
        lowerLabel.includes('other') ||
        lowerLabel.includes('different') ||
        lowerLabel.includes('custom')
    );
};

// Check if user has selected an option that needs elaboration
// First checks for elaborationNeeded flag from workflow, falls back to detecting "Other" options
const selectedOtherOption = (question: PreAnalysisQuestion): boolean => {
    const selectedAnswer = currentAnswers.value[question.id];
    if (selectedAnswer === undefined || selectedAnswer === '') {
        return false;
    }

    const selectedOption = question.options?.find(
        (opt) => opt.value === selectedAnswer,
    );

    if (!selectedOption) {
        return false;
    }

    // Check for elaborationNeeded flag first (from workflow)
    if ('elaborationNeeded' in selectedOption) {
        return selectedOption.elaborationNeeded === true;
    }

    // Fallback: detect "Other" options by label for backwards compatibility
    return isOtherOption(selectedOption.label);
};

// Get display label for an answer
const getAnswerLabel = (
    question: PreAnalysisQuestion,
    value: string,
): string => {
    if (question.type === 'choice' || question.type === 'yes_no') {
        const option = question.options?.find((opt) => opt.value === value);
        if (option && isOtherOption(option.label) && value.includes(':')) {
            // For "Other" responses, extract the user's explanation
            return value.split(':').slice(1).join(':').trim();
        }
        return option?.label || value;
    }
    return value;
};

const startEditing = () => {
    currentAnswers.value = { ...answers.value };
    otherResponses.value = {};
    previousOtherSelections.value.clear();

    // Parse "Other" responses from combined answers
    questions.value.forEach((question) => {
        const answer = answers.value[question.id];
        if (answer && isOtherOption(getAnswerLabel(question, answer))) {
            const parts = answer.split(':');
            if (parts.length > 1) {
                otherResponses.value[question.id] = parts
                    .slice(1)
                    .join(':')
                    .trim();
                currentAnswers.value[question.id] = parts[0];
            }
        }
    });

    // Initialize tracking for any pre-existing "Other" selections
    questions.value.forEach((question) => {
        if (selectedOtherOption(question)) {
            previousOtherSelections.value.add(question.id);
        }
    });

    isEditing.value = true;
    shouldFocusFirstAnswer.value = true;
};

const cancelEditing = () => {
    isEditing.value = false;
    currentAnswers.value = {};
    otherResponses.value = {};
    form.clearErrors();
    shouldFocusEditButton.value = true;
};

// Check if all answers are valid
const allAnswersValid = computed(() => {
    return questions.value.every((question) => {
        const answer = currentAnswers.value[question.id];

        if (!answer || answer.trim().length === 0) {
            return false;
        }

        // Additional details are optional - users don't have to fill them in
        return true;
    });
});

// Check if any answers have changed from their original values
const answersHaveChanged = computed(() => {
    return questions.value.some((question) => {
        const currentAnswer = currentAnswers.value[question.id];
        const originalAnswer = answers.value[question.id];
        return currentAnswer !== originalAnswer;
    });
});

// Helper to build final answers with "Other" responses
const buildFinalAnswers = (): Record<string, string> => {
    const finalAnswers: Record<string, string> = {};

    questions.value.forEach((question) => {
        const answer = currentAnswers.value[question.id];
        if (!answer) return;

        if (selectedOtherOption(question)) {
            const otherResponse = otherResponses.value[question.id];
            if (otherResponse) {
                finalAnswers[question.id] = `${answer}: ${otherResponse}`;
            }
        } else {
            finalAnswers[question.id] = answer;
        }
    });

    return finalAnswers;
};

const submitAnswers = () => {
    if (!allAnswersValid.value) {
        return;
    }

    const finalAnswers = buildFinalAnswers();

    // If we have existing answers, this is an edit (create new prompt run)
    if (hasAnswers.value) {
        form.answers = finalAnswers;
        form.post(
            route('prompt-builder.update-quick-queries', {
                promptRun: props.promptRun.id,
            }),
            {
                onSuccess: () => {
                    window.scrollTo(0, 0);
                },
            },
        );
    } else {
        // Initial submission (continue to analysis)
        isSubmitting.value = true;
        submitError.value = null;

        router.post(
            route('prompt-builder.pre-analysis-answers', props.promptRun.id),
            { answers: finalAnswers },
            {
                onSuccess: () => {
                    window.scrollTo(0, 0);
                },
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
    }
};

const continueToAnalysis = () => {
    isSubmitting.value = true;
    submitError.value = null;

    router.post(
        route('prompt-builder.pre-analysis-answers', props.promptRun.id),
        { answers: answers.value },
        {
            onSuccess: () => {
                window.scrollTo(0, 0);
            },
            onError: (errors) => {
                submitError.value =
                    (errors as Record<string, string>)?.message ||
                    'Failed to continue to analysis. Please try again.';
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};

// Check if we have any pre-analysis questions
const hasQuestions = computed(() => questions.value.length > 0);

// Check if we have any answers (for view-edit mode)
const hasAnswers = computed(
    () => questions.value.length > 0 && Object.keys(answers.value).length > 0,
);

// Auto-start editing if there are no answers yet (reactive watcher)
watch(
    () => [hasQuestions.value, hasAnswers.value],
    ([hasQuestionsValue, hasAnswersValue]) => {
        if (hasQuestionsValue && !hasAnswersValue && !isEditing.value) {
            startEditing();
        }
    },
    { immediate: true },
);

// Get the ID of the first question
const firstQuestionId = computed(() => questions.value[0]?.id);

// Show this component if we have questions
const shouldShow = computed(() => hasQuestions.value);

const submitButtonText = computed(() =>
    hasAnswers.value ? 'Generate New Prompt' : 'Continue',
);

const isLoading = computed(() =>
    hasAnswers.value ? form.processing : isSubmitting.value,
);

const isDisabled = computed(() =>
    hasAnswers.value
        ? !allAnswersValid.value || form.processing || !answersHaveChanged.value
        : !allAnswersValid.value || isSubmitting.value,
);
</script>

<template>
    <Card v-if="shouldShow" class="space-y-4">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <h2 class="text-lg font-semibold text-indigo-900">
                    Quick Queries
                </h2>
                <p
                    v-if="!hasAnswers && promptRun.preAnalysisReasoning"
                    class="mt-1 text-sm text-indigo-600"
                >
                    {{ promptRun.preAnalysisReasoning }}
                </p>
            </div>
            <ButtonSecondary
                v-if="!isEditing && hasAnswers"
                ref="editButtonRef"
                type="button"
                class="inline-flex w-full items-center gap-1 sm:w-fit"
                @click="startEditing"
            >
                <DynamicIcon name="edit" class="h-4 w-4" />
                Edit Answers
            </ButtonSecondary>
        </div>

        <!-- View Mode (only show if has answers and not editing) -->
        <div v-if="!isEditing && hasAnswers" class="space-y-4">
            <div
                v-for="(question, index) in questions"
                :key="question.id"
                class="rounded-lg border border-indigo-200 bg-indigo-50 p-3"
            >
                <div class="flex gap-3">
                    <QuestionNumber :number="index + 1" />
                    <div class="flex-1">
                        <p class="text-xs font-medium text-indigo-600">
                            {{ question.question }}
                        </p>
                        <p class="mt-2 text-sm text-indigo-900">
                            {{
                                getAnswerLabel(
                                    question,
                                    answers[question.id] || '',
                                )
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action buttons for view mode -->
            <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                <ButtonSecondary
                    type="button"
                    class="inline-flex w-full items-center gap-1 sm:w-fit"
                    :disabled="isLoading"
                    @click="startEditing"
                >
                    <DynamicIcon name="edit" class="h-4 w-4" />
                    Edit Answers
                </ButtonSecondary>

                <ButtonPrimary
                    type="button"
                    class="inline-flex w-full items-center gap-1 sm:w-fit"
                    :disabled="isLoading"
                    :loading="isLoading"
                    @click="continueToAnalysis"
                >
                    Optimise Prompt
                </ButtonPrimary>
            </div>
        </div>

        <!-- Edit/Submit Form -->
        <form
            v-if="isEditing"
            class="space-y-8"
            @submit.prevent="submitAnswers"
        >
            <div v-for="(question, index) in questions" :key="question.id">
                <!-- Multiple choice questions -->
                <div v-if="question.type === 'choice'" class="space-y-3">
                    <div class="flex gap-3">
                        <QuestionNumber :number="index + 1" />
                        <label
                            class="block flex-1 text-sm font-medium text-indigo-900"
                        >
                            {{ question.question }}
                        </label>
                    </div>
                    <div class="space-y-3">
                        <label
                            v-for="(option, optionIndex) in question.options"
                            :key="option.value"
                            class="flex cursor-pointer items-start rounded-lg border border-indigo-200 p-3 transition-colors hover:bg-indigo-50"
                            :class="{
                                'border-indigo-500 bg-indigo-50':
                                    currentAnswers[question.id] ===
                                    option.value,
                            }"
                        >
                            <input
                                :ref="
                                    question.id === firstQuestionId &&
                                    optionIndex === 0
                                        ? (el) =>
                                              (firstAnswerRef =
                                                  el as HTMLElement)
                                        : undefined
                                "
                                v-model="currentAnswers[question.id]"
                                type="radio"
                                :name="`question-${question.id}`"
                                :value="option.value"
                                class="mt-0.5 h-4 w-4 border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="ml-3 text-sm text-indigo-900">{{
                                option.label
                            }}</span>
                        </label>
                    </div>

                    <!-- Additional details textarea for options that need elaboration -->
                    <div
                        v-if="selectedOtherOption(question)"
                        class="bg-indigo-25 rounded-lg border-2 border-indigo-300 p-3"
                    >
                        <FormTextarea
                            :id="`other-${question.id}`"
                            :ref="
                                (el) =>
                                    (otherTextareaRefs[question.id] =
                                        el as InstanceType<typeof FormTextarea>)
                            "
                            v-model="otherResponses[question.id]"
                            label="Additional details (optional):"
                            :rows="3"
                            :maxlength="500"
                            placeholder="Add any additional information or elaboration..."
                        ></FormTextarea>
                        <p class="mt-1 text-xs text-indigo-600">
                            {{ (otherResponses[question.id] || '').length }}/500
                            characters
                        </p>
                    </div>
                </div>

                <!-- Yes/No questions -->
                <div v-else-if="question.type === 'yes_no'" class="space-y-3">
                    <div class="flex gap-3">
                        <QuestionNumber :number="index + 1" />
                        <label
                            class="block flex-1 text-sm font-medium text-indigo-900"
                        >
                            {{ question.question }}
                        </label>
                    </div>
                    <div class="space-y-2">
                        <label
                            v-for="(option, optionIndex) in question.options"
                            :key="option.value"
                            class="flex cursor-pointer items-start rounded-lg border border-indigo-200 p-3 transition-colors hover:bg-indigo-50"
                            :class="{
                                'border-indigo-500 bg-indigo-50':
                                    currentAnswers[question.id] ===
                                    option.value,
                            }"
                        >
                            <input
                                :ref="
                                    question.id === firstQuestionId &&
                                    optionIndex === 0
                                        ? (el) =>
                                              (firstAnswerRef =
                                                  el as HTMLElement)
                                        : undefined
                                "
                                v-model="currentAnswers[question.id]"
                                type="radio"
                                :name="`question-${question.id}`"
                                :value="option.value"
                                class="mt-0.5 h-4 w-4 border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="ml-3 text-sm text-indigo-900">{{
                                option.label
                            }}</span>
                        </label>
                    </div>
                </div>

                <!-- Text input questions -->
                <div v-else-if="question.type === 'text'" class="space-y-3">
                    <div class="flex gap-3">
                        <QuestionNumber :number="index + 1" />
                        <span class="text-sm font-medium text-indigo-900">
                            {{ question.question }}
                        </span>
                    </div>
                    <FormTextareaWithActions
                        :id="`question-${question.id}`"
                        :ref="
                            question.id === firstQuestionId
                                ? (el) =>
                                      (firstAnswerRef = el?.$el as HTMLElement)
                                : undefined
                        "
                        v-model="currentAnswers[question.id]"
                        :label="`Answer ${index + 1}`"
                        :rows="3"
                        placeholder="Type your answer here..."
                    >
                        <template #actions>
                            <div class="flex items-center justify-end gap-3">
                                <ButtonVoiceInput
                                    @transcription="
                                        (transcript) =>
                                            handleTranscription(
                                                transcript,
                                                question.id,
                                            )
                                    "
                                />
                                <ButtonTrash
                                    :disabled="!currentAnswers[question.id]"
                                    @clear="currentAnswers[question.id] = ''"
                                />
                            </div>
                        </template>
                    </FormTextareaWithActions>
                </div>
            </div>

            <!-- Error message -->
            <div
                v-if="submitError"
                class="rounded-md border border-red-100 bg-red-50 p-3 text-sm text-red-700"
            >
                {{ submitError }}
            </div>

            <!-- Form buttons -->
            <div
                class="mt-8 flex flex-col justify-end gap-3 sm:mt-0 sm:flex-row"
            >
                <ButtonSecondary
                    v-if="hasAnswers"
                    type="button"
                    class="inline-flex w-full items-center gap-1 sm:w-fit"
                    :disabled="isLoading"
                    @click="cancelEditing"
                >
                    Cancel
                </ButtonSecondary>

                <ButtonPrimary
                    type="submit"
                    class="inline-flex w-full items-center gap-1 sm:w-fit"
                    :disabled="isDisabled"
                    :loading="isLoading"
                >
                    {{ submitButtonText }}
                </ButtonPrimary>
            </div>
        </form>
    </Card>
</template>
