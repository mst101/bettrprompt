<script setup lang="ts">
import InputLabel from '@/Components/InputLabel.vue';

defineProps<{
    id: string;
    label: string;
    modelValue: number | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
}>();

const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value === '' ? null : Number(target.value);
    emit('update:modelValue', value);
};
</script>

<template>
    <div>
        <InputLabel :for="id" :value="label" />
        <input
            type="number"
            :id="id"
            :value="modelValue ?? ''"
            @input="handleInput"
            min="0"
            max="100"
            placeholder="%"
            class="mt-1 block w-full rounded-md border-gray-300 text-right shadow-xs placeholder:text-right focus:border-indigo-500 focus:ring-indigo-500"
        />
    </div>
</template>
