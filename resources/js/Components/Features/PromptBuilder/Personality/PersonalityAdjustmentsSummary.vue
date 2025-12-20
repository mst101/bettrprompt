<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';

interface Props {
    adjustments: string[];
}

defineProps<Props>();
</script>

<template>
    <Card v-if="adjustments && adjustments.length > 0" class="space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">
            Personality Adjustments Applied
        </h2>

        <ul class="space-y-2">
            <li
                v-for="(adjustment, index) in adjustments"
                :key="index"
                class="flex items-start rounded-lg bg-gray-50 p-3"
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
                <span class="text-gray-900">{{ adjustment }}</span>
            </li>
        </ul>
    </Card>
</template>
