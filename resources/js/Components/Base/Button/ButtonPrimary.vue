<script setup lang="ts">
import Button from '@/Components/Base/Button/Button.vue';
import { ref } from 'vue';

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
    >
        <slot />
    </Button>
</template>
