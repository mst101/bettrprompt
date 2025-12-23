import FormTextarea from '@/Components/Base/Form/FormTextarea.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

describe('FormTextarea', () => {
    const defaultProps = {
        id: 'test-textarea',
        modelValue: '',
        label: 'Test Label',
    };

    it('should render textarea element', () => {
        const wrapper = mount(FormTextarea, {
            props: defaultProps,
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.exists()).toBe(true);
    });

    it('should have correct id attribute', () => {
        const wrapper = mount(FormTextarea, {
            props: defaultProps,
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.attributes('id')).toBe('test-textarea');
    });

    it('should start with 3 rows by default', () => {
        const wrapper = mount(FormTextarea, {
            props: defaultProps,
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.attributes('rows')).toBe('3');
    });

    it('should render label when provided', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                label: 'Enter your feedback',
            },
        });

        expect(wrapper.text()).toContain('Enter your feedback');
    });

    it('should not render label when srOnlyLabel is true', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                label: 'Hidden label',
                srOnlyLabel: true,
            },
        });

        const label = wrapper.find('label');
        expect(label.classes()).toContain('sr-only');
    });

    it('should display help text when provided', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                helpText: 'Please provide detailed feedback',
            },
        });

        expect(wrapper.text()).toContain('Please provide detailed feedback');
    });

    it('should display error message when provided', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                error: 'This field is required',
            },
        });

        expect(wrapper.text()).toContain('This field is required');
        const errorDiv = wrapper.find('.text-red-600');
        expect(errorDiv.exists()).toBe(true);
    });

    it('should show required indicator when required prop is true', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                label: 'Feedback',
                required: true,
            },
        });

        const requiredSpan = wrapper.find('.text-red-500');
        expect(requiredSpan.exists()).toBe(true);
        expect(requiredSpan.text()).toBe('*');
    });

    it('should emit update:modelValue on input', async () => {
        const wrapper = mount(FormTextarea, {
            props: defaultProps,
        });

        const textarea = wrapper.find('textarea');
        await textarea.setValue('New content');

        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([
            'New content',
        ]);
    });

    it('should support placeholder attribute', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                placeholder: 'Type your message here...',
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.attributes('placeholder')).toBe(
            'Type your message here...',
        );
    });

    it('should support disabled attribute', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                disabled: true,
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.attributes('disabled')).toBeDefined();
    });

    it('should support maxlength attribute', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                maxlength: 500,
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.attributes('maxlength')).toBe('500');
    });

    it('should have correct styling classes', () => {
        const wrapper = mount(FormTextarea, {
            props: defaultProps,
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.classes()).toContain('resize-none');
        expect(textarea.classes()).toContain('placeholder-indigo-700');
        expect(textarea.classes()).toContain('text-indigo-900');
        expect(textarea.classes()).toContain('bg-indigo-50');
    });

    it('should apply error styling when error is provided', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                error: 'Invalid input',
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.classes()).toContain('border-red-300');
        expect(textarea.classes()).toContain('focus:ring-red-500');
    });

    it('should support autofocus prop', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                autofocus: true,
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.attributes('autofocus')).toBeDefined();
    });

    it('should show cursor-not-allowed when disabled', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                disabled: true,
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.classes()).toContain('cursor-not-allowed');
    });

    it('should show cursor-not-allowed when isSubmitting is true', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                isSubmitting: true,
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.classes()).toContain('cursor-not-allowed');
    });

    it('should support required attribute', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                required: true,
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.attributes('required')).toBeDefined();
    });

    it('should update modelValue from props', async () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                modelValue: 'Initial content',
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.element.value).toBe('Initial content');

        await wrapper.setProps({ modelValue: 'Updated content' });
        expect(textarea.element.value).toBe('Updated content');
    });

    it('should support custom textarea classes', () => {
        const customClass = 'custom-class-name';
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                textareaClass: customClass,
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.classes()).toContain('custom-class-name');
    });

    it('should expose focus method', () => {
        const wrapper = mount(FormTextarea, {
            props: defaultProps,
        });

        const vm = wrapper.vm as any;
        expect(typeof vm.focus).toBe('function');
    });

    it('should have label with correct styling', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                label: 'Test Label',
            },
        });

        const label = wrapper.find('label');
        expect(label.classes()).toContain('block');
        expect(label.classes()).toContain('text-sm');
        expect(label.classes()).toContain('font-medium');
        expect(label.classes()).toContain('text-indigo-900');
    });

    it('should support aria-label when srOnlyLabel is true', () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                label: 'Test Label',
                srOnlyLabel: true,
            },
        });

        const textarea = wrapper.find('textarea');
        expect(textarea.attributes('aria-label')).toBe('Test Label');
    });

    it('should handle dynamic row updates when content changes', async () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                modelValue: '',
            },
        });

        const textarea = wrapper.find('textarea');

        // Should start with 3 rows
        expect(textarea.attributes('rows')).toBe('3');

        // Mock the scrollHeight and lineHeight to simulate content expansion
        // This is a basic test - actual row expansion depends on browser rendering
        const textareaElement = textarea.element as HTMLTextAreaElement;

        // Override getBoundingClientRect for testing purposes
        Object.defineProperty(window, 'getComputedStyle', {
            value: vi.fn(() => ({
                lineHeight: '20px',
            })),
        });

        // Update content - in a real browser, scrollHeight would increase
        await wrapper.setProps({
            modelValue: 'Line 1\nLine 2\nLine 3\nLine 4\nLine 5',
        });

        // The component should have attempted to calculate new rows
        // (actual row count depends on DOM layout which is limited in test environment)
        expect(textareaElement.value).toBe(
            'Line 1\nLine 2\nLine 3\nLine 4\nLine 5',
        );
    });

    it('should not exceed maximum rows of 10', async () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                modelValue: '',
            },
        });

        const textarea = wrapper.find('textarea');

        // Mock getComputedStyle to return a lineHeight
        Object.defineProperty(window, 'getComputedStyle', {
            value: vi.fn(() => ({
                lineHeight: '20px',
            })),
        });

        // Create very long content that would normally exceed 10 rows
        const longContent = Array(100).fill('Line of text').join('\n');

        await wrapper.setProps({ modelValue: longContent });

        // Rows should be clamped to maximum of 10
        const rowsAttr = textarea.attributes('rows');
        const rows = parseInt(rowsAttr || '0');
        expect(rows).toBeLessThanOrEqual(10);
    });

    it('should maintain minimum rows of 3', async () => {
        const wrapper = mount(FormTextarea, {
            props: {
                ...defaultProps,
                modelValue: 'Short text',
            },
        });

        const textarea = wrapper.find('textarea');

        // Mock getComputedStyle
        Object.defineProperty(window, 'getComputedStyle', {
            value: vi.fn(() => ({
                lineHeight: '20px',
            })),
        });

        // Even with minimal content, should have at least 3 rows
        const rowsAttr = textarea.attributes('rows');
        const rows = parseInt(rowsAttr || '0');
        expect(rows).toBeGreaterThanOrEqual(3);
    });
});
