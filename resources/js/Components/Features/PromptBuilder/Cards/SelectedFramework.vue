<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import Card from '@/Components/Base/Card.vue';
import { nextTick, ref } from 'vue';

interface Props {
    framework: {
        name: string;
        code: string;
        components: string[];
        rationale: string;
    };
    showProceedButton?: boolean;
}

withDefaults(defineProps<Props>(), {
    showProceedButton: false,
});

const emit = defineEmits<{
    proceed: [];
}>();

const proceedButtonRef = ref<InstanceType<typeof ButtonPrimary> | null>(null);

// Expose focus method for parent components
const focus = async () => {
    await nextTick();
    proceedButtonRef.value?.focus();
};

defineExpose({ focus });
</script>

<template>
    <Card class="space-y-4">
        <div>
            <h2
                class="sr-only mb-4 text-lg font-semibold text-indigo-900 sm:not-sr-only"
            >
                Selected Framework
            </h2>
            <div
                class="rounded-lg bg-indigo-50 p-3 text-indigo-800 dark:bg-indigo-100"
            >
                {{ framework.name }}
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-medium text-indigo-800">Components</h3>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="component in framework.components"
                    :key="component"
                    class="rounded-lg bg-blue-200 px-3 py-1 text-sm text-blue-900 sm:rounded-full dark:bg-blue-300"
                >
                    {{ component }}
                </span>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-medium text-indigo-800">Rationale</h3>
            <div class="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-100">
                <p class="text-indigo-800">
                    {{ framework.rationale }}
                </p>
            </div>
        </div>

        <div v-if="showProceedButton" class="flex justify-end pt-2">
            <ButtonPrimary ref="proceedButtonRef" @click="emit('proceed')">
                Proceed to Questions
            </ButtonPrimary>
        </div>
    </Card>
</template>
