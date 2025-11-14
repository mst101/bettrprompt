import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Composable for handling prompt optimization question answering
 *
 * @param promptRunId - The ID of the prompt run
 *
 * @example
 * const {
 *     answerForm,
 *     isSubmitting,
 *     submitAnswer,
 *     skipQuestion,
 *     handleTranscription,
 *     clearAnswer
 * } = usePromptAnswering(props.promptRun.id);
 */
export function usePromptAnswering(promptRunId: number) {
    const isSubmitting = ref(false);

    const answerForm = useForm({
        answer: '',
    });

    const submitAnswer = () => {
        if (!answerForm.answer.trim()) return;

        isSubmitting.value = true;
        answerForm.post(route('prompt-optimizer.answer', promptRunId), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Answer submitted successfully');
                // Don't reset the form here - the watcher will set the correct answer
                // for the next question based on currentQuestionAnswer prop
                isSubmitting.value = false;
            },
            onError: () => {
                isSubmitting.value = false;
            },
        });
    };

    const skipQuestion = () => {
        isSubmitting.value = true;
        router.post(
            route('prompt-optimizer.skip', promptRunId),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    isSubmitting.value = false;
                },
            },
        );
    };

    const handleTranscription = (text: string) => {
        // Append transcription to existing text (with space if text exists)
        if (answerForm.answer && !answerForm.answer.endsWith(' ')) {
            answerForm.answer += ' ';
        }
        answerForm.answer += text;
    };

    const clearAnswer = () => {
        answerForm.answer = '';
    };

    const goBackToPreviousQuestion = () => {
        isSubmitting.value = true;
        router.post(
            route('prompt-optimizer.go-back', promptRunId),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    isSubmitting.value = false;
                },
            },
        );
    };

    return {
        answerForm,
        isSubmitting,
        submitAnswer,
        skipQuestion,
        goBackToPreviousQuestion,
        handleTranscription,
        clearAnswer,
    };
}
