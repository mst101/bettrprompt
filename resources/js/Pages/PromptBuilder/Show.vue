<script setup lang="ts">
import ContainerPage from '@/Components/ContainerPage.vue';
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
import SelectedFramework from '@/Components/PromptBuilder/Cards/SelectedFramework.vue';
import TaskClassification from '@/Components/PromptBuilder/Cards/TaskClassification.vue';
import TaskInformation from '@/Components/PromptBuilder/Cards/TaskInformation.vue';
import TaskTraitAlignment from '@/Components/PromptBuilder/Cards/TaskTraitAlignment.vue';
import RelatedPromptRuns from '@/Components/PromptOptimizer/Cards/RelatedPromptRuns.vue';
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

const activeTab = ref<string>('task'); // Start on Your Task tab

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
        FrameworkSelected: () => {
            // Reload page to show framework selection
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
</script>

<template>
    <Head title="Prompt Analysis" />

    <HeaderPage title="Prompt Builder">
        <template #actions>
            <LinkButton :href="route('prompt-builder.index')" variant="primary">
                Create New
            </LinkButton>
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
                <TaskInformation :prompt-run="promptRun" />
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
            <ClarifyingQuestions
                v-if="activeTab === 'questions'"
                :prompt-run="promptRun"
                :current-question-answer="currentQuestionAnswer"
            />

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
