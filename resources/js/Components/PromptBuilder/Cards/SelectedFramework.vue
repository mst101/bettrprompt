<script setup lang="ts">
import Card from '@/Components/Card.vue';

interface Props {
    framework: {
        name: string;
        code: string;
        components: string[];
        rationale: string;
    };
}

defineProps<Props>();
</script>

<template>
    <Card class="space-y-4">
        <div>
            <h2 class="mb-4 text-lg font-semibold text-gray-900">
                Selected Framework
            </h2>
            <div class="rounded-lg bg-gray-50 p-3">
                <span class="text-gray-900">
                    {{ framework.name }}
                </span>
                <span class="ml-2 text-sm text-gray-600">
                    ({{ framework.code }})
                </span>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-700">Components</h3>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="component in framework.components"
                    :key="component"
                    class="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800"
                >
                    {{ component }}
                </span>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-700">Rationale</h3>
            <div class="rounded-lg bg-gray-50 p-3">
                <p class="text-gray-900">
                    {{ framework.rationale }}
                </p>
            </div>
        </div>
    </Card>
</template>
