import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('ButtonPrimary', () => {
    it('should render button element', () => {
        const wrapper = mount(ButtonPrimary);

        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('should render slot content', () => {
        const wrapper = mount(ButtonPrimary, {
            slots: {
                default: 'Click me',
            },
        });

        expect(wrapper.text()).toBe('Click me');
    });

    it('should render with primary variant styling', () => {
        const wrapper = mount(ButtonPrimary);
        const button = wrapper.find('button');

        expect(button.classes()).toContain('bg-indigo-600');
        expect(button.classes()).toContain('text-white');
    });

    it('should emit click events', async () => {
        const wrapper = mount(ButtonPrimary);

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('click')).toBeTruthy();
        expect(wrapper.emitted('click')).toHaveLength(1);
    });

    it('should support disabled prop', () => {
        const wrapper = mount(ButtonPrimary, {
            props: {
                disabled: true,
            },
        });

        const button = wrapper.find('button');
        expect(button.attributes('disabled')).toBeDefined();
    });

    it('should support type prop', () => {
        const wrapper = mount(ButtonPrimary, {
            props: {
                type: 'submit',
            },
        });

        const button = wrapper.find('button');
        expect(button.attributes('type')).toBe('submit');
    });

    it('should handle multiple clicks', async () => {
        const wrapper = mount(ButtonPrimary);

        const button = wrapper.find('button');
        await button.trigger('click');
        await button.trigger('click');
        await button.trigger('click');

        expect(wrapper.emitted('click')).toHaveLength(3);
    });

    it('should support loading prop', () => {
        const wrapper = mount(ButtonPrimary, {
            props: {
                loading: true,
            },
        });

        expect(wrapper.find('svg').exists()).toBe(true);
        expect(wrapper.find('button').attributes('disabled')).toBeDefined();
    });
});
