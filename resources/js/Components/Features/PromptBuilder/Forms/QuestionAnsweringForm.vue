<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/Base/Button/ButtonVoiceInput.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import FormTextareaWithActions from '@/Components/Base/Form/FormTextareaWithActions.vue';
import ButtonTrash from '@/Components/Common/ButtonTrash.vue';
import OptionalBadge from '@/Components/Common/OptionalBadge.vue';
import QuestionNumber from '@/Components/Features/PromptBuilder/Forms/QuestionNumber.vue';
import { useTextAppend } from '@/Composables/features/useTextAppend';
import { computed, nextTick, ref, watch } from 'vue';

import type { ClarifyingQuestion } from '@/Types/models/ClarifyingQuestion';

interface Props {
    question: ClarifyingQuestion | string;
    answer: string;
    currentQuestionNumber: number;
    totalQuestions: number;
    isSubmitting: boolean;
    canGoBack?: boolean;
    showAll?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canGoBack: false,
    showAll: false,
});

const emit = defineEmits<{
    (e: 'update:answer', value: string): void;
    (e: 'submit'): void;
    (e: 'submit-all'): void;
    (e: 'go-back'): void;
    (e: 'clear'): void;
}>();

const { appendText } = useTextAppend();
const textareaRef = ref<InstanceType<typeof FormTextareaWithActions> | null>(
    null,
);

// Check if we're on a larger screen (sm breakpoint and above)
const isLargeScreen = () => window.matchMedia('(min-width: 640px)').matches;

const focus = () => {
    // Only focus on larger screens to avoid keyboard popup on mobile
    if (!isLargeScreen()) return;

    nextTick(() => {
        textareaRef.value?.focus();
    });
};

defineExpose({ focus });

watch(
    () => props.question,
    () => {
        // Only auto-focus on larger screens to avoid keyboard popup on mobile
        if (isLargeScreen()) {
            focus();
        }
    },
);

const questionText = computed(() => {
    if (typeof props.question === 'string') {
        return props.question;
    }
    return props.question.question;
});

const questionPurpose = computed(() => {
    if (typeof props.question === 'string') {
        return undefined;
    }
    return props.question.purpose;
});

const isRequired = computed(() => {
    if (typeof props.question === 'string') {
        return true;
    }
    return props.question.required !== false;
});

const progressPercent = computed(() => {
    if (props.totalQuestions === 0) return 0;
    const answeredCount = props.currentQuestionNumber - 1;
    return (answeredCount / props.totalQuestions) * 100;
});

const handleTranscription = (text: string) => {
    const updated = appendText(props.answer, text);
    emit('update:answer', updated);
};
</script>

<template>
    <div class="">
        <!-- Progress Bar -->
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
                <span class="font-medium text-indigo-700">
                    Question {{ currentQuestionNumber }} of
                    {{ totalQuestions }}
                </span>
            </div>
            <span class="text-indigo-700">
                {{ Math.round(progressPercent) }}% complete
            </span>
        </div>
        <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-indigo-100">
            <div
                class="h-full rounded-full bg-indigo-600 transition-all duration-300"
                :style="{ width: `${progressPercent}%` }"
            />
        </div>

        <!-- Question -->
        <div class="mt-8 rounded-lg bg-indigo-50 p-4">
            <div class="flex gap-4">
                <div class="mt-0.5">
                    <QuestionNumber :number="currentQuestionNumber" />
                </div>
                <div class="flex flex-1 items-start gap-3">
                    <span class="flex-1 text-indigo-900 sm:text-xl">
                        {{ questionText }}
                        <span v-if="isRequired" class="ml-1 text-red-500">
                            *
                        </span>
                    </span>
                    <OptionalBadge v-if="!isRequired" />
                </div>
            </div>
            <p
                v-if="questionPurpose"
                class="mt-4 ml-10 text-sm text-indigo-600"
            >
                {{ questionPurpose }}
            </p>
        </div>

        <!-- Answer Input -->
        <FormTextareaWithActions
            id="answer"
            ref="textareaRef"
            data-testid="answer-textarea"
            :model-value="answer"
            label="Your Answer"
            sr-only-label
            :disabled="isSubmitting"
            placeholder="Type your answer here, or record a quick note..."
            @update:model-value="(value) => emit('update:answer', value)"
        >
            <template #actions>
                <ButtonTrash
                    v-if="answer"
                    class="mr-2"
                    :disabled="isSubmitting"
                    @click="emit('clear')"
                />
                <ButtonVoiceInput
                    :disabled="isSubmitting"
                    @transcription="handleTranscription"
                />
            </template>
        </FormTextareaWithActions>

        <!-- Action Buttons -->
        <div
            class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="mt-4 flex flex-col sm:flex-row sm:flex-wrap">
                <ButtonSecondary
                    v-if="canGoBack"
                    type="button"
                    :disabled="isSubmitting"
                    class="w-full sm:w-auto"
                    @click="emit('go-back')"
                >
                    <DynamicIcon name="arrow-left" class="mr-2 -ml-1 h-4 w-4" />
                    Back
                </ButtonSecondary>
            </div>

            <div
                class="mt-4 flex flex-col-reverse gap-4 sm:flex-row sm:items-center"
            >
                <ButtonSecondary
                    v-if="!isRequired && currentQuestionNumber < totalQuestions"
                    type="button"
                    :disabled="isSubmitting"
                    :loading="isSubmitting"
                    class="w-full sm:w-auto"
                    @click="emit('submit-all')"
                >
                    Submit All Answers
                    <DynamicIcon name="arrow-right" class="ml-2 h-4 w-4" />
                </ButtonSecondary>

                <ButtonPrimary
                    type="button"
                    data-testid="submit-answer-button"
                    :disabled="isSubmitting"
                    :loading="isSubmitting"
                    class="h-12 w-full sm:h-fit sm:w-auto"
                    @click="emit('submit')"
                >
                    {{
                        currentQuestionNumber === totalQuestions
                            ? 'Submit All Answers'
                            : 'Next Question'
                    }}
                    <DynamicIcon name="arrow-right" class="ml-2 h-4 w-4" />
                </ButtonPrimary>
            </div>
        </div>
    </div>
</template>
