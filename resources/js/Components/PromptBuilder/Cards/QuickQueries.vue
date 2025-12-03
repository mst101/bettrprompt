<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import ButtonTrash from '@/Components/PromptBuilder/ButtonTrash.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import type {
    PreAnalysisQuestion,
    PromptRunResource,
} from '@/types/resources/PromptRunResource';
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch, watchEffect } from 'vue';

interface Props {
    promptRun: PromptRunResource;
    mode?: 'view-edit' | 'initial-submit';
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'view-edit',
});

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

// Watch for changes in currentAnswers and focus the "Other" textarea when selected
watch(
    currentAnswers,
    async () => {
        await nextTick();
        questions.value.forEach((question) => {
            const textareaRef = otherTextareaRefs.value[question.id];
            if (selectedOtherOption(question) && textareaRef) {
                const textarea = textareaRef.$el?.querySelector('textarea');
                if (textarea) {
                    textarea.focus();
                }
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

// Check if user has selected an "Other" option
const selectedOtherOption = (question: PreAnalysisQuestion): boolean => {
    const selectedAnswer = currentAnswers.value[question.id];
    return (
        selectedAnswer !== undefined &&
        selectedAnswer !== '' &&
        question.options?.some(
            (opt) => opt.value === selectedAnswer && isOtherOption(opt.label),
        ) === true
    );
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

const startInitialSubmit = () => {
    currentAnswers.value = {};
    otherResponses.value = {};
    isEditing.value = true;
    shouldFocusFirstAnswer.value = true;
};

// Check if all answers are valid
const allAnswersValid = computed(() => {
    return questions.value.every((question) => {
        const answer = currentAnswers.value[question.id];

        if (!answer || answer.trim().length === 0) {
            return false;
        }

        if (selectedOtherOption(question)) {
            const otherResponse = otherResponses.value[question.id];
            return otherResponse && otherResponse.trim().length > 0;
        }

        return true;
    });
});

const submitAnswers = () => {
    if (!allAnswersValid.value || isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    submitError.value = null;

    // Combine answers with "Other" responses if applicable
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

    router.post(
        route('prompt-builder.pre-analysis-answers', props.promptRun.id),
        { answers: finalAnswers },
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

const submitUpdatedAnswers = () => {
    if (!allAnswersValid.value || form.processing) {
        return;
    }

    // Combine answers with "Other" responses if applicable
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

    form.answers = finalAnswers;

    form.post(
        route('prompt-builder.create-child', {
            parentPromptRun: props.promptRun.id,
        }),
        {
            preserveScroll: true,
        },
    );
};

// Check if we have any pre-analysis questions
const hasQuestions = computed(() => questions.value.length > 0);

// Check if we have any answers (for view-edit mode)
const hasAnswers = computed(
    () => questions.value.length > 0 && Object.keys(answers.value).length > 0,
);

// Get the ID of the first question
const firstQuestionId = computed(() => questions.value[0]?.id);

// Show this component if in initial-submit mode OR if we have answers in view-edit mode
const shouldShow = computed(() => {
    if (props.mode === 'initial-submit') {
        return hasQuestions.value;
    }
    return hasAnswers.value;
});

// Determine which title and button text to show
const cardTitle = computed(() => {
    if (props.mode === 'initial-submit') {
        return 'Quick Clarification';
    }
    return 'Quick Queries';
});

const submitButtonText = computed(() => {
    if (props.mode === 'initial-submit') {
        return 'Continue';
    }
    return 'Generate New Prompt';
});
</script>

<template>
    <Card v-if="shouldShow" class="space-y-4">
        <!-- View/Edit mode header -->
        <template v-if="props.mode === 'view-edit'">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <h2 class="text-lg font-semibold text-indigo-900">
                    {{ cardTitle }}
                </h2>
                <ButtonSecondary
                    v-if="!isEditing"
                    ref="editButtonRef"
                    type="button"
                    class="inline-flex w-full items-center gap-1 sm:w-fit"
                    @click="startEditing"
                >
                    <DynamicIcon name="edit" class="h-4 w-4" />
                    Edit Answers
                </ButtonSecondary>

                <div v-else class="space-y-2 sm:space-x-4">
                    <ButtonSecondary
                        type="button"
                        class="inline-flex w-full items-center gap-1 sm:w-fit"
                        :disabled="form.processing"
                        @click="cancelEditing"
                    >
                        Cancel
                    </ButtonSecondary>

                    <ButtonPrimary
                        type="submit"
                        class="inline-flex w-full items-center gap-1 sm:w-fit"
                        :disabled="!allAnswersValid || form.processing"
                        :loading="form.processing"
                    >
                        {{ submitButtonText }}
                    </ButtonPrimary>
                </div>
            </div>

            <!-- View Mode -->
            <div v-if="!isEditing" class="space-y-4">
                <div
                    v-for="question in questions"
                    :key="question.id"
                    class="rounded-lg border border-indigo-200 bg-indigo-50 p-3"
                >
                    <p class="text-xs font-medium text-indigo-600">
                        {{ question.question }}
                    </p>
                    <p class="mt-2 text-sm text-indigo-900">
                        {{
                            getAnswerLabel(question, answers[question.id] || '')
                        }}
                    </p>
                </div>
            </div>
        </template>

        <!-- Initial submit mode header -->
        <template v-else>
            <div v-if="!isEditing">
                <h2 class="mb-2 text-lg font-semibold text-indigo-900">
                    {{ cardTitle }}
                </h2>
                <p
                    v-if="promptRun.preAnalysisReasoning"
                    class="text-sm text-indigo-600"
                >
                    {{ promptRun.preAnalysisReasoning }}
                </p>
            </div>
        </template>

        <!-- Edit/Submit Form -->
        <form
            v-if="isEditing"
            class="space-y-4"
            @submit.prevent="
                props.mode === 'view-edit'
                    ? submitUpdatedAnswers
                    : submitAnswers
            "
        >
            <div v-for="question in questions" :key="question.id">
                <!-- Multiple choice questions -->
                <div v-if="question.type === 'choice'" class="space-y-3">
                    <label class="block text-sm font-medium text-indigo-900">
                        {{ question.question }}
                    </label>
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

                    <!-- "Other" text input for selected option -->
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
                            label="Please specify:"
                            :rows="3"
                            :maxlength="500"
                            placeholder="Please explain what you meant by 'Other'..."
                        ></FormTextarea>
                        <p class="mt-1 text-xs text-indigo-600">
                            {{ (otherResponses[question.id] || '').length }}/500
                            characters
                        </p>
                    </div>
                </div>

                <!-- Yes/No questions -->
                <div v-else-if="question.type === 'yes_no'" class="space-y-3">
                    <label class="block text-sm font-medium text-indigo-900">
                        {{ question.question }}
                    </label>
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
                    <FormTextareaWithActions
                        :id="`question-${question.id}`"
                        :ref="
                            question.id === firstQuestionId
                                ? (el) =>
                                      (firstAnswerRef = el?.$el as HTMLElement)
                                : undefined
                        "
                        v-model="currentAnswers[question.id]"
                        :label="question.question"
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
                    v-if="props.mode === 'view-edit'"
                    type="button"
                    class="inline-flex w-full items-center gap-1 sm:w-fit"
                    :disabled="form.processing"
                    @click="cancelEditing"
                >
                    Cancel
                </ButtonSecondary>

                <ButtonPrimary
                    type="submit"
                    class="inline-flex w-full items-center gap-1 sm:w-fit"
                    :disabled="
                        !allAnswersValid ||
                        (props.mode === 'view-edit'
                            ? form.processing
                            : isSubmitting)
                    "
                    :loading="
                        props.mode === 'view-edit'
                            ? form.processing
                            : isSubmitting
                    "
                >
                    {{ submitButtonText }}
                </ButtonPrimary>
            </div>
        </form>

        <!-- Initial submit mode: show start button -->
        <template v-if="props.mode === 'initial-submit' && !isEditing">
            <div class="flex justify-end">
                <ButtonPrimary
                    type="button"
                    :disabled="isSubmitting"
                    :loading="isSubmitting"
                    @click="startInitialSubmit"
                >
                    {{ submitButtonText }}
                </ButtonPrimary>
            </div>
        </template>
    </Card>
</template>
