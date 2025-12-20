<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';

interface Props {
    tier: 'full' | 'partial' | 'none';
    adjustments: string[];
}

defineProps<Props>();
</script>

<template>
    <Card class="space-y-4">
        <h2 class="text-lg font-semibold text-indigo-900">
            Personality Adjustments
        </h2>

        <div>
            <h3 class="mb-2 text-sm font-medium text-indigo-700">
                Adjustment Level
            </h3>
            <div class="rounded-lg bg-indigo-50 p-3">
                <span class="text-indigo-900 capitalize">{{ tier }}</span>
            </div>
        </div>

        <div v-if="adjustments.length > 0">
            <h3 class="mb-2 text-sm font-medium text-indigo-700">
                Adjustments Applied
            </h3>
            <ul class="space-y-2">
                <li
                    v-for="(adjustment, index) in adjustments"
                    :key="index"
                    class="flex items-start rounded-lg bg-indigo-50 p-3"
                >
                    <svg
                        class="mt-0.5 mr-2 h-5 w-5 shrink-0 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span class="text-indigo-900">{{ adjustment }}</span>
                </li>
            </ul>
        </div>
    </Card>
</template>
