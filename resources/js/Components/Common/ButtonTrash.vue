<script setup lang="ts">
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';

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
        size="md"
        :title="`${label} text`"
        :disabled="disabled"
        icon="trash"
        @click="emit('clear')"
    >
        {{ label }}
    </ButtonSecondary>
</template>
