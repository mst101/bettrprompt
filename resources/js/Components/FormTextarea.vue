<script setup lang="ts">
import type { FormTextareaProps } from '@/types';
import { computed, ref } from 'vue';

const props = withDefaults(defineProps<FormTextareaProps>(), {
    modelValue: '',
    rows: 3,
    error: '',
    required: false,
    placeholder: '',
    disabled: false,
    helpText: '',
    autofocus: false,
    isSubmitting: false,
    textareaClass: '',
    srOnlyLabel: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

defineOptions({
    inheritAttrs: false,
});

const textareaClasses = computed(() => {
    if (props.textareaClass) {
        return props.textareaClass;
    }
    return [
        'mt-2 sm:text-lg block w-full text-indigo-900 rounded-md border-indigo-100 bg-indigo-50 dark:bg-indigo-100 inset-4 inset-shadow focus:ring-2 focus:ring-indigo-500',
        {
            'cursor-not-allowed': props.disabled || props.isSubmitting,
        },
        {
            'border-red-300 focus:border-red-500 focus:ring-red-500':
                !!props.error,
        },
    ];
});

const textarea = ref<HTMLTextAreaElement | null>(null);

const focus = (options?: { cursorPosition?: 'start' | 'end' }) => {
    textarea.value?.focus();

    if (options?.cursorPosition === 'start' && textarea.value) {
        textarea.value.setSelectionRange(0, 0);
        textarea.value.scrollTop = 0;
    } else if (options?.cursorPosition === 'end' && textarea.value) {
        const length = textarea.value.value.length;
        textarea.value.setSelectionRange(length, length);
    }
};

defineExpose({ focus });

const labelClass = computed(() =>
    props.srOnlyLabel ? 'sr-only' : 'block text-sm font-medium text-black',
);
</script>

<template>
    <div>
        <label v-if="props.label" :for="props.id" :class="labelClass">
            {{ props.label }}
            <span v-if="props.required" class="text-red-500">*</span>
        </label>

        <textarea
            :id="props.id"
            ref="textarea"
            :value="props.modelValue"
            :rows="props.rows"
            :placeholder="props.placeholder"
            :required="props.required"
            :disabled="props.disabled"
            :maxlength="props.maxlength"
            :autofocus="props.autofocus"
            :aria-label="props.srOnlyLabel ? props.label : undefined"
            v-bind="$attrs"
            class="placeholder-indigo-700"
            :class="textareaClasses"
            @input="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLTextAreaElement).value,
                )
            "
        />

        <p v-if="props.helpText" class="mt-1 text-xs text-indigo-600">
            {{ props.helpText }}
        </p>

        <p v-if="props.error" class="mt-1 text-sm text-red-600">
            {{ props.error }}
        </p>
    </div>
</template>
