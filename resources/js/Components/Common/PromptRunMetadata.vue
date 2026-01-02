<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import { useWorkflowStageColor } from '@/Composables/features/useWorkflowStageColor';

interface Props {
    workflowStage: string;
    user: {
        name: string;
        email: string;
    } | null;
    personalityType: string | null;
    createdAt: string;
}

defineProps<Props>();

const { getWorkflowStageColor } = useWorkflowStageColor();
</script>

<template>
    <Card>
        <div class="flex flex-wrap items-center gap-4 sm:gap-6">
            <!-- Workflow Stage -->
            <div
                class="flex items-center gap-2 border-r border-indigo-200 pr-4 last:border-r-0 sm:pr-6"
            >
                <span class="text-sm font-medium text-indigo-700">
                    Workflow:
                </span>
                <span
                    :class="[
                        'inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                        getWorkflowStageColor(workflowStage),
                    ]"
                >
                    {{ workflowStage }}
                </span>
            </div>

            <!-- User -->
            <div
                v-if="user"
                class="flex items-center gap-2 border-r border-indigo-200 pr-4 last:border-r-0 sm:pr-6"
            >
                <span class="text-sm font-medium text-indigo-700">User:</span>
                <span class="text-sm text-indigo-900">
                    {{ user.name }}
                    <span class="text-indigo-500">({{ user.email }})</span>
                </span>
            </div>

            <!-- Personality Type -->
            <div
                v-if="personalityType"
                class="flex items-center gap-2 border-r border-indigo-200 pr-4 last:border-r-0 sm:pr-6"
            >
                <span class="text-sm font-medium text-indigo-700">Type:</span>
                <span
                    class="inline-flex rounded-full bg-purple-100 px-3 py-1 text-sm font-semibold text-purple-800"
                >
                    {{ personalityType }}
                </span>
            </div>

            <!-- Created Date -->
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-indigo-700">
                    Created:
                </span>
                <span class="text-sm text-indigo-500">
                    {{ new Date(createdAt).toLocaleString() }}
                </span>
            </div>
        </div>
    </Card>
</template>
