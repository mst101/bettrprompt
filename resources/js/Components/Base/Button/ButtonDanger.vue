<script setup lang="ts">
import Button from '@/Components/Base/Button/Button.vue';

type IconPosition = 'left' | 'right';

interface Props {
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    icon?: string;
    iconPosition?: IconPosition;
}

withDefaults(defineProps<Props>(), {
    disabled: false,
    type: 'button',
    icon: '',
    iconPosition: 'left',
});
</script>

<template>
    <Button
        variant="danger"
        :disabled="disabled"
        :type="type"
        :icon="icon"
        :icon-position="iconPosition"
    >
        <slot />
    </Button>
</template>
