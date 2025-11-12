<script setup lang="ts">
import AllQuestions from '@/Components/PromptOptimizer/Cards/AllQuestions.vue';
import ClarifyingQuestions from '@/Components/PromptOptimizer/Cards/ClarifyingQuestions.vue';
import FrameworkSelection from '@/Components/PromptOptimizer/Cards/FrameworkSelection.vue';
import OptimizedPrompt from '@/Components/PromptOptimizer/Cards/OptimizedPrompt.vue';
import RelatedPromptRuns from '@/Components/PromptOptimizer/Cards/RelatedPromptRuns.vue';
import TaskInformation from '@/Components/PromptOptimizer/Cards/TaskInformation.vue';
import ClarifyingAnswersEdit from '@/Components/PromptOptimizer/ClarifyingAnswersEdit.vue';
import EditTaskForm from '@/Components/PromptOptimizer/EditTaskForm.vue';
import ErrorDisplay from '@/Components/PromptOptimizer/ErrorDisplay.vue';
import LoadingStateCard from '@/Components/PromptOptimizer/LoadingStateCard.vue';
import QuestionAnsweringForm from '@/Components/PromptOptimizer/QuestionAnsweringForm.vue';
import Tabs, { type Tab } from '@/Components/Tabs.vue';
import { usePromptAnswering } from '@/Composables/usePromptAnswering';
import { useRealtimeUpdates } from '@/Composables/useRealtimeUpdates';
import { PERSONALITY_TYPE_NAMES } from '@/constants/workflow';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { N8nErrorResponse, PromptRunResource } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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

// Edit mode state for task description
const isEditingTask = ref(false);

const startEditingTask = () => {
    isEditingTask.value = true;
};

const cancelEditingTask = () => {
    isEditingTask.value = false;
};

// Edit mode state for clarifying answers
const isEditingAnswers = ref(false);

const startEditingAnswers = () => {
    isEditingAnswers.value = true;
};

const cancelEditingAnswers = () => {
    isEditingAnswers.value = false;
};

// Reset edit modes when navigating to different prompt run
watch(
    () => props.promptRun.id,
    () => {
        isEditingTask.value = false;
        isEditingAnswers.value = false;
    },
);

// Show all questions mode
const showAllQuestions = ref(false);

const toggleShowAll = () => {
    showAllQuestions.value = !showAllQuestions.value;
};

// Question answering composable with pre-population from parent
const { answerForm, isSubmitting, submitAnswer, skipQuestion, clearAnswer } =
    usePromptAnswering(props.promptRun.id);

// Pre-populate answer if similar question exists in parent
const findSimilarAnswer = (currentQuestion: string): string | null => {
    if (!props.promptRun.parent) return null;

    const parentQuestions = props.promptRun.parent.frameworkQuestions;
    const parentAnswers = props.promptRun.parent.clarifyingAnswers;

    if (!parentQuestions || !parentAnswers) return null;

    // Find exact match or similar question
    const index = parentQuestions.findIndex((q) => q === currentQuestion);
    if (index !== -1 && parentAnswers[index]) {
        return parentAnswers[index];
    }

    return null;
};

// Watch for current question changes and pre-populate if available
watch(
    () => props.currentQuestion,
    (newQuestion) => {
        if (newQuestion && !answerForm.answer) {
            const similarAnswer = findSimilarAnswer(newQuestion);
            if (similarAnswer) {
                answerForm.answer = similarAnswer;
            }
        }
    },
    { immediate: true },
);

// Save edited optimised prompt
const saveOptimizedPrompt = (editedPrompt: string) => {
    router.patch(
        route('prompt-optimizer.update-prompt', {
            promptRun: props.promptRun.id,
        }),
        {
            optimized_prompt: editedPrompt,
        },
        {
            preserveScroll: true,
        },
    );
};

// Real-time updates composable
useRealtimeUpdates(
    `prompt-run.${props.promptRun.id}`,
    {
        FrameworkSelected: () => {},
        PromptOptimizationCompleted: () => {},
    },
    { only: ['promptRun', 'currentQuestion', 'progress'] },
);

// Tab navigation for completed runs
const activeTab = ref('prompt');

const hasRelatedRuns = computed(
    () =>
        props.promptRun.parent ||
        (props.promptRun.children && props.promptRun.children.length > 0),
);

const hasAnsweredQuestions = computed(
    () =>
        props.promptRun.frameworkQuestions &&
        props.promptRun.frameworkQuestions.length > 0 &&
        props.promptRun.clarifyingAnswers &&
        props.promptRun.clarifyingAnswers.length > 0 &&
        props.promptRun.workflowStage === 'completed',
);

const tabs = computed<Tab[]>(() => {
    const allTabs: Tab[] = [];

    // Optimised Prompt tab (first, only for completed runs with prompt)
    if (props.promptRun.optimizedPrompt) {
        allTabs.push({
            id: 'prompt',
            label: 'Optimised Prompt',
            icon: 'sparkles',
        });
    }

    // Your Task tab (always shown)
    allTabs.push({
        id: 'task',
        label: 'Your Task',
        icon: 'squares-2x2',
    });

    if (props.promptRun.selectedFramework) {
        allTabs.push({
            id: 'framework',
            label: 'Framework',
            icon: 'beaker',
        });
    }

    if (hasAnsweredQuestions.value) {
        allTabs.push({
            id: 'questions',
            label: 'Questions',
            icon: 'question-mark-circle',
            badge: props.promptRun.frameworkQuestions?.length || undefined,
        });
    }

    return allTabs;
});

// Reset to prompt tab when navigating between prompt runs
watch(
    () => props.promptRun.id,
    () => {
        activeTab.value = 'prompt';
    },
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
            <!-- Tabs for completed runs with optional sections -->
            <div
                v-if="
                    promptRun.workflowStage === 'completed' && tabs.length > 1
                "
                class="mb-6"
            >
                <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
                    <div class="px-6 pt-6">
                        <Tabs v-model="activeTab" :tabs="tabs" />
                    </div>

                    <div class="p-6">
                        <!-- Optimised Prompt Tab -->
                        <div v-show="activeTab === 'prompt'">
                            <OptimizedPrompt
                                v-if="promptRun.optimizedPrompt"
                                :optimized-prompt="promptRun.optimizedPrompt"
                                :prompt-run-id="promptRun.id"
                                @save="saveOptimizedPrompt"
                            />
                        </div>

                        <!-- Your Task Tab -->
                        <div v-show="activeTab === 'task'">
                            <TaskInformation
                                v-if="!isEditingTask"
                                :prompt-run="promptRun"
                                :show-edit-button="true"
                                @edit="startEditingTask"
                                class="mb-6"
                            />

                            <div
                                v-else
                                class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-gray-50"
                            >
                                <div class="p-6">
                                    <h3
                                        class="mb-4 text-lg font-semibold text-gray-900"
                                    >
                                        Edit Task & Create New Optimisation
                                    </h3>
                                    <EditTaskForm
                                        :prompt-run-id="promptRun.id"
                                        :initial-task-description="
                                            promptRun.taskDescription
                                        "
                                        @cancel="cancelEditingTask"
                                    />
                                </div>
                            </div>

                            <RelatedPromptRuns
                                v-if="hasRelatedRuns"
                                :parent="promptRun.parent"
                                :children="promptRun.children"
                            />
                        </div>

                        <!-- Framework Tab -->
                        <div v-show="activeTab === 'framework'">
                            <FrameworkSelection
                                v-if="
                                    promptRun.selectedFramework &&
                                    promptRun.frameworkReasoning
                                "
                                :framework="promptRun.selectedFramework"
                                :reasoning="promptRun.frameworkReasoning"
                            />
                        </div>

                        <!-- Questions Tab -->
                        <div v-show="activeTab === 'questions'">
                            <ClarifyingQuestions
                                v-if="hasAnsweredQuestions && !isEditingAnswers"
                                :prompt-run="promptRun"
                                @edit="startEditingAnswers"
                            />

                            <div
                                v-if="isEditingAnswers"
                                class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50"
                            >
                                <div class="p-6">
                                    <h3
                                        class="mb-4 text-lg font-semibold text-gray-900"
                                    >
                                        Edit Clarifying Answers & Create New
                                        Optimisation
                                    </h3>
                                    <ClarifyingAnswersEdit
                                        :prompt-run="promptRun"
                                        @cancel="cancelEditingAnswers"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Non-completed or single-section runs: show original layout -->
            <template v-else>
                <!-- Related Prompt Runs -->
                <RelatedPromptRuns
                    v-if="hasRelatedRuns"
                    :parent="promptRun.parent"
                    :children="promptRun.children"
                    class="mb-6"
                />

                <!-- Input Information -->
                <TaskInformation
                    v-if="!isEditingTask"
                    :prompt-run="promptRun"
                    :personality-type-label="personalityTypeLabel"
                    :show-edit-button="promptRun.status === 'completed'"
                    @edit="startEditingTask"
                    class="mb-6"
                />

                <!-- Edit Task Form -->
                <div
                    v-else
                    class="mb-6 overflow-hidden bg-white shadow-xs sm:rounded-lg"
                >
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">
                            Edit Task & Create New Optimisation
                        </h3>
                        <EditTaskForm
                            :prompt-run-id="promptRun.id"
                            :initial-task-description="
                                promptRun.taskDescription
                            "
                            @cancel="cancelEditingTask"
                        />
                    </div>
                </div>

                <!-- Framework Selection Info -->
                <FrameworkSelection
                    v-if="
                        promptRun.selectedFramework &&
                        promptRun.frameworkReasoning
                    "
                    :framework="promptRun.selectedFramework"
                    :reasoning="promptRun.frameworkReasoning"
                    class="mb-6"
                />
            </template>

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
            <AllQuestions
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
