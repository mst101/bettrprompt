<script setup lang="ts">
import ButtonDanger from '@/Components/Base/Button/ButtonDanger.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';

interface Props {
    errorMessage: string;
}

defineProps<Props>();

const emit = defineEmits<{
    retry: [];
}>();
</script>

<template>
    <div class="rounded-lg border border-red-300 bg-red-50 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    <DynamicIcon
                        name="exclamation-circle"
                        class="size-5 text-red-600"
                    />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-900">
                        {{ $t('components.common.workflowError.title') }}
                    </h3>
                    <p class="mt-1 text-sm text-red-700">
                        {{ errorMessage }}
                    </p>
                </div>
            </div>
            <ButtonDanger
                icon="arrow-path"
                class="shrink-0 sm:self-start"
                @click="emit('retry')"
            >
                {{ $t('components.common.workflowError.retry') }}
            </ButtonDanger>
        </div>
    </div>
</template>
