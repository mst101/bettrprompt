<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: number;
    min?: number;
    max?: number;
    leftLabel: string;
    rightLabel: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    min: 1,
    max: 7,
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void;
}>();

const options = computed(() => {
    const result = [];
    for (let i = props.min; i <= props.max; i++) {
        result.push(i);
    }
    return result;
});

const getCircleSize = (index: number) => {
    // Sizes from smallest to largest
    const sizes = [
        'h-12 w-12', // 1
        'h-10 w-10', // 2
        'h-8 w-8', // 3
        'h-6 w-6', // 4
        'h-8 w-8', // 5
        'h-10 w-10', // 6
        'h-12 w-12', // 7
    ];
    return sizes[index] || 'h-14 w-14';
};

const getCircleColor = (index: number, isSelected: boolean) => {
    // Color gradient from teal/green to purple
    const colors = [
        { border: 'border-teal-600', bg: 'bg-teal-600' }, // 1
        { border: 'border-teal-500', bg: 'bg-teal-500' }, // 2
        { border: 'border-teal-400', bg: 'bg-teal-400' }, // 3
        { border: 'border-gray-400', bg: 'bg-gray-400' }, // 4
        { border: 'border-purple-400', bg: 'bg-purple-400' }, // 5
        { border: 'border-purple-500', bg: 'bg-purple-500' }, // 6
        { border: 'border-purple-600', bg: 'bg-purple-600' }, // 7
    ];

    const color = colors[index] || colors[3];
    return isSelected ? color.bg : color.border;
};

const selectOption = (value: number) => {
    if (!props.disabled) {
        emit('update:modelValue', value);
    }
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Circles -->
        <div class="flex items-center justify-center gap-2 sm:gap-4">
            <button
                v-for="(value, index) in options"
                :key="value"
                type="button"
                @click="selectOption(value)"
                :disabled="disabled"
                :class="[
                    getCircleSize(index),
                    'rounded-full border-2 transition-all duration-200',
                    getCircleColor(index, modelValue === value),
                    disabled
                        ? 'cursor-not-allowed opacity-50'
                        : 'cursor-pointer hover:scale-110',
                    modelValue !== value && 'bg-white',
                ]"
                :aria-label="`Select option ${value}`"
                :aria-pressed="modelValue === value"
            ></button>
        </div>

        <!-- Labels -->
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-teal-600">{{
                leftLabel
            }}</span>
            <span class="text-sm font-medium text-purple-600">{{
                rightLabel
            }}</span>
        </div>
    </div>
</template>
