<script setup lang="ts">
import Card from '@/Components/Card.vue';

interface Framework {
    name: string;
    code: string;
    when_to_use_instead: string;
}

interface Props {
    frameworks: Framework[];
}

defineProps<Props>();
</script>

<template>
    <Card v-if="frameworks.length > 0" class="space-y-3">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">
            Alternative Frameworks
        </h2>
        <div
            v-for="framework in frameworks"
            :key="framework.code"
            class="rounded-lg border border-gray-200 bg-gray-50 p-4"
        >
            <div class="mb-2 flex items-start justify-between">
                <div>
                    <h3 class="font-medium text-gray-900">
                        {{ framework.name }}
                    </h3>
                    <span class="text-sm text-gray-600">
                        {{ framework.code }}
                    </span>
                </div>
            </div>
            <div class="mt-2">
                <p class="text-sm text-gray-700">
                    <span class="font-medium">When to use:</span>
                    {{ framework.when_to_use_instead }}
                </p>
            </div>
        </div>
    </Card>
    <Card v-else>
        <div class="text-center text-gray-500">
            No alternative frameworks suggested
        </div>
    </Card>
</template>
