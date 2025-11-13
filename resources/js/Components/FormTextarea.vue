<script setup lang="ts">
import FormFieldWrapper from '@/Components/FormFieldWrapper.vue';
import type { FormTextareaProps } from '@/types';
import { computed } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(defineProps<FormTextareaProps>(), {
    modelValue: '',
    rows: 3,
    error: '',
    required: false,
    placeholder: '',
    disabled: false,
    helpText: '',
    autofocus: false,
    textareaClass: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const textareaClasses = computed(() => {
    if (props.textareaClass) {
        return props.textareaClass;
    }
    return [
        'mt-2 block w-full text-black rounded-md border-indigo-300 bg-indigo-50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
        { 'cursor-not-allowed opacity-50': props.disabled },
    ];
});
</script>

<template>
    <FormFieldWrapper
        :id="props.id"
        :label="props.label"
        :error="props.error"
        :required="props.required"
        :help-text="props.helpText"
    >
        <textarea
            :id="props.id"
            :value="props.modelValue"
            :rows="props.rows"
            :placeholder="props.placeholder"
            :required="props.required"
            :disabled="props.disabled"
            :maxlength="props.maxlength"
            :autofocus="props.autofocus"
            v-bind="$attrs"
            :class="textareaClasses"
            @input="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLTextAreaElement).value,
                )
            "
        />
    </FormFieldWrapper>
</template>
