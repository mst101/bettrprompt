<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import ButtonTrash from '@/Components/PromptBuilder/ButtonTrash.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import type {
    PreAnalysisQuestion,
    PromptRunResource,
} from '@/types/resources/PromptRunResource';
import { useForm } from '@inertiajs/vue3';
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
const shouldFocusFirstAnswer = ref(false);
const shouldFocusEditButton = ref(false);
const editAnswers = ref<Record<string, string>>({});
const otherResponses = ref<Record<string, string>>({});
const otherTextareaRefs = ref<
    Record<string, InstanceType<typeof FormTextarea>>
>({});
const firstAnswerRef = ref<HTMLInputElement | HTMLTextAreaElement | null>(null);
const editButtonRef = ref<InstanceType<typeof ButtonSecondary> | null>(null);

const form = useForm({
    answers: {} as Record<string, string>,
});

const { appendText } = useTextAppend();

const handleTranscription = (transcript: string, questionId: string) => {
    editAnswers.value[questionId] = appendText(
        editAnswers.value[questionId] || '',
        transcript,
    );
};

// Focus first answer when entering edit mode
watchEffect(() => {
    if (shouldFocusFirstAnswer.value && firstAnswerRef.value) {
        firstAnswerRef.value.focus();
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

// Watch for changes in editAnswers and focus the "Other" textarea when selected
watch(
    editAnswers,
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
    const selectedAnswer = editAnswers.value[question.id];
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
    editAnswers.value = { ...answers.value };
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
                editAnswers.value[question.id] = parts[0];
            }
        }
    });

    isEditing.value = true;
    shouldFocusFirstAnswer.value = true;
};

const cancelEditing = () => {
    isEditing.value = false;
    editAnswers.value = {};
    otherResponses.value = {};
    form.clearErrors();
    shouldFocusEditButton.value = true;
};

// Check if all answers are valid during editing
const allAnswersValid = computed(() => {
    return questions.value.every((question) => {
        const answer = editAnswers.value[question.id];

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

const submitUpdatedAnswers = () => {
    if (!allAnswersValid.value || form.processing) {
        return;
    }

    // Combine answers with "Other" responses if applicable
    const finalAnswers: Record<string, string> = {};

    questions.value.forEach((question) => {
        const answer = editAnswers.value[question.id];
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

// Check if we have any pre-analysis questions and answers
const hasAnswers = computed(
    () => questions.value.length > 0 && Object.keys(answers.value).length > 0,
);
</script>

<template>
    <Card v-if="hasAnswers" class="space-y-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <h2 class="text-lg font-semibold text-indigo-900">Quick Queries</h2>
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
                    Generate New Prompt
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
                    {{ getAnswerLabel(question, answers[question.id] || '') }}
                </p>
            </div>
        </div>

        <!-- Edit Mode -->
        <form v-else class="space-y-4" @submit.prevent="submitUpdatedAnswers">
            <div v-for="question in questions" :key="question.id">
                <label class="block text-sm font-medium text-indigo-900">
                    {{ question.question }}
                </label>

                <!-- Multiple choice questions -->
                <div v-if="question.type === 'choice'" class="mt-3 space-y-3">
                    <label
                        v-for="option in question.options"
                        :key="option.value"
                        class="flex cursor-pointer items-start rounded-lg border border-indigo-200 p-3 transition-colors hover:bg-indigo-50"
                        :class="{
                            'border-indigo-500 bg-indigo-50':
                                editAnswers[question.id] === option.value,
                        }"
                    >
                        <input
                            :ref="
                                questions[0].id === question.id
                                    ? (el) =>
                                          (firstAnswerRef =
                                              el as HTMLInputElement)
                                    : undefined
                            "
                            v-model="editAnswers[question.id]"
                            type="radio"
                            :name="`edit-${question.id}`"
                            :value="option.value"
                            class="mt-0.5 h-4 w-4 border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="ml-3 text-sm text-indigo-900">{{
                            option.label
                        }}</span>
                    </label>

                    <!-- "Other" text input for selected option -->
                    <div
                        v-if="selectedOtherOption(question)"
                        class="bg-indigo-25 rounded-lg border-2 border-indigo-300 p-3"
                    >
                        <FormTextarea
                            :id="`edit-other-${question.id}`"
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
                <div
                    v-else-if="question.type === 'yes_no'"
                    class="mt-3 space-y-2"
                >
                    <label
                        v-for="option in question.options"
                        :key="option.value"
                        class="flex cursor-pointer items-start rounded-lg border border-indigo-200 p-3 transition-colors hover:bg-indigo-50"
                        :class="{
                            'border-indigo-500 bg-indigo-50':
                                editAnswers[question.id] === option.value,
                        }"
                    >
                        <input
                            :ref="
                                questions[0].id === question.id
                                    ? (el) =>
                                          (firstAnswerRef =
                                              el as HTMLInputElement)
                                    : undefined
                            "
                            v-model="editAnswers[question.id]"
                            type="radio"
                            :name="`edit-${question.id}`"
                            :value="option.value"
                            class="mt-0.5 h-4 w-4 border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="ml-3 text-sm text-indigo-900">{{
                            option.label
                        }}</span>
                    </label>
                </div>

                <!-- Text input questions -->
                <div v-else-if="question.type === 'text'" class="mt-3">
                    <div class="mb-3 flex items-center gap-3">
                        <ButtonVoiceInput
                            @transcription="
                                (transcript) =>
                                    handleTranscription(transcript, question.id)
                            "
                        />
                        <ButtonTrash
                            :disabled="!editAnswers[question.id]"
                            @clear="editAnswers[question.id] = ''"
                        />
                    </div>
                    <textarea
                        :ref="
                            questions[0].id === question.id
                                ? (el) =>
                                      (firstAnswerRef =
                                          el as HTMLTextAreaElement)
                                : undefined
                        "
                        v-model="editAnswers[question.id]"
                        rows="3"
                        class="block w-full rounded-lg border border-indigo-200 px-3 py-2 text-sm text-indigo-900 placeholder-indigo-300 focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Type your answer here..."
                    ></textarea>
                </div>
            </div>

            <div
                class="mt-8 flex flex-col justify-end gap-3 sm:mt-0 sm:flex-row"
            >
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
                    Generate New Prompt
                </ButtonPrimary>
            </div>
        </form>
    </Card>
</template>
