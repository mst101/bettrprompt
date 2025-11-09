<script setup lang="ts">
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

const updateValue = (event: Event) => {
    const target = event.target as HTMLInputElement | HTMLTextAreaElement;
    emit('update:modelValue', target.value);
};
</script>

<template>
    <div>
        <InputLabel :for="id" :value="label" />

        <!-- Textarea variant -->
        <textarea
            v-if="type === 'textarea'"
            :id="id"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            :autofocus="autofocus"
            :autocomplete="autocomplete"
            :rows="rows"
            :disabled="disabled"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :class="inputClass"
            @input="updateValue"
        />

        <!-- Text input variant -->
        <TextInput
            v-else
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
            class="mt-1 block w-full"
            :class="inputClass"
            @update:model-value="(value) => emit('update:modelValue', value)"
        />

        <InputError v-if="error" class="mt-2" :message="error" />
    </div>
</template>
