<script setup lang="ts">
import Button from '@/Components/Button.vue';

interface Props {
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    loading?: boolean;
}

withDefaults(defineProps<Props>(), {
    disabled: false,
    type: 'button',
    loading: false,
});
</script>

<template>
    <Button
        class="text-xs tracking-wider uppercase"
        variant="primary"
        :disabled="disabled"
        :type="type"
        :loading="loading"
    >
        <slot />
    </Button>
</template>
