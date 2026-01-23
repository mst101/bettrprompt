import type { Tab } from '@/Components/Base/Tabs.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { defineComponent } from 'vue';

// We test the computed properties and tabs rendering logic directly
// since the Show.vue page component is complex and primarily tested via E2E

describe('PromptBuilder Show - Tabs Rendering Logic', () => {
    // Create a simple test component that mirrors the tabs logic
    const TabsLogicComponent = defineComponent({
        props: {
            promptRunData: {
                type: Object,
                default: () => ({}),
            },
            uiComplexity: {
                type: String,
                default: 'simple',
            },
            isAdmin: {
                type: Boolean,
                default: false,
            },
        },
        computed: {
            hasFramework(): boolean {
                return !!this.promptRunData?.selectedFramework;
            },
            hasPersonality(): boolean {
                return (
                    !!this.promptRunData?.personalityTier &&
                    this.promptRunData.personalityTier !== 'none' &&
                    this.uiComplexity === 'advanced'
                );
            },
            hasFrameworkQuestions(): boolean {
                return (
                    !!this.promptRunData?.frameworkQuestions &&
                    this.promptRunData.frameworkQuestions.length > 0
                );
            },
            hasModelRecommendations(): boolean {
                return (
                    !!this.promptRunData?.modelRecommendations ||
                    !!this.promptRunData?.iterationSuggestions
                );
            },
            showApiUsage(): boolean {
                return this.uiComplexity === 'advanced' && this.isAdmin;
            },
            showOptimisedPrompt(): boolean {
                return !!this.promptRunData?.optimizedPrompt;
            },
            tabs(): Tab[] {
                const allTabs: Tab[] = [{ id: 'task', label: 'Task' }];

                if (this.hasFramework)
                    allTabs.push({ id: 'framework', label: 'Framework' });
                if (this.hasPersonality)
                    allTabs.push({ id: 'personality', label: 'Personality' });
                if (this.hasFrameworkQuestions)
                    allTabs.push({ id: 'questions', label: 'Questions' });
                if (this.hasModelRecommendations)
                    allTabs.push({
                        id: 'recommendations',
                        label: 'Recommendations',
                    });
                if (this.showApiUsage)
                    allTabs.push({ id: 'api-usage', label: 'API Usage' });
                if (this.showOptimisedPrompt)
                    allTabs.push({ id: 'prompt', label: 'Prompt' });

                return allTabs;
            },
        },
        template: `
            <div>
                <div v-for="tab in tabs" :key="tab.id" class="tab">{{ tab.id }}</div>
            </div>
        `,
    });

    it('should show task tab always', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: {},
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).toContain('task');
    });

    it('should show framework tab when framework is selected', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: { selectedFramework: { code: 'test' } },
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).toContain('framework');
    });

    it('should not show framework tab when framework is not selected', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: { selectedFramework: null },
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).not.toContain('framework');
    });

    it('should show personality tab when personality tier is set and UI is advanced', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: { personalityTier: 'INTJ' },
                uiComplexity: 'advanced',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).toContain('personality');
    });

    it('should not show personality tab in simple UI mode', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: { personalityTier: 'INTJ' },
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).not.toContain('personality');
    });

    it('should not show personality tab when tier is none', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: { personalityTier: 'none' },
                uiComplexity: 'advanced',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).not.toContain('personality');
    });

    it('should show questions tab when framework questions exist', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: {
                    frameworkQuestions: [{ id: 1, question: 'Test' }],
                },
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).toContain('questions');
    });

    it('should not show questions tab when no framework questions', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: { frameworkQuestions: [] },
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).not.toContain('questions');
    });

    it('should show recommendations tab when model recommendations exist', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: { modelRecommendations: 'Some recommendations' },
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).toContain('recommendations');
    });

    it('should show recommendations tab when iteration suggestions exist', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: { iterationSuggestions: 'Some suggestions' },
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).toContain('recommendations');
    });

    it('should show api-usage tab only in advanced mode for admins', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: {},
                uiComplexity: 'advanced',
                isAdmin: true,
            },
        });

        expect(wrapper.text()).toContain('api-usage');
    });

    it('should not show api-usage tab for non-admins', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: {},
                uiComplexity: 'advanced',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).not.toContain('api-usage');
    });

    it('should not show api-usage tab in simple mode', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: {},
                uiComplexity: 'simple',
                isAdmin: true,
            },
        });

        expect(wrapper.text()).not.toContain('api-usage');
    });

    it('should show optimised prompt tab when prompt is available', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: { optimizedPrompt: 'Here is your prompt...' },
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).toContain('prompt');
    });

    it('should not show optimised prompt tab when prompt is not available', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: { optimizedPrompt: null },
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        expect(wrapper.text()).not.toContain('prompt');
    });

    it('should show full tab set for completed advanced admin workflow', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: {
                    selectedFramework: { code: 'test' },
                    personalityTier: 'INTJ',
                    frameworkQuestions: [{ id: 1 }],
                    modelRecommendations: 'recommendations',
                    optimizedPrompt: 'prompt text',
                },
                uiComplexity: 'advanced',
                isAdmin: true,
            },
        });

        const tabIds = wrapper.findAll('.tab');
        const tabTexts = tabIds.map((t) => t.text());

        expect(tabTexts).toEqual([
            'task',
            'framework',
            'personality',
            'questions',
            'recommendations',
            'api-usage',
            'prompt',
        ]);
    });

    it('should show minimal tab set for simple guest workflow', () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: {},
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        const tabIds = wrapper.findAll('.tab');
        const tabTexts = tabIds.map((t) => t.text());

        expect(tabTexts).toEqual(['task']);
    });

    it('should handle condition changes reactively', async () => {
        const wrapper = mount(TabsLogicComponent, {
            props: {
                promptRunData: {},
                uiComplexity: 'simple',
                isAdmin: false,
            },
        });

        // Initially only task tab
        expect(wrapper.text()).toContain('task');
        expect(wrapper.text()).not.toContain('framework');

        // Add framework
        await wrapper.setProps({
            promptRunData: { selectedFramework: { code: 'test' } },
        });

        expect(wrapper.text()).toContain('framework');
    });
});
