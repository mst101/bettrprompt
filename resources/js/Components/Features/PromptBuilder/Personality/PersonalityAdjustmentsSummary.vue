<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';

interface Props {
    adjustments: string[];
}

defineProps<Props>();
</script>

<template>
    <Card v-if="adjustments && adjustments.length > 0" class="space-y-4">
        <h2 class="text-lg font-semibold text-indigo-900">
            Personality Adjustments Applied
        </h2>

        <ul class="space-y-2">
            <li
                v-for="(adjustment, index) in adjustments"
                :key="index"
                class="flex items-start rounded-lg bg-indigo-50 p-3"
            >
                <DynamicIcon
                    name="check-circle"
                    class="mt-0.5 mr-2 h-5 w-5 shrink-0 text-blue-600"
                />
                <span class="text-indigo-900">{{ adjustment }}</span>
            </li>
        </ul>
    </Card>
</template>
