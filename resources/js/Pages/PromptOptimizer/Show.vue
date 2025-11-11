<script setup lang="ts">
import AllQuestionsCard from '@/Components/PromptOptimizer/AllQuestionsCard.vue';
import ClarifyingQuestionsCard from '@/Components/PromptOptimizer/ClarifyingQuestionsCard.vue';
import ErrorDisplay from '@/Components/PromptOptimizer/ErrorDisplay.vue';
import FrameworkSelectionDisplay from '@/Components/PromptOptimizer/FrameworkSelectionDisplay.vue';
import LoadingStateCard from '@/Components/PromptOptimizer/LoadingStateCard.vue';
import OptimizedPromptDisplay from '@/Components/PromptOptimizer/OptimizedPromptDisplay.vue';
import QuestionAnsweringForm from '@/Components/PromptOptimizer/QuestionAnsweringForm.vue';
import TaskInformationCard from '@/Components/PromptOptimizer/TaskInformationCard.vue';
import { usePromptAnswering } from '@/Composables/usePromptAnswering';
import { useRealtimeUpdates } from '@/Composables/useRealtimeUpdates';
import { PERSONALITY_TYPE_NAMES } from '@/constants/workflow';
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

const getPersonalityTypeName = (type: string | null) => {
    if (!type) return '';
    // Extract base type without -A/-T suffix
    const baseType = type.split('-')[0] as keyof typeof PERSONALITY_TYPE_NAMES;
    return PERSONALITY_TYPE_NAMES[baseType] || '';
};

const getFullPersonalityType = (type: string | null) => {
    if (!type) return '';
    const name = getPersonalityTypeName(type);
    return name ? `${name} (${type})` : type;
};

const personalityTypeLabel = computed(() =>
    getFullPersonalityType(props.promptRun.personalityType),
);

// Type guard for n8nResponsePayload
const errorResponse = computed((): N8nErrorResponse | null => {
    const payload = props.promptRun.n8nResponsePayload;
    if (payload && typeof payload === 'object' && 'details' in payload) {
        return payload as N8nErrorResponse;
    }
    return null;
});

// Show all questions mode
const showAllQuestions = ref(false);

const toggleShowAll = () => {
    showAllQuestions.value = !showAllQuestions.value;
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
            <TaskInformationCard
                :prompt-run="promptRun"
                :personality-type-label="personalityTypeLabel"
                class="mb-6"
            />

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
                    currentQuestion &&
                    !showAllQuestions
                "
                :question="currentQuestion"
                v-model:answer="answerForm.answer"
                :current-question-number="progress.answered + 1"
                :total-questions="progress.total"
                :is-submitting="isSubmitting"
                :has-error="!!answerForm.errors.answer"
                :error-message="answerForm.errors.answer"
                :show-all="showAllQuestions"
                @submit="submitAnswer"
                @skip="skipQuestion"
                @clear="clearAnswer"
                @toggle-show-all="toggleShowAll"
                class="mb-6"
            />

            <!-- Show All Questions Mode -->
            <AllQuestionsCard
                v-if="
                    (promptRun.workflowStage === 'framework_selected' ||
                        promptRun.workflowStage === 'answering_questions') &&
                    showAllQuestions &&
                    promptRun.frameworkQuestions
                "
                :prompt-run="promptRun"
                :progress="progress"
                @toggle-show-all="toggleShowAll"
                class="mb-6"
            />

            <!-- Generating Prompt Loading State -->
            <LoadingStateCard
                v-if="promptRun.workflowStage === 'generating_prompt'"
                state="generating-prompt"
                :selected-framework="promptRun.selectedFramework ?? undefined"
            />

            <!-- Clarifying Questions & Answers -->
            <ClarifyingQuestionsCard
                v-if="
                    promptRun.frameworkQuestions &&
                    promptRun.frameworkQuestions.length > 0 &&
                    promptRun.clarifyingAnswers &&
                    promptRun.clarifyingAnswers.length > 0 &&
                    promptRun.workflowStage === 'completed'
                "
                :prompt-run="promptRun"
                class="mb-6"
            />

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
            <LoadingStateCard
                v-else-if="
                    promptRun.status === 'processing' &&
                    promptRun.workflowStage === 'submitted'
                "
                state="selecting-framework"
            />
        </div>
    </div>
</template>
