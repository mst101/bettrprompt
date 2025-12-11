<script setup lang="ts">
interface Props {
    show: boolean;
    title: string;
}

defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="bg-opacity-50 fixed inset-0 z-50 flex h-screen items-center justify-center bg-white p-4 text-black"
            @click="emit('close')"
        >
            <div
                class="flex h-screen w-full max-w-screen flex-col rounded-lg shadow-lg"
                @click.stop
            >
                <div
                    class="flex items-center justify-between border-b bg-indigo-600 px-6 py-4 text-white"
                >
                    <h2 class="text-lg font-semibold">{{ title }}</h2>
                    <button
                        class="rounded hover:bg-indigo-700"
                        title="Close"
                        @click="emit('close')"
                    >
                        ✕
                    </button>
                </div>
                <slot />
            </div>
        </div>
    </Teleport>
</template>
