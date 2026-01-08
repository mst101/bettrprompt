<script setup lang="ts">
import {
    useStatusBadge,
    type StatusType,
} from '@/Composables/ui/useStatusBadge';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    workflowStage: StatusType;
}

const props = defineProps<Props>();
const { t } = useI18n({ useScope: 'global' });

const { getStatusConfig } = useStatusBadge();
const config = computed(() => getStatusConfig(props.workflowStage));

// Translate status labels based on workflow stage
const translatedLabel = computed(() => {
    const labelMap: Record<string, string> = {
        '2_completed': 'status.completed',
        '0_completed': 'status.awaitingQuestions',
        '1_completed': 'status.awaitingAnswers',
        '0_processing': 'status.processing',
        '1_processing': 'status.processing',
        '2_processing': 'status.processing',
        '0_failed': 'status.failed',
        '1_failed': 'status.failed',
        '2_failed': 'status.failed',
    };
    const key = labelMap[props.workflowStage as string];
    return key ? t(key) : config.value.label;
});
</script>

<template>
    <span
        data-testid="status-badge"
        :data-test-workflow-stage="workflowStage"
        class="inline-flex w-40 items-center justify-center rounded-full px-2 py-1 text-xs font-semibold tracking-wide uppercase"
        :class="config.colorClass"
    >
        {{ translatedLabel }}
    </span>
</template>
