<script setup lang="ts">
import { useStatusBadge, type StatusType } from '@/Composables/useStatusBadge';

interface Props {
    status: StatusType;
    class?: string;
}

const props = defineProps<Props>();

const { getStatusConfig } = useStatusBadge();
const config = getStatusConfig(props.status);
</script>

<template>
    <span
        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold uppercase"
        :class="[config.colorClass, class]"
    >
        {{ config.label }}
    </span>
</template>
