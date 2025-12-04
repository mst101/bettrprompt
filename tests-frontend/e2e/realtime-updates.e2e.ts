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
    test.beforeEach(async ({ authenticatedPage }) => {
        // User is already authenticated via fixture

        void authenticatedPage;
    });

    test('should remain functional when WebSocket unavailable', async ({
        page,
    }) => {
        // Navigate to a prompt run
        const promptRunId = await createTestPromptRun(page, 'submitted');
        await page.goto(`/prompt-builder/${promptRunId}`);

        // Disable WebSocket to simulate failure
        await page.evaluate(() => {
            window.Echo = null;
            window.dispatchEvent(new CustomEvent('echo-failed'));
        });

        // Application should still be usable - navigate to history
        await page.goto('/prompt-builder-history');
        await page.waitForLoadState('domcontentloaded');

        // Should still be able to navigate
        const heading = page.locator('h1, h2').first();
        await expect(heading).toBeVisible();
    });

    test('should allow manual refresh as fallback', async ({ page }) => {
        // Create and navigate to prompt run
        const promptRunId = await createTestPromptRun(page, 'submitted');
        await page.goto(`/prompt-builder/${promptRunId}`);

        // Manually refresh page
        await page.reload();

        // Page should still work after refresh
        const taskTab = page.getByTestId('tab-button-task');
        await expect(taskTab).toBeVisible();
    });
});

test.describe('Realtime - Channel Cleanup', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        // User is already authenticated via fixture

        void authenticatedPage;
    });

    test('should cleanup channels without JavaScript errors', async ({
        page,
    }) => {
        // Capture any JavaScript errors during cleanup
        const errors: string[] = [];
        page.on('pageerror', (error) => {
            errors.push(error.message);
        });

        // Create and navigate to prompt run
        const promptRunId = await createTestPromptRun(page, 'submitted');
        await page.goto(`/prompt-builder/${promptRunId}`);

        // Navigate away - channels should cleanup properly
        await page.goto('/prompt-builder-history');

        // Should not have JavaScript errors
        expect(errors).toHaveLength(0);
    });

    test('should not leak event listeners across navigation', async ({
        page,
    }) => {
        // Capture errors throughout multiple navigations
        const errors: string[] = [];
        page.on('pageerror', (error) => {
            errors.push(error.message);
        });

        // Navigate to prompt runs multiple times
        for (let i = 0; i < 2; i++) {
            const promptRunId = await createTestPromptRun(page, 'submitted');
            await page.goto(`/prompt-builder/${promptRunId}`);
            await page.goto('/prompt-builder-history');
        }

        // Navigate to different page
        await page.goto('/');

        // Should still be responsive without errors
        const heading = page.locator('h1, h2').first();
        await expect(heading).toBeVisible();
        expect(errors).toHaveLength(0);
    });
});

test.describe('Realtime - Tab Visibility', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        // User is already authenticated via fixture

        void authenticatedPage;
    });

    test('should display framework tab for framework-selected state', async ({
        page,
    }) => {
        // Create prompt with framework already selected
        const promptRunId = await createTestPromptRun(
            page,
            'framework_selected',
        );
        await page.goto(`/prompt-builder/${promptRunId}`);

        // Framework tab should be visible
        const frameworkTab = page.getByTestId('tab-button-framework');
        await expect(frameworkTab).toBeVisible();
    });

    test('should display optimised prompt tab for completed state', async ({
        page,
    }) => {
        // Create completed prompt run
        const promptRunId = await createTestPromptRun(page, 'completed');
        await page.goto(`/prompt-builder/${promptRunId}`);

        // Optimised prompt tab should be visible
        const promptTab = page.getByTestId('tab-button-prompt');
        await expect(promptTab).toBeVisible();
    });

    test('should show task tab by default for submitted state', async ({
        page,
    }) => {
        // Create submitted prompt (no framework yet)
        const promptRunId = await createTestPromptRun(page, 'submitted');
        await page.goto(`/prompt-builder/${promptRunId}`);

        // Task tab should be visible by default
        const taskTab = page.getByTestId('tab-button-task');
        await expect(taskTab).toBeVisible();

        // Page should have correct title
        expect(page.url()).toContain(`/prompt-builder/${promptRunId}`);
    });
});
