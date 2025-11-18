import QuestionAnsweringForm from '@/Components/PromptOptimizer/QuestionAnsweringForm.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('QuestionAnsweringForm', () => {
    const defaultProps = {
        question: 'What is your main goal?',
        answer: '',
        currentQuestionNumber: 1,
        totalQuestions: 3,
        isSubmitting: false,
    };

    it('should display question text', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: defaultProps,
        });

        expect(wrapper.text()).toContain('What is your main goal?');
    });

    it('should display progress with correct question numbers', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: defaultProps,
        });

        const progress = wrapper.find('[data-testid="progress-indicator"]');
        expect(progress.text()).toContain('Question 1 of 3');
    });

    it('should calculate progress percentage correctly', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                currentQuestionNumber: 2,
                totalQuestions: 4,
            },
        });

        const progress = wrapper.find('[data-testid="progress-indicator"]');
        expect(progress.text()).toContain('25% complete');
    });

    it('should update progress bar width based on percentage', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                currentQuestionNumber: 1,
                totalQuestions: 4,
            },
        });

        const progressBar = wrapper.find('[data-testid="progress-bar"]');
        expect(progressBar.attributes('style')).toContain('width: 0%');
    });

    it('should disable submit button when answer is empty', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                answer: '',
            },
        });

        const submitButton = wrapper.find(
            '[data-testid="submit-answer-button"]',
        );
        expect(submitButton.attributes('disabled')).toBeDefined();
    });

    it('should disable submit button when answer is whitespace', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                answer: '   ',
            },
        });

        const submitButton = wrapper.find(
            '[data-testid="submit-answer-button"]',
        );
        expect(submitButton.attributes('disabled')).toBeDefined();
    });

    it('should enable submit button when answer has content', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                answer: 'My detailed answer',
            },
        });

        const submitButton = wrapper.find(
            '[data-testid="submit-answer-button"]',
        );
        expect(submitButton.attributes('disabled')).toBeUndefined();
    });

    it('should disable submit button when submitting', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                answer: 'Answer',
                isSubmitting: true,
            },
        });

        const submitButton = wrapper.find(
            '[data-testid="submit-answer-button"]',
        );
        expect(submitButton.attributes('disabled')).toBeDefined();
    });

    it('should emit submit event when submit button clicked', async () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                answer: 'My answer',
            },
        });

        await wrapper
            .find('[data-testid="submit-answer-button"]')
            .trigger('click');
        expect(wrapper.emitted('submit')).toBeTruthy();
    });

    it('should emit skip event when skip button clicked', async () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: defaultProps,
        });

        await wrapper
            .find('[data-testid="skip-question-button"]')
            .trigger('click');
        expect(wrapper.emitted('skip')).toBeTruthy();
    });

    it('should disable skip button when submitting', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                isSubmitting: true,
            },
        });

        const skipButton = wrapper.find('[data-testid="skip-question-button"]');
        expect(skipButton.attributes('disabled')).toBeDefined();
    });

    it('should emit toggle-show-all when toggle button clicked', async () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: defaultProps,
        });

        const toggleButton = wrapper.find('button');
        await toggleButton.trigger('click');

        expect(wrapper.emitted('toggle-show-all')).toBeTruthy();
    });

    it('should display correct toggle button text when showAll is false', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                showAll: false,
            },
        });

        expect(wrapper.text()).toContain('(show all)');
    });

    it('should display correct toggle button text when showAll is true', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                showAll: true,
            },
        });

        expect(wrapper.text()).toContain('(one-at-a-time)');
    });

    it('should handle zero total questions gracefully', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                currentQuestionNumber: 0,
                totalQuestions: 0,
            },
        });

        const progressBar = wrapper.find('[data-testid="progress-bar"]');
        expect(progressBar.attributes('style')).toContain('width: 0%');
    });

    it('should round progress percentage to nearest integer', () => {
        const wrapper = mount(QuestionAnsweringForm, {
            props: {
                ...defaultProps,
                currentQuestionNumber: 2,
                totalQuestions: 3,
            },
        });

        // 1/3 = 33.333...%
        const progress = wrapper.find('[data-testid="progress-indicator"]');
        expect(progress.text()).toContain('33% complete');
    });
});
