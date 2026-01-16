<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import ButtonVoiceInput from '@/Components/Base/Button/ButtonVoiceInput.vue';
import FormTextareaWithActions from '@/Components/Base/Form/FormTextareaWithActions.vue';
import OptionalBadge from '@/Components/Common/OptionalBadge.vue';
import QuestionNumber from '@/Components/Features/PromptBuilder/Forms/QuestionNumber.vue';
import { useTextAppend } from '@/Composables/features/useTextAppend';
import type { ClarifyingQuestion } from '@/Types/models/ClarifyingQuestion';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        questions: ClarifyingQuestion[];
        answers: (string | null)[];
        savedAnswers: (string | null)[]; // Backend-saved answers for comparison
        hasOptionalQuestions: boolean;
        optionalQuestionsLabel: string;
        isSubmitting: boolean;
        submitLabel?: string;
        showBack?: boolean;
        backLabel?: string;
        isEditMode?: boolean;
    }>(),
    {
        showBack: true,
        isEditMode: false,
    },
);

const emit = defineEmits<{
    (e: 'update:answer', index: number, value: string): void;
    (e: 'save-answer', index: number, value: string): void;
    (e: 'submit-all'): void;
    (e: 'back'): void;
}>();

const { t } = useI18n({ useScope: 'global' });
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

const handleBlur = (index: number, value: string) => {
    // Only save in answering mode, not edit mode
    if (props.isEditMode) {
        return;
    }

    // Normalize values for comparison (trim and treat empty as null)
    const normalizeValue = (val: string | null | undefined) => {
        if (!val) return null;
        const trimmed = val.trim();
        return trimmed.length ? trimmed : null;
    };

    const currentValue = normalizeValue(value);
    const savedValue = normalizeValue(props.savedAnswers[index]);

    // Only save if the value has actually changed from what's saved in the backend
    if (currentValue !== savedValue) {
        emit('save-answer', index, value);
    }
};

const resolvedSubmitLabel = computed(
    () =>
        props.submitLabel ||
        t('promptBuilder.components.bulkQuestions.submitAll'),
);

const resolvedBackLabel = computed(
    () =>
        props.backLabel ||
        t('promptBuilder.components.bulkQuestions.backToSingle'),
);
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
                    :label="
                        $t(
                            'promptBuilder.components.bulkQuestions.answerLabel',
                            {
                                number: index + 1,
                            },
                        )
                    "
                    :disabled="isSubmitting"
                    :placeholder="
                        $t(
                            'promptBuilder.components.bulkQuestions.answerPlaceholder',
                        )
                    "
                    @update:model-value="
                        (value: string) => updateAnswer(index, value)
                    "
                    @blur="handleBlur(index, answers[index] ?? '')"
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
                {{ resolvedBackLabel }}
            </ButtonSecondary>
            <ButtonPrimary
                type="button"
                :disabled="isSubmitting"
                :loading="isSubmitting"
                class="w-full sm:w-auto"
                @click="emit('submit-all')"
            >
                {{ resolvedSubmitLabel }}
            </ButtonPrimary>
        </div>
    </div>
</template>
