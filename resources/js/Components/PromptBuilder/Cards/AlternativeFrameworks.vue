<script setup lang="ts">
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
    <div v-if="frameworks.length > 0" class="space-y-3">
        <div
            v-for="framework in frameworks"
            :key="framework.code"
            class="border-grey-200 bg-grey-50 rounded-lg border p-4"
        >
            <div class="mb-2 flex items-start justify-between">
                <div>
                    <h3 class="text-grey-900 font-medium">
                        {{ framework.name }}
                    </h3>
                    <span class="text-grey-600 text-sm">
                        {{ framework.code }}
                    </span>
                </div>
            </div>
            <div class="mt-2">
                <p class="text-grey-700 text-sm">
                    <span class="font-medium">When to use:</span>
                    {{ framework.when_to_use_instead }}
                </p>
            </div>
        </div>
    </div>
    <div v-else class="text-grey-500 text-center">
        No alternative frameworks suggested
    </div>
</template>
