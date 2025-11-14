<script setup lang="ts">
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import EditTaskForm from '@/Components/PromptOptimizer/EditTaskForm.vue';
import type { PromptRunResource } from '@/types';

interface Props {
    promptRun: PromptRunResource;
    showEditButton?: boolean;
    isEditing?: boolean;
}

withDefaults(defineProps<Props>(), {
    showEditButton: false,
    isEditing: false,
});

const emit = defineEmits<{
    (e: 'edit'): void;
    (e: 'cancel'): void;
}>();
</script>

<template>
    <!-- View Mode -->
    <div v-if="!isEditing" class="space-y-4">
        <div class="flex items-center justify-end">
            <ButtonSecondary
                v-if="showEditButton"
                type="button"
                class="inline-flex items-center gap-1"
                @click="emit('edit')"
            >
                <DynamicIcon name="edit" class="h-4 w-4" />
                Edit Task
            </ButtonSecondary>
        </div>

        <textarea
            :value="promptRun.taskDescription"
            disabled
            class="block w-full rounded-md border-gray-300 bg-gray-50 text-sm text-gray-900 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-100"
            rows="8"
        />
    </div>

    <!-- Edit Mode -->
    <div
        v-else
        class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50"
    >
        <div class="p-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">
                Edit Task & Create New Optimisation
            </h3>
            <EditTaskForm
                :prompt-run-id="promptRun.id"
                :initial-task-description="promptRun.taskDescription"
                @cancel="emit('cancel')"
            />
        </div>
    </div>
</template>
