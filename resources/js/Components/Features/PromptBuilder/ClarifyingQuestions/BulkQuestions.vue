<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/Base/Button/ButtonVoiceInput.vue';
import FormTextareaWithActions from '@/Components/Base/Form/FormTextareaWithActions.vue';
import OptionalBadge from '@/Components/Common/OptionalBadge.vue';
import QuestionNumber from '@/Components/Features/PromptBuilder/Forms/QuestionNumber.vue';
import PromptRating from '@/Components/Features/PromptBuilder/PromptRating.vue';
import { useTextAppend } from '@/Composables/features/useTextAppend';
import type { ClarifyingQuestion } from '@/Types/models/ClarifyingQuestion';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        questions: ClarifyingQuestion[];
        answers: (string | null)[];
        savedAnswers: (string | null)[]; // Backend-saved answers for comparison
        hasOptionalQuestions: boolean;
        optionalQuestionsLabel: string;
        isSubmitting: boolean;
        submitLabel?: string;
        showBack?: boolean;
        backLabel?: string;
        isEditMode?: boolean;
        questionRatings?: Map<
            number,
            { rating: number | null; explanation: string | null }
        >;
        savedQuestionRatings?: Set<number>;
    }>(),
    {
        showBack: true,
        isEditMode: false,
    },
);

const emit = defineEmits<{
    (e: 'update:answer', index: number, value: string): void;
    (e: 'save-answer', index: number, value: string): void;
    (e: 'submit-all'): void;
    (e: 'back'): void;
    (
        e: 'update-question-rating-draft',
        data: {
            index: number;
            rating?: number | null;
            explanation?: string | null;
        },
    ): void;
    (e: 'save-star-rating', data: { index: number; rating: number }): void;
    (
        e: 'submit-explanation',
        data: { index: number; rating: number; explanation: string | null },
    ): void;
}>();

const { t } = useI18n({ useScope: 'global' });
const { appendText } = useTextAppend();

const textareaRefs = ref<
    (InstanceType<typeof FormTextareaWithActions> | null)[]
>([]);
const expandedRatingUIs = ref<Set<number>>(new Set());
const visibleThankYouMessages = ref<Set<number>>(new Set());
const thankYouTimeouts = ref<Map<number, ReturnType<typeof setTimeout>>>(
    new Map(),
);

// Check if we're on a larger screen (sm breakpoint and above)
const isLargeScreen = () => window.matchMedia('(min-width: 640px)').matches;

const setTextareaRef = (
    el: InstanceType<typeof FormTextareaWithActions> | null,
    index: number,
) => {
    if (el) {
        textareaRefs.value[index] = el;
    }
};

const focusFirstTextarea = () => {
    // Only focus on larger screens to avoid keyboard popup on mobile
    if (!isLargeScreen()) return;

    const firstRef = textareaRefs.value[0];
    if (firstRef) {
        firstRef.focus();
    }
};

defineExpose({ focusFirstTextarea });

const updateAnswer = (index: number, value: string) => {
    emit('update:answer', index, value ?? '');
};

const handleTranscription = (index: number, transcript: string) => {
    const current = props.answers[index] ?? '';
    updateAnswer(index, appendText(current, transcript));
};

const handleBlur = (index: number, value: string) => {
    // Only save in answering mode, not edit mode
    if (props.isEditMode) {
        return;
    }

    // Normalize values for comparison (trim and treat empty as null)
    const normalizeValue = (val: string | null | undefined) => {
        if (!val) return null;
        const trimmed = val.trim();
        return trimmed.length ? trimmed : null;
    };

    const currentValue = normalizeValue(value);
    const savedValue = normalizeValue(props.savedAnswers[index]);

    // Only save if the value has actually changed from what's saved in the backend
    if (currentValue !== savedValue) {
        emit('save-answer', index, value);
    }
};

const resolvedSubmitLabel = computed(
    () =>
        props.submitLabel ||
        t('promptBuilder.components.bulkQuestions.submitAll'),
);

const resolvedBackLabel = computed(
    () =>
        props.backLabel ||
        t('promptBuilder.components.bulkQuestions.backToSingle'),
);

const showThankYouMessageWithAutoHide = (questionIndex: number) => {
    const existingTimeout = thankYouTimeouts.value.get(questionIndex);
    if (existingTimeout) clearTimeout(existingTimeout);

    visibleThankYouMessages.value.add(questionIndex);
    const timeout = setTimeout(() => {
        visibleThankYouMessages.value.delete(questionIndex);
        thankYouTimeouts.value.delete(questionIndex);
    }, 4000);
    thankYouTimeouts.value.set(questionIndex, timeout);
};

const handleExplanationSubmit = (
    index: number,
    data: { rating: number; explanation: string | null },
) => {
    emit('submit-explanation', { index, ...data });
    showThankYouMessageWithAutoHide(index);
};

const toggleRatingUI = (index: number) => {
    if (expandedRatingUIs.value.has(index)) {
        expandedRatingUIs.value.delete(index);
    } else {
        expandedRatingUIs.value.add(index);
    }
};

const handleStarRatingSaveBulk = (index: number, rating: number) => {
    // Auto-expand rating UI when user clicks a star
    expandedRatingUIs.value.add(index);
    emit('save-star-rating', { index, rating });
};
</script>

<template>
    <div class="mt-10 space-y-16">
        <div v-for="(question, index) in questions" :key="question.id ?? index">
            <div class="flex gap-4">
                <div class="mt-0.5">
                    <QuestionNumber :number="index + 1" />
                </div>
                <div class="flex flex-1 items-start">
                    <span class="flex-1 text-indigo-900 sm:text-lg">
                        {{ question.question }}
                    </span>
                    <OptionalBadge v-if="question.required === false" />
                </div>
            </div>
            <div class="mt-2 space-y-3 sm:mt-0 sm:ml-10">
                <p
                    v-if="question.purpose"
                    class="mt-4 mb-0 text-sm text-indigo-600"
                >
                    {{ question.purpose }}
                </p>
                <FormTextareaWithActions
                    :id="`bulk-answer-${index}`"
                    :ref="
                        (
                            el: InstanceType<
                                typeof FormTextareaWithActions
                            > | null,
                        ) => setTextareaRef(el, index)
                    "
                    :model-value="answers[index] ?? ''"
                    :label="
                        $t(
                            'promptBuilder.components.bulkQuestions.answerLabel',
                            {
                                number: index + 1,
                            },
                        )
                    "
                    :disabled="isSubmitting"
                    :placeholder="
                        $t(
                            'promptBuilder.components.bulkQuestions.answerPlaceholder',
                        )
                    "
                    @update:model-value="
                        (value: string) => updateAnswer(index, value)
                    "
                    @blur="handleBlur(index, answers[index] ?? '')"
                >
                    <template #actions>
                        <ButtonVoiceInput
                            :disabled="isSubmitting"
                            @transcription="
                                (transcript: string) =>
                                    handleTranscription(index, transcript)
                            "
                        />
                    </template>
                </FormTextareaWithActions>

                <!-- Question rating UI (collapsible) -->
                <div class="mt-4 border-t border-indigo-200 pt-4">
                    <!-- Toggle button to show/hide rating UI -->
                    <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        @click="toggleRatingUI(index)"
                    >
                        {{
                            expandedRatingUIs.has(index)
                                ? $t(
                                      'promptBuilder.components.bulkQuestions.hideRateQuestion',
                                  )
                                : $t(
                                      'promptBuilder.components.bulkQuestions.rateQuestion',
                                  )
                        }}
                    </button>

                    <!-- Rating component (shown only when expanded) -->
                    <div v-if="expandedRatingUIs.has(index)" class="mt-3">
                        <PromptRating
                            :model-value="
                                props.questionRatings?.get(index)?.rating ??
                                null
                            "
                            :explanation="
                                props.questionRatings?.get(index)
                                    ?.explanation ?? null
                            "
                            :is-saved="
                                props.savedQuestionRatings?.has(index) ?? false
                            "
                            size="sm"
                            :show-explanation="true"
                            :placeholder="
                                $t(
                                    'promptBuilder.components.promptRating.placeholder',
                                )
                            "
                            @update:model-value="
                                (rating) =>
                                    emit('update-question-rating-draft', {
                                        index,
                                        rating,
                                    })
                            "
                            @rate-immediately="
                                handleStarRatingSaveBulk(index, $event)
                            "
                            @update:explanation="
                                (explanation) =>
                                    emit('update-question-rating-draft', {
                                        index,
                                        explanation,
                                    })
                            "
                            @submit="handleExplanationSubmit(index, $event)"
                        />
                        <p
                            v-if="visibleThankYouMessages.has(index)"
                            class="mt-2 text-xs text-green-600"
                        >
                            {{
                                $t(
                                    'promptBuilder.components.clarifyingQuestions.rateQuestionThankYou',
                                )
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div
            class="flex flex-col-reverse justify-end gap-2 sm:flex-row sm:items-center sm:space-x-2"
        >
            <ButtonSecondary
                v-if="showBack"
                type="button"
                :disabled="isSubmitting"
                class="w-full sm:w-auto"
                @click="emit('back')"
            >
                {{ resolvedBackLabel }}
            </ButtonSecondary>
            <ButtonPrimary
                type="button"
                :disabled="isSubmitting"
                :loading="isSubmitting"
                class="w-full sm:w-auto"
                @click="emit('submit-all')"
            >
                {{ resolvedSubmitLabel }}
            </ButtonPrimary>
        </div>
    </div>
</template>
