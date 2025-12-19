<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import Modal from '@/Components/Base/Modal/Modal.vue';

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
                        <ButtonSecondary type="button" @click="emit('close')">
                            Cancel
                        </ButtonSecondary>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
