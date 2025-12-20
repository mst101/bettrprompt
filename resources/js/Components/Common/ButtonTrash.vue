<script setup lang="ts">
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';

interface Props {
    disabled?: boolean;
    label?: string;
}

withDefaults(defineProps<Props>(), {
    disabled: false,
    label: 'Clear',
});

const emit = defineEmits<{
    clear: [];
}>();
</script>

<template>
    <ButtonSecondary
        type="button"
        size="sm"
        :title="`${label} text`"
        :disabled="disabled"
        @click="emit('clear')"
    >
        <DynamicIcon name="trash" class="h-5 w-5 text-indigo-800" />
        <span>{{ label }}</span>
    </ButtonSecondary>
</template>
