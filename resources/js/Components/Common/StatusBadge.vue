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

// Translate status label from the labelKey
const translatedLabel = computed(() => t(config.value.labelKey));
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
