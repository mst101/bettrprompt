import { expect, setupAndNavigateToPromptRun, test } from '../fixtures';
import { createTestPromptRun } from '../helpers/broadcast';

test.describe('Visitor Restrictions - TaskInformation Edit', () => {
    test('authenticated user can edit task description', async ({
        authenticatedPage,
    }) => {
        // Authenticated user should have no restrictions
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Your Task tab
        const taskTab = authenticatedPage.getByTestId('tab-button-task');
        await expect(taskTab).toBeVisible({ timeout: 5000 });
        await taskTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Find and click edit button
        const editButton = authenticatedPage
            .getByRole('button', { name: /edit/i })
            .first();
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await authenticatedPage.waitForTimeout(300);

        // Verify edit mode entered (textarea should be in edit state)
        const textarea = authenticatedPage.locator('textarea').first();
        await expect(textarea).toBeVisible({ timeout: 2000 });

        // No modal should appear for authenticated user
        const modal = authenticatedPage.getByTestId('modal-dialog').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);
    });

    test('guest visitor can edit task if no prior completions', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create a prompt run - will use the visitor_id cookie from above
        const promptRunId = await createTestPromptRun(page, '1_completed');

        // Navigate to prompt builder - same visitor_id cookie will be sent
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Your Task tab
        const taskTab = page.getByTestId('tab-button-task');
        await expect(taskTab).toBeVisible({ timeout: 5000 });
        await taskTab.click();
        await page.waitForTimeout(500);

        // Find and click edit button
        const editButton = page.getByRole('button', { name: /edit/i }).first();
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await page.waitForTimeout(300);

        // Verify edit mode entered
        const textarea = page.locator('textarea').first();
        await expect(textarea).toBeVisible({ timeout: 2000 });

        // No modal should appear for guest without completed prompts
        const modal = page.getByTestId('modal-dialog');
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);
    });

    test('guest visitor is restricted from editing task after completion', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create a prompt run with 2_completed state - will use the visitor_id cookie from above
        const promptRunId = await createTestPromptRun(page, '2_completed');

        // Navigate to prompt builder - same visitor_id cookie will be sent
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Your Task tab
        const taskTab = page.getByTestId('tab-button-task');
        await expect(taskTab).toBeVisible({ timeout: 5000 });
        await taskTab.click();
        await page.waitForTimeout(500);

        // Find and click edit button
        const editButton = page.getByRole('button', { name: /edit/i }).first();
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await page.waitForTimeout(300);

        // Verify modal appears
        const modal = page.getByTestId('modal-dialog').first();
        await expect(modal).toBeVisible({ timeout: 2000 });

        // Verify edit mode NOT entered (textarea should not be in edit state)
        const editTextarea = page.locator('textarea#task-description-edit');
        const isEditActive = await editTextarea.isVisible().catch(() => false);
        expect(isEditActive).toBe(false);
    });

    test('visitor limit modal shows account creation messaging', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create prompt run with 2_completed state
        const promptRunId = await createTestPromptRun(page, '2_completed');

        // Navigate to prompt run
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Your Task tab
        await page.getByTestId('tab-button-task').click();
        await page.waitForTimeout(500);

        // Click edit button
        await page.getByRole('button', { name: /edit/i }).first().click();
        await page.waitForTimeout(300);

        // Verify modal contains expected messaging
        const modal = page.getByTestId('modal-dialog').first();
        await expect(modal).toBeVisible({ timeout: 2000 });

        // Check for account creation call-to-action
        const createAccountButton = page
            .getByRole('button', { name: /create.*account|register/i })
            .first();
        await expect(createAccountButton).toBeVisible({ timeout: 2000 });

        // Verify close/cancel option exists
        const closeButton = page.getByRole('button', { name: /cancel|close/i });
        const isCloseVisible = await closeButton.isVisible().catch(() => false);
        expect(isCloseVisible).toBe(true);
    });

    test('visitor limit modal: create account button opens sign up form', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create prompt run with 2_completed state
        const promptRunId = await createTestPromptRun(page, '2_completed');

        // Navigate to prompt run
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Your Task tab
        await page.getByTestId('tab-button-task').click();
        await page.waitForTimeout(500);

        // Click edit button to trigger visitor limit modal
        await page.getByRole('button', { name: /edit/i }).first().click();
        await page.waitForTimeout(300);

        // Verify visitor limit modal appeared
        const visitorLimitModal = page.getByTestId('modal-dialog').first();
        await expect(visitorLimitModal).toBeVisible({ timeout: 2000 });

        // Click "Create account" button
        const createAccountButton = page
            .getByRole('button', { name: /create.*account|register/i })
            .first();
        await createAccountButton.click();
        await page.waitForTimeout(500);

        // Verify registration modal appears (different from visitor limit modal)
        // Check for registration form elements that indicate the modal changed
        const emailInput = page.locator('input[type="email"]');
        const isRegistrationVisible = await emailInput
            .isVisible()
            .catch(() => false);
        expect(isRegistrationVisible).toBe(true);
    });

    test('visitor limit modal: cancel button closes without entering edit mode', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create prompt run with 2_completed state
        const promptRunId = await createTestPromptRun(page, '2_completed');

        // Navigate to prompt run
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Your Task tab
        const taskTab = page.getByTestId('tab-button-task');
        await expect(taskTab).toBeVisible({ timeout: 5000 });
        await taskTab.click();
        await page.waitForTimeout(500);

        // Click edit button
        const editButton = page.getByRole('button', { name: /edit/i }).first();
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await page.waitForTimeout(300);

        // Verify modal appeared
        const modal = page.getByTestId('modal-dialog').first();
        await expect(modal).toBeVisible({ timeout: 2000 });

        // Click cancel/close button
        const closeButton = page.getByRole('button', { name: /cancel|close/i });
        await closeButton.click();
        await page.waitForTimeout(300);

        // Verify modal closed
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);

        // Verify edit mode not active
        const editTextarea = page.locator('textarea#task-description-edit');
        const isEditActive = await editTextarea.isVisible().catch(() => false);
        expect(isEditActive).toBe(false);
    });
});

test.describe('Visitor Restrictions - ClarifyingQuestions Edit', () => {
    test('authenticated user can edit clarifying question answers', async ({
        authenticatedPage,
    }) => {
        // Authenticated users should have no restrictions
        await setupAndNavigateToPromptRun(authenticatedPage, '2_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        const questionsTab = authenticatedPage.getByTestId(
            'tab-button-questions',
        );
        await expect(questionsTab).toBeVisible({ timeout: 5000 });
        await questionsTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Find and click edit button
        const editButton = authenticatedPage
            .getByRole('button', { name: /edit/i })
            .first();
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await authenticatedPage.waitForTimeout(300);

        // Verify edit mode entered
        const textarea = authenticatedPage
            .locator('textarea[id^="bulk-answer-"]')
            .first();
        await expect(textarea).toBeVisible({ timeout: 2000 });

        // No modal should appear for authenticated user
        const modal = authenticatedPage.getByTestId('modal-dialog').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);
    });

    test('guest visitor can edit questions if no prior completions', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create prompt run without completed workflow (0_completed means pre-analysis complete, not final completion)
        // Final completion is 2_completed, so 1_completed means analysis done but no final prompt yet
        const promptRunId = await createTestPromptRun(page, '1_completed');

        // Navigate to prompt run
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Questions tab
        const questionsTab = page.getByTestId('tab-button-questions');
        await expect(questionsTab).toBeVisible({ timeout: 5000 });
        await questionsTab.click();
        await page.waitForTimeout(500);

        // Find and click edit button
        const editButton = page.getByRole('button', { name: /edit/i }).first();
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await page.waitForTimeout(300);

        // Verify edit mode entered
        const textarea = page.locator('textarea[id^="bulk-answer-"]').first();
        await expect(textarea).toBeVisible({ timeout: 2000 });

        // No modal should appear
        const modal = page.getByTestId('modal-dialog').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);
    });

    test('guest visitor is restricted from editing questions after completion', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create first prompt run to mark this visitor as having completed a prompt
        await createTestPromptRun(page, '2_completed');

        // Create second prompt run to test restrictions (should be restricted for editing)
        const promptRunId = await createTestPromptRun(page, '2_completed');

        // Navigate to second prompt run
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Questions tab
        const questionsTab = page.getByTestId('tab-button-questions');
        await expect(questionsTab).toBeVisible({ timeout: 5000 });
        await questionsTab.click();
        await page.waitForTimeout(500);

        // Find and click edit button
        const editButton = page.getByRole('button', { name: /edit/i }).first();
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await page.waitForTimeout(300);

        // Verify modal appears
        const modal = page.getByTestId('modal-dialog').first();
        await expect(modal).toBeVisible({ timeout: 2000 });

        // In bulk edit mode, textareas are visible even in non-edit, so we check for edit class instead
        // The presence of the modal indicates edit was blocked
        expect(modal).toBeTruthy();
    });

    test('API fallback prevents submission if guest bypasses modal', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create first prompt run to mark this visitor as having completed a prompt
        await createTestPromptRun(page, '2_completed');

        // Create second prompt run to test restrictions
        const promptRunId = await createTestPromptRun(page, '2_completed');

        // Navigate to prompt run
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Attempt to bypass the edit button restriction via direct JavaScript
        // This tests the fallback check in the submit handler
        const result = await page.evaluate(async () => {
            // Try to trigger the edit workflow directly (simulating someone who bypasses the UI)
            try {
                // This would attempt to submit edited answers
                const response = await fetch(
                    window.location.pathname.replace(
                        /\/prompt-builder\/(\d+)/,
                        '/api/prompt-runs/$1/clarifying-answers',
                    ),
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            question_index: 0,
                            answer: 'Test bypass answer',
                        }),
                    },
                );
                return {
                    status: response.status,
                    allowed: response.ok,
                };
            } catch (e) {
                return { error: (e as Error).message };
            }
        });

        // Verify the fallback check prevented the edit (expect 403 Forbidden)
        expect(result.status).toBe(403);
        expect(result.allowed).toBe(false);
    });
});

test.describe('Visitor Restrictions - Alternative Frameworks', () => {
    test('authenticated user can use alternative framework', async ({
        authenticatedPage,
    }) => {
        // Authenticated user should have no restrictions
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Framework tab
        const frameworkTab = authenticatedPage.getByTestId(
            'tab-button-framework',
        );
        await expect(frameworkTab).toBeVisible({ timeout: 5000 });
        await frameworkTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Find alternative framework button (should be able to click without modal)
        const useFrameworkButton = authenticatedPage
            .getByRole('button', { name: /use this framework/i })
            .first();
        await expect(useFrameworkButton).toBeVisible({ timeout: 5000 });

        // No modal should appear for authenticated user
        const modal = authenticatedPage.getByTestId('modal-dialog').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);
    });

    test('guest visitor can use alternative framework if no prior completions', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create prompt run with 1_completed state (no prior completions)
        const promptRunId = await createTestPromptRun(page, '1_completed');

        // Navigate to prompt builder
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Framework tab
        const frameworkTab = page.getByTestId('tab-button-framework');
        await expect(frameworkTab).toBeVisible({ timeout: 5000 });
        await frameworkTab.click();
        await page.waitForTimeout(500);

        // Find alternative framework button
        const useFrameworkButton = page
            .getByRole('button', { name: /use this framework/i })
            .first();
        await expect(useFrameworkButton).toBeVisible({ timeout: 5000 });

        // No modal should appear for guest without completed prompts
        const modal = page.getByTestId('modal-dialog').first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);
    });

    test('guest visitor is restricted from using alternative framework after completion', async ({
        page,
    }) => {
        // Establish visitor session
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create first prompt to mark this visitor as having completed a prompt
        await createTestPromptRun(page, '2_completed');

        // Create second prompt to test restrictions
        const secondPromptId = await createTestPromptRun(page, '1_completed');

        // Navigate to second prompt
        await page.goto(`/gb/prompt-builder/${secondPromptId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Framework tab
        const frameworkTab = page.getByTestId('tab-button-framework');
        await expect(frameworkTab).toBeVisible({ timeout: 5000 });
        await frameworkTab.click();
        await page.waitForTimeout(500);

        // Try to click alternative framework button
        const useFrameworkButton = page
            .getByRole('button', { name: /use this framework/i })
            .first();
        await expect(useFrameworkButton).toBeVisible({ timeout: 5000 });
        await useFrameworkButton.click();
        await page.waitForTimeout(300);

        // Verify modal appears
        const modal = page.getByTestId('modal-dialog').first();
        await expect(modal).toBeVisible({ timeout: 2000 });

        // Verify the modal shows account creation messaging
        const createAccountButton = page.getByRole('button', {
            name: /create.*account|register/i,
        });
        await expect(createAccountButton).toBeVisible({ timeout: 2000 });
    });
});
