<script setup lang="ts">
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import LinkButton from '@/Components/Base/LinkButton.vue';
import Tabs, { type Tab } from '@/Components/Base/Tabs.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import VisitorLimitBanner from '@/Components/Common/VisitorLimitBanner.vue';
import WorkflowError from '@/Components/Common/WorkflowError.vue';
import ApiUsage from '@/Components/Features/PromptBuilder/ApiUsage/ApiUsage.vue';
import ClarifyingQuestions from '@/Components/Features/PromptBuilder/ClarifyingQuestions/ClarifyingQuestions.vue';
import AlternativeFrameworks from '@/Components/Features/PromptBuilder/Framework/AlternativeFrameworks.vue';
import SelectedFramework from '@/Components/Features/PromptBuilder/Framework/SelectedFramework.vue';
import OptimizedPrompt from '@/Components/Features/PromptBuilder/OptimisedPrompt/OptimisedPrompt.vue';
import PersonalityAdjustments from '@/Components/Features/PromptBuilder/Personality/PersonalityAdjustments.vue';
import PersonalityAdjustmentsSummary from '@/Components/Features/PromptBuilder/Personality/PersonalityAdjustmentsSummary.vue';
import TaskTraitAlignment from '@/Components/Features/PromptBuilder/Personality/TaskTraitAlignment.vue';
import AnalysisProgress from '@/Components/Features/PromptBuilder/Progress/AnalysisProgress.vue';
import GenerationProgress from '@/Components/Features/PromptBuilder/Progress/GenerationProgress.vue';
import PreAnalysisProgress from '@/Components/Features/PromptBuilder/Progress/PreAnalysisProgress.vue';
import Recommendations from '@/Components/Features/PromptBuilder/Recommendations/Recommendations.vue';
import CognitiveRequirements from '@/Components/Features/PromptBuilder/YourTask/CognitiveRequirements.vue';
import QuickQueries from '@/Components/Features/PromptBuilder/YourTask/QuickQueries.vue';
import RelatedPromptRuns from '@/Components/Features/PromptBuilder/YourTask/RelatedPromptRuns.vue';
import TaskClassification from '@/Components/Features/PromptBuilder/YourTask/TaskClassification.vue';
import TaskInformation from '@/Components/Features/PromptBuilder/YourTask/TaskInformation.vue';
import { useRealtimeUpdates } from '@/Composables/data/useRealtimeUpdates';
import { useAlert } from '@/Composables/ui/useAlert';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { ClaudeModel, PromptRunResource } from '@/Types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const props = defineProps<Props>();

const page = usePage<{
    auth?: { user?: User };
    visitorHasCompletedPrompts?: boolean;
}>();

const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.isAdmin ?? false);
const isGuest = computed(() => !user.value);

// Show banner for guests who just completed their prompt
// We show it if the guest has an optimized prompt available
const showFirstPromptBanner = computed(
    () => isGuest.value && props.promptRun.optimizedPrompt,
);

// Helper computed to check if workflow is currently processing
const isPromptRunProcessing = computed(() => {
    return props.promptRun.workflowStage?.endsWith('_processing') ?? false;
});

// Helper computed to check if workflow has failed at any stage
const hasWorkflowFailed = computed(() => {
    return props.promptRun.workflowStage?.endsWith('_failed') ?? false;
});

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
    uiComplexity?: 'simple' | 'advanced';
    claudeModels?: ClaudeModel[];
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

    // Framework tab (show whenever a framework has been selected, regardless of workflow stage)
    if (props.promptRun.selectedFramework) {
        allTabs.push({
            id: 'framework',
            label: 'Framework',
            mobileLabel: 'Selected Framework',
            icon: 'cube',
        });
    }

    // Add personality tab if tier is not 'none' and UI complexity is advanced
    if (
        props.promptRun.personalityTier &&
        props.promptRun.personalityTier !== 'none' &&
        props.uiComplexity === 'advanced'
    ) {
        allTabs.push({
            id: 'personality',
            label: 'Personality',
            icon: 'user',
        });
    }

    // Add questions tab (only when framework questions exist)
    if (
        props.promptRun.frameworkQuestions &&
        props.promptRun.frameworkQuestions.length > 0
    ) {
        allTabs.push({
            id: 'questions',
            label: 'Clarifying Questions',
            icon: 'question-mark-circle',
        });
    }

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

    // API Usage tab (only shown in advanced mode and for admins)
    if (props.uiComplexity === 'advanced' && isAdmin.value) {
        allTabs.push({
            id: 'api-usage',
            label: 'API Usage',
            icon: 'chart-bar',
        });
    }

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

    // If analysis is complete and framework is selected
    if (
        props.promptRun.selectedFramework &&
        (props.promptRun.workflowStage === '1_completed' ||
            props.promptRun.workflowStage === '2_completed')
    ) {
        // Check if user has viewed the framework tab before
        const storageKey = `promptRun_${props.promptRun.id}_viewedFramework`;
        const hasViewedFramework = localStorage.getItem(storageKey) === 'true';

        // If they've viewed the framework and questions exist, show questions tab
        // Otherwise, show framework tab first so they can review the selection
        if (hasViewedFramework && props.promptRun.frameworkQuestions?.length) {
            return 'questions';
        }

        return 'framework';
    }

    // Default to task tab (including during analysis)
    return 'task';
};

const activeTab = ref<string>(getInitialTab());
const clarifyingQuestionsRef = ref<InstanceType<
    typeof ClarifyingQuestions
> | null>(null);
const selectedFrameworkRef = ref<InstanceType<typeof SelectedFramework> | null>(
    null,
);

// Check if we're on a larger screen (sm breakpoint and above)
// We use matchMedia to detect screen size at runtime
const isLargeScreen = () => window.matchMedia('(min-width: 640px)').matches;

// Track when user views the framework tab
const markFrameworkAsViewed = () => {
    const storageKey = `promptRun_${props.promptRun.id}_viewedFramework`;
    localStorage.setItem(storageKey, 'true');
};

// Mark as viewed if we're showing the framework tab initially
if (activeTab.value === 'framework') {
    markFrameworkAsViewed();
}

// Also mark as viewed when user switches to the framework tab
// And focus the "Proceed to ClarifyingQuestions" button on larger screens
watch(activeTab, async (newTab) => {
    if (newTab === 'framework') {
        markFrameworkAsViewed();

        // Focus the proceed button only on larger screens (where tabs are shown)
        if (isLargeScreen() && shouldShowProceedButton.value) {
            await nextTick();
            selectedFrameworkRef.value?.focus();
        }
    }
});

const hasRelatedRuns = computed(
    () =>
        !!props.promptRun.parent ||
        (props.promptRun.children && props.promptRun.children.length > 0) ||
        false,
);

// Determine if user should proceed to questions
const shouldShowProceedButton = computed(() => {
    if (props.promptRun.workflowStage !== '1_completed') {
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

// Track if we've recently seen a processing state (allows polling to complete after workflow finishes)
const hasRecentlyBeenProcessing = ref(false);
let processingStateTimeout: number | null = null;

// Determine if we should poll for updates
// Keep polling active for 2 seconds after workflow stops processing to catch completion
const shouldPollForUpdates = computed(() => {
    return hasRecentlyBeenProcessing.value;
});

// Watch for processing state changes and maintain the hasRecentlyBeenProcessing flag
watch(
    () => isPromptRunProcessing.value,
    (isProcessing) => {
        if (isProcessing) {
            // Workflow is processing - start polling
            hasRecentlyBeenProcessing.value = true;
            if (processingStateTimeout) {
                clearTimeout(processingStateTimeout);
            }
        } else if (hasRecentlyBeenProcessing.value) {
            // Workflow stopped processing - keep polling for a bit to catch completion notification
            processingStateTimeout = window.setTimeout(() => {
                hasRecentlyBeenProcessing.value = false;
                processingStateTimeout = null;
            }, 2000);
        }
    },
    { immediate: true },
);

// Real-time updates via Laravel Echo with smart polling fallback
useRealtimeUpdates(
    computed(() => `prompt-run.${props.promptRun.id}`),
    {
        PreAnalysisCompleted: () => {
            // Reload page to show Quick Queries
            router.reload({
                only: ['promptRun'],
                onSuccess: () => {
                    // Stay on Task tab to show Quick Queries
                    activeTab.value = 'task';
                },
            });
        },
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
            // The watcher on optimizedPrompt will automatically switch to the prompt tab
            router.reload({
                only: ['promptRun'],
            });
        },
        WorkflowFailed: () => {
            // Reload page to show error message
            router.reload({
                only: ['promptRun'],
                onSuccess: () => {
                    // Stay on Task tab to show error
                    activeTab.value = 'task';
                },
            });
        },
    },
    { only: ['promptRun'] },
    1000, // Poll every 1 second when WebSockets unavailable (faster feedback for errors)
    shouldPollForUpdates, // Only poll when workflow is processing
);

// Watch for workflow stage changes and reload if needed (fallback to WebSocket events)
watch(
    () => props.promptRun.workflowStage,
    (newStage, oldStage) => {
        // When pre-analysis completes (either with questions or proceeding to analysis)
        if (
            oldStage === '0_processing' &&
            (newStage === '0_completed' || newStage === '1_processing')
        ) {
            if (newStage === '0_completed') {
                // Quick Queries are ready - reload to show them
                router.reload({
                    only: ['promptRun'],
                    onSuccess: () => {
                        activeTab.value = 'task';
                    },
                });
            } else if (newStage === '1_processing') {
                // Proceeding directly to analysis - reload to show AnalysisProgress
                router.reload({
                    only: ['promptRun'],
                });
            }
        }
        // When main analysis completes
        if (oldStage === '1_processing' && newStage === '1_completed') {
            router.reload({
                only: ['promptRun'],
                onSuccess: () => {
                    activeTab.value = 'framework';
                },
            });
        }
    },
    { immediate: false }, // Don't trigger on initial mount (oldStage will be undefined)
);

// Explicit handler for AnalysisCompleted event that might fire before watcher
// This handles the case where the page is redirected and props are immediately '1_processing'
watch(
    () => props.promptRun.selectedFramework,
    (newFramework, oldFramework) => {
        // If selectedFramework changed from empty to populated while in 1_completed state,
        // the analysis just completed - switch to framework tab
        if (
            newFramework &&
            !oldFramework &&
            props.promptRun.workflowStage === '1_completed'
        ) {
            activeTab.value = 'framework';
        }
    },
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

// Watch for tab validity - if current tab is no longer in tabs array, switch to first available tab
watch(
    tabs,
    (newTabs) => {
        const tabIds = newTabs.map((tab) => tab.id);
        if (!tabIds.includes(activeTab.value)) {
            // Current tab is no longer valid, switch to first tab
            activeTab.value = newTabs[0]?.id || 'task';
        }
    },
    { immediate: true },
);

// Focus first textarea and reload data when switching to questions tab
watch(activeTab, async (newTab) => {
    if (newTab === 'questions') {
        // Reload the prompt run data to ensure we have the latest answers from auto-saves
        router.reload({
            only: ['promptRun'],
            onSuccess: async () => {
                await nextTick();
                // The component will handle screen size check internally
                clarifyingQuestionsRef.value?.focus();
            },
        });
    }
});

const { confirm } = useAlert();

const handleDelete = async () => {
    const confirmed = await confirm(
        'Are you sure you want to delete this prompt run? This action cannot be undone.',
        'Delete Prompt Run',
        { confirmButtonStyle: 'danger', confirmText: 'Delete' },
    );

    if (!confirmed) {
        return;
    }

    router.delete(route('prompt-builder.destroy', props.promptRun.id), {
        onSuccess: () => {
            // Redirect to history page after successful deletion
            router.visit(route('prompt-builder.history'));
        },
    });
};

/**
 * Retry the prompt generation when it fails
 */
const retryWorkflow = () => {
    // Use the existing retry endpoint which handles all workflow failures
    router.post(
        route('prompt-builder.retry', props.promptRun.id),
        {},
        {
            onSuccess: () => {
                // The retry endpoint redirects, so just follow it
            },
            onError: () => {
                alert('Failed to retry generation. Please try again.');
            },
        },
    );
};

// Cleanup timeout when component unmounts
onUnmounted(() => {
    if (processingStateTimeout) {
        clearTimeout(processingStateTimeout);
    }
});
</script>

<template>
    <Head title="Prompt Analysis" />

    <HeaderPage title="Prompt Builder">
        <template #actions>
            <div class="flex items-center space-x-4">
                <ButtonSecondary
                    type="button"
                    class="hidden! sm:inline-flex!"
                    icon="trash"
                    @click="handleDelete"
                >
                    Delete
                </ButtonSecondary>
                <LinkButton
                    :href="route('prompt-builder.index')"
                    variant="primary"
                    icon="plus"
                    icon-position="left"
                >
                    CREATE NEW
                </LinkButton>
            </div>
        </template>
    </HeaderPage>

    <ContainerPage>
        <div
            class="mb-6 max-w-4xl shadow-xs sm:rounded-lg"
            data-testid="prompt-builder-container"
        >
            <Tabs
                v-model="activeTab"
                :tabs="tabs"
                data-testid="prompt-builder-tabs"
            />

            <!-- Optimised Prompt Tab -->
            <div v-if="activeTab === 'prompt'" data-testid="tab-prompt">
                <OptimizedPrompt
                    v-if="promptRun.optimizedPrompt"
                    :optimized-prompt="promptRun.optimizedPrompt"
                    :prompt-run-id="promptRun.id"
                />
            </div>

            <!-- Your Task Tab -->
            <div
                v-if="activeTab === 'task'"
                class="space-y-4"
                data-testid="tab-task"
            >
                <!-- Workflow error display for any failed stage -->
                <WorkflowError
                    v-if="hasWorkflowFailed && promptRun.errorMessage"
                    :error-message="promptRun.errorMessage"
                    @retry="retryWorkflow"
                />

                <!-- Enhanced loading state when generating pre-analysis questions (Workflow 0) -->
                <PreAnalysisProgress
                    v-if="
                        promptRun.workflowStage === '0_processing' &&
                        isPromptRunProcessing
                    "
                />

                <!-- Enhanced loading state when main analysis is in progress (Workflow 1) -->
                <AnalysisProgress
                    v-if="
                        promptRun.workflowStage === '1_processing' &&
                        isPromptRunProcessing
                    "
                />

                <!-- Enhanced loading state when prompt generation is in progress (Workflow 2) -->
                <GenerationProgress
                    v-if="
                        promptRun.workflowStage === '2_processing' &&
                        isPromptRunProcessing
                    "
                />

                <TaskInformation
                    :prompt-run="promptRun"
                    :visitor-has-completed-prompts="
                        visitorHasCompletedPrompts || false
                    "
                />

                <!-- Pre-analysis questions/answers -->
                <QuickQueries :prompt-run="promptRun" />

                <RelatedPromptRuns
                    v-if="hasRelatedRuns"
                    :parent="promptRun.parent"
                    :children="promptRun.children"
                />
                <TaskClassification
                    v-if="
                        promptRun.taskClassification &&
                        uiComplexity === 'advanced'
                    "
                    :classification="promptRun.taskClassification as any"
                />
                <CognitiveRequirements
                    v-if="
                        promptRun.cognitiveRequirements &&
                        uiComplexity === 'advanced'
                    "
                    :requirements="promptRun.cognitiveRequirements as any"
                />
            </div>

            <!-- Framework Tab -->
            <div
                v-if="activeTab === 'framework'"
                class="space-y-4"
                data-testid="tab-framework"
            >
                <SelectedFramework
                    v-if="promptRun.selectedFramework"
                    ref="selectedFrameworkRef"
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
            <div
                v-if="activeTab === 'personality'"
                class="space-y-4"
                data-testid="tab-personality"
            >
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

            <!-- ClarifyingQuestions Tab -->
            <div
                v-if="activeTab === 'questions'"
                class="space-y-4"
                data-testid="tab-questions"
            >
                <!-- Enhanced loading state when generation is in progress or failed -->
                <GenerationProgress
                    v-if="
                        (promptRun.workflowStage === '2_processing' &&
                            isPromptRunProcessing) ||
                        promptRun.workflowStage === '2_failed'
                    "
                    :error-message="
                        promptRun.workflowStage === '2_failed'
                            ? promptRun.errorMessage
                            : null
                    "
                    :on-retry="
                        promptRun.workflowStage === '2_failed'
                            ? () => retryWorkflow()
                            : undefined
                    "
                />

                <ClarifyingQuestions
                    ref="clarifyingQuestionsRef"
                    :prompt-run="promptRun"
                    :ui-complexity="uiComplexity"
                    :visitor-has-completed-prompts="
                        visitorHasCompletedPrompts || false
                    "
                />
            </div>

            <!-- Recommendations Tab -->
            <div
                v-if="activeTab === 'recommendations'"
                data-testid="tab-recommendations"
            >
                <Recommendations
                    :model-recommendations="
                        promptRun.modelRecommendations as any
                    "
                    :iteration-suggestions="
                        promptRun.iterationSuggestions as any
                    "
                />
            </div>

            <!-- API Usage Tab -->
            <div v-if="activeTab === 'api-usage'" data-testid="tab-api-usage">
                <ApiUsage
                    :pre-analysis-usage="promptRun.preAnalysisApiUsage as any"
                    :analysis-usage="promptRun.analysisApiUsage as any"
                    :generation-usage="promptRun.generationApiUsage as any"
                    :claude-models="claudeModels || []"
                />
            </div>
        </div>

        <!-- First Prompt Banner for Guests -->
        <VisitorLimitBanner
            v-if="showFirstPromptBanner"
            @register="$inertia.visit(route('register'))"
        />
    </ContainerPage>
</template>
