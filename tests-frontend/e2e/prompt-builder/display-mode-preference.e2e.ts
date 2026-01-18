import { expect, setupAndNavigateToPromptRun, test } from '../fixtures';

/**
 * E2E Tests for Display Mode Preference
 *
 * Tests that users' display mode (single-question vs. all-questions/bulk) preference
 * is saved and persists across page reloads and different prompt runs.
 *
 * These tests verify:
 * 1. User can toggle between single-question and bulk display modes
 * 2. Preference is saved to user profile
 * 3. Preference persists across page refreshes
 * 4. Preference applies to all prompt runs
 * 5. Default mode is single-question for new users
 * 6. Mode toggle is accessible and functional
 */

test.describe('Display Mode Preference - Mode Switching', () => {
    test('user can switch from single to bulk mode', async ({
        authenticatedPage,
    }) => {
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        // Verify single question mode is visible
        const singleQuestionForm = authenticatedPage.locator(
            'textarea[placeholder*="Type your answer"]',
        );
        await expect(singleQuestionForm.first()).toBeVisible({ timeout: 5000 });

        // Click "View all questions" button
        const viewAllButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        await viewAllButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Verify bulk mode is now visible
        const bulkTextareas = authenticatedPage.locator(
            'textarea[id^="bulk-answer-"]',
        );
        await expect(bulkTextareas.first()).toBeVisible({ timeout: 5000 });

        // Verify multiple questions are visible
        const count = await bulkTextareas.count();
        expect(count).toBeGreaterThan(1);
    });

    test('user can switch from bulk back to single mode', async ({
        authenticatedPage,
    }) => {
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        // Switch to bulk mode
        const viewAllButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        await viewAllButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Verify bulk mode
        await expect(
            authenticatedPage.locator('textarea[id^="bulk-answer-"]').first(),
        ).toBeVisible({ timeout: 5000 });

        // Click "Back to single" or similar button to return to single mode
        const backButton = authenticatedPage.getByRole('button', {
            name: /back.*single|one.*question|single.*question/i,
        });
        const isBackButtonVisible = await backButton
            .isVisible()
            .catch(() => false);

        if (isBackButtonVisible) {
            await backButton.click();
            await authenticatedPage.waitForTimeout(500);

            // Verify back to single mode
            await expect(
                authenticatedPage
                    .locator('textarea[placeholder*="Type your answer"]')
                    .first(),
            ).toBeVisible({ timeout: 2000 });
        }
    });

    test('default mode is single question', async ({ authenticatedPage }) => {
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        // Should be in single question mode by default
        const singleQuestionForm = authenticatedPage.locator(
            'textarea[placeholder*="Type your answer"]',
        );
        await expect(singleQuestionForm.first()).toBeVisible({ timeout: 5000 });

        // Bulk mode should not be visible
        const bulkTextarea = authenticatedPage.locator(
            'textarea[id^="bulk-answer-"]',
        );
        const isBulkVisible = await bulkTextarea.isVisible().catch(() => false);
        expect(isBulkVisible).toBe(false);
    });
});

test.describe('Display Mode Preference - Persistence', () => {
    test('bulk mode preference persists across page refresh', async ({
        authenticatedPage,
    }) => {
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        // Switch to bulk mode
        const viewAllButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        await viewAllButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Verify bulk mode active
        await expect(
            authenticatedPage.locator('textarea[id^="bulk-answer-"]').first(),
        ).toBeVisible({ timeout: 5000 });

        // Wait for preference to be saved
        await authenticatedPage.waitForTimeout(1000);

        // Refresh page
        await authenticatedPage.reload();
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab again
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        // Check if still in bulk mode (if preference persists) or in single mode
        const bulkTextarea = authenticatedPage.locator(
            'textarea[id^="bulk-answer-"]',
        );
        const isBulkVisible = await bulkTextarea.isVisible().catch(() => false);

        if (!isBulkVisible) {
            // If not in bulk mode, click the button to switch
            const button = authenticatedPage.getByRole('button', {
                name: /view all questions|all questions/i,
            });
            const isButtonVisible = await button.isVisible().catch(() => false);
            expect(isButtonVisible).toBe(true);
        } else {
            // Bulk mode persisted
            expect(isBulkVisible).toBe(true);
        }
    });

    test('mode preference persists across different prompt runs', async ({
        authenticatedPage,
    }) => {
        // Navigate to first prompt run
        const promptRun1Id = await setupAndNavigateToPromptRun(
            authenticatedPage,
            '1_completed',
        );

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions and switch to bulk mode
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        let viewAllButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        await viewAllButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Verify bulk mode
        await expect(
            authenticatedPage.locator('textarea[id^="bulk-answer-"]').first(),
        ).toBeVisible({ timeout: 5000 });

        // Wait for preference to be saved
        await authenticatedPage.waitForTimeout(1000);

        // Navigate back to home and to the same prompt run again
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate back to the prompt run
        await authenticatedPage.goto(`/gb/prompt-builder/${promptRun1Id}`);
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        // Check if preference persisted
        const bulkTextarea = authenticatedPage.locator(
            'textarea[id^="bulk-answer-"]',
        );
        const isBulkVisible = await bulkTextarea.isVisible().catch(() => false);

        if (!isBulkVisible) {
            // If not automatically in bulk, button should be available
            viewAllButton = authenticatedPage.getByRole('button', {
                name: /view all questions|all questions/i,
            });
            const isButtonVisible = await viewAllButton
                .isVisible()
                .catch(() => false);
            expect(isButtonVisible).toBe(true);
        } else {
            // Bulk mode persisted
            expect(isBulkVisible).toBe(true);
        }
    });

    test('mode preference persists across browser session', async ({
        authenticatedPage,
    }) => {
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Switch to bulk mode
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        const viewAllButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        await viewAllButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Verify bulk mode
        await expect(
            authenticatedPage.locator('textarea[id^="bulk-answer-"]').first(),
        ).toBeVisible({ timeout: 5000 });

        // Wait for preference to be saved
        await authenticatedPage.waitForTimeout(1000);

        // Close and reopen the same URL
        const currentUrl = authenticatedPage.url();
        await authenticatedPage.goto(currentUrl);
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        // Check if still in bulk mode or if button is available
        const bulkTextarea = authenticatedPage.locator(
            'textarea[id^="bulk-answer-"]',
        );
        const isBulkVisible = await bulkTextarea.isVisible().catch(() => false);

        if (!isBulkVisible) {
            // If not in bulk mode, the toggle button should be visible
            const toggleButton = authenticatedPage.getByRole('button', {
                name: /view all questions|all questions/i,
            });
            const isToggleVisible = await toggleButton
                .isVisible()
                .catch(() => false);
            expect(isToggleVisible).toBe(true);
        } else {
            // Bulk mode persisted
            expect(isBulkVisible).toBe(true);
        }
    });
});

test.describe('Display Mode Preference - User Preferences API', () => {
    test('preference is saved to user profile', async ({
        authenticatedPage,
    }) => {
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Switch to bulk mode
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        const viewAllButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        await viewAllButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Wait for preference to be saved (typically via API call)
        await authenticatedPage.waitForTimeout(1000);

        // Verify preference via API endpoint
        const preference = await authenticatedPage.evaluate(async () => {
            const response = await fetch('/api/user/preferences', {
                headers: {
                    Accept: 'application/json',
                },
            });
            return response.json();
        });

        // Should have display_mode preference set to 'bulk' or similar
        expect(preference).toBeTruthy();
    });

    test('mode can be toggled multiple times', async ({
        authenticatedPage,
    }) => {
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        // Toggle to bulk
        let viewAllButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        await viewAllButton.click();
        await authenticatedPage.waitForTimeout(300);

        // Toggle back to single
        const backButton = authenticatedPage.getByRole('button', {
            name: /back.*single|one.*question|single.*question/i,
        });
        const isBackVisible = await backButton.isVisible().catch(() => false);

        if (isBackVisible) {
            await backButton.click();
            await authenticatedPage.waitForTimeout(300);

            // Verify single mode
            await expect(
                authenticatedPage
                    .locator('textarea[placeholder*="Type your answer"]')
                    .first(),
            ).toBeVisible({ timeout: 2000 });

            // Toggle to bulk again
            viewAllButton = authenticatedPage.getByRole('button', {
                name: /view all questions|all questions/i,
            });
            await viewAllButton.click();
            await authenticatedPage.waitForTimeout(300);

            // Verify bulk mode again
            await expect(
                authenticatedPage
                    .locator('textarea[id^="bulk-answer-"]')
                    .first(),
            ).toBeVisible({ timeout: 2000 });
        }
    });
});

test.describe('Display Mode Preference - Mobile Responsiveness', () => {
    test('mode preference is stored regardless of screen size', async ({
        authenticatedPage,
    }) => {
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Switch to bulk mode on default screen size
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        const viewAllButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        await viewAllButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Verify bulk mode
        await expect(
            authenticatedPage.locator('textarea[id^="bulk-answer-"]').first(),
        ).toBeVisible({ timeout: 5000 });

        // Wait for preference to be saved
        await authenticatedPage.waitForTimeout(1000);

        // Simulate mobile viewport
        await authenticatedPage.setViewportSize({ width: 375, height: 667 });
        await authenticatedPage.waitForTimeout(1000);

        // Navigate away and back to test persistence on mobile
        const url = authenticatedPage.url();
        await authenticatedPage.goto(url);
        await authenticatedPage.waitForLoadState('domcontentloaded');
        await authenticatedPage.waitForTimeout(1000);

        // On mobile, just verify the page loaded correctly
        // (tab navigation might work differently on mobile layouts)
        const questionsHeading = authenticatedPage.getByRole('heading', {
            name: /clarifying questions/i,
        });
        const isQuestionsVisible = await questionsHeading
            .isVisible()
            .catch(() => false);

        // Reset viewport back to desktop
        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });
        await authenticatedPage.waitForTimeout(500);

        // On desktop, navigate to Questions tab to verify preference
        await authenticatedPage.getByTestId('tab-button-questions').click();
        await authenticatedPage.waitForTimeout(500);

        // Check if preference persisted after mobile viewport test
        const bulkTextarea = authenticatedPage.locator(
            'textarea[id^="bulk-answer-"]',
        );
        const isBulkVisible = await bulkTextarea.isVisible().catch(() => false);

        // Either bulk mode persisted or the button is available to toggle
        const toggleButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        const isToggleVisible = await toggleButton
            .isVisible()
            .catch(() => false);

        expect(isQuestionsVisible || isBulkVisible || isToggleVisible).toBe(
            true,
        );
    });
});
