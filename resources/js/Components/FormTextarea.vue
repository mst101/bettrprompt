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
        'mt-2 block w-full text-black rounded-md border-indigo-300 bg-indigo-50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
        { 'cursor-not-allowed opacity-50': props.disabled },
    ];
});

const textarea = ref<HTMLTextAreaElement | null>(null);

const focus = () => {
    textarea.value?.focus();
};

defineExpose({ focus });
</script>

<template>
    <div>
        <label
            v-if="props.label"
            :for="props.id"
            class="block text-sm font-medium text-black"
        >
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
            v-bind="$attrs"
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
