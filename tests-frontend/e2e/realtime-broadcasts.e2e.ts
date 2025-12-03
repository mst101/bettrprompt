import { expect, test } from '@playwright/test';
import { loginAsTestUser } from './helpers/auth';
import {
    createTestPromptRun,
    triggerAnalysisCompleted,
} from './helpers/broadcast';

/**
 * Real-time Broadcast E2E Tests
 *
 * Tests verify that WebSocket events trigger page reloads with updated data.
 * Uses test endpoints to manually trigger broadcast events in a controlled way.
 */

test.describe('Realtime - Event Broadcasting', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsTestUser(page);
    });

    test('should update UI when AnalysisCompleted event broadcasts', async ({
        page,
    }) => {
        // Create a submitted prompt run
        const promptRunId = await createTestPromptRun(page, 'submitted');
        await page.goto(`/prompt-builder/${promptRunId}`);

        // Verify we navigated correctly
        expect(page.url()).toContain(`/prompt-builder/${promptRunId}`);

        // Trigger the AnalysisCompleted event
        await triggerAnalysisCompleted(page, promptRunId);

        // Framework tab should appear after page reloads with updated data
        const frameworkTab = page.getByTestId('tab-button-framework');
        await expect(frameworkTab).toBeVisible({ timeout: 10000 });
    });
});
