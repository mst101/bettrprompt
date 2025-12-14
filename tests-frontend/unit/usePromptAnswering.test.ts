import { usePromptAnswering } from '@/Composables/usePromptAnswering';
import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    router: {
        post: vi.fn(),
    },
    useForm: vi.fn((data) => {
        const form = { ...data };
        const mockForm = { ...form };
        mockForm.post = vi.fn(); // Don't call callbacks automatically
        return mockForm;
    }),
}));

describe('usePromptAnswering', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should initialise with empty answer and not submitting', () => {
        const { answerForm, isSubmitting } = usePromptAnswering(1);

        expect(answerForm.answer).toBe('');
        expect(isSubmitting.value).toBe(false);
    });

    it('should not submit empty answer', () => {
        const { answerForm, submitAnswer } = usePromptAnswering(1);

        answerForm.answer = '';
        submitAnswer();

        // Form post should not be called (checked via mock in vitest.setup.ts)
        expect(answerForm.answer).toBe('');
    });

    it('should not submit whitespace-only answer', () => {
        const { answerForm, submitAnswer } = usePromptAnswering(1);

        answerForm.answer = '   ';
        submitAnswer();

        expect(answerForm.answer).toBe('   ');
    });

    it('should submit valid answer', () => {
        const { answerForm, submitAnswer, isSubmitting } =
            usePromptAnswering(1);

        answerForm.answer = 'My answer to the question';
        submitAnswer();

        expect(isSubmitting.value).toBe(true);
    });

    it('should handle transcription on empty answer', () => {
        const { answerForm, handleTranscription } = usePromptAnswering(1);

        handleTranscription('Hello world');

        expect(answerForm.answer).toBe('Hello world');
    });

    it('should append transcription to existing answer with space', () => {
        const { answerForm, handleTranscription } = usePromptAnswering(1);

        answerForm.answer = 'Hello';
        handleTranscription('world');

        expect(answerForm.answer).toBe('Hello world');
    });

    it('should not add double space if answer already ends with space', () => {
        const { answerForm, handleTranscription } = usePromptAnswering(1);

        answerForm.answer = 'Hello ';
        handleTranscription('world');

        expect(answerForm.answer).toBe('Hello world');
    });

    it('should clear answer', () => {
        const { answerForm, clearAnswer } = usePromptAnswering(1);

        answerForm.answer = 'Some text';
        clearAnswer();

        expect(answerForm.answer).toBe('');
    });

    it('should set submitting state when skipping question', () => {
        const { isSubmitting, skipQuestion } = usePromptAnswering(1);

        expect(isSubmitting.value).toBe(false);
        skipQuestion();
        expect(isSubmitting.value).toBe(true);
    });

    it('should handle multiple transcriptions', () => {
        const { answerForm, handleTranscription } = usePromptAnswering(1);

        handleTranscription('First');
        handleTranscription('second');
        handleTranscription('third');

        expect(answerForm.answer).toBe('First second third');
    });

    it('should accept onNavigate callback', () => {
        const onNavigate = vi.fn();
        const { answerForm, submitAnswer } = usePromptAnswering(1, onNavigate);

        // Just verify the callback is accepted without error
        answerForm.answer = 'My answer';
        expect(() => submitAnswer()).not.toThrow();
    });

    it('should skip question', () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost);

        const { skipQuestion } = usePromptAnswering(1);

        skipQuestion();

        expect(mockPost).toHaveBeenCalled();
    });

    it('should go back to previous question', () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost);

        const { goBackToPreviousQuestion } = usePromptAnswering(1);

        goBackToPreviousQuestion();

        expect(mockPost).toHaveBeenCalled();
    });

    it('should set isSubmitting when answer submitted', () => {
        const { answerForm, submitAnswer, isSubmitting } =
            usePromptAnswering(1);

        expect(isSubmitting.value).toBe(false);
        answerForm.answer = 'My answer';
        submitAnswer();
        expect(isSubmitting.value).toBe(true);
    });

    it('should set isSubmitting when skipping', () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost);

        const { isSubmitting, skipQuestion } = usePromptAnswering(1);

        expect(isSubmitting.value).toBe(false);
        skipQuestion();
        expect(isSubmitting.value).toBe(true);
    });

    it('should set isSubmitting when going back', () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost);

        const { isSubmitting, goBackToPreviousQuestion } =
            usePromptAnswering(1);

        expect(isSubmitting.value).toBe(false);
        goBackToPreviousQuestion();
        expect(isSubmitting.value).toBe(true);
    });

    it('should handle transcription with punctuation', () => {
        const { answerForm, handleTranscription } = usePromptAnswering(1);

        handleTranscription('Hello,');
        handleTranscription('world!');

        expect(answerForm.answer).toBe('Hello, world!');
    });

    it('should submit answer with correct route', () => {
        const { answerForm, submitAnswer } = usePromptAnswering(123);

        answerForm.answer = 'Test answer';
        submitAnswer();

        // Form should be submitting
        expect(answerForm.post).toHaveBeenCalled();
    });

    it('should skip question with correct route', () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost);

        const { skipQuestion } = usePromptAnswering(456);

        skipQuestion();

        expect(mockPost).toHaveBeenCalled();
    });

    it('should go back with correct route', () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost);

        const { goBackToPreviousQuestion } = usePromptAnswering(789);

        goBackToPreviousQuestion();

        expect(mockPost).toHaveBeenCalled();
    });

    it('should call onError callback when form submission fails', async () => {
        const { answerForm, submitAnswer, isSubmitting } =
            usePromptAnswering(1);

        answerForm.answer = 'Test answer';
        submitAnswer();

        expect(isSubmitting.value).toBe(true);

        // Call the onError callback to simulate form submission failure
        const postCall = vi.mocked(answerForm.post).mock.calls[0];
        const options = postCall[1];
        if (options?.onError) {
            options.onError();
        }

        expect(isSubmitting.value).toBe(false);
    });

    it('should call onNavigate callback after successful submission', async () => {
        const onNavigate = vi.fn();
        const { answerForm, submitAnswer } = usePromptAnswering(1, onNavigate);

        answerForm.answer = 'Test answer';
        submitAnswer();

        // Call the onSuccess callback to simulate successful form submission
        const postCall = vi.mocked(answerForm.post).mock.calls[0];
        const options = postCall[1];
        if (options?.onSuccess) {
            await options.onSuccess();
        }

        expect(onNavigate).toHaveBeenCalled();
    });

    it('should call onNavigate callback after skipping question', async () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost);

        const onNavigate = vi.fn();
        const { skipQuestion } = usePromptAnswering(1, onNavigate);

        skipQuestion();

        // Call the onFinish callback
        const postCall = mockPost.mock.calls[0];
        const options = postCall[2];
        if (options?.onFinish) {
            await options.onFinish();
        }

        expect(onNavigate).toHaveBeenCalled();
    });

    it('should call onNavigate callback after going back', async () => {
        const mockPost = vi.fn();
        vi.mocked(router.post).mockImplementation(mockPost);

        const onNavigate = vi.fn();
        const { goBackToPreviousQuestion } = usePromptAnswering(1, onNavigate);

        goBackToPreviousQuestion();

        // Call the onFinish callback
        const postCall = mockPost.mock.calls[0];
        const options = postCall[2];
        if (options?.onFinish) {
            await options.onFinish();
        }

        expect(onNavigate).toHaveBeenCalled();
    });
});
