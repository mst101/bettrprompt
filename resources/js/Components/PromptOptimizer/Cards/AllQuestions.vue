<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonText from '@/Components/ButtonText.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import Card from '@/Components/Card.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import ButtonTrash from '@/Components/PromptOptimizer/ButtonTrash.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
import type { PromptRunResource } from '@/types';
import { computed, ref } from 'vue';

interface Progress {
    answered: number;
    total: number;
}

interface Props {
    promptRun: PromptRunResource;
    progress: Progress;
    editable?: boolean;
    answers?: Map<number, string>;
    isSubmitting?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    editable: false,
    answers: () => new Map(),
    isSubmitting: false,
});

const emit = defineEmits<{
    (e: 'toggle-show-all'): void;
    (e: 'update:answer', index: number, value: string): void;
    (e: 'submit-all'): void;
}>();

const { appendText } = useTextAppend();

// Local state for answers when in editable mode
const localAnswers = ref<Map<number, string>>(
    props.answers ? new Map(props.answers) : new Map(),
);

// Update local answer
const updateAnswer = (index: number, value: string) => {
    localAnswers.value.set(index, value);
    emit('update:answer', index, value);
};

const handleTranscription = (index: number, text: string) => {
    const currentAnswer = getAnswer(index);
    const newAnswer = appendText(currentAnswer, text);
    updateAnswer(index, newAnswer);
};

const clearAnswer = (index: number) => {
    updateAnswer(index, '');
};

// Get the answer for a specific index
const getAnswer = (index: number): string => {
    if (props.editable && localAnswers.value.has(index)) {
        return localAnswers.value.get(index) || '';
    }
    if (
        props.promptRun.clarifyingAnswers &&
        props.promptRun.clarifyingAnswers[index]
    ) {
        return props.promptRun.clarifyingAnswers[index] || '';
    }
    return '';
};

// Calculate how many questions have been answered (non-empty)
const answeredCount = computed(() => {
    if (!props.editable) {
        return props.progress.answered;
    }

    let count = 0;
    for (let i = 0; i < props.progress.total; i++) {
        const answer = getAnswer(i);
        if (answer && answer.trim()) {
            count++;
        }
    }
    return count;
});

const progressPercent = computed(() => {
    if (props.progress.total === 0) return 0;
    return (answeredCount.value / props.progress.total) * 100;
});

// Check if we can submit (at least one question answered)
const canSubmit = computed(() => {
    return answeredCount.value > 0 && !props.isSubmitting;
});

const isAnswered = (index: number) => {
    const answer = getAnswer(index);
    return answer && answer.trim().length > 0;
};

const isSkipped = (index: number) => {
    if (props.editable) {
        return false; // In edit mode, we don't show skipped state
    }
    return (
        props.promptRun.clarifyingAnswers &&
        props.promptRun.clarifyingAnswers[index] === null
    );
};
</script>

<template>
    <Card>
        <div class="space-y-6">
            <!-- Header with progress -->
            <div>
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-700"
                            >All Questions{{
                                editable ? '' : ' (Read-only)'
                            }}</span
                        >
                        <ButtonText
                            id="toggle-question-view"
                            type="button"
                            :underline="true"
                            @click="emit('toggle-show-all')"
                        >
                            (one-at-a-time)
                        </ButtonText>
                    </div>
                    <span class="text-gray-600"
                        >{{ answeredCount }} of
                        {{ progress.total }} answered</span
                    >
                </div>
                <div
                    v-if="editable"
                    class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200"
                >
                    <div
                        class="h-full bg-indigo-600 transition-all duration-300"
                        :style="{ width: `${progressPercent}%` }"
                    ></div>
                </div>
            </div>

            <!-- All questions list -->
            <div class="space-y-4">
                <div
                    v-for="(question, index) in promptRun.frameworkQuestions"
                    :key="index"
                    class="rounded-lg border p-4"
                    :class="{
                        'border-green-200 bg-green-50':
                            !editable && isAnswered(index),
                        'border-gray-200': editable || !isAnswered(index),
                    }"
                >
                    <!-- Question Header -->
                    <div class="mb-3 flex items-start gap-2">
                        <span
                            class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-medium text-indigo-800"
                        >
                            {{ index + 1 }}
                        </span>
                        <p class="flex-1 text-sm font-medium text-gray-900">
                            {{ question }}
                        </p>
                    </div>

                    <!-- Editable Answer Input -->
                    <div v-if="editable" class="ml-8">
                        <FormTextareaWithActions
                            :id="`question-${index}`"
                            :model-value="getAnswer(index)"
                            :label="`Answer ${index + 1}`"
                            :disabled="isSubmitting"
                            placeholder="Your answer (leave empty to skip)..."
                            :rows="3"
                            @update:model-value="updateAnswer(index, $event)"
                        >
                            <template #actions>
                                <ButtonTrash
                                    v-if="getAnswer(index)"
                                    :disabled="isSubmitting"
                                    @click="clearAnswer(index)"
                                />
                                <ButtonVoiceInput
                                    :disabled="isSubmitting"
                                    @transcription="
                                        handleTranscription(index, $event)
                                    "
                                />
                            </template>
                        </FormTextareaWithActions>
                    </div>

                    <!-- Read-only Answer Display -->
                    <div v-else class="ml-8">
                        <div
                            v-if="isAnswered(index)"
                            class="text-sm text-gray-700"
                        >
                            <strong>Answer:</strong>
                            {{ promptRun.clarifyingAnswers![index] }}
                        </div>
                        <div
                            v-else-if="isSkipped(index)"
                            class="text-sm text-gray-500 italic"
                        >
                            Question skipped
                        </div>
                        <div v-else class="text-sm text-gray-400 italic">
                            Not yet answered
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons (only in editable mode) -->
            <div
                v-if="editable"
                class="flex items-center justify-between gap-3"
            >
                <ButtonSecondary
                    type="button"
                    :disabled="isSubmitting"
                    @click="emit('toggle-show-all')"
                >
                    Back to One-at-a-Time
                </ButtonSecondary>

                <ButtonPrimary
                    type="button"
                    :disabled="!canSubmit"
                    :loading="isSubmitting"
                    @click="emit('submit-all')"
                >
                    Submit All Answers
                </ButtonPrimary>
            </div>
        </div>
    </Card>
</template>
