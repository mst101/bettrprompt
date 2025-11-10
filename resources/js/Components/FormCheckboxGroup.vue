<script setup lang="ts">
import FormCheckbox from '@/Components/ui/FormCheckbox.vue';
import FormFieldWrapper from '@/Components/ui/FormFieldWrapper.vue';
import { computed } from 'vue';

interface Option {
    value: string;
    label: string;
}

interface Props {
    id: string;
    modelValue: string[];
    name: string;
    label?: string;
    options: Option[];
    columns?: number;
    error?: string;
    helpText?: string;
    required?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    label: '',
    columns: 3,
    error: '',
    helpText: '',
    required: false,
});

const emit = defineEmits(['update:modelValue']);

function updateValue(newValue: string[]) {
    emit('update:modelValue', newValue);
}

// Compute the grid class based on columns prop
const columnsClass = computed(() => {
    switch (props.columns) {
        case 1:
            return 'grid-cols-1';
        case 2:
            return 'grid-cols-1 sm:grid-cols-2';
        case 3:
            return 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3';
        case 4:
            return 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4';
        default:
            return 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3';
    }
});
</script>

<template>
    <FormFieldWrapper
        :id="id"
        :label="label"
        :error="error"
        :required="required"
        :help-text="helpText"
    >
        <div class="mt-2 grid gap-2" :class="columnsClass">
            <FormCheckbox
                v-for="option in options"
                :id="`${name}-${option.value}`"
                :key="option.value"
                :model-value="modelValue"
                :value="option.value"
                :label="option.label"
                @update:model-value="updateValue"
            />
        </div>
    </FormFieldWrapper>
</template>
