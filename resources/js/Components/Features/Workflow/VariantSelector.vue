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

const getCsrfToken = () => {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    if (!token) {
        // Fallback: try to extract from cookies
        const name = 'XSRF-TOKEN';
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop()?.split(';').shift();
    }
    return token;
};

const handleVariantChange = async () => {
    try {
        const csrfToken = getCsrfToken();
        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
        };
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await fetch(
            `/debug/workflow/${props.workflowNumber}/variant`,
            {
                method: 'POST',
                headers,
                body: JSON.stringify({ variant: selectedVariant.value }),
            },
        );

        if (!response.ok) {
            console.error(
                'Failed to switch variant:',
                response.status,
                response.statusText,
            );
            selectedVariant.value = props.currentVariant;
            return;
        }

        const result = await response.json();
        if (!result.success) {
            console.error('Failed to switch variant:', result.error);
            selectedVariant.value = props.currentVariant;
            return;
        }

        // Reload page to fetch new variant data
        window.location.reload();
    } catch (err) {
        console.error('Failed to switch variant:', err);
        selectedVariant.value = props.currentVariant;
    }
};
</script>

<template>
    <div class="flex items-center gap-3">
        <FormSelect
            id="variant-selector"
            v-model="selectedVariant"
            label="Variant:"
            label-sr-only
            :options="variantOptions"
            :show-placeholder="false"
            @update:model-value="handleVariantChange"
        />
    </div>
</template>
