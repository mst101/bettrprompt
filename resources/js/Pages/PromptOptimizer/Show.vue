<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import LinkButton from '@/Components/LinkButton.vue';
import FrameworkSelection from '@/Components/PromptOptimizer/Cards/FrameworkSelection.vue';
import OptimizedPrompt from '@/Components/PromptOptimizer/Cards/OptimizedPrompt.vue';
import RelatedPromptRuns from '@/Components/PromptOptimizer/Cards/RelatedPromptRuns.vue';
import TaskInformation from '@/Components/PromptOptimizer/Cards/TaskInformation.vue';
import ClarifyingAnswersEdit from '@/Components/PromptOptimizer/ClarifyingAnswersEdit.vue';
import ErrorDisplay from '@/Components/PromptOptimizer/ErrorDisplay.vue';
import LoadingStateCard from '@/Components/PromptOptimizer/LoadingStateCard.vue';
import QuestionAnsweringForm from '@/Components/PromptOptimizer/QuestionAnsweringForm.vue';
import Tabs, { type Tab } from '@/Components/Tabs.vue';
import { usePromptAnswering } from '@/Composables/usePromptAnswering';
import { useRealtimeUpdates } from '@/Composables/useRealtimeUpdates';
import { PERSONALITY_TYPE_NAMES } from '@/constants/workflow';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { N8nErrorResponse, PromptRunResource } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

const page = usePage<{
    flash: {
        previous_answer?: string | null;
    };
}>();

interface Progress {
    answered: number;
    total: number;
}

interface Props {
    promptRun: PromptRunResource;
    currentQuestion: string | null;
    progress: Progress;
}

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

// Initialize form for editing answers (convert nulls to empty strings)
const initialAnswers =
    props.promptRun.clarifyingAnswers?.map((answer) => answer ?? '') ?? [];

const answersEditForm = useForm({
    clarifying_answers: initialAnswers,
});

const startEditingAnswers = () => {
    isEditingAnswers.value = true;
    // Reset form to current values when starting edit
    answersEditForm.clarifying_answers =
        props.promptRun.clarifyingAnswers?.map((answer) => answer ?? '') ?? [];
};

const cancelEditingAnswers = () => {
    isEditingAnswers.value = false;
    answersEditForm.reset();
    answersEditForm.clearErrors();
};

const submitEditedAnswers = () => {
    answersEditForm.post(
        route('prompt-optimizer.create-child-from-answers', {
            parentPromptRun: props.promptRun.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditingAnswers.value = false;
                // Redirect happens automatically via controller
            },
        },
    );
};

// Reset edit modes when navigating to different prompt run
watch(
    () => props.promptRun.id,
    () => {
        isEditingTask.value = false;
        isEditingAnswers.value = false;
        answersEditForm.reset();
        answersEditForm.clearErrors();
    },
);

// Show all questions mode
const showAllQuestions = ref(false);

const toggleShowAll = () => {
    showAllQuestions.value = !showAllQuestions.value;
};

// Question answering composable with pre-population from parent
const {
    answerForm,
    isSubmitting,
    submitAnswer,
    skipQuestion,
    goBackToPreviousQuestion,
    clearAnswer,
} = usePromptAnswering(props.promptRun.id);

// Get the current answer if it exists (for going back or pre-population)
const getCurrentAnswer = (): string | null => {
    // First check if we have a previousAnswer from going back (via flash)
    const flashPreviousAnswer = page.props.flash.previous_answer;
    if (flashPreviousAnswer !== undefined && flashPreviousAnswer !== null) {
        return flashPreviousAnswer;
    }

    // Otherwise check clarifyingAnswers array
    if (!props.promptRun.clarifyingAnswers) return null;

    // Current question index is progress.answered (0-based)
    const currentIndex = props.progress.answered;
    const answer = props.promptRun.clarifyingAnswers[currentIndex];

    return answer ?? null;
};

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
        if (newQuestion) {
            // First check if we have a current answer (e.g., when going back)
            const currentAnswer = getCurrentAnswer();
            if (currentAnswer) {
                answerForm.answer = currentAnswer;
                return;
            }

            // Otherwise, try to find a similar answer from parent
            if (!answerForm.answer) {
                const similarAnswer = findSimilarAnswer(newQuestion);
                if (similarAnswer) {
                    answerForm.answer = similarAnswer;
                }
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

// Tab navigation for completed runs and question answering
const activeTab = ref('prompt');

const hasRelatedRuns = computed(
    () =>
        !!props.promptRun.parent ||
        (props.promptRun.children && props.promptRun.children.length > 0) ||
        false,
);

const hasAnsweredQuestions = computed(
    () =>
        props.promptRun.frameworkQuestions &&
        props.promptRun.frameworkQuestions.length > 0 &&
        props.promptRun.clarifyingAnswers &&
        props.promptRun.clarifyingAnswers.length > 0 &&
        props.promptRun.workflowStage === 'completed',
);

const isAnsweringQuestions = computed(
    () =>
        props.promptRun.workflowStage === 'framework_selected' ||
        props.promptRun.workflowStage === 'answering_questions',
);

const hasFrameworkQuestions = computed(
    () =>
        props.promptRun.frameworkQuestions &&
        props.promptRun.frameworkQuestions.length > 0,
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

    // Framework tab (shown when framework is selected)
    if (props.promptRun.selectedFramework) {
        allTabs.push({
            id: 'framework',
            label: 'Framework',
            icon: 'beaker',
        });
    }

    // Questions tab for completed runs with answered questions
    if (hasAnsweredQuestions.value) {
        allTabs.push({
            id: 'questions',
            label: 'Questions',
            icon: 'question-mark-circle',
            badge: props.promptRun.frameworkQuestions?.length || undefined,
        });
    }

    // Questions tab for runs in progress (answering questions)
    if (isAnsweringQuestions.value && hasFrameworkQuestions.value) {
        allTabs.push({
            id: 'questions',
            label: 'Clarifying Questions',
            icon: 'question-mark-circle',
            badge: props.promptRun.frameworkQuestions?.length || undefined,
        });
    }

    // Related Runs tab (shown when there are parent or children)
    if (hasRelatedRuns.value) {
        allTabs.push({
            id: 'related',
            label: 'Related Runs',
            icon: 'arrow-path',
        });
    }

    return allTabs;
});

// Set default active tab based on workflow stage
watch(
    () => props.promptRun.id,
    () => {
        if (isAnsweringQuestions.value) {
            activeTab.value = 'questions';
        } else if (props.promptRun.optimizedPrompt) {
            activeTab.value = 'prompt';
        } else {
            activeTab.value = 'task';
        }
    },
    { immediate: true },
);

// Switch to prompt tab when optimized prompt is returned
watch(
    () => props.promptRun.optimizedPrompt,
    (newPrompt) => {
        if (newPrompt) {
            activeTab.value = 'prompt';
        }
    },
);
</script>

<template>
    <Head title="Optimised Prompt" />

    <HeaderPage title="Prompt Optimiser">
        <template #actions>
            <LinkButton
                :href="route('prompt-optimizer.index')"
                variant="primary"
            >
                Create New
            </LinkButton>
        </template>
    </HeaderPage>

    <ContainerPage>
        <!-- Tabs for completed runs and question answering stage -->
        <div
            v-if="
                (promptRun.workflowStage === 'completed' ||
                    isAnsweringQuestions) &&
                tabs.length > 1
            "
            class="mb-6"
        >
            <div
                class="max-w-4xl overflow-hidden bg-white shadow-xs sm:rounded-lg"
            >
                <Tabs v-model="activeTab" :tabs="tabs" />

                <OptimizedPrompt
                    v-if="activeTab === 'prompt' && promptRun.optimizedPrompt"
                    :optimized-prompt="promptRun.optimizedPrompt"
                    :prompt-run-id="promptRun.id"
                    @save="saveOptimizedPrompt"
                />

                <!-- Your Task Tab -->
                <TaskInformation
                    v-show="activeTab === 'task'"
                    :prompt-run="promptRun"
                    :show-edit-button="true"
                    :has-related-runs="hasRelatedRuns"
                    :is-editing="isEditingTask"
                    class="mb-6 px-6"
                    @edit="startEditingTask"
                    @cancel="cancelEditingTask"
                />

                <!-- Framework Tab -->
                <div v-show="activeTab === 'framework'">
                    <FrameworkSelection
                        v-if="
                            promptRun.selectedFramework &&
                            promptRun.frameworkReasoning
                        "
                        :framework="promptRun.selectedFramework"
                        :reasoning="promptRun.frameworkReasoning"
                        :personality-approach="promptRun.personalityApproach"
                    />
                </div>

                <!-- Questions Tab -->
                <div v-show="activeTab === 'questions'">
                    <!-- Question Answering Interface (for in-progress runs) -->
                    <QuestionAnsweringForm
                        v-if="
                            isAnsweringQuestions &&
                            currentQuestion &&
                            !showAllQuestions
                        "
                        v-model:answer="answerForm.answer"
                        :question="currentQuestion"
                        :current-question-number="progress.answered + 1"
                        :total-questions="progress.total"
                        :is-submitting="isSubmitting"
                        :can-go-back="progress.answered > 0"
                        :has-error="!!answerForm.errors.answer"
                        :error-message="answerForm.errors.answer"
                        :show-all="showAllQuestions"
                        @submit="submitAnswer"
                        @skip="skipQuestion"
                        @go-back="goBackToPreviousQuestion"
                        @clear="clearAnswer"
                        @toggle-show-all="toggleShowAll"
                    />

                    <!-- All Questions View (for completed runs) -->
                    <div
                        v-else-if="promptRun.workflowStage === 'completed'"
                        class="overflow-hidden rounded-lg bg-white shadow-xs"
                    >
                        <div class="p-6">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Clarifying Questions
                                </h3>
                                <div
                                    v-if="!isEditingAnswers"
                                    class="flex items-center gap-2"
                                >
                                    <ButtonSecondary
                                        type="button"
                                        @click="startEditingAnswers"
                                    >
                                        Edit Answers
                                    </ButtonSecondary>
                                </div>
                                <div v-else class="flex items-center gap-2">
                                    <ButtonSecondary
                                        type="button"
                                        :disabled="answersEditForm.processing"
                                        @click="cancelEditingAnswers"
                                    >
                                        Cancel
                                    </ButtonSecondary>
                                    <ButtonPrimary
                                        type="button"
                                        :loading="answersEditForm.processing"
                                        @click="submitEditedAnswers"
                                    >
                                        Optimise Prompt with Edited Answers
                                    </ButtonPrimary>
                                </div>
                            </div>

                            <ClarifyingAnswersEdit
                                v-if="isEditingAnswers"
                                :prompt-run="promptRun"
                                :form="answersEditForm"
                            />

                            <div v-else class="space-y-3">
                                <div
                                    v-for="(
                                        question, index
                                    ) in promptRun.frameworkQuestions"
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
                                            <p
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{ question }}
                                            </p>
                                            <div
                                                v-if="
                                                    promptRun.clarifyingAnswers &&
                                                    promptRun.clarifyingAnswers[
                                                        index
                                                    ] !== null &&
                                                    promptRun.clarifyingAnswers[
                                                        index
                                                    ] !== undefined
                                                "
                                                class="mt-2 rounded-md bg-gray-50 p-3"
                                            >
                                                <p
                                                    class="text-sm whitespace-break-spaces text-gray-700"
                                                >
                                                    {{
                                                        promptRun
                                                            .clarifyingAnswers[
                                                            index
                                                        ]
                                                    }}
                                                </p>
                                            </div>
                                            <div
                                                v-else
                                                class="mt-2 rounded-md bg-gray-50 p-3"
                                            >
                                                <p
                                                    class="text-sm text-gray-500 italic"
                                                >
                                                    [Skipped]
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Runs Tab -->
                <div v-show="activeTab === 'related'">
                    <RelatedPromptRuns
                        v-if="hasRelatedRuns"
                        :parent="promptRun.parent"
                        :children="promptRun.children"
                    />
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
                :prompt-run="promptRun"
                :personality-type-label="personalityTypeLabel"
                :show-edit-button="promptRun.status === 'completed'"
                :has-related-runs="hasRelatedRuns"
                :is-editing="isEditingTask"
                class="mb-6"
                @edit="startEditingTask"
                @cancel="cancelEditingTask"
            />

            <!-- Framework Selection Info -->
            <FrameworkSelection
                v-if="
                    promptRun.selectedFramework && promptRun.frameworkReasoning
                "
                :framework="promptRun.selectedFramework"
                :reasoning="promptRun.frameworkReasoning"
                :personality-approach="promptRun.personalityApproach"
                class="mb-6"
            />
        </template>

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
    </ContainerPage>
</template>
