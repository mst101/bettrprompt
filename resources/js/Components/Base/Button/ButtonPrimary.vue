<script setup lang="ts">
import Button from '@/Components/Base/Button/Button.vue';
import { ref } from 'vue';

type IconPosition = 'left' | 'right';

interface Props {
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    loading?: boolean;
    icon?: string;
    iconPosition?: IconPosition;
}

withDefaults(defineProps<Props>(), {
    disabled: false,
    type: 'button',
    loading: false,
    icon: '',
    iconPosition: 'left',
});

defineOptions({
    inheritAttrs: false,
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
        variant="primary"
        :disabled="disabled"
        :type="type"
        :loading="loading"
        :icon="icon"
        :icon-position="iconPosition"
        v-bind="$attrs"
    >
        <slot />
    </Button>
</template>
