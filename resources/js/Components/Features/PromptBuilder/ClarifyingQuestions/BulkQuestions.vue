<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/Base/Button/ButtonVoiceInput.vue';
import FormTextareaWithActions from '@/Components/Base/Form/FormTextareaWithActions.vue';
import OptionalBadge from '@/Components/Common/OptionalBadge.vue';
import QuestionNumber from '@/Components/Features/PromptBuilder/Forms/QuestionNumber.vue';
import { useTextAppend } from '@/Composables/features/useTextAppend';
import type { ClarifyingQuestion } from '@/Types/models/ClarifyingQuestion';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        questions: ClarifyingQuestion[];
        answers: (string | null)[];
        hasOptionalQuestions: boolean;
        optionalQuestionsLabel: string;
        isSubmitting: boolean;
        submitLabel?: string;
        showBack?: boolean;
        backLabel?: string;
    }>(),
    {
        submitLabel: 'Submit All Answers',
        showBack: true,
        backLabel: 'Back to One-at-a-Time',
    },
);

const emit = defineEmits<{
    (e: 'update:answer', index: number, value: string): void;
    (e: 'submit-all'): void;
    (e: 'back'): void;
}>();

const { appendText } = useTextAppend();

const textareaRefs = ref<
    (InstanceType<typeof FormTextareaWithActions> | null)[]
>([]);

// Check if we're on a larger screen (sm breakpoint and above)
const isLargeScreen = () => window.matchMedia('(min-width: 640px)').matches;

const setTextareaRef = (
    el: InstanceType<typeof FormTextareaWithActions> | null,
    index: number,
) => {
    if (el) {
        textareaRefs.value[index] = el;
    }
};

const focusFirstTextarea = () => {
    // Only focus on larger screens to avoid keyboard popup on mobile
    if (!isLargeScreen()) return;

    const firstRef = textareaRefs.value[0];
    if (firstRef) {
        firstRef.focus();
    }
};

defineExpose({ focusFirstTextarea });

const updateAnswer = (index: number, value: string) => {
    emit('update:answer', index, value ?? '');
};

const handleTranscription = (index: number, transcript: string) => {
    const current = props.answers[index] ?? '';
    updateAnswer(index, appendText(current, transcript));
};
</script>

<template>
    <div class="mt-10 space-y-16">
        <div v-for="(question, index) in questions" :key="question.id ?? index">
            <div class="flex gap-4">
                <div class="mt-0.5">
                    <QuestionNumber :number="index + 1" />
                </div>
                <div class="flex flex-1 items-start">
                    <span class="flex-1 text-indigo-900 sm:text-lg">
                        {{ question.question }}
                    </span>
                    <OptionalBadge v-if="question.required === false" />
                </div>
            </div>
            <div class="mt-2 space-y-3 sm:mt-0 sm:ml-10">
                <p
                    v-if="question.purpose"
                    class="mt-4 mb-0 text-sm text-indigo-600"
                >
                    {{ question.purpose }}
                </p>
                <FormTextareaWithActions
                    :id="`bulk-answer-${index}`"
                    :ref="(el: any) => setTextareaRef(el, index)"
                    :model-value="answers[index] ?? ''"
                    :label="`Answer ${index + 1}`"
                    :disabled="isSubmitting"
                    placeholder="Type your answer here, or record a quick note..."
                    @update:model-value="
                        (value: string) => updateAnswer(index, value)
                    "
                >
                    <template #actions>
                        <ButtonVoiceInput
                            :disabled="isSubmitting"
                            @transcription="
                                (transcript: string) =>
                                    handleTranscription(index, transcript)
                            "
                        />
                    </template>
                </FormTextareaWithActions>
            </div>
        </div>
        <div
            class="flex flex-col-reverse justify-end gap-2 sm:flex-row sm:items-center sm:space-x-2"
        >
            <ButtonSecondary
                v-if="showBack"
                type="button"
                :disabled="isSubmitting"
                class="w-full sm:w-auto"
                @click="emit('back')"
            >
                {{ backLabel }}
            </ButtonSecondary>
            <ButtonPrimary
                type="button"
                :disabled="isSubmitting"
                :loading="isSubmitting"
                class="w-full sm:w-auto"
                @click="emit('submit-all')"
            >
                {{ submitLabel }}
            </ButtonPrimary>
        </div>
    </div>
</template>
