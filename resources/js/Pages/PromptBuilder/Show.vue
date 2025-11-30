<script setup lang="ts">
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import LinkButton from '@/Components/LinkButton.vue';
import AlternativeFrameworks from '@/Components/PromptBuilder/Cards/AlternativeFrameworks.vue';
import ApiUsage from '@/Components/PromptBuilder/Cards/ApiUsage.vue';
import ClarifyingQuestions from '@/Components/PromptBuilder/Cards/ClarifyingQuestions.vue';
import CognitiveRequirements from '@/Components/PromptBuilder/Cards/CognitiveRequirements.vue';
import OptimizedPrompt from '@/Components/PromptBuilder/Cards/OptimizedPrompt.vue';
import PersonalityAdjustments from '@/Components/PromptBuilder/Cards/PersonalityAdjustments.vue';
import PersonalityAdjustmentsSummary from '@/Components/PromptBuilder/Cards/PersonalityAdjustmentsSummary.vue';
import Recommendations from '@/Components/PromptBuilder/Cards/Recommendations.vue';
import RelatedPromptRuns from '@/Components/PromptBuilder/Cards/RelatedPromptRuns.vue';
import SelectedFramework from '@/Components/PromptBuilder/Cards/SelectedFramework.vue';
import TaskClassification from '@/Components/PromptBuilder/Cards/TaskClassification.vue';
import TaskInformation from '@/Components/PromptBuilder/Cards/TaskInformation.vue';
import TaskTraitAlignment from '@/Components/PromptBuilder/Cards/TaskTraitAlignment.vue';
import Tabs, { type Tab } from '@/Components/Tabs.vue';
import { useRealtimeUpdates } from '@/Composables/useRealtimeUpdates';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PromptRunResource } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

interface Progress {
    answered: number;
    total: number;
}

interface Props {
    promptRun: PromptRunResource;
    currentQuestion?: string | null;
    currentQuestionAnswer?: string | null;
    progress?: Progress;
    visitorHasCompletedPrompts?: boolean;
}

// Define tabs
const tabs = computed<Tab[]>(() => {
    const allTabs: Tab[] = [];

    // Your Task tab (always shown)
    allTabs.push({
        id: 'task',
        label: 'Your Task',
        icon: 'squares-2x2',
    });

    // Framework tab
    allTabs.push({
        id: 'framework',
        label: 'Framework',
        icon: 'cube',
    });

    // Add personality tab if tier is not 'none'
    if (
        props.promptRun.personalityTier &&
        props.promptRun.personalityTier !== 'none'
    ) {
        allTabs.push({
            id: 'personality',
            label: 'Personality',
            icon: 'user',
        });
    }

    // Add questions tab
    allTabs.push({
        id: 'questions',
        label: 'Clarifying Questions',
        icon: 'question-mark-circle',
    });

    // Recommendations tab (only shown when model recommendations exist)
    if (
        props.promptRun.modelRecommendations ||
        props.promptRun.iterationSuggestions
    ) {
        allTabs.push({
            id: 'recommendations',
            label: 'Recommendations',
            icon: 'light-bulb',
        });
    }

    // API Usage tab (always shown for transparency)
    allTabs.push({
        id: 'api-usage',
        label: 'API Usage',
        icon: 'chart-bar',
    });

    // Optimised Prompt tab (only for completed runs with prompt)
    if (props.promptRun.optimizedPrompt) {
        allTabs.push({
            id: 'prompt',
            label: 'Optimised Prompt',
            icon: 'sparkles',
        });
    }

    return allTabs;
});

// Determine initial tab based on workflow stage
const getInitialTab = (): string => {
    // If we have an optimised prompt, show it
    if (props.promptRun.optimizedPrompt) {
        return 'prompt';
    }

    // Only show framework tab if analysis is complete
    // Don't show it during 'submitted' or 'processing' stages
    if (
        props.promptRun.selectedFramework &&
        props.promptRun.workflowStage === 'analysis_complete' &&
        props.promptRun.status !== 'processing'
    ) {
        return 'framework';
    }

    // Default to task tab (including during analysis)
    return 'task';
};

const activeTab = ref<string>(getInitialTab());

const hasRelatedRuns = computed(
    () =>
        !!props.promptRun.parent ||
        (props.promptRun.children && props.promptRun.children.length > 0) ||
        false,
);

// Determine if user should proceed to questions
const shouldShowProceedButton = computed(() => {
    if (props.promptRun.workflowStage !== 'analysis_complete') {
        return false;
    }

    // Show button unless ALL questions have been answered
    const totalQuestions = props.promptRun.frameworkQuestions?.length || 0;
    const answeredQuestions =
        props.promptRun.clarifyingAnswers?.filter((a: unknown) => a !== null)
            .length || 0;

    return answeredQuestions < totalQuestions;
});

// Handle proceed to questions button
const handleProceedToQuestions = async () => {
    activeTab.value = 'questions';
};

// Real-time updates via Laravel Echo
useRealtimeUpdates(
    `prompt-run.${props.promptRun.id}`,
    {
        AnalysisCompleted: () => {
            // Reload page to show analysis results
            router.reload({
                only: ['promptRun'],
                onSuccess: () => {
                    // Switch to Framework tab after reload
                    activeTab.value = 'framework';
                },
            });
        },
        PromptOptimizationCompleted: () => {
            // Reload page to show completed prompt
            router.reload({
                only: ['promptRun'],
                onSuccess: () => {
                    // Switch to Optimised Prompt tab after reload
                    activeTab.value = 'prompt';
                },
            });
        },
    },
    { only: ['promptRun'] },
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

const handleDelete = () => {
    if (
        !confirm(
            'Are you sure you want to delete this prompt run? This action cannot be undone.',
        )
    ) {
        return;
    }

    router.delete(route('prompt-builder.destroy', props.promptRun.id), {
        onSuccess: () => {
            // Redirect to history page after successful deletion
            router.visit(route('prompt-builder.history'));
        },
    });
};
</script>

<template>
    <Head title="Prompt Analysis" />

    <HeaderPage title="Prompt Builder">
        <template #actions>
            <div class="space-x-4">
                <ButtonSecondary type="button" @click="handleDelete">
                    <DynamicIcon name="trash" class="h-4 w-4" />
                    Delete
                </ButtonSecondary>
                <LinkButton
                    :href="route('prompt-builder.index')"
                    variant="primary"
                >
                    Create New
                </LinkButton>
            </div>
        </template>
    </HeaderPage>

    <ContainerPage>
        <div class="mb-6 max-w-4xl shadow-xs sm:rounded-lg">
            <Tabs v-model="activeTab" :tabs="tabs" />

            <!-- Optimised Prompt Tab -->
            <OptimizedPrompt
                v-if="activeTab === 'prompt' && promptRun.optimizedPrompt"
                :optimized-prompt="promptRun.optimizedPrompt"
                :prompt-run-id="promptRun.id"
            />

            <!-- Your Task Tab -->
            <div v-if="activeTab === 'task'" class="space-y-4">
                <TaskInformation
                    :prompt-run="promptRun"
                    :visitor-has-completed-prompts="
                        visitorHasCompletedPrompts || false
                    "
                />

                <!-- Loading state when analysis is in progress -->
                <div
                    v-if="
                        promptRun.workflowStage === 'submitted' &&
                        promptRun.status === 'processing'
                    "
                    class="rounded-lg border border-blue-200 bg-blue-50 p-6 text-center"
                >
                    <div
                        class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"
                    ></div>
                    <p class="font-medium text-blue-900">
                        Analysing your task...
                    </p>
                    <p class="mt-1 text-sm text-blue-700">
                        This usually takes 10-30 seconds
                    </p>
                </div>

                <RelatedPromptRuns
                    v-if="hasRelatedRuns"
                    :parent="promptRun.parent"
                    :children="promptRun.children"
                />
                <TaskClassification
                    v-if="promptRun.taskClassification"
                    :classification="promptRun.taskClassification as any"
                />
                <CognitiveRequirements
                    v-if="promptRun.cognitiveRequirements"
                    :requirements="promptRun.cognitiveRequirements as any"
                />
            </div>

            <!-- Framework Tab -->
            <div v-if="activeTab === 'framework'" class="space-y-4">
                <SelectedFramework
                    v-if="promptRun.selectedFramework"
                    :framework="promptRun.selectedFramework as any"
                    :show-proceed-button="shouldShowProceedButton"
                    @proceed="handleProceedToQuestions"
                />
                <AlternativeFrameworks
                    v-if="promptRun.alternativeFrameworks"
                    :frameworks="promptRun.alternativeFrameworks as any"
                    :prompt-run-id="promptRun.id"
                />
            </div>

            <!-- Personality Tab -->
            <div v-if="activeTab === 'personality'" class="space-y-4">
                <TaskTraitAlignment
                    v-if="promptRun.taskTraitAlignment"
                    :alignment="promptRun.taskTraitAlignment as any"
                />
                <PersonalityAdjustments
                    v-if="promptRun.personalityTier"
                    :tier="promptRun.personalityTier as any"
                    :adjustments="promptRun.personalityAdjustmentsPreview || []"
                />
                <PersonalityAdjustmentsSummary
                    v-if="promptRun.personalityAdjustmentsSummary"
                    :adjustments="
                        promptRun.personalityAdjustmentsSummary as any
                    "
                />
            </div>

            <!-- Questions Tab -->
            <div v-if="activeTab === 'questions'" class="space-y-4">
                <!-- Loading state when generation is in progress -->
                <div
                    v-if="
                        promptRun.workflowStage === 'generating_prompt' &&
                        promptRun.status === 'processing'
                    "
                    class="rounded-lg border border-green-200 bg-green-50 p-6 text-center"
                >
                    <div
                        class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-green-200 border-t-green-600"
                    ></div>
                    <p class="font-medium text-green-900">
                        Generating your optimised prompt...
                    </p>
                    <p class="mt-1 text-sm text-green-700">
                        This usually takes 20-40 seconds
                    </p>
                </div>

                <ClarifyingQuestions
                    :prompt-run="promptRun"
                    :current-question-answer="currentQuestionAnswer"
                    :visitor-has-completed-prompts="
                        visitorHasCompletedPrompts || false
                    "
                />
            </div>

            <!-- Recommendations Tab -->
            <Recommendations
                v-if="activeTab === 'recommendations'"
                :model-recommendations="promptRun.modelRecommendations as any"
                :iteration-suggestions="promptRun.iterationSuggestions as any"
            />

            <!-- API Usage Tab -->
            <ApiUsage
                v-if="activeTab === 'api-usage'"
                :analysis-usage="promptRun.analysisApiUsage as any"
                :generation-usage="promptRun.generationApiUsage as any"
            />
        </div>
    </ContainerPage>
</template>
