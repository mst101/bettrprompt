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
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { ClaudeModel, PromptRunResource } from '@/Types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    promptRun: PromptRunResource;
    claudeModels?: ClaudeModel[];
}

const props = defineProps<Props>();

defineOptions({
    layout: AdminLayout,
});

const { countryRoute } = useCountryRoute();
const { t } = useI18n({ useScope: 'global' });

const pageTitle = computed(
    () => `${t('promptBuilder.admin.promptRun')} #${props.promptRun.id}`,
);

// Define tabs dynamically based on available data
const tabs = computed<Tab[]>(() => {
    const allTabs: Tab[] = [];

    // Your Task tab (always shown)
    allTabs.push({
        id: 'task',
        label: t('promptBuilder.tabs.task'),
        icon: 'squares-2x2',
    });

    // Framework tab (show if framework has been selected)
    if (props.promptRun.selectedFramework) {
        allTabs.push({
            id: 'framework',
            label: t('promptBuilder.tabs.framework'),
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
            label: t('promptBuilder.tabs.personality'),
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
            label: t('promptBuilder.tabs.questions'),
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
            label: t('promptBuilder.tabs.recommendations'),
            icon: 'light-bulb',
        });
    }

    // Costs tab (always shown for admin)
    allTabs.push({
        id: 'costs',
        label: t('promptBuilder.tabs.costs'),
        icon: 'chart-bar',
    });

    // Optimised Prompt tab (show if prompt exists)
    if (props.promptRun.optimizedPrompt) {
        allTabs.push({
            id: 'prompt',
            label: t('promptBuilder.tabs.prompt'),
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
    <Head :title="`Admin - ${pageTitle}`" />

    <HeaderPage :title="pageTitle">
        <template #actions>
            <Link
                :href="countryRoute('admin.tasks.index')"
                class="text-sm text-indigo-600 hover:text-indigo-900"
            >
                ← {{ $t('promptBuilder.admin.backToTasks') }}
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
                    :classification="promptRun.taskClassification"
                />

                <CognitiveRequirements
                    v-if="promptRun.cognitiveRequirements"
                    :requirements="promptRun.cognitiveRequirements"
                />
            </div>

            <!-- Framework Tab -->
            <div v-if="activeTab === 'framework'" class="space-y-4">
                <SelectedFramework
                    v-if="promptRun.selectedFramework"
                    :framework="promptRun.selectedFramework"
                    :show-proceed-button="false"
                />

                <AlternativeFrameworks
                    v-if="promptRun.alternativeFrameworks"
                    :frameworks="promptRun.alternativeFrameworks"
                    :prompt-run-id="promptRun.id"
                    :prompt-run="promptRun"
                    :current-framework="promptRun.selectedFramework"
                />
            </div>

            <!-- Personality Tab -->
            <div v-if="activeTab === 'personality'" class="space-y-4">
                <TaskTraitAlignment
                    v-if="promptRun.taskTraitAlignment"
                    :alignment="promptRun.taskTraitAlignment"
                />

                <PersonalityAdjustments
                    v-if="promptRun.personalityTier"
                    :tier="promptRun.personalityTier"
                    :adjustments="promptRun.personalityAdjustmentsPreview || []"
                />

                <PersonalityAdjustmentsSummary
                    v-if="promptRun.personalityAdjustmentsSummary"
                    :adjustments="promptRun.personalityAdjustmentsSummary"
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
                    :model-recommendations="promptRun.modelRecommendations"
                    :iteration-suggestions="promptRun.iterationSuggestions"
                />
            </div>

            <!-- Costs Tab -->
            <div v-if="activeTab === 'costs'" class="space-y-4">
                <ApiUsage
                    :pre-analysis-usage="promptRun.preAnalysisApiUsage"
                    :analysis-usage="promptRun.analysisApiUsage"
                    :generation-usage="promptRun.generationApiUsage"
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
</template>
