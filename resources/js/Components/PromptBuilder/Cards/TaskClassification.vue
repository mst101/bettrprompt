<script setup lang="ts">
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
    <div class="space-y-4">
        <div>
            <h3 class="text-grey-700 mb-2 text-sm font-medium">Category</h3>
            <div class="bg-grey-50 rounded-lg p-3">
                <span class="text-grey-900">
                    {{ classification.primary_category }}
                </span>
                <span
                    v-if="classification.secondary_category"
                    class="text-grey-600 ml-2"
                >
                    / {{ classification.secondary_category }}
                </span>
            </div>
        </div>

        <div>
            <h3 class="text-grey-700 mb-2 text-sm font-medium">Complexity</h3>
            <div class="bg-grey-50 rounded-lg p-3">
                <span class="text-grey-900 capitalize">
                    {{ classification.complexity }}
                </span>
            </div>
        </div>

        <div>
            <h3 class="text-grey-700 mb-2 text-sm font-medium">Reasoning</h3>
            <div class="bg-grey-50 rounded-lg p-3">
                <p class="text-grey-900">
                    {{ classification.classification_reasoning }}
                </p>
            </div>
        </div>
    </div>
</template>
