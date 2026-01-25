import { useTabVisibility } from '@/Composables/features/useTabVisibility';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';

// Mock i18n
vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => {
            const translations: Record<string, string> = {
                'promptBuilder.tabs.task': 'Task',
                'promptBuilder.tabs.framework': 'Framework',
                'promptBuilder.tabs.frameworkMobile': 'Framework',
                'promptBuilder.tabs.personality': 'Personality',
                'promptBuilder.tabs.questions': 'Questions',
                'promptBuilder.tabs.recommendations': 'Recommendations',
                'promptBuilder.tabs.costs': 'API Costs',
                'promptBuilder.tabs.prompt': 'Prompt',
            };
            return translations[key] || key;
        },
    }),
}));

describe('useTabVisibility', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('with plain object', () => {
        it('should show only task tab when no data is available', () => {
            const promptRun = {
                selectedFramework: null,
                personalityTier: null,
                frameworkQuestions: null,
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '0_processing',
            };

            const { tabs } = useTabVisibility(promptRun, 'advanced', false);

            expect(tabs.value.map((t) => t.id)).toEqual(['task']);
        });

        it('should show framework tab when selectedFramework is present', () => {
            const promptRun = {
                selectedFramework: { name: 'Socratic' },
                personalityTier: null,
                frameworkQuestions: null,
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '1_completed',
            };

            const { tabs } = useTabVisibility(promptRun, 'advanced', false);

            expect(tabs.value.map((t) => t.id)).toContain('framework');
        });

        it('should show questions tab when frameworkQuestions are present', () => {
            const promptRun = {
                selectedFramework: { name: 'Socratic' },
                personalityTier: null,
                frameworkQuestions: [{ id: '1', question: 'Test?' }],
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '1_completed',
            };

            const { tabs } = useTabVisibility(promptRun, 'advanced', false);

            expect(tabs.value.map((t) => t.id)).toContain('questions');
        });

        it('should show personality tab only for advanced UI and when personalityTier is set', () => {
            const promptRun = {
                selectedFramework: null,
                personalityTier: 'analyst',
                frameworkQuestions: null,
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '1_completed',
            };

            const { tabs: advancedTabs } = useTabVisibility(
                promptRun,
                'advanced',
                false,
            );
            expect(advancedTabs.value.map((t) => t.id)).toContain(
                'personality',
            );

            const { tabs: simpleTabs } = useTabVisibility(
                promptRun,
                'simple',
                false,
            );
            expect(simpleTabs.value.map((t) => t.id)).not.toContain(
                'personality',
            );
        });

        it('should hide personality tab when personalityTier is "none"', () => {
            const promptRun = {
                selectedFramework: null,
                personalityTier: 'none',
                frameworkQuestions: null,
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '1_completed',
            };

            const { tabs } = useTabVisibility(promptRun, 'advanced', false);

            expect(tabs.value.map((t) => t.id)).not.toContain('personality');
        });

        it('should show recommendations tab when modelRecommendations exist', () => {
            const promptRun = {
                selectedFramework: null,
                personalityTier: null,
                frameworkQuestions: null,
                modelRecommendations: { models: [] },
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '2_completed',
            };

            const { tabs } = useTabVisibility(promptRun, 'advanced', false);

            expect(tabs.value.map((t) => t.id)).toContain('recommendations');
        });

        it('should show recommendations tab when iterationSuggestions exist', () => {
            const promptRun = {
                selectedFramework: null,
                personalityTier: null,
                frameworkQuestions: null,
                modelRecommendations: null,
                iterationSuggestions: ['Improve clarity'],
                optimizedPrompt: null,
                workflowStage: '2_completed',
            };

            const { tabs } = useTabVisibility(promptRun, 'advanced', false);

            expect(tabs.value.map((t) => t.id)).toContain('recommendations');
        });

        it('should show prompt tab when optimizedPrompt exists', () => {
            const promptRun = {
                selectedFramework: null,
                personalityTier: null,
                frameworkQuestions: null,
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: 'You are an AI assistant...',
                workflowStage: '2_completed',
            };

            const { tabs } = useTabVisibility(promptRun, 'advanced', false);

            expect(tabs.value.map((t) => t.id)).toContain('prompt');
        });

        it('should show API usage tab only for admins with advanced UI', () => {
            const promptRun = {
                selectedFramework: null,
                personalityTier: null,
                frameworkQuestions: null,
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '1_completed',
            };

            const { tabs: adminAdvancedTabs } = useTabVisibility(
                promptRun,
                'advanced',
                true,
            );
            expect(adminAdvancedTabs.value.map((t) => t.id)).toContain(
                'api-usage',
            );

            const { tabs: adminSimpleTabs } = useTabVisibility(
                promptRun,
                'simple',
                true,
            );
            expect(adminSimpleTabs.value.map((t) => t.id)).not.toContain(
                'api-usage',
            );

            const { tabs: userTabs } = useTabVisibility(
                promptRun,
                'advanced',
                false,
            );
            expect(userTabs.value.map((t) => t.id)).not.toContain('api-usage');
        });
    });

    describe('with reactive computed (Inertia reload scenario)', () => {
        it('should update tabs reactively when promptRun data changes', async () => {
            // Use a ref that we can update, simulating Inertia props
            const promptRunRef = ref({
                selectedFramework: null,
                personalityTier: null,
                frameworkQuestions: null,
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '0_processing' as const,
            });

            const { tabs } = useTabVisibility(promptRunRef, 'advanced', false);

            // Initially only task tab
            expect(tabs.value.map((t) => t.id)).toEqual(['task']);

            // Simulate Inertia reload - workflow 1 completes
            promptRunRef.value = {
                selectedFramework: { name: 'Socratic' },
                personalityTier: null,
                frameworkQuestions: [{ id: '1', question: 'Test?' }],
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '1_completed' as const,
            };

            // Allow Vue to update reactivity
            await nextTick();

            // Tabs should now include framework and questions
            expect(tabs.value.map((t) => t.id)).toContain('framework');
            expect(tabs.value.map((t) => t.id)).toContain('questions');
            expect(tabs.value.length).toBeGreaterThan(1);
        });

        it('should handle multiple reactive updates', async () => {
            const promptRunRef = ref({
                selectedFramework: null,
                personalityTier: null,
                frameworkQuestions: null,
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '0_processing' as const,
            });

            const { tabs } = useTabVisibility(promptRunRef, 'advanced', false);

            // Step 1: Pre-analysis completes
            promptRunRef.value = {
                ...promptRunRef.value,
                workflowStage: '0_completed' as const,
            };
            await nextTick();
            expect(tabs.value.map((t) => t.id)).toEqual(['task']);

            // Step 2: Analysis starts
            promptRunRef.value = {
                ...promptRunRef.value,
                workflowStage: '1_processing' as const,
            };
            await nextTick();
            expect(tabs.value.map((t) => t.id)).toEqual(['task']);

            // Step 3: Analysis completes with framework
            promptRunRef.value = {
                selectedFramework: { name: 'Feynman' },
                personalityTier: null,
                frameworkQuestions: [
                    { id: '1', question: 'Question 1?' },
                    { id: '2', question: 'Question 2?' },
                ],
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '1_completed' as const,
            };
            await nextTick();
            const step3Tabs = tabs.value.map((t) => t.id);
            expect(step3Tabs).toContain('framework');
            expect(step3Tabs).toContain('questions');

            // Step 4: Generation completes
            promptRunRef.value = {
                ...promptRunRef.value,
                workflowStage: '2_completed' as const,
                optimizedPrompt: 'Final optimized prompt...',
            };
            await nextTick();
            const step4Tabs = tabs.value.map((t) => t.id);
            expect(step4Tabs).toContain('prompt');
        });

        it('should remove tabs when data becomes null again', async () => {
            const promptRunRef = ref({
                selectedFramework: { name: 'Socratic' },
                personalityTier: null,
                frameworkQuestions: [{ id: '1', question: 'Test?' }],
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '1_completed' as const,
            });

            const { tabs } = useTabVisibility(promptRunRef, 'advanced', false);

            // Initially have framework and questions
            expect(tabs.value.map((t) => t.id)).toContain('framework');
            expect(tabs.value.map((t) => t.id)).toContain('questions');

            // Simulate retry/failure - data is cleared
            promptRunRef.value = {
                selectedFramework: null,
                personalityTier: null,
                frameworkQuestions: null,
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '1_processing' as const,
            };

            await nextTick();

            // Tabs should be back to just task
            expect(tabs.value.map((t) => t.id)).toEqual(['task']);
        });
    });

    describe('with ref (alternative reactive pattern)', () => {
        it('should update tabs when ref value changes', () => {
            const promptRun = ref({
                selectedFramework: null,
                personalityTier: null,
                frameworkQuestions: null,
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '0_processing',
            });

            const { tabs } = useTabVisibility(promptRun, 'advanced', false);

            expect(tabs.value.map((t) => t.id)).toEqual(['task']);

            // Update the ref
            promptRun.value = {
                selectedFramework: { name: 'Socratic' },
                personalityTier: null,
                frameworkQuestions: [{ id: '1', question: 'Test?' }],
                modelRecommendations: null,
                iterationSuggestions: null,
                optimizedPrompt: null,
                workflowStage: '1_completed',
            };

            expect(tabs.value.map((t) => t.id)).toContain('framework');
            expect(tabs.value.map((t) => t.id)).toContain('questions');
        });
    });

    describe('visibility flag exports', () => {
        it('should export visibility flags that match tab presence', () => {
            const promptRun = {
                selectedFramework: { name: 'Socratic' },
                personalityTier: 'analyst',
                frameworkQuestions: [{ id: '1', question: 'Test?' }],
                modelRecommendations: { models: [] },
                iterationSuggestions: null,
                optimizedPrompt: 'Prompt text',
                workflowStage: '2_completed',
            };

            const {
                hasFramework,
                hasPersonality,
                hasFrameworkQuestions,
                hasModelRecommendations,
                showOptimisedPrompt,
                showApiUsage,
                tabs,
            } = useTabVisibility(promptRun, 'advanced', true);

            expect(hasFramework.value).toBe(true);
            expect(hasPersonality.value).toBe(true);
            expect(hasFrameworkQuestions.value).toBe(true);
            expect(hasModelRecommendations.value).toBe(true);
            expect(showOptimisedPrompt.value).toBe(true);
            expect(showApiUsage.value).toBe(true);

            const tabIds = tabs.value.map((t) => t.id);
            expect(tabIds).toContain('framework');
            expect(tabIds).toContain('personality');
            expect(tabIds).toContain('questions');
            expect(tabIds).toContain('recommendations');
            expect(tabIds).toContain('api-usage');
            expect(tabIds).toContain('prompt');
        });
    });
});
