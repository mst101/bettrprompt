<script setup lang="ts">
interface Props {
    value?: string;
    required?: boolean;
}

withDefaults(defineProps<Props>(), {
    value: '',
    required: false,
});
</script>

<template>
    <label class="block text-sm font-medium text-gray-700">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
        <span v-if="required" class="ml-1 text-red-500">*</span>
    </label>
</template>
