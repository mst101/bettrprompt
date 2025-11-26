<script setup lang="ts">
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
    <div class="space-y-4">
        <div>
            <h3 class="text-grey-700 mb-2 text-sm font-medium">Framework</h3>
            <div class="bg-grey-50 rounded-lg p-3">
                <span class="text-grey-900">
                    {{ framework.name }}
                </span>
                <span class="text-grey-600 ml-2 text-sm">
                    ({{ framework.code }})
                </span>
            </div>
        </div>

        <div>
            <h3 class="text-grey-700 mb-2 text-sm font-medium">Components</h3>
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
            <h3 class="text-grey-700 mb-2 text-sm font-medium">Rationale</h3>
            <div class="bg-grey-50 rounded-lg p-3">
                <p class="text-grey-900">
                    {{ framework.rationale }}
                </p>
            </div>
        </div>
    </div>
</template>
