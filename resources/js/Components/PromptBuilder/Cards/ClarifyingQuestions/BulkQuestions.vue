<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/ButtonVoiceInput.vue';
import FormTextareaWithActions from '@/Components/FormTextareaWithActions.vue';
import type { ClarifyingQuestion } from '@/Components/PromptBuilder/Cards/clarifyingQuestion';
import { useTextAppend } from '@/Composables/useTextAppend';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        questions: ClarifyingQuestion[];
        answers: (string | null)[];
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

const setTextareaRef = (el: any, index: number) => {
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
            <div class="flex items-start gap-4">
                <span
                    class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-800"
                >
                    {{ index + 1 }}
                </span>
                <div class="flex-1 space-y-3">
                    <div>
                        <p class="text-sm font-medium text-indigo-900">
                            {{ question.question }}
                            <span
                                v-if="question.required"
                                class="ml-1 text-red-500"
                            >
                                *
                            </span>
                        </p>
                        <p
                            v-if="question.purpose"
                            class="mt-1 text-sm text-indigo-600"
                        >
                            {{ question.purpose }}
                        </p>
                    </div>
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
        </div>
        <div class="flex items-center justify-between gap-3">
            <div>
                <ButtonSecondary
                    v-if="showBack"
                    type="button"
                    :disabled="isSubmitting"
                    @click="emit('back')"
                >
                    {{ backLabel }}
                </ButtonSecondary>
            </div>
            <ButtonPrimary
                type="button"
                :disabled="isSubmitting"
                :loading="isSubmitting"
                @click="emit('submit-all')"
            >
                {{ submitLabel }}
            </ButtonPrimary>
        </div>
    </div>
</template>
