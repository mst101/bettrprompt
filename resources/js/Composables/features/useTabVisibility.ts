import type { Tab } from '@/Components/Base/Tabs.vue';
import type { PromptRunResource } from '@/Types';
import { computed, isRef, type ComputedRef, type Ref } from 'vue';
import { useI18n } from 'vue-i18n';

/**
 * Minimal subset of PromptRunResource fields used for tab visibility
 * @internal Used internally by useTabVisibility
 */
export interface PromptRunTabData {
    selectedFramework?: Record<string, unknown> | null;
    personalityTier?: string | null;
    frameworkQuestions?: unknown[] | null;
    modelRecommendations?: unknown | null;
    iterationSuggestions?: unknown | null;
    optimizedPrompt?: unknown | null;
    workflowStage?: string;
}

export interface TabVisibilityFlags {
    hasFramework: ComputedRef<boolean>;
    hasPersonality: ComputedRef<boolean>;
    hasFrameworkQuestions: ComputedRef<boolean>;
    hasModelRecommendations: ComputedRef<boolean>;
    showApiUsage: ComputedRef<boolean>;
    showOptimisedPrompt: ComputedRef<boolean>;
}

export interface TabVisibilityResult extends TabVisibilityFlags {
    tabs: ComputedRef<Tab[]>;
}

/**
 * Composable for managing tab visibility based on prompt run state
 * Centralises visibility logic and tab definitions
 *
 * @param promptRun Can be a plain object or a computed/ref for reactive updates
 */
export function useTabVisibility(
    promptRun:
        | PromptRunResource
        | PromptRunTabData
        | ComputedRef<PromptRunResource | PromptRunTabData>
        | Ref<PromptRunResource | PromptRunTabData>,
    uiComplexity: string | undefined,
    isAdmin: boolean,
): TabVisibilityResult {
    const { t } = useI18n({ useScope: 'global' });

    // Convert to computed for consistent reactive access
    const promptRunComputed = computed(() => {
        if (isRef(promptRun)) {
            return promptRun.value;
        }
        return promptRun as PromptRunResource | PromptRunTabData;
    });

    // Tab visibility flags
    const hasFramework = computed(
        () => !!promptRunComputed.value.selectedFramework,
    );

    const hasPersonality = computed(
        () =>
            !!promptRunComputed.value.personalityTier &&
            promptRunComputed.value.personalityTier !== 'none' &&
            uiComplexity === 'advanced',
    );

    const hasFrameworkQuestions = computed(
        () =>
            !!promptRunComputed.value.frameworkQuestions &&
            promptRunComputed.value.frameworkQuestions.length > 0,
    );

    const hasModelRecommendations = computed(
        () =>
            !!promptRunComputed.value.modelRecommendations ||
            !!promptRunComputed.value.iterationSuggestions,
    );

    const showApiUsage = computed(() => uiComplexity === 'advanced' && isAdmin);

    const showOptimisedPrompt = computed(
        () => !!promptRunComputed.value.optimizedPrompt,
    );

    // Tab definitions
    const tabDefinitions = {
        task: {
            id: 'task',
            label: t('promptBuilder.tabs.task'),
            icon: 'squares-2x2',
        },
        framework: {
            id: 'framework',
            label: t('promptBuilder.tabs.framework'),
            mobileLabel: t('promptBuilder.tabs.frameworkMobile'),
            icon: 'cube',
        },
        personality: {
            id: 'personality',
            label: t('promptBuilder.tabs.personality'),
            icon: 'user',
        },
        questions: {
            id: 'questions',
            label: t('promptBuilder.tabs.questions'),
            icon: 'question-mark-circle',
        },
        recommendations: {
            id: 'recommendations',
            label: t('promptBuilder.tabs.recommendations'),
            icon: 'light-bulb',
        },
        apiUsage: {
            id: 'api-usage',
            label: t('promptBuilder.tabs.costs'),
            icon: 'chart-bar',
        },
        optimisedPrompt: {
            id: 'prompt',
            label: t('promptBuilder.tabs.prompt'),
            icon: 'sparkles',
        },
    };

    // Composite tabs
    const tabs = computed<Tab[]>(() => {
        const allTabs: Tab[] = [tabDefinitions.task as Tab];

        if (hasFramework.value) allTabs.push(tabDefinitions.framework as Tab);
        if (hasPersonality.value)
            allTabs.push(tabDefinitions.personality as Tab);
        if (hasFrameworkQuestions.value)
            allTabs.push(tabDefinitions.questions as Tab);
        if (hasModelRecommendations.value)
            allTabs.push(tabDefinitions.recommendations as Tab);
        if (showApiUsage.value) allTabs.push(tabDefinitions.apiUsage as Tab);
        if (showOptimisedPrompt.value)
            allTabs.push(tabDefinitions.optimisedPrompt as Tab);

        // Debug logging - always log for analysis completion
        console.log('📋 [useTabVisibility] Tab computation:', {
            selectedFramework: promptRunComputed.value.selectedFramework,
            frameworkQuestions: promptRunComputed.value.frameworkQuestions,
            hasFramework: hasFramework.value,
            hasFrameworkQuestions: hasFrameworkQuestions.value,
            frameworkQuestionsLength:
                promptRunComputed.value.frameworkQuestions?.length ?? null,
            tabIds: allTabs.map((t) => t.id),
            workflowStage: promptRunComputed.value.workflowStage,
        });

        return allTabs;
    });

    return {
        hasFramework,
        hasPersonality,
        hasFrameworkQuestions,
        hasModelRecommendations,
        showApiUsage,
        showOptimisedPrompt,
        tabs,
    };
}
