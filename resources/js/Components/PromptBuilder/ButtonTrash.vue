<script setup lang="ts">
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';

interface Props {
    disabled?: boolean;
}

withDefaults(defineProps<Props>(), {
    disabled: false,
});

const emit = defineEmits<{
    clear: [];
}>();
</script>

<template>
    <ButtonSecondary
        type="button"
        size="sm"
        title="Clear text"
        :disabled="disabled"
        @click="emit('clear')"
    >
        <DynamicIcon name="trash" class="h-5 w-5 text-gray-600" />
        <span>Clear</span>
    </ButtonSecondary>
</template>
