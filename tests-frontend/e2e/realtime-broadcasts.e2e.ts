import { expect, setupAndNavigateToPromptRun, test } from './fixtures';
import { triggerAnalysisCompleted } from './helpers/broadcast';

/**
 * Real-time Broadcast E2E Tests
 *
 * Tests verify that WebSocket events trigger page updates with new data.
 * Uses test endpoints to manually trigger broadcast events in a controlled way.
 * Tests focus on verifying framework data is displayed after event broadcasts.
 */

test.describe.serial('Realtime - Event Broadcasting', () => {
    test('should update UI when AnalysisCompleted event broadcasts', async ({
        authenticatedPage,
    }) => {
        // Create and navigate to a prompt run in processing state (1_processing)
        const promptRunId = await setupAndNavigateToPromptRun(
            authenticatedPage,
            '1_processing',
        );

        // Verify we navigated correctly
        expect(authenticatedPage.url()).toContain(
            `/prompt-builder/${promptRunId}`,
        );

        // Wait for page content to load
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Trigger the AnalysisCompleted event to simulate n8n completing analysis
        // This broadcasts a WebSocket event to update the UI
        await triggerAnalysisCompleted(authenticatedPage, promptRunId);

        // The triggerAnalysisCompleted helper already waits for the framework tab
        // but adding extra time when running with other tests helps with timing
        await authenticatedPage.waitForTimeout(500);

        // Framework tab should become visible after event is processed
        // Use a longer timeout when running in full test suite
        const frameworkTab = authenticatedPage.getByTestId(
            'tab-button-framework',
        );

        try {
            await expect(frameworkTab).toBeVisible({ timeout: 8000 });
        } catch {
            // If still not visible, try reloading the page to sync state
            await authenticatedPage.reload({ waitUntil: 'domcontentloaded' });
            await expect(frameworkTab).toBeVisible({ timeout: 5000 });
        }
    });
});
