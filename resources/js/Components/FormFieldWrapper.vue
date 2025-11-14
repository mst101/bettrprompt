<script setup lang="ts">
interface Props {
    id: string;
    label: string;
    error?: string;
    required?: boolean;
    helpText?: string;
}

const props = withDefaults(defineProps<Props>(), {
    error: '',
    required: false,
    helpText: '',
});
</script>

<template>
    <div>
        <label :for="props.id" class="block text-sm font-medium">
            {{ props.label }}
            <span v-if="props.required" class="text-red-500">*</span>
        </label>

        <slot />

        <p v-if="props.helpText" class="mt-1 text-xs text-gray-500">
            {{ props.helpText }}
        </p>

        <slot name="help" />

        <p v-if="props.error" class="mt-1 text-sm text-red-600">
            {{ props.error }}
        </p>
    </div>
</template>
