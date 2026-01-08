<script setup lang="ts">
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    disabled?: boolean;
    label?: string;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    label: '',
});

const emit = defineEmits<{
    clear: [];
}>();
const { t } = useI18n({ useScope: 'global' });
const resolvedLabel = computed(
    () => props.label || t('components.common.buttonTrash.label'),
);
</script>

<template>
    <ButtonSecondary
        type="button"
        size="md"
        :title="
            t('components.common.buttonTrash.title', { label: resolvedLabel })
        "
        :disabled="disabled"
        icon="trash"
        @click="emit('clear')"
    >
        {{ resolvedLabel }}
    </ButtonSecondary>
</template>
