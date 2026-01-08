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
                        {{ $t('components.common.visitorLimitModal.title') }}
                    </h3>
                    <p class="mt-2 text-indigo-700">
                        {{
                            $t(
                                'components.common.visitorLimitModal.description',
                            )
                        }}
                    </p>
                    <div class="mt-6 flex gap-3">
                        <ButtonPrimary @click="emit('register')">
                            {{ $t('components.common.visitorLimitModal.cta') }}
                        </ButtonPrimary>
                        <ButtonSecondary type="button" @click="emit('close')">
                            {{ $t('common.buttons.cancel') }}
                        </ButtonSecondary>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
