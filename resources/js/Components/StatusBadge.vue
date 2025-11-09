<script setup lang="ts">
import { useStatusBadge, type StatusType } from '@/Composables/useStatusBadge';
import { computed } from 'vue';

interface Props {
    status: StatusType;
}

const props = defineProps<Props>();

const { getStatusConfig } = useStatusBadge();
const config = computed(() => getStatusConfig(props.status));
</script>

<template>
    <span
        data-testid="status-badge"
        :data-test-status="status"
        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold uppercase"
        :class="config.colorClass"
    >
        {{ config.label }}
    </span>
</template>
