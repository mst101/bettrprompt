<script setup lang="ts">
interface Props {
    show: boolean;
    message: string;
    type: 'success' | 'error';
}

defineProps<Props>();
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed right-4 bottom-4 z-40 max-w-sm rounded-lg border p-4 shadow-lg"
            :class="{
                'border-green-200 bg-green-50 text-green-700':
                    type === 'success',
                'border-red-200 bg-red-50 text-red-700': type === 'error',
            }"
        >
            {{ message }}
        </div>
    </Teleport>
</template>
