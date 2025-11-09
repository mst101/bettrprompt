import { expect, test } from '@playwright/test';

test.describe('Prompt Optimizer - Unauthenticated', () => {
    test('should redirect to home when not logged in', async ({ page }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        // Should be redirected to home or login
        const url = page.url();
        expect(url).toMatch(/\/((\?modal=login)|login)?$/);
    });
});

test.describe('Prompt Optimizer - Authenticated Flow', () => {
    // Note: These tests require authentication setup
    // For now, they check that the pages are accessible (will redirect to login)

    test('should show prompt optimizer index page structure', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        // If redirected to login, that's expected behaviour for unauthenticated users
        const url = page.url();
        const isLoginPage = url.includes('login') || url === '/';

        // This is a placeholder - with auth, we'd check for the prompt input form
        expect(isLoginPage).toBeTruthy();
    });

    test('should show prompt optimizer history page', async ({ page }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Should redirect to login for unauthenticated users
        const url = page.url();
        const isLoginPage = url.includes('login') || url === '/';

        expect(isLoginPage).toBeTruthy();
    });
});

// These tests will work once we have authentication helpers
test.describe.skip('Prompt Optimizer - Full Journey (requires auth)', () => {
    test.beforeEach(async ({ page }) => {
        // TODO: Add authentication helper
        // await authenticateUser(page);
    });

    test('should submit a prompt and receive framework selection', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer');

        // Fill in the prompt
        const promptInput = page.getByLabel(/prompt|enter your prompt/i);
        await promptInput.fill('Help me write better code documentation');

        // Submit the form
        const submitButton = page.getByRole('button', {
            name: /submit|optimise/i,
        });
        await submitButton.click();

        // Wait for processing
        await page.waitForLoadState('networkidle');

        // Should see a status indicator or framework selection
        const statusBadge = page.getByTestId('status-badge');
        await expect(statusBadge).toBeVisible({ timeout: 10000 });
    });

    test('should display framework selection after processing', async ({
        page,
    }) => {
        // TODO: Create a prompt run via API first, then navigate to show page

        // For now, placeholder test
        await page.goto('/prompt-optimizer');
        expect(page.url()).toContain('/prompt-optimizer');
    });

    test('should answer clarifying questions', async ({ page }) => {
        // TODO: Navigate to a prompt run in 'answering_questions' state

        // Expected flow:
        // 1. See current question
        // 2. Enter answer in textarea using getByLabel
        // 3. Click Submit Answer using getByTestId('submit-answer-button')
        // 4. See next question or final result

        await page.goto('/prompt-optimizer');
        expect(page.url()).toContain('/prompt-optimizer');
    });

    test('should skip a question', async ({ page }) => {
        // TODO: Navigate to a prompt run in 'answering_questions' state

        // Expected flow:
        // 1. See current question
        // 2. Click Skip Question button using getByTestId('skip-question-button')
        // 3. See next question or final result

        await page.goto('/prompt-optimizer');
        expect(page.url()).toContain('/prompt-optimizer');
    });

    test('should display optimised prompt when complete', async ({ page }) => {
        // TODO: Navigate to a completed prompt run

        // Expected flow:
        // 1. See optimised prompt using getByTestId('optimized-prompt-display')
        // 2. See copy to clipboard button using getByTestId('copy-prompt-button')
        // 3. See framework selection using getByTestId('framework-selection-display')

        await page.goto('/prompt-optimizer');
        expect(page.url()).toContain('/prompt-optimizer');
    });

    test('should copy optimised prompt to clipboard', async ({ page }) => {
        // TODO: Navigate to a completed prompt run

        // Expected flow:
        // 1. Click copy button using getByTestId('copy-prompt-button')
        // 2. Verify clipboard contains prompt text from getByTestId('optimized-prompt-text')
        // 3. See success feedback (button text changes to "Copied!")

        await page.goto('/prompt-optimizer');
        expect(page.url()).toContain('/prompt-optimizer');
    });

    test('should retry a failed prompt run', async ({ page }) => {
        // TODO: Navigate to a failed prompt run

        // Expected flow:
        // 1. See error message
        // 2. Click retry button
        // 3. Prompt is resubmitted

        await page.goto('/prompt-optimizer');
        expect(page.url()).toContain('/prompt-optimizer');
    });

    test('should use voice input for prompt', async ({ page }) => {
        await page.goto('/prompt-optimizer');

        // Look for voice input button
        const voiceButton = page.getByRole('button', {
            name: /voice|microphone/i,
        });

        if (await voiceButton.isVisible().catch(() => false)) {
            // Voice input feature is present
            await expect(voiceButton).toBeEnabled();
        }
    });

    test('should view prompt history', async ({ page }) => {
        await page.goto('/prompt-optimizer-history');

        // Should see a list or table of previous prompts
        const heading = page.getByRole('heading', {
            name: /history|previous prompts/i,
        });
        await expect(heading).toBeVisible();
    });

    test('should navigate from history to a specific prompt', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer-history');

        // Find and click on a prompt in the history
        const promptLink = page.locator('[href*="/prompt-optimizer/"]').first();

        if (await promptLink.isVisible().catch(() => false)) {
            await promptLink.click();

            // Should navigate to the prompt show page
            await expect(page).toHaveURL(/\/prompt-optimizer\/[a-z0-9-]+/);
        }
    });
});
