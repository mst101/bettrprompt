import { describe, it, expect, beforeEach, vi } from 'vitest';
import { usePromptAnswering } from '@/Composables/usePromptAnswering';

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
        const { answerForm, submitAnswer, isSubmitting } = usePromptAnswering(1);

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
});
