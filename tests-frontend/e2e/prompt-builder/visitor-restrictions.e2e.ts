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

        // Click the task description edit button
        const editButton = authenticatedPage.getByTestId(
            'edit-task-description-button',
        );
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await authenticatedPage.waitForTimeout(300);

        // Verify edit mode entered (textarea should be in edit state)
        const textarea = authenticatedPage.locator('textarea').first();
        await expect(textarea).toBeVisible({ timeout: 2000 });

        // No modal should appear for authenticated user
        const modal = authenticatedPage
            .getByTestId('visitor-limit-modal')
            .first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);
    });

    test('guest visitor can edit task description if no prior completions', async ({
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

        // Click the task description edit button
        const editButton = page.getByTestId('edit-task-description-button');
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await page.waitForTimeout(300);

        // Verify edit mode entered
        const textarea = page.locator('textarea').first();
        await expect(textarea).toBeVisible({ timeout: 2000 });

        // No modal should appear for guest without completed prompts
        const modal = page.getByTestId('visitor-limit-modal');
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);
    });

    test('guest visitor is restricted from editing task description after completion', async ({
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

        // Click the task description edit button
        const editButton = page.getByTestId('edit-task-description-button');
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await page.waitForTimeout(300);

        // Verify visitor limit modal appears (should restrict editing)
        const modal = page.getByTestId('visitor-limit-modal').first();
        await expect(modal).toBeVisible({ timeout: 2000 });

        // Verify edit mode NOT entered (textarea should not be in edit state)
        const editTextarea = page.locator('textarea#task-description-edit');
        const isEditActive = await editTextarea.isVisible().catch(() => false);
        expect(isEditActive).toBe(false);
    });

    test('visitor limit modal shows account creation messaging when editing restricted task', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create prompt run with 2_completed state (guest has completed a prompt)
        const promptRunId = await createTestPromptRun(page, '2_completed');

        // Navigate to prompt run
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Your Task tab
        await page.getByTestId('tab-button-task').click();
        await page.waitForTimeout(500);

        // Click the task description edit button to trigger the visitor limit modal
        await page.getByTestId('edit-task-description-button').click();
        await page.waitForTimeout(300);

        // Verify visitor limit modal contains expected messaging
        const modal = page.getByTestId('visitor-limit-modal').first();
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

        // Create prompt run with 2_completed state (guest has completed a prompt)
        const promptRunId = await createTestPromptRun(page, '2_completed');

        // Navigate to prompt run
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Your Task tab
        await page.getByTestId('tab-button-task').click();
        await page.waitForTimeout(500);

        // Click the task description edit button to trigger visitor limit modal
        await page.getByTestId('edit-task-description-button').click();
        await page.waitForTimeout(300);

        // Verify visitor limit modal appeared
        const visitorLimitModal = page
            .getByTestId('visitor-limit-modal')
            .first();
        await expect(visitorLimitModal).toBeVisible({ timeout: 2000 });

        // Click "Create account" button in the visitor limit modal
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

        // Create prompt run with 2_completed state (guest has completed a prompt)
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

        // Click the task description edit button
        const editButton = page.getByTestId('edit-task-description-button');
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await page.waitForTimeout(300);

        // Verify visitor limit modal appeared
        const modal = page.getByTestId('visitor-limit-modal').first();
        await expect(modal).toBeVisible({ timeout: 2000 });

        // Click cancel/close button on the modal
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

    test('guest visitor can edit pre-analysis questions if no prior completions', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create a prompt run with 1_completed state (guest has NOT completed any prompts)
        const promptRunId = await createTestPromptRun(page, '1_completed');

        // Navigate to prompt builder - same visitor_id cookie will be sent
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Your Task tab (where pre-analysis questions appear)
        const taskTab = page.getByTestId('tab-button-task');
        await expect(taskTab).toBeVisible({ timeout: 5000 });
        await taskTab.click();
        await page.waitForTimeout(500);

        // Verify pre-analysis questions component is visible
        // Pre-analysis questions appear on the Your Task tab and auto-enter edit mode
        // when there are no answers yet
        const preAnalysisSection = page.getByTestId('pre-analysis');
        await expect(preAnalysisSection).toBeVisible({ timeout: 5000 });

        // Verify edit controls are visible (form inputs for pre-analysis questions)
        // The component will be in edit mode, so form controls should be visible
        const editControls = preAnalysisSection.locator(
            'input, select, textarea',
        );
        const firstControl = editControls.first();
        await expect(firstControl).toBeVisible({ timeout: 2000 });

        // No visitor limit modal should appear for guest without completed prompts
        const modal = page.getByTestId('visitor-limit-modal');
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);
    });
});

test.describe('Visitor Restrictions - Clarifying Questions Edit', () => {
    test('authenticated user can edit clarifying question answers on Questions tab', async ({
        authenticatedPage,
    }) => {
        // Authenticated users should have no restrictions
        // Clarifying questions appear on the Questions tab during workflow stage 1+
        await setupAndNavigateToPromptRun(authenticatedPage, '2_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab (where clarifying questions appear)
        const questionsTab = authenticatedPage.getByTestId(
            'tab-button-questions',
        );
        await expect(questionsTab).toBeVisible({ timeout: 5000 });
        await questionsTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Click the clarifying answers edit button
        const editButton = authenticatedPage.getByTestId(
            'edit-clarifying-answers-button',
        );
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await authenticatedPage.waitForTimeout(300);

        // Verify edit mode entered
        const textarea = authenticatedPage
            .locator('textarea[id^="bulk-answer-"]')
            .first();
        await expect(textarea).toBeVisible({ timeout: 2000 });

        // No modal should appear for authenticated user
        const modal = authenticatedPage
            .getByTestId('visitor-limit-modal')
            .first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);
    });

    test('guest visitor is restricted from editing clarifying question answers after completion', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create first prompt run to mark this visitor as having completed a prompt
        await createTestPromptRun(page, '2_completed');

        // Create second prompt run to test restrictions
        // (guest should be restricted from editing clarifying questions on this new prompt)
        const promptRunId = await createTestPromptRun(page, '2_completed');

        // Navigate to second prompt run
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Questions tab (where clarifying questions appear)
        const questionsTab = page.getByTestId('tab-button-questions');
        await expect(questionsTab).toBeVisible({ timeout: 5000 });
        await questionsTab.click();
        await page.waitForTimeout(500);

        // Click the clarifying answers edit button
        const editButton = page.getByTestId('edit-clarifying-answers-button');
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await page.waitForTimeout(300);

        // Verify visitor limit modal appears (should restrict editing)
        const modal = page.getByTestId('visitor-limit-modal').first();
        await expect(modal).toBeVisible({ timeout: 2000 });

        // The presence of the modal indicates edit was blocked
        expect(modal).toBeTruthy();
    });

    test('API fallback prevents submission if guest bypasses modal for clarifying questions', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create first prompt run to mark this visitor as having completed a prompt
        await createTestPromptRun(page, '2_completed');

        // Create second prompt run to test restrictions
        // Guest should be restricted from editing clarifying question answers on this prompt
        const promptRunId = await createTestPromptRun(page, '2_completed');

        // Navigate to prompt run
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Attempt to bypass the UI restriction by calling the API directly via JavaScript
        // This tests that backend validation prevents edits even if the UI modal is bypassed
        const result = await page.evaluate(async () => {
            // Try to submit edited clarifying answers directly (simulating someone who bypasses the UI)
            try {
                // Attempt to submit edited answers to the clarifying-answers API endpoint
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

        // Verify backend validation prevented the submission (expect 403 Forbidden or 404 Not Found)
        // The backend checks visitor completion status on API submission as a fallback
        expect([403, 404]).toContain(result.status);
        expect(result.allowed).toBe(false);
    });
});

test.describe('Visitor Restrictions - Alternative Frameworks', () => {
    test('authenticated user can use alternative framework on Framework tab', async ({
        authenticatedPage,
    }) => {
        // Authenticated user should have no restrictions for switching frameworks
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Framework tab (where alternative frameworks can be selected)
        const frameworkTab = authenticatedPage.getByTestId(
            'tab-button-framework',
        );
        await expect(frameworkTab).toBeVisible({ timeout: 5000 });
        await frameworkTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Find an alternative framework button (should be able to click without modal)
        const useFrameworkButton = authenticatedPage
            .getByRole('button', { name: /use this framework/i })
            .first();
        await expect(useFrameworkButton).toBeVisible({ timeout: 5000 });

        // No visitor limit modal should appear for authenticated user
        const modal = authenticatedPage
            .getByTestId('visitor-limit-modal')
            .first();
        const isModalVisible = await modal.isVisible().catch(() => false);
        expect(isModalVisible).toBe(false);
    });

    test('guest visitor can use alternative framework if no prior completions', async ({
        page,
    }) => {
        // Establish visitor session by visiting a simple page
        await page.goto('/gb');
        await page.waitForLoadState('domcontentloaded');

        // Create prompt run with 1_completed state (guest has NOT completed any prompts)
        const promptRunId = await createTestPromptRun(page, '1_completed');

        // Navigate to prompt builder
        await page.goto(`/gb/prompt-builder/${promptRunId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Framework tab (where alternative frameworks can be selected)
        const frameworkTab = page.getByTestId('tab-button-framework');
        await expect(frameworkTab).toBeVisible({ timeout: 5000 });
        await frameworkTab.click();
        await page.waitForTimeout(500);

        // Find an alternative framework button
        const useFrameworkButton = page
            .getByRole('button', { name: /use this framework/i })
            .first();
        await expect(useFrameworkButton).toBeVisible({ timeout: 5000 });

        // No visitor limit modal should appear for guest without completed prompts
        const modal = page.getByTestId('visitor-limit-modal').first();
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
        // (guest should be restricted from switching frameworks on this new prompt)
        const secondPromptId = await createTestPromptRun(page, '1_completed');

        // Navigate to second prompt
        await page.goto(`/gb/prompt-builder/${secondPromptId}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);

        // Navigate to Framework tab (where alternative frameworks can be selected)
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

        // Verify visitor limit modal appears (should restrict framework switching)
        const modal = page.getByTestId('visitor-limit-modal').first();
        await expect(modal).toBeVisible({ timeout: 2000 });

        // Verify the modal shows account creation messaging
        const createAccountButton = page.getByRole('button', {
            name: /create.*account|register/i,
        });
        await expect(createAccountButton).toBeVisible({ timeout: 2000 });
    });
});
