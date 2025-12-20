<script setup lang="ts">
interface Props {
    id: string;
    modelValue?: boolean | unknown[];
    value?: string | number | boolean | null;
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

function isCheckedValue(): boolean {
    if (Array.isArray(props.modelValue)) {
        return props.modelValue.includes(props.value);
    }
    return props.modelValue;
}

function handleChange(event: Event): void {
    const checked = (event.target as HTMLInputElement).checked;
    if (Array.isArray(props.modelValue)) {
        const updatedValue = checked
            ? [...new Set([...props.modelValue, props.value])]
            : props.modelValue.filter((v) => v !== props.value);
        emit('update:modelValue', updatedValue);
    } else {
        emit('update:modelValue', checked);
    }
}
</script>

<template>
    <div>
        <div class="flex items-center">
            <input
                :id="id"
                :checked="isCheckedValue()"
                type="checkbox"
                :name="name"
                class="size-4 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"
                :disabled="disabled"
                @change="handleChange"
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
