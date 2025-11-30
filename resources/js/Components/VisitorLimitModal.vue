<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import Modal from '@/Components/Modal.vue';

interface Props {
    show: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'register'): void;
}>();
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-start gap-3">
                <DynamicIcon
                    name="information-circle"
                    class="mt-0.5 h-6 w-6 shrink-0 text-indigo-600"
                />
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-indigo-900">
                        You've reached your visitor limit
                    </h3>
                    <p class="mt-2 text-gray-700">
                        You've already created an optimised prompt as a visitor.
                        To create more prompts and iterate on existing ones,
                        you'll need to create a free account.
                    </p>
                    <div class="mt-6 flex gap-3">
                        <ButtonPrimary @click="emit('register')">
                            Create Free Account
                        </ButtonPrimary>
                        <button
                            type="button"
                            class="rounded-md px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                            @click="emit('close')"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
