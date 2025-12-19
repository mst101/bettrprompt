import ButtonDanger from '@/Components/Base/Button/ButtonDanger.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('ButtonDanger', () => {
    it('should render button element', () => {
        const wrapper = mount(ButtonDanger);

        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('should render slot content', () => {
        const wrapper = mount(ButtonDanger, {
            slots: {
                default: 'Delete Account',
            },
        });

        expect(wrapper.text()).toBe('Delete Account');
    });

    it('should render with danger variant styling', () => {
        const wrapper = mount(ButtonDanger);
        const button = wrapper.find('button');

        expect(button.classes()).toContain('inline-flex');
        expect(button.classes()).toContain('rounded-md');
        expect(button.classes()).toContain('bg-red-600');
        expect(button.classes()).toContain('text-white');
    });

    it('should emit click events', async () => {
        const wrapper = mount(ButtonDanger);

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('click')).toBeTruthy();
        expect(wrapper.emitted('click')).toHaveLength(1);
    });

    it('should support disabled prop', () => {
        const wrapper = mount(ButtonDanger, {
            props: {
                disabled: true,
            },
        });

        const button = wrapper.find('button');
        expect(button.attributes('disabled')).toBeDefined();
    });

    it('should support type prop', () => {
        const wrapper = mount(ButtonDanger, {
            props: {
                type: 'submit',
            },
        });

        const button = wrapper.find('button');
        expect(button.attributes('type')).toBe('submit');
    });
});
