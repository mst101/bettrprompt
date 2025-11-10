<script setup lang="ts">
import FormTextarea from '@/Components/FormTextarea.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

interface Props {
    id: string;
    label: string;
    type?: string;
    modelValue?: string | number;
    error?: string;
    placeholder?: string;
    required?: boolean;
    autofocus?: boolean;
    autocomplete?: string;
    rows?: number;
    min?: number | string;
    max?: number | string;
    disabled?: boolean;
    inputClass?: string;
}

withDefaults(defineProps<Props>(), {
    type: 'text',
    required: false,
    autofocus: false,
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
}>();
</script>

<template>
    <!-- Textarea variant -->
    <FormTextarea
        v-if="type === 'textarea'"
        :id="id"
        :label="label"
        :model-value="(modelValue ?? '') as string"
        :placeholder="placeholder"
        :required="required"
        :autofocus="autofocus"
        :rows="rows"
        :disabled="disabled"
        :error="error"
        :custom-class="inputClass"
        @update:model-value="(value) => emit('update:modelValue', value)"
    />

    <!-- Text input variant -->
    <div v-else>
        <InputLabel :for="id" :value="label" />

        <TextInput
            :id="id"
            :type="type"
            :model-value="(modelValue ?? '') as string"
            :placeholder="placeholder"
            :required="required"
            :autofocus="autofocus"
            :autocomplete="autocomplete"
            :min="min"
            :max="max"
            :disabled="disabled"
            class="mt-1 block w-full bg-white text-black"
            :class="inputClass"
            @update:model-value="(value) => emit('update:modelValue', value)"
        />

        <InputError v-if="error" class="mt-2" :message="error" />
    </div>
</template>
