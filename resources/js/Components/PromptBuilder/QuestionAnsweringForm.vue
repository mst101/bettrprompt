<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
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
    showAll?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canGoBack: false,
    showAll: false,
});

const emit = defineEmits<{
    (e: 'update:answer', value: string): void;
    (e: 'submit'): void;
    (e: 'skip'): void;
    (e: 'go-back'): void;
    (e: 'clear'): void;
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
    <Card>
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
            <div class="rounded-lg bg-indigo-50 p-4">
                <h3 class="text-sm font-medium text-indigo-900">
                    {{ question }}
                </h3>
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
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <ButtonSecondary
                        v-if="canGoBack"
                        type="button"
                        :disabled="isSubmitting"
                        @click="emit('go-back')"
                    >
                        <DynamicIcon name="arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </ButtonSecondary>
                    <ButtonSecondary
                        type="button"
                        :disabled="isSubmitting || !answer"
                        @click="emit('clear')"
                    >
                        Clear
                    </ButtonSecondary>
                    <ButtonSecondary
                        type="button"
                        :disabled="isSubmitting"
                        @click="emit('skip')"
                    >
                        Skip Question
                    </ButtonSecondary>
                </div>

                <ButtonPrimary
                    type="button"
                    :disabled="isSubmitting || !answer.trim()"
                    :loading="isSubmitting"
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
    </Card>
</template>
