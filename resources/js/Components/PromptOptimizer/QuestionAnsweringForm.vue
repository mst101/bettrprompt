<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import Card from '@/Components/Card.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import { computed } from 'vue';

interface Props {
    question: string;
    answer: string;
    currentQuestionNumber: number;
    totalQuestions: number;
    isSubmitting: boolean;
    hasError?: boolean;
    errorMessage?: string;
    showAll?: boolean;
}

interface Emits {
    (e: 'update:answer', value: string): void;
    (e: 'submit'): void;
    (e: 'skip'): void;
    (e: 'clear'): void;
    (e: 'toggle-show-all'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const { appendText } = useTextAppend();

const handleTranscription = (text: string) => {
    const newAnswer = appendText(props.answer, text);
    emit('update:answer', newAnswer);
};

const progressPercent = computed(() => {
    if (props.totalQuestions === 0) return 0;
    return (props.currentQuestionNumber / props.totalQuestions) * 100;
});

const textareaClasses = computed(() => {
    const baseClasses =
        'mt-1 block w-full rounded-md border-indigo-300 bg-indigo-50 text-indigo-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm';
    const conditionalClasses = [];

    if (props.isSubmitting) {
        conditionalClasses.push('cursor-not-allowed opacity-50');
    }

    if (props.hasError) {
        conditionalClasses.push(
            'border-red-300 focus:border-red-500 focus:ring-red-500',
        );
    }

    return [baseClasses, ...conditionalClasses].join(' ');
});
</script>

<template>
    <Card>
        <div class="space-y-6">
            <!-- Progress -->
            <div data-testid="progress-indicator">
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-700"
                            >Question {{ currentQuestionNumber }} of
                            {{ totalQuestions }}</span
                        >
                        <button
                            @click="emit('toggle-show-all')"
                            type="button"
                            class="text-indigo-600 hover:text-indigo-800 hover:underline"
                        >
                            {{ showAll ? '(one-at-a-time)' : '(show all)' }}
                        </button>
                    </div>
                    <span class="text-gray-500"
                        >{{ Math.round(progressPercent) }}% complete</span
                    >
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200">
                    <div
                        data-testid="progress-bar"
                        class="h-full bg-indigo-600 transition-all duration-300"
                        :style="{ width: `${progressPercent}%` }"
                    ></div>
                </div>
            </div>

            <!-- Question -->
            <div class="rounded-lg bg-indigo-50 p-4">
                <p class="text-sm font-medium text-indigo-900">
                    {{ question }}
                </p>
            </div>

            <!-- Answer Input -->
            <FormTextareaWithActions
                id="answer"
                :model-value="answer"
                @update:model-value="emit('update:answer', $event)"
                label="Your Answer"
                :disabled="isSubmitting"
                :error="hasError && errorMessage ? errorMessage : ''"
                placeholder="Type your answer here (or enter via speech)..."
                :rows="4"
                :textarea-class="textareaClasses"
            >
                <template #actions>
                    <ButtonVoiceInput
                        @transcription="handleTranscription"
                        :disabled="isSubmitting"
                    />
                </template>
            </FormTextareaWithActions>

            <!-- Clear Button -->
            <div v-if="answer" class="mt-3 flex justify-end">
                <button
                    @click="emit('clear')"
                    type="button"
                    class="text-sm text-gray-500 hover:text-gray-700"
                    :disabled="isSubmitting"
                >
                    Clear
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between gap-3">
                <ButtonSecondary
                    data-testid="skip-question-button"
                    @click="emit('skip')"
                    :disabled="isSubmitting"
                >
                    Skip Question
                </ButtonSecondary>

                <ButtonPrimary
                    type="button"
                    data-testid="submit-answer-button"
                    @click="emit('submit')"
                    :disabled="!answer.trim() || isSubmitting"
                    :loading="isSubmitting"
                >
                    Submit Answer
                </ButtonPrimary>
            </div>
        </div>
    </Card>
</template>
