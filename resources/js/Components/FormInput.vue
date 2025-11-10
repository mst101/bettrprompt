<script setup lang="ts">
import FormFieldWrapper from '@/Components/FormFieldWrapper.vue';
import type { Nullable } from '@/types';
import { onMounted, ref } from 'vue';

interface Props {
    id: string;
    modelValue?: string | number | null;
    label: string;
    type?: string;
    error?: string;
    required?: boolean;
    placeholder?: string;
    disabled?: boolean;
    autofocus?: boolean;
    autocomplete?: string;
    customClass?: string;
    helpText?: string;
    min?: number | string;
    max?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    type: 'text',
    error: '',
    required: false,
    placeholder: '',
    disabled: false,
    autofocus: false,
    autocomplete: '',
    customClass: '',
    helpText: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
    (e: 'change', event: Event): void;
    (e: 'blur', event: FocusEvent): void;
    (e: 'focus', event: FocusEvent): void;
}>();

const input = ref<Nullable<HTMLInputElement>>(null);

onMounted(() => {
    if (props.autofocus && input.value) {
        input.value.focus();
    }
});

function updateValue(event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    if (props.type === 'number') {
        emit('update:modelValue', value === '' ? null : Number(value));
    } else {
        emit('update:modelValue', value);
    }
    emit('change', event);
}
</script>

<template>
    <FormFieldWrapper
        :id="id"
        :label="label"
        :error="error"
        :required="required"
        :help-text="helpText"
    >
        <input
            :id="id"
            ref="input"
            :type="type"
            :value="modelValue ?? ''"
            :placeholder="placeholder"
            :min="min"
            :max="max"
            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-black shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            :class="[customClass, { 'bg-gray-50': disabled }]"
            :required="required"
            :disabled="disabled"
            :autocomplete="autocomplete"
            @input="updateValue"
            @blur="$emit('blur', $event)"
            @focus="$emit('focus', $event)"
        />

        <template #help>
            <slot name="help" />
        </template>
    </FormFieldWrapper>
</template>
