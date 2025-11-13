<script setup lang="ts">
import FormFieldWrapper from '@/Components/FormFieldWrapper.vue';
import type { FormInputProps, Nullable } from '@/types';
import { onMounted, ref } from 'vue';

const props = withDefaults(defineProps<FormInputProps>(), {
    modelValue: '',
    type: 'text',
    error: '',
    required: false,
    placeholder: '',
    disabled: false,
    autofocus: false,
    autocomplete: '',
    helpText: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
    (e: 'change', event: Event): void;
    (e: 'blur', event: FocusEvent): void;
    (e: 'focus', event: FocusEvent): void;
}>();

defineOptions({
    inheritAttrs: false,
});

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
        v-bind="$attrs"
    >
        <input
            :id="id"
            ref="input"
            :type="type"
            :value="modelValue ?? ''"
            :placeholder="placeholder"
            :min="min"
            :max="max"
            :required="required"
            :disabled="disabled"
            :autocomplete="autocomplete"
            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-black shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            :class="{ 'bg-gray-50': disabled }"
            @input="updateValue"
            @blur="$emit('blur', $event)"
            @focus="$emit('focus', $event)"
        />

        <template #help>
            <slot name="help" />
        </template>
    </FormFieldWrapper>
</template>

<style scoped>
/* Override browser autofill styles to maintain white background and black text */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    transition:
        background-color 5000s ease-in-out 0s,
        color 5000s ease-in-out 0s;
}
</style>
