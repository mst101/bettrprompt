<script setup lang="ts">
import LoadingSpinner from '@/Components/Base/LoadingSpinner.vue';
import { CheckIcon } from '@heroicons/vue/20/solid';

defineProps<{
    label: string;
    status: 'pending' | 'active' | 'complete';
}>();
</script>

<template>
    <div class="flex items-center gap-3">
        <div
            class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full"
            :class="{
                'bg-indigo-200': status === 'pending',
                'bg-indigo-600': status === 'active',
                'bg-green-500': status === 'complete',
            }"
        >
            <CheckIcon
                v-if="status === 'complete'"
                class="h-4 w-4 text-white"
            />
            <LoadingSpinner
                v-else-if="status === 'active'"
                class="h-3 w-3 text-white"
            />
            <div v-else class="h-2 w-2 rounded-full bg-indigo-500" />
        </div>
        <span
            class="text-sm"
            :class="{
                'text-indigo-500': status === 'pending',
                'font-medium text-indigo-900': status === 'active',
                'text-green-700': status === 'complete',
            }"
        >
            {{ label }}
        </span>
    </div>
</template>
