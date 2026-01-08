<script setup lang="ts">
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import { computed, ref } from 'vue';

interface Variant {
    key: string;
    name: string;
    description?: string;
}

interface Props {
    workflowNumber: number;
    currentVariant: string;
    availableVariants: Variant[];
}

const props = defineProps<Props>();

const selectedVariant = ref(props.currentVariant);

const variantOptions = computed(() => {
    return props.availableVariants.map((v) => ({
        value: v.key,
        label: v.name,
    }));
});

const handleVariantChange = () => {
    const url = new URL(window.location.href);
    url.searchParams.set('variant', selectedVariant.value);
    // Reset pass to 0 when variant changes
    url.searchParams.delete('pass');
    window.location.href = url.toString();
};
</script>

<template>
    <div class="flex items-center gap-3">
        <FormSelect
            id="variant-selector"
            v-model="selectedVariant"
            :label="$t('workflow.variantSelector.label')"
            label-sr-only
            :options="variantOptions"
            :show-placeholder="false"
            @update:model-value="handleVariantChange"
        />
    </div>
</template>
