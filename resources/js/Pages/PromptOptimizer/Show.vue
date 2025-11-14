<script setup lang="ts">
import ContainerPage from '@/Components/ContainerPage.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import LinkButton from '@/Components/LinkButton.vue';
import ClarifyingQuestions from '@/Components/PromptOptimizer/Cards/ClarifyingQuestions.vue';
import FrameworkSelection from '@/Components/PromptOptimizer/Cards/FrameworkSelection.vue';
import OptimizedPrompt from '@/Components/PromptOptimizer/Cards/OptimizedPrompt.vue';
import TaskInformation from '@/Components/PromptOptimizer/Cards/TaskInformation.vue';
import ErrorDisplay from '@/Components/PromptOptimizer/ErrorDisplay.vue';
import LoadingStateCard from '@/Components/PromptOptimizer/LoadingStateCard.vue';
import Tabs, { type Tab } from '@/Components/Tabs.vue';
import { useRealtimeUpdates } from '@/Composables/useRealtimeUpdates';
import { PERSONALITY_TYPE_NAMES } from '@/constants/workflow';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { N8nErrorResponse, PromptRunResource } from '@/types';
import { Head } from '@inertiajs/vue3';
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
    currentQuestion: string | null;
    currentQuestionAnswer?: string | null;
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
        });
    }

    // Questions tab for runs in progress (answering questions)
    if (isAnsweringQuestions.value && hasFrameworkQuestions.value) {
        allTabs.push({
            id: 'questions',
            label: 'Clarifying Questions',
            icon: 'question-mark-circle',
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
        <div
            class="mb-6 max-w-4xl overflow-hidden bg-white shadow-xs sm:rounded-lg"
        >
            <Tabs v-model="activeTab" :tabs="tabs" />

            <OptimizedPrompt
                v-if="activeTab === 'prompt' && promptRun.optimizedPrompt"
                :optimized-prompt="promptRun.optimizedPrompt"
                :prompt-run-id="promptRun.id"
            />

            <TaskInformation
                v-show="activeTab === 'task'"
                :prompt-run="promptRun"
                :personality-type-label="personalityTypeLabel"
                :show-edit-button="promptRun.status === 'completed'"
                :has-related-runs="hasRelatedRuns"
                class="mb-6 px-6"
            />

            <FrameworkSelection
                v-if="
                    activeTab === 'framework' &&
                    promptRun.selectedFramework &&
                    promptRun.frameworkReasoning
                "
                :framework="promptRun.selectedFramework"
                :reasoning="promptRun.frameworkReasoning"
                :personality-approach="promptRun.personalityApproach"
            />

            <ClarifyingQuestions
                v-show="activeTab === 'questions'"
                :prompt-run="promptRun"
                :current-question="currentQuestion"
                :current-question-answer="currentQuestionAnswer"
                :progress="progress"
            />
        </div>

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
