<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import OptionalBadge from '@/Components/OptionalBadge.vue';
import type { ClarifyingQuestion } from '@/Components/PromptBuilder/Cards/clarifyingQuestion';
import QuestionNumber from '@/Components/PromptBuilder/QuestionNumber.vue';
import { useTextAppend } from '@/Composables/useTextAppend';
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

const setTextareaRef = (
    el: InstanceType<typeof FormTextareaWithActions> | null,
    index: number,
) => {
    if (el) {
        textareaRefs.value[index] = el;
    }
};

const focusFirstTextarea = () => {
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
    <div class="space-y-8">
        <div v-for="(question, index) in questions" :key="question.id ?? index">
            <div class="flex gap-4">
                <div class="mt-0.5">
                    <QuestionNumber :number="index + 1" />
                </div>
                <div class="flex flex-1 items-start gap-3">
                    <span class="flex-1 text-sm font-medium text-indigo-900">
                        {{ question.question }}
                        <span
                            v-if="question.required"
                            class="ml-1 text-red-500"
                        >
                            *
                        </span>
                    </span>
                    <OptionalBadge v-if="question.required === false" />
                </div>
            </div>
            <div class="mt-2 space-y-3 sm:mt-0 sm:ml-10">
                <p v-if="question.purpose" class="text-sm text-indigo-600">
                    {{ question.purpose }}
                </p>
                <FormTextareaWithActions
                    :id="`bulk-answer-${index}`"
                    :ref="(el: any) => setTextareaRef(el, index)"
                    :model-value="answers[index] ?? ''"
                    :label="`Answer ${index + 1}`"
                    :disabled="isSubmitting"
                    :rows="4"
                    placeholder="Share your answer (leave empty to skip)..."
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
            class="flex flex-col justify-end gap-2 sm:flex-row sm:items-center sm:space-x-2"
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
