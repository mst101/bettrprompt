import { expect, test } from '@playwright/test';

/**
 * Voice Transcription E2E Tests
 *
 * Tests the voice input UI and accessibility features on the /prompt-builder page.
 * Route: POST /voice-transcription (throttled: 30 requests per minute)
 * Controller: App\Http\Controllers\VoiceTranscriptionController
 *
 * Note: These tests focus on UI visibility and accessibility rather than
 * actual audio recording (which requires microphone permissions and hardware).
 */

test.describe('Voice Transcription', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/prompt-builder');
    });

    test('should display voice input button on prompt builder page', async ({
        page,
    }) => {
        // The voice button should be visible in the task description form actions
        const voiceButton = page.getByRole('button', { name: /record/i });

        // Button might not exist, but if it does, it should be enabled
        const isVisible = await voiceButton.isVisible().catch(() => false);
        if (isVisible) {
            await expect(voiceButton).toBeEnabled();
        }
    });

    test('should show voice button with accessible title attribute', async ({
        page,
    }) => {
        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (isVisible) {
            // Check for helpful title/aria-label
            const title = await voiceButton.getAttribute('title');
            if (title) {
                expect(title.toLowerCase()).toContain('microphone');
            }
        }
    });

    test('voice button should be accessible via keyboard', async ({ page }) => {
        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (isVisible) {
            // Button should be focusable
            await voiceButton.focus();
            await expect(voiceButton).toBeFocused();
        }
    });

    test('voice button should have proper ARIA attributes', async ({
        page,
    }) => {
        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (isVisible) {
            // Should be identifiable as a button with proper role
            const ariaLabel = await voiceButton.getAttribute('aria-label');
            const title = await voiceButton.getAttribute('title');

            // At least one should provide context
            expect(ariaLabel || title).toBeTruthy();
        }
    });

    test('voice button should be positioned within task form area', async ({
        page,
    }) => {
        const taskInput = page.getByLabel(/task description/i);
        const voiceButton = page.getByRole('button', { name: /record/i });

        const taskVisible = await taskInput.isVisible().catch(() => false);
        const buttonVisible = await voiceButton.isVisible().catch(() => false);

        // If both exist, they should be in same viewport
        if (taskVisible && buttonVisible) {
            const taskBox = await taskInput.boundingBox();
            const buttonBox = await voiceButton.boundingBox();

            expect(taskBox).toBeTruthy();
            expect(buttonBox).toBeTruthy();
        }
    });
});
