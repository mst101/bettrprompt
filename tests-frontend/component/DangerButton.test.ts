import DangerButton from '@/Components/DangerButton.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('DangerButton', () => {
    it('should render button element', () => {
        const wrapper = mount(DangerButton);

        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('should render slot content', () => {
        const wrapper = mount(DangerButton, {
            slots: {
                default: 'Delete Account',
            },
        });

        expect(wrapper.text()).toBe('Delete Account');
    });

    it('should have correct styling classes', () => {
        const wrapper = mount(DangerButton);
        const button = wrapper.find('button');

        expect(button.classes()).toContain('inline-flex');
        expect(button.classes()).toContain('rounded-md');
        expect(button.classes()).toContain('bg-red-600');
        expect(button.classes()).toContain('text-white');
    });

    it('should emit click event', async () => {
        const wrapper = mount(DangerButton);

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('click')).toBeTruthy();
    });

    it('should be uppercase', () => {
        const wrapper = mount(DangerButton);

        expect(wrapper.find('button').classes()).toContain('uppercase');
    });

    it('should have red background', () => {
        const wrapper = mount(DangerButton);
        const button = wrapper.find('button');

        expect(button.classes()).toContain('bg-red-600');
    });

    it('should have white text', () => {
        const wrapper = mount(DangerButton);
        const button = wrapper.find('button');

        expect(button.classes()).toContain('text-white');
    });
});
