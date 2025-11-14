import type { Tab } from '@/Components/Tabs.vue';
import Tabs from '@/Components/Tabs.vue';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

describe('Tabs', () => {
    const defaultTabs: Tab[] = [
        { id: 'tab1', label: 'Tab One' },
        { id: 'tab2', label: 'Tab Two' },
        { id: 'tab3', label: 'Tab Three' },
    ];

    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('should render all tabs', () => {
        const wrapper = mount(Tabs, {
            props: {
                tabs: defaultTabs,
                modelValue: 'tab1',
            },
        });

        const buttons = wrapper.findAll('button');
        expect(buttons).toHaveLength(3);
        expect(buttons[0].text()).toContain('Tab One');
        expect(buttons[1].text()).toContain('Tab Two');
        expect(buttons[2].text()).toContain('Tab Three');
    });

    it('should apply active styling to selected tab', () => {
        const wrapper = mount(Tabs, {
            props: {
                tabs: defaultTabs,
                modelValue: 'tab2',
            },
        });

        const buttons = wrapper.findAll('button');
        expect(buttons[1].classes()).toContain('border-indigo-500');
        expect(buttons[1].classes()).toContain('text-indigo-600');
    });

    it('should apply inactive styling to non-selected tabs', () => {
        const wrapper = mount(Tabs, {
            props: {
                tabs: defaultTabs,
                modelValue: 'tab2',
            },
        });

        const buttons = wrapper.findAll('button');
        expect(buttons[0].classes()).toContain('border-transparent');
        expect(buttons[0].classes()).toContain('text-gray-500');
    });

    it('should emit update:modelValue when tab is clicked', async () => {
        const wrapper = mount(Tabs, {
            props: {
                tabs: defaultTabs,
                modelValue: 'tab1',
            },
        });

        const buttons = wrapper.findAll('button');
        await buttons[1].trigger('click');

        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['tab2']);
    });

    it('should set aria-current on active tab', () => {
        const wrapper = mount(Tabs, {
            props: {
                tabs: defaultTabs,
                modelValue: 'tab1',
            },
        });

        const buttons = wrapper.findAll('button');
        expect(buttons[0].attributes('aria-current')).toBe('page');
        expect(buttons[1].attributes('aria-current')).toBeUndefined();
    });

    it('should render icon when provided', () => {
        const tabsWithIcons: Tab[] = [
            { id: 'tab1', label: 'Tab One', icon: 'sparkles' },
        ];

        const wrapper = mount(Tabs, {
            props: {
                tabs: tabsWithIcons,
                modelValue: 'tab1',
            },
        });

        // Icon should be rendered (check for icon classes)
        const button = wrapper.find('button');
        expect(button.html()).toContain('mr-2');
        expect(button.html()).toContain('-ml-0.5');
    });

    it('should not render icon when not provided', () => {
        const wrapper = mount(Tabs, {
            props: {
                tabs: [{ id: 'tab1', label: 'Tab One' }],
                modelValue: 'tab1',
            },
        });

        // Count elements with icon margin classes (should be 0 without icon)
        const button = wrapper.find('button');
        const iconElements = button.findAll('.mr-2.-ml-0.5');
        expect(iconElements).toHaveLength(0);
    });

    it('should have focus ring styling on tabs', () => {
        const wrapper = mount(Tabs, {
            props: {
                tabs: defaultTabs,
                modelValue: 'tab1',
            },
        });

        const button = wrapper.find('button');
        expect(button.classes()).toContain('focus:rounded-t-md');
        expect(button.classes()).toContain('focus:ring-2');
        expect(button.classes()).toContain('focus:ring-indigo-500');
    });

    it('should have aria-label on nav', () => {
        const wrapper = mount(Tabs, {
            props: {
                tabs: defaultTabs,
                modelValue: 'tab1',
            },
        });

        const nav = wrapper.find('nav');
        expect(nav.attributes('aria-label')).toBe('Tabs');
    });
});
