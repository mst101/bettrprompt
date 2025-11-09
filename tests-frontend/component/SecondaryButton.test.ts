import SecondaryButton from '@/Components/SecondaryButton.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('SecondaryButton', () => {
    it('should render button element', () => {
        const wrapper = mount(SecondaryButton);

        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('should render slot content', () => {
        const wrapper = mount(SecondaryButton, {
            slots: {
                default: 'Cancel',
            },
        });

        expect(wrapper.text()).toBe('Cancel');
    });

    it('should have default type of button', () => {
        const wrapper = mount(SecondaryButton);

        expect(wrapper.find('button').attributes('type')).toBe('button');
    });

    it('should accept submit type', () => {
        const wrapper = mount(SecondaryButton, {
            props: {
                type: 'submit',
            },
        });

        expect(wrapper.find('button').attributes('type')).toBe('submit');
    });

    it('should accept reset type', () => {
        const wrapper = mount(SecondaryButton, {
            props: {
                type: 'reset',
            },
        });

        expect(wrapper.find('button').attributes('type')).toBe('reset');
    });

    it('should have correct styling classes', () => {
        const wrapper = mount(SecondaryButton);
        const button = wrapper.find('button');

        expect(button.classes()).toContain('inline-flex');
        expect(button.classes()).toContain('rounded-md');
        expect(button.classes()).toContain('bg-white');
        expect(button.classes()).toContain('border-gray-300');
    });

    it('should emit click event', async () => {
        const wrapper = mount(SecondaryButton);

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('click')).toBeTruthy();
    });

    it('should be uppercase', () => {
        const wrapper = mount(SecondaryButton);

        expect(wrapper.find('button').classes()).toContain('uppercase');
    });
});
