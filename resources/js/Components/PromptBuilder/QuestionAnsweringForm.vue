<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import ButtonTrash from '@/Components/PromptBuilder/ButtonTrash.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import { computed, nextTick, ref, watch } from 'vue';

import type { ClarifyingQuestion } from '@/Components/PromptBuilder/Cards/clarifyingQuestion';

interface Props {
    question: ClarifyingQuestion | string;
    answer: string;
    currentQuestionNumber: number;
    totalQuestions: number;
    isSubmitting: boolean;
    showOptionalHints?: boolean;
    canGoBack?: boolean;
    showAll?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showOptionalHints: false,
    canGoBack: false,
    showAll: false,
});

const emit = defineEmits<{
    (e: 'update:answer', value: string): void;
    (e: 'submit'): void;
    (e: 'skip'): void;
    (e: 'go-back'): void;
    (e: 'clear'): void;
    (e: 'toggle-optional-hints'): void;
}>();

const { appendText } = useTextAppend();
const textareaRef = ref<InstanceType<typeof FormTextareaWithActions> | null>(
    null,
);

const focus = () => {
    nextTick(() => {
        textareaRef.value?.focus();
    });
};

defineExpose({ focus });

watch(
    () => props.question,
    () => {
        focus();
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
    <div class="space-y-6">
        <!-- Progress Bar -->
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
                <span class="font-medium text-gray-700">
                    Question {{ currentQuestionNumber }} of
                    {{ totalQuestions }}
                </span>
            </div>
            <span class="text-gray-600">
                {{ Math.round(progressPercent) }}% complete
            </span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-indigo-100">
            <div
                class="h-full rounded-full bg-indigo-600 transition-all duration-300"
                :style="{ width: `${progressPercent}%` }"
            />
        </div>

        <!-- Question -->
        <div class="space-y-2 rounded-lg bg-indigo-50 p-4">
            <div class="flex items-start gap-3">
                <h3 class="flex-1 text-sm font-medium text-indigo-900">
                    {{ questionText }}
                </h3>
                <span
                    v-if="!isRequired"
                    class="inline-flex shrink-0 items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800"
                >
                    Optional
                </span>
            </div>
            <p
                v-if="questionPurpose && showOptionalHints"
                class="mt-2 text-xs text-indigo-600"
            >
                {{ questionPurpose }}
            </p>
            <button
                v-if="!isRequired"
                type="button"
                :disabled="isSubmitting"
                class="text-xs text-indigo-600 underline hover:text-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                @click="emit('toggle-optional-hints')"
            >
                {{ showOptionalHints ? 'Hide' : 'Show' }} why we're asking
            </button>
        </div>

        <!-- Answer Input -->
        <FormTextareaWithActions
            id="answer"
            ref="textareaRef"
            :model-value="answer"
            label="Your Answer"
            :rows="5"
            :disabled="isSubmitting"
            placeholder="Type your answer here or record a quick note..."
            @update:model-value="(value) => emit('update:answer', value)"
        >
            <template #actions>
                <ButtonTrash
                    v-if="answer"
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
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <ButtonSecondary
                    v-if="canGoBack"
                    type="button"
                    :disabled="isSubmitting"
                    class="w-full sm:w-auto"
                    @click="emit('go-back')"
                >
                    <DynamicIcon name="arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </ButtonSecondary>

                <ButtonSecondary
                    type="button"
                    :disabled="isSubmitting"
                    class="w-full sm:w-auto"
                    @click="emit('skip')"
                >
                    Skip Question
                </ButtonSecondary>
            </div>

            <ButtonPrimary
                type="button"
                :disabled="isSubmitting || !answer.trim()"
                :loading="isSubmitting"
                class="w-full sm:w-auto"
                @click="emit('submit')"
            >
                {{
                    currentQuestionNumber === totalQuestions
                        ? 'Submit All Answers'
                        : 'Next Question'
                }}
            </ButtonPrimary>
        </div>
    </div>
</template>
