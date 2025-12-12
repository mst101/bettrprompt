<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';

interface Props {
    errorMessage: string;
}

defineProps<Props>();

const emit = defineEmits<{
    retry: [];
}>();
</script>

<template>
    <div class="rounded-lg border border-red-300 bg-red-50 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    <DynamicIcon
                        name="exclamation-circle"
                        class="size-5 text-red-600"
                    />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-900">Workflow Failed</h3>
                    <p class="mt-1 text-sm text-red-700">
                        {{ errorMessage }}
                    </p>
                </div>
            </div>
            <button
                class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none sm:self-start"
                @click="emit('retry')"
            >
                <DynamicIcon name="arrow-path" class="size-4" />
                Retry Workflow
            </button>
        </div>
    </div>
</template>
