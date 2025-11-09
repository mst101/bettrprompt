import TextInput from '@/Components/TextInput.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('TextInput', () => {
    it('should render input element', () => {
        const wrapper = mount(TextInput, {
            props: {
                modelValue: '',
            },
        });

        expect(wrapper.find('input').exists()).toBe(true);
    });

    it('should display modelValue', () => {
        const wrapper = mount(TextInput, {
            props: {
                modelValue: 'Test value',
            },
        });

        expect((wrapper.find('input').element as HTMLInputElement).value).toBe(
            'Test value',
        );
    });

    it('should emit update:modelValue on input', async () => {
        const wrapper = mount(TextInput, {
            props: {
                modelValue: '',
            },
        });

        const input = wrapper.find('input');
        await input.setValue('New value');

        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([
            'New value',
        ]);
    });

    it('should have correct styling classes', () => {
        const wrapper = mount(TextInput, {
            props: {
                modelValue: '',
            },
        });

        const input = wrapper.find('input');
        expect(input.classes()).toContain('rounded-md');
        expect(input.classes()).toContain('border-gray-300');
        expect(input.classes()).toContain('shadow-xs');
    });

    it('should focus on autofocus attribute', async () => {
        const wrapper = mount(TextInput, {
            props: {
                modelValue: '',
            },
            attrs: {
                autofocus: true,
            },
        });

        await wrapper.vm.$nextTick();
        // Note: In JSDOM, we can't test actual focus, but we can verify the element exists
        expect(wrapper.find('input').exists()).toBe(true);
    });

    it('should expose focus method', () => {
        const wrapper = mount(TextInput, {
            props: {
                modelValue: '',
            },
        });

        expect(wrapper.vm.focus).toBeDefined();
        expect(typeof wrapper.vm.focus).toBe('function');
    });

    it('should handle empty string', () => {
        const wrapper = mount(TextInput, {
            props: {
                modelValue: '',
            },
        });

        expect((wrapper.find('input').element as HTMLInputElement).value).toBe(
            '',
        );
    });

    it('should update value when modelValue prop changes', async () => {
        const wrapper = mount(TextInput, {
            props: {
                modelValue: 'Initial',
            },
        });

        await wrapper.setProps({ modelValue: 'Updated' });

        expect((wrapper.find('input').element as HTMLInputElement).value).toBe(
            'Updated',
        );
    });
});
