<script setup lang="ts">
import type { FormTextareaProps } from '@/types';
import { computed } from 'vue';

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

defineOptions({
    inheritAttrs: false,
});

const textareaClasses = computed(() => {
    if (props.textareaClass) {
        return props.textareaClass;
    }
    return [
        'mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
        { 'cursor-not-allowed bg-gray-50 text-gray-500': props.disabled },
    ];
});
</script>

<template>
    <div v-bind="$attrs">
        <div class="mb-2 flex items-center justify-between">
            <label
                :for="props.id"
                class="block text-sm font-medium text-gray-700"
            >
                {{ props.label }}
                <span v-if="props.required" class="text-red-500">*</span>
            </label>
            <div class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <textarea
            :id="props.id"
            :value="props.modelValue"
            :rows="props.rows"
            :placeholder="props.placeholder"
            :required="props.required"
            :disabled="props.disabled"
            :maxlength="props.maxlength"
            :autofocus="props.autofocus"
            :class="textareaClasses"
            @input="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLTextAreaElement).value,
                )
            "
        />

        <p v-if="props.helpText" class="mt-1 text-xs text-gray-500">
            {{ props.helpText }}
        </p>

        <p v-if="props.error" class="mt-1 text-sm text-red-600">
            {{ props.error }}
        </p>
    </div>
</template>
