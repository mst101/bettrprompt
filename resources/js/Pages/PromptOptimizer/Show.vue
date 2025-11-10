<script setup lang="ts">
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import ErrorDisplay from '@/Components/PromptOptimizer/ErrorDisplay.vue';
import FrameworkSelectionDisplay from '@/Components/PromptOptimizer/FrameworkSelectionDisplay.vue';
import OptimizedPromptDisplay from '@/Components/PromptOptimizer/OptimizedPromptDisplay.vue';
import QuestionAnsweringForm from '@/Components/PromptOptimizer/QuestionAnsweringForm.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { usePromptAnswering } from '@/Composables/usePromptAnswering';
import { useRealtimeUpdates } from '@/Composables/useRealtimeUpdates';
import { getWorkflowStageLabel } from '@/constants/workflow';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { N8nErrorResponse, PromptRunResource } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({
    layout: AppLayout,
});

interface Progress {
    answered: number;
    total: number;
}

interface Props {
    promptRun: PromptRunResource;
    currentQuestion: string | null;
    progress: Progress;
}

const props = defineProps<Props>();

// Type guard for n8nResponsePayload
const errorResponse = computed((): N8nErrorResponse | null => {
    const payload = props.promptRun.n8nResponsePayload;
    if (payload && typeof payload === 'object' && 'details' in payload) {
        return payload as N8nErrorResponse;
    }
    return null;
});

// Collapsible Q&A state for answered questions
const expandedQuestions = ref<Set<number>>(new Set());

const toggleQuestion = (index: number) => {
    if (expandedQuestions.value.has(index)) {
        expandedQuestions.value.delete(index);
    } else {
        expandedQuestions.value.add(index);
    }
};

const allExpanded = () => {
    const totalQuestions = props.promptRun.frameworkQuestions?.length ?? 0;
    return (
        totalQuestions > 0 && expandedQuestions.value.size === totalQuestions
    );
};

const toggleAll = () => {
    if (allExpanded()) {
        expandedQuestions.value.clear();
    } else {
        const totalQuestions = props.promptRun.frameworkQuestions?.length ?? 0;
        for (let i = 0; i < totalQuestions; i++) {
            expandedQuestions.value.add(i);
        }
    }
};

// Question answering composable
const { answerForm, isSubmitting, submitAnswer, skipQuestion, clearAnswer } =
    usePromptAnswering(props.promptRun.id);

// Real-time updates composable
useRealtimeUpdates(
    `prompt-run.${props.promptRun.id}`,
    {
        FrameworkSelected: () => {},
        PromptOptimizationCompleted: () => {},
    },
    { only: ['promptRun', 'currentQuestion', 'progress'] },
);
</script>

<template>
    <Head title="Optimised Prompt" />

    <header class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    Prompt Optimiser
                </h2>
                <Link
                    :href="route('prompt-optimizer.index')"
                    class="items-centre inline-flex rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-xs hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                >
                    Create New
                </Link>
            </div>
        </div>
    </header>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <!-- Input Information -->
            <Card class="mb-6">
                <div class="flex justify-between">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">
                        Your Task
                    </h3>

                    <!-- Status Badges -->
                    <div class="mb-4 flex items-center gap-2">
                        <StatusBadge :status="promptRun.status" />
                        <span
                            v-if="promptRun.status !== 'completed'"
                            class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800"
                        >
                            {{ getWorkflowStageLabel(promptRun.workflowStage) }}
                        </span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <span class="text-sm font-medium text-gray-700"
                            >Personality Type:</span
                        >
                        <span class="ml-2 text-sm text-gray-900">{{
                            promptRun.personalityType
                        }}</span>
                    </div>

                    <div>
                        <span class="text-sm font-medium text-gray-700"
                            >Task Description:</span
                        >
                        <p
                            class="mt-1 ml-2 text-sm whitespace-pre-wrap text-gray-900"
                        >
                            {{ promptRun.taskDescription }}
                        </p>
                    </div>
                </div>
            </Card>

            <!-- Framework Selection Info -->
            <FrameworkSelectionDisplay
                v-if="
                    promptRun.selectedFramework && promptRun.frameworkReasoning
                "
                :framework="promptRun.selectedFramework"
                :reasoning="promptRun.frameworkReasoning"
                class="mb-6"
            />

            <!-- Question Answering Interface -->
            <QuestionAnsweringForm
                v-if="
                    (promptRun.workflowStage === 'framework_selected' ||
                        promptRun.workflowStage === 'answering_questions') &&
                    currentQuestion
                "
                :question="currentQuestion"
                v-model:answer="answerForm.answer"
                :current-question-number="progress.answered + 1"
                :total-questions="progress.total"
                :is-submitting="isSubmitting"
                :has-error="!!answerForm.errors.answer"
                :error-message="answerForm.errors.answer"
                @submit="submitAnswer"
                @skip="skipQuestion"
                @clear="clearAnswer"
                class="mb-6"
            />

            <!-- Generating Prompt Loading State -->
            <div
                v-else-if="promptRun.workflowStage === 'generating_prompt'"
                class="overflow-hidden bg-white shadow-xs sm:rounded-lg"
            >
                <div class="p-6">
                    <div class="flex items-center">
                        <DynamicIcon
                            name="spinner"
                            class="mr-3 h-5 w-5 text-indigo-600"
                        />
                        <div>
                            <p class="font-medium text-gray-900">
                                Generating your optimised prompt...
                            </p>
                            <p class="mt-1 text-sm text-gray-600">
                                This may take a few moments. We're crafting a
                                personalised prompt using the
                                {{ promptRun.selectedFramework }}
                                framework.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clarifying Questions & Answers -->
            <div
                v-if="
                    promptRun.frameworkQuestions &&
                    promptRun.frameworkQuestions.length > 0 &&
                    promptRun.clarifyingAnswers &&
                    promptRun.clarifyingAnswers.length > 0 &&
                    promptRun.workflowStage === 'completed'
                "
                class="mb-6 overflow-hidden bg-white shadow-xs sm:rounded-lg"
            >
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Clarifying Questions
                        </h3>
                        <button
                            @click="toggleAll"
                            class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                        >
                            {{ allExpanded() ? 'Hide All' : 'Show All' }}
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(
                                question, index
                            ) in promptRun.frameworkQuestions"
                            :key="index"
                            class="border-b border-gray-200 pb-3 last:border-b-0"
                        >
                            <div
                                @click="toggleQuestion(index)"
                                @keydown.enter="toggleQuestion(index)"
                                @keydown.space.prevent="toggleQuestion(index)"
                                role="button"
                                tabindex="0"
                                class="flex w-full cursor-pointer items-start justify-between rounded-md p-1 text-left focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                :aria-label="
                                    expandedQuestions.has(index)
                                        ? `Hide question ${index + 1}`
                                        : `Show question ${index + 1}`
                                "
                                :aria-expanded="expandedQuestions.has(index)"
                            >
                                <div class="flex-1">
                                    <div class="flex items-start">
                                        <span
                                            class="mt-0.5 mr-2 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-medium text-indigo-800"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <p
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ question }}
                                        </p>
                                    </div>
                                </div>
                                <DynamicIcon
                                    name="chevron-down"
                                    :class="[
                                        'ml-4 h-5 w-5 shrink-0 text-gray-400 transition-transform',
                                        expandedQuestions.has(index)
                                            ? 'rotate-180'
                                            : '',
                                    ]"
                                />
                            </div>

                            <div
                                v-show="expandedQuestions.has(index)"
                                class="mt-2 ml-8"
                            >
                                <div
                                    v-if="
                                        promptRun.clarifyingAnswers[index] !==
                                            null &&
                                        promptRun.clarifyingAnswers[index] !==
                                            undefined
                                    "
                                    class="rounded-md bg-gray-50 p-3"
                                >
                                    <p
                                        class="text-sm whitespace-break-spaces text-gray-700"
                                    >
                                        {{ promptRun.clarifyingAnswers[index] }}
                                    </p>
                                </div>
                                <div v-else class="rounded-md bg-gray-50 p-3">
                                    <p class="text-sm text-gray-500 italic">
                                        [Skipped]
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Optimised Prompt Result -->
            <OptimizedPromptDisplay
                v-if="
                    promptRun.workflowStage === 'completed' &&
                    promptRun.optimizedPrompt
                "
                :optimized-prompt="promptRun.optimizedPrompt"
            />

            <!-- Error Message -->
            <ErrorDisplay
                v-else-if="promptRun.status === 'failed'"
                :prompt-run-id="promptRun.id"
                :error-message="promptRun.errorMessage ?? undefined"
                :error-response="errorResponse"
            />

            <!-- Processing State (initial submission) -->
            <div
                v-else-if="
                    promptRun.status === 'processing' &&
                    promptRun.workflowStage === 'submitted'
                "
                class="overflow-hidden bg-white shadow-xs sm:rounded-lg"
            >
                <div class="p-6">
                    <div class="flex items-center">
                        <DynamicIcon
                            name="spinner"
                            class="mr-3 h-5 w-5 text-indigo-600"
                        />
                        <span class="text-gray-700"
                            >Selecting optimal framework...</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
