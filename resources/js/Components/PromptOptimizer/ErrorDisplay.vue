<script setup lang="ts">
import Card from '@/Components/Card.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { N8nErrorResponse } from '@/types';
import { router } from '@inertiajs/vue3';

interface Props {
    promptRunId: number;
    errorMessage?: string;
    errorResponse?: N8nErrorResponse | null;
}

const props = defineProps<Props>();

const retry = () => {
    router.post(route('prompt-optimizer.retry', props.promptRunId));
};
</script>

<template>
    <Card>
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <div class="rounded-lg bg-red-100 p-2 text-red-600">
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Something Went Wrong
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{
                            errorMessage ||
                            'An error occurred whilst processing your prompt.'
                        }}
                    </p>
                </div>
            </div>

            <!-- Detailed error (if available) -->
            <div
                v-if="errorResponse?.details"
                class="rounded-lg bg-red-50 p-4"
            >
                <p class="text-sm font-medium text-red-900">Error Details:</p>
                <p class="mt-1 text-sm text-red-700">
                    {{ errorResponse.details }}
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <SecondaryButton @click="retry"> Try Again </SecondaryButton>
                <a
                    :href="route('prompt-optimizer.index')"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Start New Request
                </a>
            </div>
        </div>
    </Card>
</template>
