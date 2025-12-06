import { expect, test } from './fixtures';
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
    test.beforeEach(async ({ authenticatedPage }) => {
        // User is already authenticated via fixture
        void authenticatedPage;
    });

    test('should update UI when AnalysisCompleted event broadcasts', async ({
        authenticatedPage,
    }) => {
        // Create a submitted prompt run
        const promptRunId = await createTestPromptRun(
            authenticatedPage,
            'submitted',
        );
        await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);

        // Verify we navigated correctly
        expect(authenticatedPage.url()).toContain(
            `/prompt-builder/${promptRunId}`,
        );

        // Trigger the AnalysisCompleted event
        await triggerAnalysisCompleted(authenticatedPage, promptRunId);

        // Framework tab should appear after page reloads with updated data
        const frameworkTab = authenticatedPage.getByTestId(
            'tab-button-framework',
        );
        const frameworkBadge =
            authenticatedPage.getByText(/framework selected/i);

        const hasFrameworkTab = await frameworkTab
            .isVisible({ timeout: 5000 })
            .catch(() => false);
        const hasFrameworkBadge = await frameworkBadge
            .isVisible({ timeout: 2000 })
            .catch(() => false);

        expect(hasFrameworkTab || hasFrameworkBadge).toBe(true);
    });
});
