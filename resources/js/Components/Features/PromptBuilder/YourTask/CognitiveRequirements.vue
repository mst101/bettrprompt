<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';

interface Props {
    requirements: {
        primary: string[];
        secondary: string[];
        reasoning: string;
    };
}

defineProps<Props>();
</script>

<template>
    <Card class="space-y-4">
        <h2 class="text-lg font-semibold text-indigo-900">
            {{ $t('promptBuilder.components.cognitiveRequirements.title') }}
        </h2>

        <div>
            <h3 class="mb-2 text-sm font-medium text-indigo-700">
                {{
                    $t('promptBuilder.components.cognitiveRequirements.primary')
                }}
            </h3>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="req in requirements.primary"
                    :key="req"
                    class="rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800"
                >
                    {{ req }}
                </span>
            </div>
        </div>

        <div v-if="requirements.secondary && requirements.secondary.length > 0">
            <h3 class="mb-2 text-sm font-medium text-indigo-700">
                {{
                    $t(
                        'promptBuilder.components.cognitiveRequirements.secondary',
                    )
                }}
            </h3>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="req in requirements.secondary"
                    :key="req"
                    class="rounded-full bg-indigo-100 px-3 py-1 text-sm text-indigo-700"
                >
                    {{ req }}
                </span>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-medium text-indigo-700">
                {{
                    $t(
                        'promptBuilder.components.cognitiveRequirements.reasoning',
                    )
                }}
            </h3>
            <div class="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-100">
                <p class="text-indigo-900">
                    {{ requirements.reasoning }}
                </p>
            </div>
        </div>
    </Card>
</template>
