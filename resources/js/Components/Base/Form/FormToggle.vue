<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean;
    label?: string;
    enabledText?: string;
    disabledText?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const toggle = () => {
    emit('update:modelValue', !props.modelValue);
};
</script>

<template>
    <div
        class="flex items-center gap-3 rounded-md border border-indigo-200 bg-indigo-50 p-3 focus:ring-offset-indigo-100"
    >
        <span v-if="label" class="text-sm font-medium text-indigo-700">
            {{ label }}
        </span>
        <button
            type="button"
            :class="[
                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 focus:outline-hidden',
                modelValue ? 'bg-indigo-600' : 'bg-indigo-300',
            ]"
            role="switch"
            :aria-checked="modelValue"
            :aria-label="label"
            @click="toggle"
        >
            <span
                :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out',
                    modelValue ? 'translate-x-5' : 'translate-x-0',
                ]"
            />
        </button>
        <span
            v-if="enabledText || disabledText"
            class="text-sm text-indigo-600"
        >
            {{ modelValue ? enabledText : disabledText }}
        </span>
    </div>
</template>
