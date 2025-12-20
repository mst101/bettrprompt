<script setup lang="ts">
import type { FormInputProps, Nullable } from '@/Types';
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
    <div>
        <label :for="id" class="block font-medium text-indigo-900">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

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
            v-bind="$attrs"
            class="mt-1 block w-full rounded-md border-indigo-100 bg-indigo-50 text-indigo-900 placeholder-indigo-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-indigo-100"
            :class="{ 'bg-indigo-100': disabled }"
            @input="updateValue"
            @blur="$emit('blur', $event)"
            @focus="$emit('focus', $event)"
        />

        <p v-if="helpText" class="mt-1 text-indigo-600">
            {{ helpText }}
        </p>

        <slot name="help" />

        <p v-if="error" class="mt-1 text-red-600">
            {{ error }}
        </p>
    </div>
</template>

<style scoped>
/* Override browser autofill styles to maintain indigo background and text */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    transition:
        background-color 5000s ease-in-out 0s,
        color 5000s ease-in-out 0s;
    -webkit-text-fill-color: rgb(67, 56, 202) !important; /* indigo-900 */
}

input:-webkit-autofill {
    -webkit-box-shadow: 0 0 0 1000px rgb(238, 235, 254) inset !important; /* indigo-50 */
}
</style>
