<script setup lang="ts">
import { computed } from 'vue';
import { useStatusBadge, type StatusType } from '@/Composables/useStatusBadge';

interface Props {
    status: StatusType;
}

const props = defineProps<Props>();

const { getStatusConfig } = useStatusBadge();
const config = computed(() => getStatusConfig(props.status));
</script>

<template>
    <span
        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold uppercase"
        :class="config.colorClass"
    >
        {{ config.label }}
    </span>
</template>
