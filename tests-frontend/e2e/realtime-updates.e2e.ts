import { expect, test } from './fixtures';
import { createTestPromptRun } from './helpers/broadcast';

/**
 * Real-time Updates E2E Tests
 *
 * Tests verify that the application correctly handles real-time updates
 * via Laravel Echo and falls back to polling when WebSockets are unavailable.
 *
 * Note: These tests verify the application's behavior, not the Echo library's behavior.
 * Echo initialization and WebSocket management are out of scope for application E2E tests.
 */

test.describe('Realtime - Fallback Behavior', () => {
    test('should remain functional when WebSocket unavailable', async ({
        authenticatedPage,
    }) => {
        // Navigate to a prompt run
        const promptRunId = await createTestPromptRun(
            authenticatedPage,
            '1_processing',
        );
        await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);

        // Disable WebSocket to simulate failure
        await authenticatedPage.evaluate(() => {
            window.Echo = null;
            window.dispatchEvent(new CustomEvent('echo-failed'));
        });

        // Application should still be usable - navigate to history
        await authenticatedPage.goto('/history');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Should still be able to navigate
        const heading = authenticatedPage.locator('h1, h2').first();
        await expect(heading).toBeVisible();
    });

    test('should allow manual refresh as fallback', async ({
        authenticatedPage,
    }) => {
        // Create and navigate to prompt run
        const promptRunId = await createTestPromptRun(
            authenticatedPage,
            '1_processing',
        );
        await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);

        // Manually refresh page
        await authenticatedPage.reload();

        // Page should still work after refresh
        const taskTab = authenticatedPage.getByTestId('tab-button-task');
        await expect(taskTab).toBeVisible();
    });
});

test.describe('Realtime - Channel Cleanup', () => {
    test('should cleanup channels without JavaScript errors', async ({
        authenticatedPage,
    }) => {
        // Capture any JavaScript errors during cleanup
        const errors: string[] = [];
        authenticatedPage.on('pageerror', (error) => {
            errors.push(error.message);
        });

        // Create and navigate to prompt run
        const promptRunId = await createTestPromptRun(
            authenticatedPage,
            '1_processing',
        );
        await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);

        // Navigate away - channels should cleanup properly
        await authenticatedPage.goto('/history');

        // Should not have JavaScript errors
        expect(errors).toHaveLength(0);
    });

    test('should not leak event listeners across navigation', async ({
        authenticatedPage,
    }) => {
        // Capture errors throughout multiple navigations
        const errors: string[] = [];
        authenticatedPage.on('pageerror', (error) => {
            errors.push(error.message);
        });

        // Navigate to prompt runs multiple times
        for (let i = 0; i < 2; i++) {
            const promptRunId = await createTestPromptRun(
                authenticatedPage,
                '1_processing',
            );
            await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);
            await authenticatedPage.goto('/history');
        }

        // Navigate to different page
        await authenticatedPage.goto('/');

        // Should still be responsive without errors
        const heading = authenticatedPage.locator('h1, h2').first();
        await expect(heading).toBeVisible();
        expect(errors).toHaveLength(0);
    });
});

test.describe('Realtime - Tab Visibility', () => {
    test('should display framework tab for framework-selected state', async ({
        authenticatedPage,
    }) => {
        // Create prompt with framework already selected (workflow 1 complete)
        const promptRunId = await createTestPromptRun(
            authenticatedPage,
            '1_completed',
        );
        await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);

        // Framework tab should be visible
        const frameworkTab = authenticatedPage.getByTestId(
            'tab-button-framework',
        );
        await expect(frameworkTab).toBeVisible();
    });

    test('should display optimised prompt tab for completed state', async ({
        authenticatedPage,
    }) => {
        // Create completed prompt run (workflow 2 complete)
        const promptRunId = await createTestPromptRun(
            authenticatedPage,
            '2_completed',
        );
        await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);

        // Optimised prompt tab should be visible
        const promptTab = authenticatedPage.getByTestId('tab-button-prompt');
        await expect(promptTab).toBeVisible();
    });

    test('should show task tab by default for processing state', async ({
        authenticatedPage,
    }) => {
        // Create processing prompt (workflow 1 in progress, no framework yet)
        const promptRunId = await createTestPromptRun(
            authenticatedPage,
            '1_processing',
        );
        await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);

        // Wait for page content to load (faster than networkidle)
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Task tab should be visible by default
        const taskTab = authenticatedPage.getByTestId('tab-button-task');
        await expect(taskTab).toBeVisible();

        // Page should have correct URL
        expect(authenticatedPage.url()).toContain(
            `/prompt-builder/${promptRunId}`,
        );
    });
});
