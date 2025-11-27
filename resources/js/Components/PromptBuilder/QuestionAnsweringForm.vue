<script setup lang="ts">
import FormTextarea from '@/Components/FormTextarea.vue';

interface Props {
    question: string;
    answer: string;
    currentQuestionNumber: number;
    totalQuestions: number;
    isSubmitting: boolean;
    canGoBack: boolean;
    showAll: boolean;
}

interface Emits {
    (e: 'update:answer', value: string): void;
    (e: 'submit'): void;
    (e: 'skip'): void;
    (e: 'goBack'): void;
    (e: 'clear'): void;
    (e: 'toggleShowAll'): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const handleSubmit = () => {
    emit('submit');
};

const handleSkip = () => {
    emit('skip');
};

const handleGoBack = () => {
    emit('goBack');
};

const handleClear = () => {
    emit('clear');
};

const handleToggleShowAll = () => {
    emit('toggleShowAll');
};
</script>

<template>
    <div class="space-y-4">
        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">
                    Question {{ currentQuestionNumber }} of {{ totalQuestions }}
                </span>
                <span class="text-sm text-gray-600">
                    {{
                        Math.round(
                            (currentQuestionNumber / totalQuestions) * 100,
                        )
                    }}% complete
                </span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                <div
                    class="h-full rounded-full bg-blue-600 transition-all duration-300"
                    :style="{
                        width: `${(currentQuestionNumber / totalQuestions) * 100}%`,
                    }"
                />
            </div>
        </div>

        <!-- Question -->
        <div class="rounded-lg bg-gray-50 p-4">
            <h3 class="mb-2 text-lg font-medium text-gray-900">
                {{ question }}
            </h3>
        </div>

        <!-- Answer Input -->
        <FormTextarea
            id="answer"
            :model-value="answer"
            label="Your Answer"
            :rows="6"
            :disabled="isSubmitting"
            placeholder="Type your answer here..."
            @update:model-value="(value) => emit('update:answer', value)"
        />

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-2">
                <button
                    v-if="canGoBack"
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    :disabled="isSubmitting"
                    @click="handleGoBack"
                >
                    Back
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    :disabled="isSubmitting || !answer"
                    @click="handleClear"
                >
                    Clear
                </button>
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    :disabled="isSubmitting"
                    @click="handleSkip"
                >
                    Skip
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    :disabled="isSubmitting || !answer.trim()"
                    @click="handleSubmit"
                >
                    <span v-if="isSubmitting">Submitting...</span>
                    <span v-else-if="currentQuestionNumber === totalQuestions">
                        Submit All Answers
                    </span>
                    <span v-else>Next Question</span>
                </button>
            </div>
        </div>

        <!-- Toggle to show all questions -->
        <div class="mt-4 text-center">
            <button
                type="button"
                class="text-sm text-blue-600 underline hover:text-blue-700"
                @click="handleToggleShowAll"
            >
                {{ showAll ? 'Back to one-at-a-time' : 'Show all questions' }}
            </button>
        </div>
    </div>
</template>
