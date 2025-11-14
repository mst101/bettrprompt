<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ClarifyingAnswersEdit from '@/Components/PromptOptimizer/ClarifyingAnswersEdit.vue';
import QuestionAnsweringForm from '@/Components/PromptOptimizer/QuestionAnsweringForm.vue';
import type { PromptRunResource } from '@/types';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    promptRun: PromptRunResource;
    currentQuestion?: string | null;
    progress?: { answered: number; total: number };
    isEditing?: boolean;
    editForm?: InertiaForm<{ clarifying_answers: string[] }>;
    // Question answering props
    answerForm?: InertiaForm<{ answer: string }>;
    isSubmitting?: boolean;
    showAllQuestions?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isEditing: false,
    isSubmitting: false,
    showAllQuestions: false,
});

const emit = defineEmits<{
    (e: 'edit'): void;
    (e: 'cancel'): void;
    (e: 'submit'): void;
    // Question answering events
    (e: 'submit-answer'): void;
    (e: 'skip-question'): void;
    (e: 'go-back'): void;
    (e: 'clear-answer'): void;
    (e: 'toggle-show-all'): void;
}>();

const isAnsweringQuestions = computed(
    () =>
        props.promptRun.workflowStage === 'framework_selected' ||
        props.promptRun.workflowStage === 'answering_questions',
);

const isCompleted = computed(
    () => props.promptRun.workflowStage === 'completed',
);
</script>

<template>
    <!-- Question Answering Interface (for in-progress runs) -->
    <!-- eslint-disable vue/no-mutating-props -->
    <QuestionAnsweringForm
        v-if="
            isAnsweringQuestions &&
            currentQuestion &&
            !showAllQuestions &&
            answerForm
        "
        v-model:answer="answerForm.answer"
        :question="currentQuestion"
        :current-question-number="progress ? progress.answered + 1 : 0"
        :total-questions="progress ? progress.total : 0"
        :is-submitting="isSubmitting"
        :can-go-back="progress ? progress.answered > 0 : false"
        :has-error="!!answerForm.errors.answer"
        :error-message="answerForm.errors.answer"
        :show-all="showAllQuestions"
        @submit="emit('submit-answer')"
        @skip="emit('skip-question')"
        @go-back="emit('go-back')"
        @clear="emit('clear-answer')"
        @toggle-show-all="emit('toggle-show-all')"
    />

    <!-- All Questions View (for completed runs) -->
    <div
        v-else-if="isCompleted"
        class="overflow-hidden rounded-lg bg-white shadow-xs"
    >
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                    Clarifying Questions
                </h3>
                <div v-if="!isEditing" class="flex items-center gap-2">
                    <ButtonSecondary type="button" @click="emit('edit')">
                        Edit Answers
                    </ButtonSecondary>
                </div>
                <div v-else class="flex items-center gap-2">
                    <ButtonSecondary
                        type="button"
                        :disabled="editForm?.processing"
                        @click="emit('cancel')"
                    >
                        Cancel
                    </ButtonSecondary>
                    <ButtonPrimary
                        type="button"
                        :loading="editForm?.processing"
                        @click="emit('submit')"
                    >
                        Optimise Prompt with Edited Answers
                    </ButtonPrimary>
                </div>
            </div>

            <ClarifyingAnswersEdit
                v-if="isEditing && editForm"
                :prompt-run="promptRun"
                :form="editForm"
            />

            <div v-else class="space-y-3">
                <div
                    v-for="(question, index) in promptRun.frameworkQuestions"
                    :key="index"
                    class="border-b border-gray-200 pb-3 last:border-b-0"
                >
                    <div class="flex items-start">
                        <span
                            class="mt-0.5 mr-2 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-medium text-indigo-800"
                        >
                            {{ index + 1 }}
                        </span>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                {{ question }}
                            </p>
                            <div
                                v-if="
                                    promptRun.clarifyingAnswers &&
                                    promptRun.clarifyingAnswers[index] !==
                                        null &&
                                    promptRun.clarifyingAnswers[index] !==
                                        undefined
                                "
                                class="mt-2 rounded-md bg-gray-50 p-3"
                            >
                                <p
                                    class="text-sm whitespace-break-spaces text-gray-700"
                                >
                                    {{ promptRun.clarifyingAnswers[index] }}
                                </p>
                            </div>
                            <div v-else class="mt-2 rounded-md bg-gray-50 p-3">
                                <p class="text-sm text-gray-500 italic">
                                    [Skipped]
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
