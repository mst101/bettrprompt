<script setup lang="ts">
import Button from '@/Components/Base/Button/Button.vue';

interface Props {
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    icon?: string;
}

withDefaults(defineProps<Props>(), {
    disabled: false,
    type: 'button',
    icon: '',
});
</script>

<template>
    <Button variant="danger" :disabled="disabled" :type="type" :icon="icon">
        <slot />
    </Button>
</template>
