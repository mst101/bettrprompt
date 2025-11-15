<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonText from '@/Components/ButtonText.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import ButtonTrash from '@/Components/PromptOptimizer/ButtonTrash.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import { computed, nextTick, ref, watch } from 'vue';

interface Props {
    question: string;
    answer: string;
    currentQuestionNumber: number;
    totalQuestions: number;
    isSubmitting: boolean;
    canGoBack?: boolean;
    hasError?: boolean;
    errorMessage?: string;
    showAll?: boolean;
}

interface Emits {
    (e: 'update:answer', value: string): void;
    (e: 'submit'): void;
    (e: 'skip'): void;
    (e: 'go-back'): void;
    (e: 'clear'): void;
    (e: 'toggle-show-all'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const { appendText } = useTextAppend();
const textareaRef = ref<InstanceType<typeof FormTextareaWithActions> | null>(
    null,
);

// Focus textarea when question changes
watch(
    () => props.question,
    () => {
        nextTick(() => {
            // Find the textarea element within the component
            const textarea = textareaRef.value?.$el?.querySelector('textarea');
            if (textarea) {
                textarea.focus();
            }
        });
    },
);

const handleTranscription = (text: string) => {
    const newAnswer = appendText(props.answer, text);
    emit('update:answer', newAnswer);
};

const progressPercent = computed(() => {
    if (props.totalQuestions === 0) return 0;
    // Calculate based on completed questions (current question number - 1)
    const answeredCount = props.currentQuestionNumber - 1;
    return (answeredCount / props.totalQuestions) * 100;
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
                        <ButtonText
                            id="show-all-questions"
                            type="button"
                            class="text-indigo-600 hover:text-indigo-800 hover:underline"
                            @click="emit('toggle-show-all')"
                        >
                            {{ showAll ? '(one-at-a-time)' : '(show all)' }}
                        </ButtonText>
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
                ref="textareaRef"
                :model-value="answer"
                label="Your Answer"
                :disabled="isSubmitting"
                :error="hasError && errorMessage ? errorMessage : ''"
                placeholder="Type your answer here (or enter via speech)..."
                :rows="4"
                :textarea-class="textareaClasses"
                autofocus
                @update:model-value="emit('update:answer', $event)"
            >
                <template #actions>
                    <ButtonTrash v-if="answer" :disabled="isSubmitting" />
                    <ButtonVoiceInput
                        :disabled="isSubmitting"
                        @transcription="handleTranscription"
                    />
                </template>
            </FormTextareaWithActions>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between gap-3">
                <div class="flex gap-3">
                    <ButtonSecondary
                        v-if="canGoBack"
                        data-testid="back-button"
                        :disabled="isSubmitting"
                        @click="emit('go-back')"
                    >
                        <DynamicIcon name="arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </ButtonSecondary>
                    <ButtonSecondary
                        data-testid="skip-question-button"
                        :disabled="isSubmitting"
                        @click="emit('skip')"
                    >
                        Skip Question
                    </ButtonSecondary>
                </div>

                <ButtonPrimary
                    type="button"
                    data-testid="submit-answer-button"
                    :disabled="!answer.trim() || isSubmitting"
                    :loading="isSubmitting"
                    @click="emit('submit')"
                >
                    Submit Answer
                </ButtonPrimary>
            </div>
        </div>
    </Card>
</template>
