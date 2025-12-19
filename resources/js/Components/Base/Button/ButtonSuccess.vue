<script setup lang="ts">
import Button from '@/Components/Base/Button/Button.vue';
import { ref } from 'vue';

interface Props {
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
}

withDefaults(defineProps<Props>(), {
    disabled: false,
    type: 'button',
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
        class="text-xs tracking-wider uppercase"
        variant="success"
        :disabled="disabled"
        :type="type"
    >
        <slot />
    </Button>
</template>
