<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    id: string;
    modelValue?: boolean | unknown[];
    value?: string | number | boolean;
    label?: string;
    disabled?: boolean;
    error?: string;
    helpText?: string;
    name?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    value: null,
    label: '',
    disabled: false,
    error: '',
    helpText: '',
    name: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean | unknown[]): void;
}>();

const isChecked = computed({
    get() {
        if (Array.isArray(props.modelValue)) {
            return props.modelValue.includes(props.value);
        }
        return props.modelValue;
    },
    set(checked) {
        if (Array.isArray(props.modelValue)) {
            const updatedValue = [...props.modelValue];
            if (checked) {
                updatedValue.push(props.value);
            } else {
                const index = updatedValue.indexOf(props.value);
                if (index !== -1) {
                    updatedValue.splice(index, 1);
                }
            }
            emit('update:modelValue', updatedValue);
        } else {
            emit('update:modelValue', checked);
        }
    },
});
</script>

<template>
    <div>
        <div class="flex items-center">
            <input
                :id="id"
                v-model="isChecked"
                type="checkbox"
                :name="name"
                class="size-4 rounded-md border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                :disabled="disabled"
            />
            <label
                v-if="label"
                :for="id"
                class="ml-2 block px-4 py-1 text-indigo-900 hover:text-indigo-700"
            >
                {{ label }}
            </label>
            <slot v-else />
        </div>

        <p v-if="helpText" class="mt-1 text-indigo-600">
            {{ helpText }}
        </p>

        <div v-if="error" class="mt-1 text-red-600">
            {{ error }}
        </div>
    </div>
</template>
