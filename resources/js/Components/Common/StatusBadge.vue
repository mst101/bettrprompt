<script setup lang="ts">
import {
    useStatusBadge,
    type StatusType,
} from '@/Composables/ui/useStatusBadge';
import { computed } from 'vue';

interface Props {
    workflowStage: StatusType;
}

const props = defineProps<Props>();

const { getStatusConfig } = useStatusBadge();
const config = computed(() => getStatusConfig(props.workflowStage));
</script>

<template>
    <span
        data-testid="status-badge"
        :data-test-workflow-stage="workflowStage"
        class="inline-flex w-40 items-center justify-center rounded-full px-2 py-1 text-xs font-semibold tracking-wide uppercase sm:text-sm"
        :class="config.colorClass"
    >
        {{ config.label }}
    </span>
</template>
