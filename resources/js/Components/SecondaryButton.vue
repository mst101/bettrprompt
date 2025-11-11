<script setup lang="ts">
import Button from '@/Components/Button.vue';

interface Props {
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
}

withDefaults(defineProps<Props>(), {
    disabled: false,
    type: 'button',
});
</script>

<template>
    <Button variant="secondary" :disabled="disabled" :type="type">
        <slot />
    </Button>
</template>
