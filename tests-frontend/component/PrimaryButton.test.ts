import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import PrimaryButton from '@/Components/PrimaryButton.vue';

describe('PrimaryButton', () => {
    it('should render button element', () => {
        const wrapper = mount(PrimaryButton);

        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('should render slot content', () => {
        const wrapper = mount(PrimaryButton, {
            slots: {
                default: 'Click me',
            },
        });

        expect(wrapper.text()).toBe('Click me');
    });

    it('should have correct CSS classes for styling', () => {
        const wrapper = mount(PrimaryButton);

        const button = wrapper.find('button');
        expect(button.classes()).toContain('bg-gray-800');
        expect(button.classes()).toContain('text-white');
        expect(button.classes()).toContain('rounded-md');
        expect(button.classes()).toContain('px-4');
        expect(button.classes()).toContain('py-2');
    });

    it('should have hover and focus classes', () => {
        const wrapper = mount(PrimaryButton);

        const button = wrapper.find('button');
        expect(button.classes()).toContain('hover:bg-gray-700');
        expect(button.classes()).toContain('focus:bg-gray-700');
        expect(button.classes()).toContain('focus:ring-2');
        expect(button.classes()).toContain('focus:ring-indigo-500');
    });

    it('should emit click events', async () => {
        const wrapper = mount(PrimaryButton);

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('click')).toBeTruthy();
        expect(wrapper.emitted('click')).toHaveLength(1);
    });

    it('should handle multiple clicks', async () => {
        const wrapper = mount(PrimaryButton);

        const button = wrapper.find('button');
        await button.trigger('click');
        await button.trigger('click');
        await button.trigger('click');

        expect(wrapper.emitted('click')).toHaveLength(3);
    });

    it('should support disabled attribute', () => {
        const wrapper = mount(PrimaryButton, {
            attrs: {
                disabled: true,
            },
        });

        const button = wrapper.find('button');
        expect(button.attributes('disabled')).toBeDefined();
    });

    it('should support type attribute', () => {
        const wrapper = mount(PrimaryButton, {
            attrs: {
                type: 'submit',
            },
        });

        const button = wrapper.find('button');
        expect(button.attributes('type')).toBe('submit');
    });
});
