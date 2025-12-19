<script setup lang="ts">
interface Props {
    id: string;
    modelValue?: string | number | boolean;
    value: string | number | boolean;
    name: string;
    label?: string;
    disabled?: boolean;
    error?: string;
    helpText?: string;
    required?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: undefined,
    label: '',
    disabled: false,
    error: '',
    helpText: '',
    required: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | boolean): void;
}>();

function updateValue(): void {
    emit('update:modelValue', props.value);
}
</script>

<template>
    <div>
        <label class="flex items-center">
            <input
                :id="id"
                :checked="modelValue === value"
                type="radio"
                :name="name"
                :value="value"
                :required="required"
                :disabled="disabled"
                class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                @change="updateValue"
            />
            <span
                v-if="label"
                class="ml-2 text-sm text-gray-700"
                :class="{ 'text-gray-400': disabled }"
            >
                {{ label }}
            </span>
            <slot v-else />
        </label>

        <p v-if="helpText" class="mt-1 ml-6 text-xs text-gray-500">
            {{ helpText }}
        </p>

        <div v-if="error" class="mt-1 ml-6 text-sm text-red-600">
            {{ error }}
        </div>
    </div>
</template>
