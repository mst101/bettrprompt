<script setup lang="ts">
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

const currentVariantDescription = computed(() => {
    return (
        props.availableVariants.find((v) => v.key === selectedVariant.value)
            ?.description || ''
    );
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
    <div class="items-centre flex gap-3">
        <label
            for="variant-selector"
            class="text-sm font-medium text-slate-700"
        >
            Variant:
        </label>
        <select
            id="variant-selector"
            v-model="selectedVariant"
            class="rounded-md border border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="handleVariantChange"
        >
            <option
                v-for="variant in availableVariants"
                :key="variant.key"
                :value="variant.key"
            >
                {{ variant.name }}
            </option>
        </select>
        <span v-if="currentVariantDescription" class="text-xs text-slate-500">
            {{ currentVariantDescription }}
        </span>
    </div>
</template>
