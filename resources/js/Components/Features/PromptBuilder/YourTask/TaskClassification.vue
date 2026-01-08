<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';

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
        <h2 class="text-lg font-semibold text-indigo-900">
            {{ $t('promptBuilder.components.taskClassification.title') }}
        </h2>

        <div>
            <h3 class="mb-2 text-sm font-medium text-indigo-700">
                {{ $t('promptBuilder.components.taskClassification.category') }}
            </h3>
            <div class="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-100">
                <span class="text-indigo-900">
                    {{ classification.primary_category }}
                </span>
                <span
                    v-if="classification.secondary_category"
                    class="ml-2 text-indigo-600"
                >
                    / {{ classification.secondary_category }}
                </span>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-medium text-indigo-700">
                {{
                    $t('promptBuilder.components.taskClassification.complexity')
                }}
            </h3>
            <div class="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-100">
                <span class="text-indigo-900 capitalize">
                    {{ classification.complexity }}
                </span>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-medium text-indigo-700">
                {{
                    $t('promptBuilder.components.taskClassification.reasoning')
                }}
            </h3>
            <div class="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-100">
                <p class="text-indigo-900">
                    {{ classification.classification_reasoning }}
                </p>
            </div>
        </div>
    </Card>
</template>
