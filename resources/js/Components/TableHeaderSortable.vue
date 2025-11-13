<script setup lang="ts">
import SvgChevronDown from '@/Components/Svg/SvgChevronDown.vue';
import SvgChevronUp from '@/Components/Svg/SvgChevronUp.vue';

interface Props {
    /** The column identifier to sort by */
    column: string;
    /** The currently sorted column */
    currentSort?: string;
    /** The current sort direction */
    sortDirection?: string;
}

const props = withDefaults(defineProps<Props>(), {
    currentSort: '',
    sortDirection: 'asc',
});

const emit = defineEmits<{
    (e: 'sort', column: string): void;
}>();

const isSorted = (): boolean => {
    return props.currentSort === props.column;
};
</script>

<template>
    <button
        @click="emit('sort', column)"
        class="group inline-flex items-center gap-1 rounded p-1 hover:text-gray-700 focus:text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100 focus:outline-hidden"
    >
        <slot />
        <span class="flex flex-col">
            <SvgChevronUp
                class="h-3 w-3 transition-colors"
                :class="{
                    'text-indigo-600': isSorted() && sortDirection === 'asc',
                    'text-gray-400': !isSorted() || sortDirection !== 'asc',
                }"
            />
            <SvgChevronDown
                class="-mt-1 h-3 w-3 transition-colors"
                :class="{
                    'text-indigo-600': isSorted() && sortDirection === 'desc',
                    'text-gray-400': !isSorted() || sortDirection !== 'desc',
                }"
            />
        </span>
    </button>
</template>
