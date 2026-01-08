import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import { router, useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

/**
 * Composable for handling prompt optimization question answering
 *
 * @param promptRunId - The ID of the prompt run
 * @param onNavigate - Optional callback to focus textarea after navigation
 *
 * @example
 * const {
 *     answerForm,
 *     isSubmitting,
 *     submitAnswer,
 *     handleTranscription,
 *     clearAnswer
 * } = usePromptAnswering(props.promptRun.id, () => textareaRef.value?.focus());
 */
export function usePromptAnswering(
    promptRunId: number,
    onNavigate?: () => void,
) {
    const { localeRoute } = useLocaleRoute();
    const isSubmitting = ref(false);

    const answerForm = useForm({
        answer: '',
    });

    const submitAnswer = () => {
        if (!answerForm.answer.trim()) return;

        isSubmitting.value = true;
        answerForm.post(localeRoute('prompt-builder.answer', promptRunId), {
            preserveScroll: true,
            onSuccess: async () => {
                // Don't reset the form here - the watcher will set the correct answer
                // for the next question based on currentQuestionAnswer prop
                isSubmitting.value = false;
                await nextTick();
                onNavigate?.();
            },
            onError: () => {
                isSubmitting.value = false;
            },
        });
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
            localeRoute('prompt-builder.go-back', promptRunId),
            {},
            {
                preserveScroll: true,
                onFinish: async () => {
                    isSubmitting.value = false;
                    await nextTick();
                    onNavigate?.();
                },
            },
        );
    };

    return {
        answerForm,
        isSubmitting,
        submitAnswer,
        goBackToPreviousQuestion,
        handleTranscription,
        clearAnswer,
    };
}
