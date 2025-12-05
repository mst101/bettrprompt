<script setup lang="ts">
import FormTextarea from '@/Components/FormTextarea.vue';
import type { FormTextareaProps } from '@/types';
import { ref } from 'vue';

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
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

defineOptions({
    inheritAttrs: false,
});

const formTextarea = ref<InstanceType<typeof FormTextarea> | null>(null);

const focus = () => {
    formTextarea.value?.focus();
};

defineExpose({ focus });
</script>

<template>
    <div v-bind="$attrs" class="relative">
        <div class="mb-2 flex flex-col justify-between">
            <label
                v-if="props.label"
                :for="props.id"
                class="block text-sm font-medium text-gray-700"
            >
                <span class="whitespace-nowrap"
                    >{{ props.label }}
                    <span v-if="props.required" class="text-red-500"
                        >*</span
                    ></span
                >
            </label>
            <div class="mt-2 flex items-center justify-end">
                <slot name="actions" />
            </div>
        </div>

        <FormTextarea
            :id="props.id"
            ref="formTextarea"
            :model-value="props.modelValue"
            :rows="props.rows"
            :placeholder="props.placeholder"
            :disabled="props.disabled"
            :maxlength="props.maxlength"
            :autofocus="props.autofocus"
            :error="props.error"
            :help-text="props.helpText"
            :is-submitting="props.isSubmitting"
            :textarea-class="props.textareaClass"
            label=""
            @update:model-value="emit('update:modelValue', $event)"
        />
    </div>
</template>
