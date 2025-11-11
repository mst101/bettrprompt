<script setup lang="ts">
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { getWorkflowStageLabel } from '@/constants/workflow';
import type { PromptRunResource } from '@/types';

interface Props {
    promptRun: PromptRunResource;
    personalityTypeLabel: string;
    showEditButton?: boolean;
}

withDefaults(defineProps<Props>(), {
    showEditButton: false,
});

const emit = defineEmits<{
    (e: 'edit'): void;
}>();
</script>

<template>
    <Card>
        <div class="flex justify-between">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Your Task</h3>

            <!-- Status Badges -->
            <div class="mb-4 flex items-center gap-2">
                <StatusBadge :status="promptRun.status" />
                <span
                    v-if="promptRun.status !== 'completed'"
                    class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800"
                >
                    {{ getWorkflowStageLabel(promptRun.workflowStage) }}
                </span>
            </div>
        </div>

        <div class="space-y-3">
            <div>
                <span class="text-sm font-medium text-gray-700"
                    >Personality Type:</span
                >
                <span class="ml-2 text-sm text-gray-900">{{
                    personalityTypeLabel
                }}</span>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700"
                        >Task Description:</span
                    >
                    <ButtonSecondary
                        v-if="showEditButton"
                        type="button"
                        @click="emit('edit')"
                        class="inline-flex items-center gap-1"
                    >
                        <DynamicIcon name="edit" class="h-4 w-4" />
                        Edit
                    </ButtonSecondary>
                </div>
                <p class="mt-1 ml-2 text-sm whitespace-pre-wrap text-gray-900">
                    {{ promptRun.taskDescription }}
                </p>
            </div>
        </div>
    </Card>
</template>
