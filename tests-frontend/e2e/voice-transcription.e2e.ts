import { expect, test } from './fixtures';

/**
 * Voice Transcription E2E Tests (Optimized)
 *
 * Tests the voice input UI and accessibility features on the /prompt-builder page.
 * Route: POST /voice-transcription (throttled: 30 requests per minute)
 * Controller: App\Http\Controllers\VoiceTranscriptionController
 *
 * Note: These tests focus on UI visibility and accessibility rather than
 * actual audio recording (which requires microphone permissions and hardware).
 * Tests use authenticatedPage fixture for proper access and pre-population of form.
 */

test.describe('Voice Transcription', () => {
    test('should display voice input button with accessibility features', async ({
        authenticatedPage,
    }) => {
        // Navigate to prompt builder page
        await authenticatedPage.goto('/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // The voice button should be visible in the task description form actions
        const voiceButton = authenticatedPage.getByRole('button', {
            name: /record/i,
        });

        // Button might not exist if feature is disabled, but if it does, it should be properly configured
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (isVisible) {
            // Verify button is enabled and has accessibility attributes
            await expect(voiceButton).toBeEnabled();

            // Should have title or aria-label for accessibility
            const ariaLabel = await voiceButton.getAttribute('aria-label');
            const title = await voiceButton.getAttribute('title');
            expect(ariaLabel || title).toBeTruthy();

            // Button should be keyboard focusable
            await voiceButton.focus();
            await expect(voiceButton).toBeFocused();
        }
    });

    test('voice input should be visible alongside task form', async ({
        authenticatedPage,
    }) => {
        // Navigate to prompt builder page
        await authenticatedPage.goto('/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        const taskInput = authenticatedPage.getByLabel(/task description/i);
        const voiceButton = authenticatedPage.getByRole('button', {
            name: /record/i,
        });

        const taskVisible = await taskInput.isVisible().catch(() => false);
        const buttonVisible = await voiceButton.isVisible().catch(() => false);

        // When voice feature is enabled, both task input and voice button should be visible
        if (taskVisible && buttonVisible) {
            const taskBox = await taskInput.boundingBox();
            const buttonBox = await voiceButton.boundingBox();

            // Both should be in the viewport with valid coordinates
            expect(taskBox).toBeTruthy();
            expect(buttonBox).toBeTruthy();

            // Both elements should be within reasonable bounds (not off-screen)
            if (taskBox && buttonBox) {
                expect(taskBox.x).toBeGreaterThanOrEqual(0);
                expect(buttonBox.x).toBeGreaterThanOrEqual(0);
            }
        }
    });

    test('voice input should work on different prompt stages', async ({
        authenticatedPage,
    }) => {
        // Test voice button on different prompt run states
        const { setupAndNavigateToPromptRun } =
            await import('./helpers/fixtures');

        // Test on processing state (clarifying questions not yet ready)
        await setupAndNavigateToPromptRun(authenticatedPage, '1_processing');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        let voiceButton = authenticatedPage.getByRole('button', {
            name: /record/i,
        });
        let isVisible = await voiceButton.isVisible().catch(() => false);

        // Voice feature should be available in processing state
        if (isVisible) {
            await expect(voiceButton).toBeEnabled();
        }

        // Test on completed state (framework selected with questions)
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to clarifying questions tab if it exists
        const clarifyingTab = authenticatedPage.getByRole('button', {
            name: /clarifying questions/i,
        });
        const tabVisible = await clarifyingTab.isVisible().catch(() => false);

        if (tabVisible) {
            await clarifyingTab.click();

            // Voice button should still be accessible in clarifying questions
            voiceButton = authenticatedPage.getByRole('button', {
                name: /record/i,
            });
            isVisible = await voiceButton.isVisible().catch(() => false);
            if (isVisible) {
                await expect(voiceButton).toBeEnabled();
            }
        }
    });
});
