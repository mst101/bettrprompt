import FormField from '@/Components/FormField.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('FormField', () => {
    it('should render label', () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'email',
                label: 'Email Address',
            },
        });

        const label = wrapper.findComponent(InputLabel);
        expect(label.exists()).toBe(true);
        expect(label.props('value')).toBe('Email Address');
    });

    it('should render text input by default', () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'name',
                label: 'Name',
            },
        });

        const input = wrapper.findComponent(TextInput);
        expect(input.exists()).toBe(true);
    });

    it('should render textarea when type is textarea', () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'description',
                label: 'Description',
                type: 'textarea',
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.exists()).toBe(true);
    });

    it('should display error message', () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'email',
                label: 'Email',
                error: 'Invalid email format',
            },
        });

        const error = wrapper.findComponent(InputError);
        expect(error.exists()).toBe(true);
        expect(error.props('message')).toBe('Invalid email format');
    });

    it('should not display error when no error prop', () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'email',
                label: 'Email',
            },
        });

        const error = wrapper.findComponent(InputError);
        expect(error.exists()).toBe(false);
    });

    it('should emit update:modelValue on input change', async () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'name',
                label: 'Name',
                modelValue: '',
            },
        });

        const input = wrapper.findComponent(TextInput);
        await input.vm.$emit('update:modelValue', 'John Doe');

        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['John Doe']);
    });

    it('should pass placeholder to input', () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'email',
                label: 'Email',
                placeholder: 'Enter your email',
            },
        });

        const input = wrapper.findComponent(TextInput);
        expect(input.attributes('placeholder')).toBe('Enter your email');
    });

    it('should pass required prop to input', () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'name',
                label: 'Name',
                required: true,
            },
        });

        const input = wrapper.findComponent(TextInput);
        expect(input.attributes('required')).toBeDefined();
    });

    it('should pass disabled prop to input', () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'name',
                label: 'Name',
                disabled: true,
            },
        });

        const input = wrapper.findComponent(TextInput);
        expect(input.attributes('disabled')).toBeDefined();
    });

    it('should pass autofocus prop to input', () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'name',
                label: 'Name',
                autofocus: true,
            },
        });

        const input = wrapper.findComponent(TextInput);
        expect(input.attributes('autofocus')).toBeDefined();
    });

    it('should pass rows prop to textarea', () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'description',
                label: 'Description',
                type: 'textarea',
                rows: 5,
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.attributes('rows')).toBe('5');
    });

    it('should pass min and max to number input', () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'age',
                label: 'Age',
                type: 'number',
                min: 18,
                max: 100,
            },
        });

        const input = wrapper.findComponent(TextInput);
        expect(input.attributes('min')).toBe('18');
        expect(input.attributes('max')).toBe('100');
    });

    it('should handle textarea input event', async () => {
        const wrapper = mount(FormField, {
            props: {
                id: 'description',
                label: 'Description',
                type: 'textarea',
                modelValue: '',
            },
        });

        const textarea = wrapper.find('textarea');
        await textarea.setValue('New description');

        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([
            'New description',
        ]);
    });
});
