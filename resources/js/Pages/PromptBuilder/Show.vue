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
import PreAnalysisQuestions from '@/Components/Features/PromptBuilder/YourTask/PreAnalysisQuestions.vue';
import RelatedPromptRuns from '@/Components/Features/PromptBuilder/YourTask/RelatedPromptRuns.vue';
import TaskClassification from '@/Components/Features/PromptBuilder/YourTask/TaskClassification.vue';
import TaskInformation from '@/Components/Features/PromptBuilder/YourTask/TaskInformation.vue';
import { useRealtimeUpdates } from '@/Composables/data/useRealtimeUpdates';
import { useAlert } from '@/Composables/ui/useAlert';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import { analyticsService } from '@/services/analytics';
import type { ClaudeModel, PromptRunResource, User } from '@/Types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, inject, nextTick, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<Props>();

const page = usePage<{
    auth?: { user?: User };
    visitorHasCompletedPrompts?: boolean;
}>();

const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.isAdmin ?? false);
const isGuest = computed(() => !user.value);
const openRegisterModal = inject<() => void>('openRegisterModal');
const openLoginModal = inject<() => void>('openLoginModal');
const { t } = useI18n({ useScope: 'global' });
const { countryRoute } = useCountryRoute();

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

// Compute whether visitor has completed prompts (reactively)
// This updates when the current prompt completes via WebSocket
const visitorHasCompletedPromptsComputed = computed(() => {
    // If the backend prop was true, keep it true
    if (props.visitorHasCompletedPrompts) {
        return true;
    }
    // If guest and current prompt is complete, they now have a completed prompt
    if (isGuest.value && props.promptRun.workflowStage === '2_completed') {
        return true;
    }
    return false;
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
    visitorHasAccount?: boolean;
    uiComplexity?: 'simple' | 'advanced';
    claudeModels?: ClaudeModel[];
}

// Define tabs
const tabs = computed<Tab[]>(() => {
    const allTabs: Tab[] = [];

    // Your Task tab (always shown)
    allTabs.push({
        id: 'task',
        label: t('promptBuilder.tabs.task'),
        icon: 'squares-2x2',
    });

    // Framework tab (show whenever a framework has been selected, regardless of workflow stage)
    if (props.promptRun.selectedFramework) {
        allTabs.push({
            id: 'framework',
            label: t('promptBuilder.tabs.framework'),
            mobileLabel: t('promptBuilder.tabs.frameworkMobile'),
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
            label: t('promptBuilder.tabs.personality'),
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
            label: t('promptBuilder.tabs.questions'),
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
            label: t('promptBuilder.tabs.recommendations'),
            icon: 'light-bulb',
        });
    }

    // API Usage tab (only shown in advanced mode and for admins)
    if (props.uiComplexity === 'advanced' && isAdmin.value) {
        allTabs.push({
            id: 'api-usage',
            label: t('promptBuilder.tabs.costs'),
            icon: 'chart-bar',
        });
    }

    // Optimised Prompt tab (only for completed runs with prompt)
    if (props.promptRun.optimizedPrompt) {
        allTabs.push({
            id: 'prompt',
            label: t('promptBuilder.tabs.prompt'),
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
const isProgrammaticTabSwitch = ref(false);
const clarifyingQuestionsRef = ref<InstanceType<
    typeof ClarifyingQuestions
> | null>(null);
const selectedFrameworkRef = ref<InstanceType<typeof SelectedFramework> | null>(
    null,
);

// Helper to switch tabs programmatically (without triggering analytics)
const switchTabProgrammatically = (tab: string) => {
    isProgrammaticTabSwitch.value = true;
    activeTab.value = tab;
    nextTick(() => {
        isProgrammaticTabSwitch.value = false;
    });
};

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
        PreAnalysisCompleted: (data) => {
            console.log(
                '🎉 [WebSocket] PreAnalysisCompleted event received:',
                data,
            );

            // Track workflow 0 completion
            analyticsService.track({
                name: 'workflow_completed',
                properties: {
                    prompt_run_id: props.promptRun.id,
                    workflow_stage: 0,
                },
            });

            // Reload page to show Quick Queries
            router.reload({
                only: ['promptRun'],
                onSuccess: () => {
                    // Stay on Task tab to show Quick Queries
                    switchTabProgrammatically('task');
                },
            });
        },
        AnalysisCompleted: (data) => {
            console.log(
                '🎉 [WebSocket] AnalysisCompleted event received:',
                data,
            );

            // Track workflow 1 completion
            analyticsService.track({
                name: 'workflow_completed',
                properties: {
                    prompt_run_id: props.promptRun.id,
                    workflow_stage: 1,
                    framework_selected: data.selectedFramework?.code,
                },
            });

            // Reload page to show analysis results
            router.reload({
                only: ['promptRun'],
                onSuccess: () => {
                    // Switch to Framework tab after reload
                    switchTabProgrammatically('framework');
                },
            });
        },
        PromptOptimizationCompleted: (data) => {
            console.log(
                '🎉 [WebSocket] PromptOptimizationCompleted event received:',
                data,
            );

            // Track prompt completion
            analyticsService.track({
                name: 'prompt_completed',
                properties: {
                    prompt_run_id: props.promptRun.id,
                    workflow_stage: 2,
                    personality_type: props.promptRun.personalityType,
                    framework_used: props.promptRun.selectedFramework?.code,
                },
            });

            // Track workflow 2 completion
            analyticsService.track({
                name: 'workflow_completed',
                properties: {
                    prompt_run_id: props.promptRun.id,
                    workflow_stage: 2,
                },
            });

            // Reload page to show completed prompt
            // The watcher on optimizedPrompt will automatically switch to the prompt tab
            router.reload({
                only: ['promptRun'],
            });
        },
        WorkflowFailed: (data) => {
            console.log('🎉 [WebSocket] WorkflowFailed event received:', data);
            // Reload page to show error message
            router.reload({
                only: ['promptRun'],
                onSuccess: () => {
                    // Stay on Task tab to show error
                    switchTabProgrammatically('task');
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
                        switchTabProgrammatically('task');
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
                    switchTabProgrammatically('framework');
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
            // Fire framework_recommended event
            analyticsService.track({
                name: 'framework_recommended',
                properties: {
                    prompt_run_id: props.promptRun.id,
                    recommended_framework: newFramework.code,
                    task_category: props.promptRun.taskCategory,
                    personality_type: props.promptRun.personalityType,
                },
            });

            switchTabProgrammatically('framework');
        }
    },
);

// Switch to prompt tab when optimized prompt is returned
watch(
    () => props.promptRun.optimizedPrompt,
    (newPrompt) => {
        if (newPrompt) {
            switchTabProgrammatically('prompt');
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
            switchTabProgrammatically(newTabs[0]?.id || 'task');
        }
    },
    { immediate: true },
);

// Focus first textarea when switching to questions tab
watch(activeTab, async (newTab) => {
    if (newTab === 'questions') {
        await nextTick();
        // The component will handle screen size check internally
        clarifyingQuestionsRef.value?.focus();
    }
});

// Track manual tab switches for analytics
watch(activeTab, (newTab, oldTab) => {
    if (!isProgrammaticTabSwitch.value && oldTab) {
        analyticsService.track({
            name: 'tab_viewed',
            page_path: window.location.pathname,
            properties: {
                tab: newTab,
                previous_tab: oldTab,
                prompt_run_id: props.promptRun.id,
                workflow_stage: props.promptRun.workflowStage,
            },
        });
    }
});

const { confirm } = useAlert();

const handleDelete = async () => {
    const confirmed = await confirm(
        t('promptBuilder.confirmations.deletePromptRun.message'),
        t('promptBuilder.confirmations.deletePromptRun.title'),
        {
            confirmButtonStyle: 'danger',
            confirmText: t('common.buttons.delete'),
        },
    );

    if (!confirmed) {
        return;
    }

    router.delete(
        countryRoute('prompt-builder.destroy', {
            promptRun: props.promptRun.id,
        }),
        {
            onSuccess: () => {
                // Redirect to history page after successful deletion
                router.visit(countryRoute('prompt-builder.history'));
            },
        },
    );
};

/**
 * Retry the prompt generation when it fails
 */
const retryWorkflow = () => {
    // Use the existing retry endpoint which handles all workflow failures
    router.post(
        countryRoute('prompt-builder.retry', {
            promptRun: props.promptRun.id,
        }),
        {},
        {
            onSuccess: () => {
                // The retry endpoint redirects, so just follow it
            },
            onError: () => {
                alert(t('promptBuilder.errors.retryFailed'));
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
    <Head :title="$t('promptBuilder.analysis.title')" />

    <HeaderPage :title="$t('promptBuilder.title')">
        <template #actions>
            <div class="flex items-center space-x-4">
                <ButtonSecondary
                    type="button"
                    class="hidden! sm:inline-flex!"
                    icon="trash"
                    @click="handleDelete"
                >
                    {{ $t('common.buttons.delete') }}
                </ButtonSecondary>
                <LinkButton
                    :href="countryRoute('prompt-builder.index')"
                    variant="primary"
                    icon="plus"
                    icon-position="left"
                >
                    {{ $t('promptBuilder.actions.createNew') }}
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

                <!-- Success message when pre-analysis is skipped -->
                <div
                    v-if="
                        promptRun.preAnalysisSkipped &&
                        promptRun.workflowStage === '1_processing' &&
                        isPromptRunProcessing
                    "
                    class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4"
                    data-testid="pre-analysis-skipped-message"
                >
                    <div class="flex items-start gap-3">
                        <svg
                            class="mt-0.5 h-5 w-5 shrink-0 text-green-600"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <div class="flex-1">
                            <h4 class="font-semibold text-green-900">
                                {{
                                    $t(
                                        'promptBuilder.messages.preAnalysisSkippedTitle',
                                    )
                                }}
                            </h4>
                            <p class="mt-1 text-sm text-green-700">
                                {{
                                    $t(
                                        'promptBuilder.messages.preAnalysisSkippedDescription',
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>

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
                        visitorHasCompletedPromptsComputed
                    "
                />

                <!-- Pre-analysis questions/answers -->
                <PreAnalysisQuestions
                    :prompt-run="promptRun"
                    :visitor-has-completed-prompts="
                        visitorHasCompletedPromptsComputed
                    "
                />

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
                    :prompt-run="promptRun"
                    :current-framework="promptRun.selectedFramework as any"
                    :visitor-has-completed-prompts="
                        visitorHasCompletedPromptsComputed
                    "
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
                        visitorHasCompletedPromptsComputed
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
            :visitor-has-account="visitorHasAccount"
            @register="openRegisterModal"
            @login="openLoginModal"
        />
    </ContainerPage>
</template>
