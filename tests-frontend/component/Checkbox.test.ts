import FormCheckbox from '@/Components/Base/Form/FormCheckbox.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('FormCheckbox', () => {
    const defaultProps = {
        id: 'test-checkbox',
    };

    it('should render checkbox input', () => {
        const wrapper = mount(FormCheckbox, {
            props: defaultProps,
        });

        const input = wrapper.find('input[type="checkbox"]');
        expect(input.exists()).toBe(true);
    });

    it('should be unchecked by default', () => {
        const wrapper = mount(FormCheckbox, {
            props: {
                ...defaultProps,
                modelValue: false,
            },
        });

        const input = wrapper.find('input[type="checkbox"]')
            .element as HTMLInputElement;
        expect(input.checked).toBe(false);
    });

    it('should be checked when prop is true', () => {
        const wrapper = mount(FormCheckbox, {
            props: {
                ...defaultProps,
                modelValue: true,
            },
        });

        const input = wrapper.find('input[type="checkbox"]')
            .element as HTMLInputElement;
        expect(input.checked).toBe(true);
    });

    it('should emit update:modelValue when clicked', async () => {
        const wrapper = mount(FormCheckbox, {
            props: {
                ...defaultProps,
                modelValue: false,
            },
        });

        const input = wrapper.find('input[type="checkbox"]');
        await input.setValue(true);

        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true]);
    });

    it('should toggle from checked to unchecked', async () => {
        const wrapper = mount(FormCheckbox, {
            props: {
                ...defaultProps,
                modelValue: true,
            },
        });

        const input = wrapper.find('input[type="checkbox"]');
        await input.setValue(false);

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false]);
    });

    it('should have correct styling classes', () => {
        const wrapper = mount(FormCheckbox, {
            props: defaultProps,
        });

        const input = wrapper.find('input[type="checkbox"]');
        expect(input.classes()).toContain('size-4');
        expect(input.classes()).toContain('border-indigo-300');
        expect(input.classes()).toContain('text-indigo-600');
    });

    it('should work without value prop', () => {
        const wrapper = mount(FormCheckbox, {
            props: defaultProps,
        });

        expect(wrapper.find('input[type="checkbox"]').exists()).toBe(true);
    });
});
