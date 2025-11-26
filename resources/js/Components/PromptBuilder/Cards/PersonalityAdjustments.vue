<script setup lang="ts">
interface Props {
    tier: 'full' | 'partial' | 'none';
    adjustments: string[];
}

defineProps<Props>();
</script>

<template>
    <div class="space-y-4">
        <div>
            <h3 class="text-grey-700 mb-2 text-sm font-medium">
                Adjustment Level
            </h3>
            <div class="bg-grey-50 rounded-lg p-3">
                <span class="text-grey-900 capitalize">{{ tier }}</span>
            </div>
        </div>

        <div v-if="adjustments.length > 0">
            <h3 class="text-grey-700 mb-2 text-sm font-medium">
                Adjustments Applied
            </h3>
            <ul class="space-y-2">
                <li
                    v-for="(adjustment, index) in adjustments"
                    :key="index"
                    class="bg-grey-50 flex items-start rounded-lg p-3"
                >
                    <svg
                        class="mt-0.5 mr-2 h-5 w-5 flex-shrink-0 text-blue-600"
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
                    <span class="text-grey-900">{{ adjustment }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
