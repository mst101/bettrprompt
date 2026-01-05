<script setup lang="ts">
import Tabs, { type Tab } from '@/Components/Base/Tabs.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import PromptRunMetadata from '@/Components/Common/PromptRunMetadata.vue';
import WorkflowError from '@/Components/Common/WorkflowError.vue';
import ApiUsage from '@/Components/Features/PromptBuilder/ApiUsage/ApiUsage.vue';
import ClarifyingQuestions from '@/Components/Features/PromptBuilder/ClarifyingQuestions/ClarifyingQuestions.vue';
import AlternativeFrameworks from '@/Components/Features/PromptBuilder/Framework/AlternativeFrameworks.vue';
import SelectedFramework from '@/Components/Features/PromptBuilder/Framework/SelectedFramework.vue';
import OptimisedPrompt from '@/Components/Features/PromptBuilder/OptimisedPrompt/OptimisedPrompt.vue';
import PersonalityAdjustments from '@/Components/Features/PromptBuilder/Personality/PersonalityAdjustments.vue';
import PersonalityAdjustmentsSummary from '@/Components/Features/PromptBuilder/Personality/PersonalityAdjustmentsSummary.vue';
import TaskTraitAlignment from '@/Components/Features/PromptBuilder/Personality/TaskTraitAlignment.vue';
import Recommendations from '@/Components/Features/PromptBuilder/Recommendations/Recommendations.vue';
import CognitiveRequirements from '@/Components/Features/PromptBuilder/YourTask/CognitiveRequirements.vue';
import PreAnalysisQuestions from '@/Components/Features/PromptBuilder/YourTask/PreAnalysisQuestions.vue';
import RelatedPromptRuns from '@/Components/Features/PromptBuilder/YourTask/RelatedPromptRuns.vue';
import TaskClassification from '@/Components/Features/PromptBuilder/YourTask/TaskClassification.vue';
import TaskInformation from '@/Components/Features/PromptBuilder/YourTask/TaskInformation.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { ClaudeModel, PromptRunResource } from '@/Types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    promptRun: PromptRunResource;
    claudeModels?: ClaudeModel[];
}

const props = defineProps<Props>();

// Define tabs dynamically based on available data
const tabs = computed<Tab[]>(() => {
    const allTabs: Tab[] = [];

    // Your Task tab (always shown)
    allTabs.push({
        id: 'task',
        label: 'Your Task',
        icon: 'squares-2x2',
    });

    // Framework tab (show if framework has been selected)
    if (props.promptRun.selectedFramework) {
        allTabs.push({
            id: 'framework',
            label: 'Framework',
            icon: 'cube',
        });
    }

    // Personality tab (show if personalityTier is not 'none')
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

    // Questions tab (show if framework questions exist)
    if (
        props.promptRun.frameworkQuestions &&
        props.promptRun.frameworkQuestions.length > 0
    ) {
        allTabs.push({
            id: 'questions',
            label: 'Questions',
            icon: 'question-mark-circle',
        });
    }

    // Recommendations tab (show if recommendations or suggestions exist)
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

    // Costs tab (always shown for admin)
    allTabs.push({
        id: 'costs',
        label: 'Costs',
        icon: 'chart-bar',
    });

    // Optimised Prompt tab (show if prompt exists)
    if (props.promptRun.optimizedPrompt) {
        allTabs.push({
            id: 'prompt',
            label: 'Optimised Prompt',
            icon: 'sparkles',
        });
    }

    return allTabs;
});

const activeTab = ref<string>('task');

// Check if workflow has failed
const hasWorkflowFailed = computed(() => {
    return props.promptRun.workflowStage?.endsWith('_failed') ?? false;
});

// Check if prompt run has related runs
const hasRelatedRuns = computed(
    () =>
        !!props.promptRun.parent ||
        (props.promptRun.children && props.promptRun.children.length > 0) ||
        false,
);
</script>

<template>
    <Head :title="`Admin - Prompt Run #${props.promptRun.id}`" />

    <AppLayout>
        <HeaderPage :title="`Prompt Run #${props.promptRun.id}`">
            <template #actions>
                <Link
                    :href="route('admin.tasks.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-900"
                >
                    ← Back to Tasks
                </Link>
            </template>
        </HeaderPage>

        <ContainerPage v-if="props.promptRun" spacing>
            <!-- Prompt Run Metadata -->
            <PromptRunMetadata
                :workflow-stage="promptRun.workflowStage"
                :user="promptRun.user ?? null"
                :personality-type="promptRun.personalityType"
                :created-at="promptRun.createdAt"
            />

            <!-- Tabbed Content -->
            <div class="max-w-4xl shadow-xs sm:rounded-lg">
                <Tabs v-model="activeTab" class="mb-2" :tabs="tabs" />

                <!-- Your Task Tab -->
                <div v-if="activeTab === 'task'" class="space-y-4">
                    <!-- Workflow error display for any failed stage -->
                    <WorkflowError
                        v-if="hasWorkflowFailed && promptRun.errorMessage"
                        :error-message="promptRun.errorMessage"
                    />

                    <TaskInformation
                        :prompt-run="promptRun"
                        :visitor-has-completed-prompts="false"
                    />

                    <PreAnalysisQuestions :prompt-run="promptRun" />

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
                        :show-proceed-button="false"
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
                        :adjustments="
                            promptRun.personalityAdjustmentsPreview || []
                        "
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
                    <ClarifyingQuestions
                        :prompt-run="promptRun"
                        ui-complexity="simple"
                        :visitor-has-completed-prompts="false"
                    />
                </div>

                <!-- Recommendations Tab -->
                <div v-if="activeTab === 'recommendations'" class="space-y-4">
                    <Recommendations
                        :model-recommendations="
                            promptRun.modelRecommendations as any
                        "
                        :iteration-suggestions="
                            promptRun.iterationSuggestions as any
                        "
                    />
                </div>

                <!-- Costs Tab -->
                <div v-if="activeTab === 'costs'" class="space-y-4">
                    <ApiUsage
                        :pre-analysis-usage="
                            promptRun.preAnalysisApiUsage as any
                        "
                        :analysis-usage="promptRun.analysisApiUsage as any"
                        :generation-usage="promptRun.generationApiUsage as any"
                        :claude-models="claudeModels || []"
                    />
                </div>

                <!-- Optimised Prompt Tab -->
                <div
                    v-if="activeTab === 'prompt' && promptRun.optimizedPrompt"
                    class="space-y-4"
                >
                    <OptimisedPrompt
                        :optimized-prompt="promptRun.optimizedPrompt"
                        :prompt-run-id="promptRun.id"
                    />
                </div>
            </div>
        </ContainerPage>
    </AppLayout>
</template>
