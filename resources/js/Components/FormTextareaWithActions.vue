<script setup lang="ts">
import FormTextarea from '@/Components/FormTextarea.vue';
import type { FormTextareaProps } from '@/types';

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
</script>

<template>
    <div v-bind="$attrs" class="relative">
        <div class="mb-2 flex items-center justify-between">
            <label
                v-if="props.label"
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

        <FormTextarea
            :id="props.id"
            :model-value="props.modelValue"
            :rows="props.rows"
            :placeholder="props.placeholder"
            :disabled="props.disabled"
            :maxlength="props.maxlength"
            :autofocus="props.autofocus"
            :error="props.error"
            :help-text="props.helpText"
            :textarea-class="props.textareaClass"
            label=""
            @update:model-value="emit('update:modelValue', $event)"
        />
    </div>
</template>
