import { expect, test } from './fixtures';
import {
    createTestPromptRun,
    triggerAnalysisCompleted,
} from './helpers/broadcast';

/**
 * Real-time Broadcast E2E Tests
 *
 * Tests verify that WebSocket events trigger page updates with new data.
 * Uses test endpoints to manually trigger broadcast events in a controlled way.
 * Tests focus on verifying framework data is displayed after event broadcasts.
 */

test.describe('Realtime - Event Broadcasting', () => {
    test('should update UI when AnalysisCompleted event broadcasts', async ({
        authenticatedPage,
    }) => {
        // Create a prompt run in processing state (1_processing)
        const promptRunId = await createTestPromptRun(
            authenticatedPage,
            '1_processing',
        );
        await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);

        // Verify we navigated correctly
        expect(authenticatedPage.url()).toContain(
            `/prompt-builder/${promptRunId}`,
        );

        // Wait for page content to load (faster than networkidle)
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Trigger the AnalysisCompleted event to simulate n8n completing analysis
        // This broadcasts a WebSocket event to update the UI
        await triggerAnalysisCompleted(authenticatedPage, promptRunId);

        // Framework tab should become visible after event is processed
        // The triggerAnalysisCompleted helper waits for the framework tab internally
        const frameworkTab = authenticatedPage.getByTestId(
            'tab-button-framework',
        );
        await expect(frameworkTab).toBeVisible({ timeout: 5000 });
    });
});
