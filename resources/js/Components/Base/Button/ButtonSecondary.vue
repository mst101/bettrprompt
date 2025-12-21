<script setup lang="ts">
import Button from '@/Components/Base/Button/Button.vue';
import { ref } from 'vue';

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

const buttonRef = ref<InstanceType<typeof Button> | null>(null);

const focus = () => {
    buttonRef.value?.focus();
};

defineExpose({ focus });
</script>

<template>
    <Button
        ref="buttonRef"
        variant="secondary"
        :disabled="disabled"
        :type="type"
        :icon="icon"
        :icon-position="iconPosition"
    >
        <slot />
    </Button>
</template>
