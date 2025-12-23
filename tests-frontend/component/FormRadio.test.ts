import FormRadio from '@/Components/Base/Form/FormRadio.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('FormRadio', () => {
    const defaultProps = {
        id: 'radio-1',
        value: 'option1',
        name: 'test-radio',
    };

    it('should render radio input', () => {
        const wrapper = mount(FormRadio, {
            props: defaultProps,
        });

        const input = wrapper.find('input[type="radio"]');
        expect(input.exists()).toBe(true);
    });

    it('should have correct id attribute', () => {
        const wrapper = mount(FormRadio, {
            props: defaultProps,
        });

        const input = wrapper.find('input');
        expect(input.attributes('id')).toBe('radio-1');
    });

    it('should have correct name attribute', () => {
        const wrapper = mount(FormRadio, {
            props: defaultProps,
        });

        const input = wrapper.find('input');
        expect(input.attributes('name')).toBe('test-radio');
    });

    it('should have correct value attribute', () => {
        const wrapper = mount(FormRadio, {
            props: defaultProps,
        });

        const input = wrapper.find('input');
        expect(input.attributes('value')).toBe('option1');
    });

    it('should be unchecked when modelValue does not match value', () => {
        const wrapper = mount(FormRadio, {
            props: {
                ...defaultProps,
                modelValue: 'option2',
            },
        });

        const input = wrapper.find('input');
        expect(input.element.checked).toBe(false);
    });

    it('should be checked when modelValue matches value', () => {
        const wrapper = mount(FormRadio, {
            props: {
                ...defaultProps,
                modelValue: 'option1',
            },
        });

        const input = wrapper.find('input');
        expect(input.element.checked).toBe(true);
    });

    it('should emit update:modelValue when changed', async () => {
        const wrapper = mount(FormRadio, {
            props: defaultProps,
        });

        const input = wrapper.find('input');
        await input.trigger('change');

        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['option1']);
    });

    it('should render label when provided', () => {
        const wrapper = mount(FormRadio, {
            props: {
                ...defaultProps,
                label: 'Option One',
            },
        });

        expect(wrapper.text()).toContain('Option One');
    });

    it('should render slot content when no label provided', () => {
        const wrapper = mount(FormRadio, {
            props: defaultProps,
            slots: {
                default: '<span>Custom Label</span>',
            },
        });

        expect(wrapper.html()).toContain('<span>Custom Label</span>');
    });

    it('should display help text when provided', () => {
        const wrapper = mount(FormRadio, {
            props: {
                ...defaultProps,
                helpText: 'This is help text',
            },
        });

        expect(wrapper.text()).toContain('This is help text');
    });

    it('should display error message when provided', () => {
        const wrapper = mount(FormRadio, {
            props: {
                ...defaultProps,
                error: 'This field is required',
            },
        });

        expect(wrapper.text()).toContain('This field is required');
        const errorDiv = wrapper.find('.text-red-600');
        expect(errorDiv.exists()).toBe(true);
    });

    it('should support disabled prop', () => {
        const wrapper = mount(FormRadio, {
            props: {
                ...defaultProps,
                disabled: true,
            },
        });

        const input = wrapper.find('input');
        expect(input.attributes('disabled')).toBeDefined();
    });

    it('should apply disabled styling to label', () => {
        const wrapper = mount(FormRadio, {
            props: {
                ...defaultProps,
                label: 'Test',
                disabled: true,
            },
        });

        const label = wrapper.find('.text-indigo-900');
        expect(label.classes()).toContain('text-indigo-300');
    });

    it('should support required prop', () => {
        const wrapper = mount(FormRadio, {
            props: {
                ...defaultProps,
                required: true,
            },
        });

        const input = wrapper.find('input');
        expect(input.attributes('required')).toBeDefined();
    });

    it('should have correct styling classes', () => {
        const wrapper = mount(FormRadio, {
            props: defaultProps,
        });

        const input = wrapper.find('input');
        expect(input.classes()).toContain('h-4');
        expect(input.classes()).toContain('w-4');
        expect(input.classes()).toContain('border-indigo-300');
        expect(input.classes()).toContain('text-indigo-600');
    });

    it('should work with numeric values', () => {
        const wrapper = mount(FormRadio, {
            props: {
                ...defaultProps,
                value: 1,
                modelValue: 1,
            },
        });

        const input = wrapper.find('input');
        expect(input.element.checked).toBe(true);
    });

    it('should work with boolean values', () => {
        const wrapper = mount(FormRadio, {
            props: {
                ...defaultProps,
                value: true,
                modelValue: true,
            },
        });

        const input = wrapper.find('input');
        expect(input.element.checked).toBe(true);
    });
});
