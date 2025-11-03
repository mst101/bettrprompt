<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

interface PromptRun {
    id: number;
    personality_type: string;
    trait_percentages: Record<string, number> | null;
    task_description: string;
    optimized_prompt: string | null;
    status: string;
    error_message: string | null;
    created_at: string;
    completed_at: string | null;
}

interface Props {
    promptRun: PromptRun;
}

const props = defineProps<Props>();

const copied = ref(false);

const copyToClipboard = async () => {
    if (!props.promptRun.optimized_prompt) return;

    try {
        await navigator.clipboard.writeText(props.promptRun.optimized_prompt);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'processing':
            return 'bg-yellow-100 text-yellow-800';
        case 'failed':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="Optimised Prompt" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-centre justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Optimised Prompt
                </h2>
                <a
                    :href="route('prompt-optimizer.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    Create New
                </a>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Status Badge -->
                <div class="mb-4">
                    <span
                        :class="getStatusBadgeClass(promptRun.status)"
                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase"
                    >
                        {{ promptRun.status }}
                    </span>
                </div>

                <!-- Input Information -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Input Information</h3>

                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Personality Type:</span>
                                <span class="ml-2 text-sm text-gray-900">{{ promptRun.personality_type }}</span>
                            </div>

                            <div v-if="promptRun.trait_percentages">
                                <span class="text-sm font-medium text-gray-700">Trait Percentages:</span>
                                <div class="ml-2 mt-1 text-sm text-gray-900">
                                    <span
                                        v-for="(value, key) in promptRun.trait_percentages"
                                        :key="key"
                                        class="mr-3"
                                    >
                                        {{ key }}: {{ value }}%
                                    </span>
                                </div>
                            </div>

                            <div>
                                <span class="text-sm font-medium text-gray-700">Task Description:</span>
                                <p class="ml-2 mt-1 whitespace-pre-wrap text-sm text-gray-900">
                                    {{ promptRun.task_description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Optimised Prompt Result -->
                <div v-if="promptRun.status === 'completed' && promptRun.optimized_prompt" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4 flex items-centre justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Optimised Prompt</h3>
                            <button
                                @click="copyToClipboard"
                                class="inline-flex items-centre rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                <svg
                                    v-if="!copied"
                                    class="mr-2 h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="mr-2 h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
                            </button>
                        </div>

                        <div class="rounded-md bg-gray-50 p-4">
                            <pre class="whitespace-pre-wrap font-mono text-sm text-gray-900">{{ promptRun.optimized_prompt }}</pre>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-else-if="promptRun.status === 'failed'" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-red-600">Error</h3>
                        <p class="text-sm text-gray-900">
                            {{ promptRun.error_message || 'An error occurred whilst processing your request.' }}
                        </p>
                        <div class="mt-4">
                            <a
                                :href="route('prompt-optimizer.index')"
                                class="text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                Try again
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Processing State -->
                <div v-else-if="promptRun.status === 'processing'" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-centre">
                            <svg class="mr-3 h-5 w-5 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-gray-700">Processing your request...</span>
                        </div>
                    </div>
                </div>

                <!-- Back to History -->
                <div class="mt-6 text-centre">
                    <a
                        :href="route('prompt-optimizer.history')"
                        class="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        View All History
                    </a>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
