import type { Tab } from '@/Components/Base/Tabs.vue';
import type { PromptRunResource } from '@/Types';
import { computed, type ComputedRef } from 'vue';
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
 */
export function useTabVisibility(
    promptRun: PromptRunResource | PromptRunTabData,
    uiComplexity: string,
    isAdmin: boolean,
): TabVisibilityResult {
    const { t } = useI18n({ useScope: 'global' });

    // Tab visibility flags
    const hasFramework = computed(() => !!promptRun.selectedFramework);

    const hasPersonality = computed(
        () =>
            !!promptRun.personalityTier &&
            promptRun.personalityTier !== 'none' &&
            uiComplexity === 'advanced',
    );

    const hasFrameworkQuestions = computed(
        () =>
            !!promptRun.frameworkQuestions &&
            promptRun.frameworkQuestions.length > 0,
    );

    const hasModelRecommendations = computed(
        () =>
            !!promptRun.modelRecommendations ||
            !!promptRun.iterationSuggestions,
    );

    const showApiUsage = computed(() => uiComplexity === 'advanced' && isAdmin);

    const showOptimisedPrompt = computed(() => !!promptRun.optimizedPrompt);

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
