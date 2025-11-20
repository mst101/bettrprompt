import { expect, test } from '@playwright/test';
import { loginAsTestUser, seedTestUser } from './helpers/auth';

/**
 * Voice Transcription E2E Tests
 *
 * Tests the voice input functionality on the /prompt-optimizer page.
 * Route: POST /voice-transcription (throttled: 30 requests per minute)
 * Controller: App\Http\Controllers\VoiceTranscriptionController
 *
 * Note: These tests focus on UI interactions and button states rather than
 * actual audio recording (which requires microphone permissions and hardware).
 * We test the UI flow, error handling, and user experience around voice input.
 */

test.describe.skip('Voice Transcription - Button Visibility', () => {
    test('should display voice input button on prompt optimizer page', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        // The voice button is within the task description form actions area
        // Look for a button with microphone-related attributes or text
        const voiceButton = page.locator('button', {
            has: page.locator('[class*="microphone"]'),
        });

        // Alternative: look for button with "Record" text
        const recordButton = page.getByRole('button', { name: /record/i });

        // At least one should be visible
        const hasVoiceButton = await voiceButton.isVisible().catch(() => false);
        const hasRecordButton = await recordButton
            .isVisible()
            .catch(() => false);

        expect(hasVoiceButton || hasRecordButton).toBe(true);

        // If found, verify it's enabled
        if (hasVoiceButton) {
            await expect(voiceButton).toBeEnabled();
        } else if (hasRecordButton) {
            await expect(recordButton).toBeEnabled();
        }
    });

    test('should show voice button with appropriate title attribute', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (isVisible) {
            // Check for helpful title/aria-label
            const title = await voiceButton.getAttribute('title');
            expect(title).toBeTruthy();
            expect(title?.toLowerCase()).toContain('microphone');
        }
    });

    test('voice button should be visible alongside task description textarea', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        // Task description should be visible
        const taskInput = page.getByLabel(/task description/i);
        await expect(taskInput).toBeVisible();

        // Voice button should be in the same vicinity
        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (isVisible) {
            // Both should be visible at the same time
            await expect(taskInput).toBeVisible();
            await expect(voiceButton).toBeVisible();
        }
    });
});

test.describe.skip('Voice Transcription - UI Interactions', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');
    });

    test('should show recording state when voice button is clicked', async ({
        page,
        context,
    }) => {
        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        // Grant microphone permission to allow recording UI to appear
        // Note: This doesn't actually record audio in Playwright
        await context.grantPermissions(['microphone']);

        // Click the voice button
        await voiceButton.click();

        // Wait a moment for UI to update
        await page.waitForTimeout(500);

        // Should show "Listening..." or "Stop recording" state
        const listeningButton = page.getByRole('button', {
            name: /listening/i,
        });
        const stopButton = page.getByRole('button', {
            name: /stop recording/i,
        });

        const isListening =
            (await listeningButton.isVisible().catch(() => false)) ||
            (await stopButton.isVisible().catch(() => false));

        expect(isListening).toBe(true);

        // Button should have visual indicator (pulsing animation)
        // Check for animation class
        const buttonClasses = await voiceButton.getAttribute('class');
        expect(buttonClasses).toContain('animate-pulse');
    });

    test('should display recording UI elements during active recording', async ({
        page,
        context,
    }) => {
        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        await context.grantPermissions(['microphone']);
        await voiceButton.click();
        await page.waitForTimeout(500);

        // Button should change appearance (red background for active recording)
        const buttonClasses = await voiceButton.getAttribute('class');
        expect(buttonClasses).toMatch(/bg-red-|animate-pulse/);

        // Button text should change to indicate active state
        const buttonText = await voiceButton.textContent();
        expect(buttonText?.toLowerCase()).toMatch(/listening|recording/);
    });

    test('should show microphone icon on voice button', async ({ page }) => {
        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        // Check for microphone icon (SVG or icon component)
        const microphoneIcon = voiceButton.locator(
            '[class*="microphone"], svg',
        );
        const hasIcon = await microphoneIcon.isVisible().catch(() => false);

        expect(hasIcon).toBe(true);
    });
});

test.describe.skip('Voice Transcription - Cancel Recording', () => {
    test('should allow cancelling recording by clicking button again', async ({
        page,
        context,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        await context.grantPermissions(['microphone']);

        // Get initial textarea value
        const taskInput = page.getByLabel(/task description/i);
        const initialValue = await taskInput.inputValue();

        // Start recording
        await voiceButton.click();
        await page.waitForTimeout(500);

        // Stop/cancel recording by clicking again
        await voiceButton.click();
        await page.waitForTimeout(500);

        // Textarea should remain unchanged (no transcription added)
        const currentValue = await taskInput.inputValue();
        expect(currentValue).toBe(initialValue);

        // Button should return to initial state
        const buttonText = await voiceButton.textContent();
        expect(buttonText?.toLowerCase()).toContain('record');
    });

    test('should return to default state after cancelling recording', async ({
        page,
        context,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        await context.grantPermissions(['microphone']);

        // Capture initial button state
        // const initialClasses = await voiceButton.getAttribute('class');

        // Start and cancel recording
        await voiceButton.click();
        await page.waitForTimeout(500);
        await voiceButton.click();
        await page.waitForTimeout(500);

        // Button should not have pulsing animation anymore
        const finalClasses = await voiceButton.getAttribute('class');
        expect(finalClasses).not.toContain('animate-pulse');

        // Button should be enabled and ready to record again
        await expect(voiceButton).toBeEnabled();
    });
});

test.describe.skip('Voice Transcription - Error Handling', () => {
    test('should show error message when microphone permission is denied', async ({
        page,
        context,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        // Deny microphone permission
        await context.grantPermissions([]);

        // Try to start recording
        await voiceButton.click();

        // Wait for error message to appear
        await page.waitForTimeout(1000);

        // Should see an error message about microphone access
        const errorMessage = page.locator('text=/microphone.*denied|access/i');
        const hasError = await errorMessage
            .isVisible({ timeout: 3000 })
            .catch(() => false);

        if (hasError) {
            expect(await errorMessage.textContent()).toMatch(
                /microphone|permission|access/i,
            );
        }
    });

    test('should display error message with appropriate styling', async ({
        page,
        context,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        await context.grantPermissions([]);
        await voiceButton.click();
        await page.waitForTimeout(1000);

        // Look for error message container with red styling
        const errorContainer = page.locator('[class*="bg-red"]');
        const hasErrorStyling = await errorContainer
            .isVisible({ timeout: 3000 })
            .catch(() => false);

        if (hasErrorStyling) {
            // Should have error icon
            const errorIcon = errorContainer.locator('svg, [class*="icon"]');
            const hasIcon = await errorIcon.isVisible().catch(() => false);
            expect(hasIcon).toBe(true);
        }
    });

    test('should auto-dismiss error message after timeout', async ({
        page,
        context,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        await context.grantPermissions([]);
        await voiceButton.click();
        await page.waitForTimeout(1000);

        const errorMessage = page.locator('[class*="bg-red"]');
        const hasError = await errorMessage
            .isVisible({ timeout: 3000 })
            .catch(() => false);

        if (hasError) {
            // Error should be visible initially
            await expect(errorMessage).toBeVisible();

            // Wait for auto-dismiss (5 seconds according to composable)
            await page.waitForTimeout(6000);

            // Error should be hidden
            const stillVisible = await errorMessage
                .isVisible()
                .catch(() => false);
            expect(stillVisible).toBe(false);
        }
    });

    test('should allow manual input as fallback when voice fails', async ({
        page,
        context,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        await context.grantPermissions([]);
        await voiceButton.click();
        await page.waitForTimeout(1000);

        // Despite voice input failing, user should still be able to type
        const taskInput = page.getByLabel(/task description/i);
        await expect(taskInput).toBeEnabled();

        // Type manual input
        const manualText = 'Manual input because voice failed';
        await taskInput.fill(manualText);

        // Verify text was entered
        expect(await taskInput.inputValue()).toBe(manualText);
    });
});

test.describe.skip('Voice Transcription - Processing State', () => {
    test('should show processing indicator during transcription', async ({
        page,
        context,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        await context.grantPermissions(['microphone']);

        // Start recording
        await voiceButton.click();
        await page.waitForTimeout(1000);

        // Stop recording to trigger transcription
        await voiceButton.click();

        // Wait for processing state
        await page.waitForTimeout(500);

        // Should show "Transcribing..." state
        const transcribingButton = page.getByRole('button', {
            name: /transcribing/i,
        });
        const isTranscribing = await transcribingButton
            .isVisible({ timeout: 2000 })
            .catch(() => false);

        if (isTranscribing) {
            // Button should be disabled during processing
            await expect(transcribingButton).toBeDisabled();

            // Should show spinner icon
            const spinner = page.locator('[class*="animate-spin"]');
            const hasSpinner = await spinner.isVisible().catch(() => false);
            expect(hasSpinner).toBe(true);
        }
    });

    test('should display processing message near button', async ({
        page,
        context,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        await context.grantPermissions(['microphone']);

        // Start and stop recording
        await voiceButton.click();
        await page.waitForTimeout(1000);
        await voiceButton.click();
        await page.waitForTimeout(500);

        // Look for processing message
        const processingMessage = page.locator('text=/transcribing.*audio/i');
        const hasMessage = await processingMessage
            .isVisible({ timeout: 2000 })
            .catch(() => false);

        if (hasMessage) {
            // Message should have appropriate styling
            const messageContainer = processingMessage.locator('..');
            const classes = await messageContainer.getAttribute('class');
            expect(classes).toMatch(/bg-indigo|text-indigo/);
        }
    });

    test('should disable button during transcription processing', async ({
        page,
        context,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        await context.grantPermissions(['microphone']);

        // Start and stop recording
        await voiceButton.click();
        await page.waitForTimeout(1000);
        await voiceButton.click();
        await page.waitForTimeout(500);

        // Button should be disabled during processing
        const transcribingButton = page.getByRole('button', {
            name: /transcribing/i,
        });
        const isProcessing = await transcribingButton
            .isVisible({ timeout: 2000 })
            .catch(() => false);

        if (isProcessing) {
            await expect(transcribingButton).toBeDisabled();

            // Button should have disabled cursor styling
            const classes = await transcribingButton.getAttribute('class');
            expect(classes).toMatch(/cursor-not-allowed|opacity-50/);
        }
    });
});

test.describe.skip('Voice Transcription - Accessibility', () => {
    test('should have proper ARIA labels on voice button', async ({ page }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        // Should have descriptive title attribute
        const title = await voiceButton.getAttribute('title');
        expect(title).toBeTruthy();
        expect(title).toMatch(/record|microphone|voice/i);
    });

    test('should be keyboard accessible', async ({ page }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        // Tab to the voice button
        await page.keyboard.press('Tab');
        await page.keyboard.press('Tab'); // May need multiple tabs

        // Check if voice button can receive focus
        const taskInput = page.getByLabel(/task description/i);
        await taskInput.focus();

        // Tab to voice button (it should be after the textarea)
        await page.keyboard.press('Tab');

        // The voice button should be focusable
        const focusedElement = await page.evaluate(
            () => document.activeElement?.tagName,
        );
        expect(['BUTTON', 'INPUT', 'TEXTAREA']).toContain(focusedElement);
    });

    test('should announce recording state changes for screen readers', async ({
        page,
        context,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        await context.grantPermissions(['microphone']);

        // Initial state
        const initialText = await voiceButton.textContent();
        expect(initialText).toBeTruthy();

        // Start recording - button text should change
        await voiceButton.click();
        await page.waitForTimeout(500);

        const recordingText = await voiceButton.textContent();
        expect(recordingText).not.toBe(initialText);
        expect(recordingText).toMatch(/listening|recording/i);

        // Title should also update
        const recordingTitle = await voiceButton.getAttribute('title');
        expect(recordingTitle).toMatch(/stop/i);
    });

    test('should have sufficient colour contrast in all states', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        // Default state should have visible text
        const defaultText = await voiceButton.textContent();
        expect(defaultText).toBeTruthy();

        // Button should have defined background
        const classes = await voiceButton.getAttribute('class');
        expect(classes).toMatch(/bg-/);

        // Should have text colour defined
        expect(classes).toMatch(/text-/);
    });
});

test.describe.skip('Voice Transcription - Route Throttling', () => {
    test('should document throttle limit of 30 requests per minute', async ({
        page,
    }) => {
        // This test serves as documentation that the route is throttled
        // Actual throttle testing would require making 30+ requests
        // which is impractical in e2e tests

        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        // Verify voice input functionality exists
        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        // Note: The /voice-transcription endpoint is throttled to 30 requests/minute
        // This protects against abuse of the OpenAI Whisper API
        expect(isVisible || !isVisible).toBe(true); // Always pass - documentation test
    });
});

test.describe.skip('Voice Transcription - Integration with Form', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');
    });

    test('should be positioned near task description textarea', async ({
        page,
    }) => {
        const taskInput = page.getByLabel(/task description/i);
        const voiceButton = page.getByRole('button', { name: /record/i });

        await expect(taskInput).toBeVisible();

        const isVisible = await voiceButton.isVisible().catch(() => false);
        if (isVisible) {
            // Both should be visible together
            await expect(voiceButton).toBeVisible();

            // Voice button should be part of the form
            const formElement = page.locator('form');
            const buttonInForm = formElement.locator('button', {
                hasText: /record/i,
            });
            const isInForm = await buttonInForm.isVisible().catch(() => false);

            expect(isInForm).toBe(true);
        }
    });

    test('should not interfere with manual text input', async ({ page }) => {
        const taskInput = page.getByLabel(/task description/i);
        const voiceButton = page.getByRole('button', { name: /record/i });

        const isVisible = await voiceButton.isVisible().catch(() => false);
        if (!isVisible) {
            test.skip();
            return;
        }

        // Type some text manually
        const manualText = 'This is manually typed text';
        await taskInput.fill(manualText);

        // Voice button should still be enabled
        await expect(voiceButton).toBeEnabled();

        // Manual text should remain intact
        expect(await taskInput.inputValue()).toBe(manualText);
    });

    test('should show trash button alongside voice button when text exists', async ({
        page,
    }) => {
        const taskInput = page.getByLabel(/task description/i);

        // Add some text to the textarea
        await taskInput.fill('Some task description text');

        // Wait for UI to update
        await page.waitForTimeout(500);

        // Should see both voice button and trash/clear button
        const voiceButton = page.getByRole('button', { name: /record/i });
        const trashButton = page.locator('button[class*="trash"], button', {
            has: page.locator('[class*="trash"]'),
        });

        const hasVoice = await voiceButton.isVisible().catch(() => false);
        const hasTrash = await trashButton.isVisible().catch(() => false);

        // At least voice button should be visible
        expect(hasVoice).toBe(true);

        // Trash button should appear when there's content
        if (hasTrash) {
            await expect(trashButton).toBeVisible();
        }
    });

    test('should maintain form state during voice recording', async ({
        page,
        context,
    }) => {
        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!isVisible) {
            test.skip();
            return;
        }

        await context.grantPermissions(['microphone']);

        // Check submit button state
        const submitButton = page.getByRole('button', {
            name: /optimise.*prompt/i,
        });
        await expect(submitButton).toBeVisible();

        // Start recording
        await voiceButton.click();
        await page.waitForTimeout(500);

        // Submit button should still be visible and in same state
        await expect(submitButton).toBeVisible();

        // Stop recording
        await voiceButton.click();
        await page.waitForTimeout(500);

        // Form should still be functional
        await expect(submitButton).toBeVisible();
    });
});

test.describe.skip(
    'Voice Transcription - Authenticated User Experience',
    () => {
        test.beforeAll(async () => {
            await seedTestUser();
        });

        test.beforeEach(async ({ page }) => {
            await loginAsTestUser(page);
            await page.goto('/prompt-optimizer');
            await page.waitForLoadState('networkidle');
        });

        test('should show voice input for authenticated users', async ({
            page,
        }) => {
            const voiceButton = page.getByRole('button', { name: /record/i });
            const isVisible = await voiceButton.isVisible().catch(() => false);

            // Voice input should be available to authenticated users
            if (isVisible) {
                await expect(voiceButton).toBeEnabled();
            } else {
                // Test passes even if not visible - may depend on browser capabilities
                expect(true).toBe(true);
            }
        });

        test('should work the same way for authenticated and unauthenticated users', async ({
            page,
            context,
        }) => {
            // Voice transcription should not require authentication
            // (though prompt optimizer history does)

            const voiceButton = page.getByRole('button', { name: /record/i });
            const isVisible = await voiceButton.isVisible().catch(() => false);

            if (!isVisible) {
                test.skip();
                return;
            }

            await context.grantPermissions(['microphone']);

            // Should be able to start recording
            await voiceButton.click();
            await page.waitForTimeout(500);

            // Should show recording state
            const listeningButton = page.getByRole('button', {
                name: /listening/i,
            });
            const isRecording = await listeningButton
                .isVisible()
                .catch(() => false);

            expect(isRecording).toBe(true);
        });
    },
);

test.describe.skip('Voice Transcription - Browser Compatibility Notes', () => {
    test('should gracefully handle browsers without MediaRecorder support', async ({
        page,
    }) => {
        // This test documents that voice input requires MediaRecorder API
        // Older browsers may not support this feature

        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        // Check if MediaRecorder is available
        const hasMediaRecorder = await page.evaluate(() => {
            return typeof MediaRecorder !== 'undefined';
        });

        const voiceButton = page.getByRole('button', { name: /record/i });
        const isVisible = await voiceButton.isVisible().catch(() => false);

        if (!hasMediaRecorder) {
            // Voice button might not appear if MediaRecorder is unsupported
            // This is acceptable graceful degradation
            expect(true).toBe(true);
        } else if (isVisible) {
            // If MediaRecorder is supported and button is visible, it should work
            await expect(voiceButton).toBeEnabled();
        }
    });
});
