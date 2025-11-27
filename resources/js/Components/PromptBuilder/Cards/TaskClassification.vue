<script setup lang="ts">
import Card from '@/Components/Card.vue';

interface Props {
    classification: {
        primary_category: string;
        secondary_category: string | null;
        complexity: string;
        classification_reasoning: string;
    };
}

defineProps<Props>();
</script>

<template>
    <Card class="space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">Classification</h2>

        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-700">Category</h3>
            <div class="rounded-lg bg-gray-50 p-3">
                <span class="text-gray-900">
                    {{ classification.primary_category }}
                </span>
                <span
                    v-if="classification.secondary_category"
                    class="ml-2 text-gray-600"
                >
                    / {{ classification.secondary_category }}
                </span>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-700">Complexity</h3>
            <div class="rounded-lg bg-gray-50 p-3">
                <span class="text-gray-900 capitalize">
                    {{ classification.complexity }}
                </span>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-700">Reasoning</h3>
            <div class="rounded-lg bg-gray-50 p-3">
                <p class="text-gray-900">
                    {{ classification.classification_reasoning }}
                </p>
            </div>
        </div>
    </Card>
</template>
