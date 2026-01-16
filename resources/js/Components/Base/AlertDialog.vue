<script setup lang="ts">
import ButtonDanger from '@/Components/Base/Button/ButtonDanger.vue';
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import Modal from '@/Components/Base/Modal/Modal.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { alertState, closeAlert } = useAlert();
const { t } = useI18n({ useScope: 'global' });

// Translate alert title based on type
const translatedTitle = computed(() => {
    const titleMap: Record<string, string> = {
        success: 'alert.successTitle',
        warning: 'alert.warningTitle',
        error: 'alert.errorTitle',
        confirm: 'alert.confirmTitle',
        info: 'alert.infoTitle',
    };
    const key = titleMap[alertState.type];
    return key ? t(key) : alertState.title;
});

// Translate cancel button text (only for confirm dialogs)
const translatedCancelText = computed(() => {
    if (alertState.cancelText === 'Cancel') {
        return t('common.buttons.cancel');
    }
    return alertState.cancelText;
});

// Translate confirm button text
const translatedConfirmText = computed(() => {
    // Check if it's the default text
    if (alertState.confirmText === 'OK') {
        return t('common.buttons.ok');
    }
    if (alertState.confirmText === 'Confirm') {
        return t('alert.confirmButton');
    }
    // Custom text - keep as-is
    return alertState.confirmText;
});

const iconConfig = computed(() => {
    switch (alertState.type) {
        case 'success':
            return {
                name: 'check-circle',
                bgColor: 'bg-green-100',
                iconColor: 'text-green-600',
            };
        case 'warning':
            return {
                name: 'exclamation-triangle',
                bgColor: 'bg-yellow-100',
                iconColor: 'text-yellow-600',
            };
        case 'error':
            return {
                name: 'x-circle',
                bgColor: 'bg-red-100',
                iconColor: 'text-red-600',
            };
        case 'confirm':
            return {
                name: 'question-mark-circle',
                bgColor: 'bg-indigo-100',
                iconColor: 'text-indigo-600',
            };
        default:
            return {
                name: 'information-circle',
                bgColor: 'bg-blue-100',
                iconColor: 'text-blue-600',
            };
    }
});

const handleConfirm = () => {
    closeAlert(true);
};

const handleCancel = () => {
    closeAlert(false);
};

const handleClose = () => {
    // For non-confirm dialogs, closing is the same as confirming
    closeAlert(alertState.type !== 'confirm');
};
</script>

<template>
    <Modal
        :show="alertState.show"
        max-width="md"
        :closeable="true"
        @close="handleClose"
    >
        <div class="relative p-6">
            <!-- Icon -->
            <div class="mx-auto flex items-center justify-center">
                <div
                    :class="[
                        'rounded-full p-3',
                        iconConfig.bgColor,
                        iconConfig.iconColor,
                    ]"
                >
                    <DynamicIcon :name="iconConfig.name" class="h-8 w-8" />
                </div>
            </div>

            <!-- Title -->
            <h3
                class="mt-4 text-center text-base font-semibold text-indigo-900 sm:text-lg"
                data-testid="alert-title"
            >
                {{ translatedTitle }}
            </h3>

            <!-- Message -->
            <p
                class="mt-3 text-center text-sm text-indigo-600"
                data-testid="alert-message"
            >
                {{ alertState.message }}
            </p>

            <!-- Actions -->
            <div
                class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center"
            >
                <!-- Cancel button (only for confirm type) -->
                <ButtonSecondary
                    v-if="alertState.type === 'confirm'"
                    type="button"
                    class="w-full sm:w-auto"
                    data-testid="alert-cancel-button"
                    @click="handleCancel"
                >
                    {{ translatedCancelText }}
                </ButtonSecondary>

                <!-- Confirm/OK button -->
                <ButtonPrimary
                    v-if="alertState.confirmButtonStyle === 'primary'"
                    type="button"
                    class="w-full sm:w-auto"
                    data-testid="alert-confirm-button"
                    @click="handleConfirm"
                >
                    {{ translatedConfirmText }}
                </ButtonPrimary>

                <ButtonDanger
                    v-else
                    type="button"
                    class="w-full sm:w-auto"
                    data-testid="alert-confirm-button"
                    @click="handleConfirm"
                >
                    {{ translatedConfirmText }}
                </ButtonDanger>
            </div>
        </div>
    </Modal>
</template>
