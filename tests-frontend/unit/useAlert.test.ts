import { useAlert } from '@/Composables/ui/useAlert';
import { describe, expect, it } from 'vitest';

describe('useAlert', () => {
    it('should initialise with alert hidden', () => {
        const { alertState } = useAlert();
        expect(alertState.show).toBe(false);
    });

    it('should show alert with custom message', async () => {
        const { showAlert, alertState } = useAlert();

        const promise = showAlert({ message: 'Test message' });

        expect(alertState.show).toBe(true);
        expect(alertState.message).toBe('Test message');
        expect(alertState.type).toBe('info');

        // Resolve the promise
        alertState.resolveCallback?.(true);
        const result = await promise;
        expect(result).toBe(true);
    });

    it('should show success alert', async () => {
        const { success, alertState } = useAlert();

        const promise = success('Operation successful');

        expect(alertState.show).toBe(true);
        expect(alertState.type).toBe('success');
        expect(alertState.message).toBe('Operation successful');
        expect(alertState.title).toBe('Success');

        alertState.resolveCallback?.(true);
        await promise;
    });

    it('should show warning alert', async () => {
        const { warning, alertState } = useAlert();

        const promise = warning('Warning message');

        expect(alertState.show).toBe(true);
        expect(alertState.type).toBe('warning');
        expect(alertState.message).toBe('Warning message');
        expect(alertState.title).toBe('Warning');

        alertState.resolveCallback?.(true);
        await promise;
    });

    it('should show error alert', async () => {
        const { error, alertState } = useAlert();

        const promise = error('Something went wrong');

        expect(alertState.show).toBe(true);
        expect(alertState.type).toBe('error');
        expect(alertState.message).toBe('Something went wrong');
        expect(alertState.title).toBe('Error');

        alertState.resolveCallback?.(true);
        await promise;
    });

    it('should show confirmation dialog', async () => {
        const { confirm, alertState } = useAlert();

        const promise = confirm('Are you sure?');

        expect(alertState.show).toBe(true);
        expect(alertState.type).toBe('confirm');
        expect(alertState.message).toBe('Are you sure?');
        expect(alertState.title).toBe('Confirm');
        expect(alertState.confirmText).toBe('Confirm');
        expect(alertState.cancelText).toBe('Cancel');

        alertState.resolveCallback?.(true);
        const result = await promise;
        expect(result).toBe(true);
    });

    it('should handle confirmation rejection', async () => {
        const { confirm, alertState } = useAlert();

        const promise = confirm('Proceed?');

        alertState.resolveCallback?.(false);
        const result = await promise;

        expect(result).toBe(false);
    });

    it('should close alert after confirmation', () => {
        const { showAlert, closeAlert, alertState } = useAlert();

        showAlert({ message: 'Test' });
        expect(alertState.show).toBe(true);

        closeAlert(true);
        expect(alertState.show).toBe(false);
        expect(alertState.resolveCallback).toBeNull();
    });

    it('should use custom title', async () => {
        const { alert, alertState } = useAlert();

        const promise = alert('Custom message', 'Custom Title', 'success');

        expect(alertState.title).toBe('Custom Title');

        alertState.resolveCallback?.(true);
        await promise;
    });

    it('should use custom button text in confirm', async () => {
        const { confirm, alertState } = useAlert();

        const promise = confirm('Delete this?', 'Delete', {
            confirmText: 'Delete Permanently',
            cancelText: 'Keep',
        });

        expect(alertState.confirmText).toBe('Delete Permanently');
        expect(alertState.cancelText).toBe('Keep');

        alertState.resolveCallback?.(true);
        await promise;
    });

    it('should use danger style for confirm button', async () => {
        const { confirm, alertState } = useAlert();

        const promise = confirm('Delete this?', 'Delete', {
            confirmButtonStyle: 'danger',
        });

        expect(alertState.confirmButtonStyle).toBe('danger');

        alertState.resolveCallback?.(true);
        await promise;
    });

    it('should get default titles for all alert types', async () => {
        const { alert, alertState } = useAlert();

        // Test info (default)
        let promise = alert('Message', undefined, 'info');
        expect(alertState.title).toBe('Information');
        alertState.resolveCallback?.(true);
        await promise;

        // Test success
        promise = alert('Message', undefined, 'success');
        expect(alertState.title).toBe('Success');
        alertState.resolveCallback?.(true);
        await promise;

        // Test warning
        promise = alert('Message', undefined, 'warning');
        expect(alertState.title).toBe('Warning');
        alertState.resolveCallback?.(true);
        await promise;

        // Test error
        promise = alert('Message', undefined, 'error');
        expect(alertState.title).toBe('Error');
        alertState.resolveCallback?.(true);
        await promise;

        // Test confirm (covers default title for confirm type)
        promise = alert('Message', undefined, 'confirm');
        expect(alertState.title).toBe('Confirm');
        alertState.resolveCallback?.(true);
        await promise;
    });

    it('should set default confirm and cancel text', async () => {
        const { showAlert, alertState } = useAlert();

        const promise = showAlert({ message: 'Test' });

        expect(alertState.confirmText).toBe('OK');
        expect(alertState.cancelText).toBe('Cancel');

        alertState.resolveCallback?.(true);
        await promise;
    });

    it('should allow custom confirm and cancel text in alert', async () => {
        const { showAlert, alertState } = useAlert();

        const promise = showAlert({
            message: 'Test',
            confirmText: 'Yes',
            cancelText: 'No',
        });

        expect(alertState.confirmText).toBe('Yes');
        expect(alertState.cancelText).toBe('No');

        alertState.resolveCallback?.(true);
        await promise;
    });

    it('should handle multiple sequential alerts', async () => {
        const { alert, alertState } = useAlert();

        let promise = alert('First alert', 'Alert 1', 'info');
        expect(alertState.title).toBe('Alert 1');
        alertState.resolveCallback?.(true);
        await promise;

        promise = alert('Second alert', 'Alert 2', 'success');
        expect(alertState.title).toBe('Alert 2');
        alertState.resolveCallback?.(true);
        await promise;
    });

    it('should return false when confirmation is cancelled', async () => {
        const { showAlert, closeAlert, alertState } = useAlert();

        const promise = showAlert({ message: 'Test' });

        // Get the resolve callback before calling close
        const callback = alertState.resolveCallback;
        expect(callback).not.toBeNull();

        // Close with false (cancelled)
        closeAlert(false);
        expect(alertState.show).toBe(false);
        expect(alertState.resolveCallback).toBeNull();

        const result = await promise;
        expect(result).toBe(false);
    });
});
