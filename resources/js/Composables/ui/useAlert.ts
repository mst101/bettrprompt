import { reactive, readonly } from 'vue';
import { useI18n } from 'vue-i18n';

export type AlertType = 'success' | 'warning' | 'error' | 'info' | 'confirm';

export interface AlertOptions {
    type?: AlertType;
    title?: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    confirmButtonStyle?: 'primary' | 'danger';
}

interface AlertState {
    show: boolean;
    type: AlertType;
    title: string;
    message: string;
    confirmText: string;
    cancelText: string;
    confirmButtonStyle: 'primary' | 'danger';
    resolveCallback: ((value: boolean) => void) | null;
}

const state = reactive<AlertState>({
    show: false,
    type: 'info',
    title: '',
    message: '',
    confirmText: 'OK',
    cancelText: 'Cancel',
    confirmButtonStyle: 'primary',
    resolveCallback: null,
});

const { t } = useI18n();

export function useAlert() {
    const showAlert = (options: AlertOptions): Promise<boolean> => {
        return new Promise((resolve) => {
            state.show = true;
            state.type = options.type || 'info';
            state.title =
                options.title || getDefaultTitle(options.type || 'info');
            state.message = options.message;
            state.confirmText = options.confirmText || t('common.buttons.ok');
            state.cancelText = options.cancelText || t('common.buttons.cancel');
            state.confirmButtonStyle = options.confirmButtonStyle || 'primary';
            state.resolveCallback = resolve;
        });
    };

    const confirm = (
        message: string,
        title?: string,
        options?: Partial<AlertOptions>,
    ): Promise<boolean> => {
        return showAlert({
            type: 'confirm',
            title: title || t('alert.confirmTitle'),
            message,
            confirmText: options?.confirmText || t('common.buttons.confirm'),
            cancelText: options?.cancelText || t('common.buttons.cancel'),
            confirmButtonStyle: options?.confirmButtonStyle || 'primary',
        });
    };

    const alert = (
        message: string,
        title?: string,
        type: AlertType = 'info',
    ): Promise<boolean> => {
        return showAlert({
            type,
            title: title || getDefaultTitle(type),
            message,
            confirmText: t('common.buttons.ok'),
        });
    };

    const success = (message: string, title?: string): Promise<boolean> => {
        return alert(message, title || t('alert.successTitle'), 'success');
    };

    const warning = (message: string, title?: string): Promise<boolean> => {
        return alert(message, title || t('alert.warningTitle'), 'warning');
    };

    const error = (message: string, title?: string): Promise<boolean> => {
        return alert(message, title || t('alert.errorTitle'), 'error');
    };

    const closeAlert = (confirmed: boolean) => {
        if (state.resolveCallback) {
            state.resolveCallback(confirmed);
            state.resolveCallback = null;
        }
        state.show = false;
    };

    return {
        // State (readonly to prevent external mutation)
        alertState: readonly(state),

        // Methods
        showAlert,
        confirm,
        alert,
        success,
        warning,
        error,
        closeAlert,
    };
}

function getDefaultTitle(type: AlertType): string {
    switch (type) {
        case 'success':
            return t('alert.successTitle');
        case 'warning':
            return t('alert.warningTitle');
        case 'error':
            return t('alert.errorTitle');
        case 'confirm':
            return t('alert.confirmTitle');
        default:
            return t('alert.infoTitle');
    }
}
