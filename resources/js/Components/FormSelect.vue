<script setup lang="ts">
import FormFieldWrapper from '@/Components/FormFieldWrapper.vue';
import type { Nullable } from '@/types';
import { onMounted, ref } from 'vue';

interface SelectOption {
    value: string | number;
    label: string;
}

interface Props {
    id: string;
    label: string;
    modelValue?: string | number;
    options: SelectOption[];
    error?: string;
    required?: boolean;
    disabled?: boolean;
    placeholder?: string;
    showPlaceholder?: boolean;
    customClass?: string;
    helpText?: string;
    autofocus?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    error: '',
    required: false,
    disabled: false,
    placeholder: 'Please select...',
    showPlaceholder: true,
    customClass: '',
    helpText: '',
    autofocus: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
}>();

const select = ref<Nullable<HTMLSelectElement>>(null);

onMounted(() => {
    if (props.autofocus && select.value) {
        select.value.focus();
    }
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
        <select
            ref="select"
            :id="props.id"
            :value="props.modelValue"
            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-black shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            :class="[
                props.customClass,
                { 'cursor-not-allowed bg-gray-50': props.disabled },
            ]"
            :required="props.required"
            :disabled="props.disabled"
            @change="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLSelectElement).value,
                )
            "
        >
            <option v-if="props.showPlaceholder" value="" disabled>
                {{ props.placeholder }}
            </option>
            <option
                v-for="option in props.options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
    </FormFieldWrapper>
</template>
