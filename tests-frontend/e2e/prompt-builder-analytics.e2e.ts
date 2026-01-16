import { expect, setupAndNavigateToPromptRun, test } from './fixtures';

/**
 * E2E Tests for PromptBuilder Analytics
 *
 * Tests analytics tracking for:
 * 1. Tab navigation events (manual switches only)
 * 2. Question answering events (one-at-a-time and bulk modes)
 * 3. Auto-save on blur functionality
 *
 * These tests verify that analytics events are properly tracked in the
 * analytics_events table with correct properties.
 *
 * NOTE: Analytics consent is automatically granted by the authenticatedPage
 * fixture, so no manual consent setup is needed in these tests.
 */

test.describe('PromptBuilder Analytics - Tab Navigation', () => {
    test('should NOT track page_view event when switching to Questions tab', async ({
        authenticatedPage,
    }) => {
        // Create a prompt run in 1_completed state
        await setupAndNavigateToPromptRun(authenticatedPage, '1_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Get initial page_view count for this page
        const initialPageViews = await authenticatedPage.evaluate(
            async (path: string) => {
                const response = await fetch(
                    `/test/analytics-events?event_name=page_view&page_path=${encodeURIComponent(path)}`,
                    {
                        headers: {
                            'X-Test-Auth': 'playwright-e2e-tests',
                        },
                    },
                );
                return response.json();
            },
            authenticatedPage.url(),
        );

        const initialPageViewCount = initialPageViews.length;

        // Click on Questions tab
        const questionsTab = authenticatedPage.getByTestId(
            'tab-button-questions',
        );
        await questionsTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Verify no new page_view events were created
        const finalPageViews = await authenticatedPage.evaluate(
            async (path: string) => {
                const response = await fetch(
                    `/test/analytics-events?event_name=page_view&page_path=${encodeURIComponent(path)}`,
                    {
                        headers: {
                            'X-Test-Auth': 'playwright-e2e-tests',
                        },
                    },
                );
                return response.json();
            },
            authenticatedPage.url(),
        );

        // Should not have any new page_view events
        expect(finalPageViews.length).toBe(initialPageViewCount);
    });
});

test.describe('PromptBuilder Analytics - Question Answering (One-at-a-time)', () => {
    test('should track question_answered event when answering sequentially', async ({
        authenticatedPage,
    }) => {
        // Create a prompt run in 1_completed state (questions are available)
        const promptRunId = await setupAndNavigateToPromptRun(
            authenticatedPage,
            '1_completed',
        );

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        const questionsTab = authenticatedPage.getByTestId(
            'tab-button-questions',
        );
        await questionsTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Wait for the question form to be visible
        const questionForm = authenticatedPage.locator(
            'textarea[placeholder*="Type your answer"]',
        );
        await expect(questionForm.first()).toBeVisible({ timeout: 5000 });

        // Answer the first question
        const testAnswer = 'This is my answer to the clarifying question.';
        await questionForm.first().fill(testAnswer);
        await authenticatedPage.waitForTimeout(300);

        // Click "Next" or submit button to save the answer
        const nextButton = authenticatedPage.getByRole('button', {
            name: /next|submit/i,
        });
        await nextButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Wait for analytics batch to flush (5s batch delay + 1s buffer)
        await authenticatedPage.waitForTimeout(6000);

        // Verify the analytics event was created
        const analyticsEvents = await authenticatedPage.evaluate(
            async (promptRunIdParam: number) => {
                const response = await fetch(
                    `/test/analytics-events?prompt_run_id=${promptRunIdParam}&event_name=question_answered`,
                    {
                        headers: {
                            'X-Test-Auth': 'playwright-e2e-tests',
                        },
                    },
                );
                return response.json();
            },
            promptRunId,
        );

        // Should have at least one question_answered event
        expect(analyticsEvents.length).toBeGreaterThan(0);

        const recentEvent = analyticsEvents[analyticsEvents.length - 1];

        // Verify event properties
        expect(recentEvent.name).toBe('question_answered');
        expect(recentEvent.properties.question_index).toBe(0);
        expect(recentEvent.properties.answer_length).toBe(testAnswer.length);
        expect(recentEvent.properties.prompt_run_id).toBe(promptRunId);
        expect(recentEvent.properties.total_questions).toBeGreaterThan(0);
        expect(recentEvent.properties.answered_count).toBeGreaterThan(0);
        expect(recentEvent.prompt_run_id).toBe(promptRunId);
    });

    test('should track multiple question_answered events in sequence', async ({
        authenticatedPage,
    }) => {
        const promptRunId = await setupAndNavigateToPromptRun(
            authenticatedPage,
            '1_completed',
        );

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        const questionsTab = authenticatedPage.getByTestId(
            'tab-button-questions',
        );
        await questionsTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Wait for the question form
        const questionForm = authenticatedPage.locator(
            'textarea[placeholder*="Type your answer"]',
        );
        await expect(questionForm.first()).toBeVisible({ timeout: 5000 });

        // Answer first question
        await questionForm.first().fill('First answer');
        const nextButton = authenticatedPage.getByRole('button', {
            name: /next|submit/i,
        });
        await nextButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Answer second question (if available)
        const secondQuestionForm = authenticatedPage
            .locator('textarea[placeholder*="Type your answer"]')
            .first();
        const isSecondQuestionVisible = await secondQuestionForm
            .isVisible()
            .catch(() => false);

        if (isSecondQuestionVisible) {
            await secondQuestionForm.fill('Second answer');
            const nextButton2 = authenticatedPage.getByRole('button', {
                name: /next|submit/i,
            });
            await nextButton2.click();
            await authenticatedPage.waitForTimeout(500);

            // Wait for analytics batch to flush (5s batch delay + 1s buffer)
            await authenticatedPage.waitForTimeout(6000);

            // Verify we have 2 question_answered events
            const analyticsEvents = await authenticatedPage.evaluate(
                async (promptRunIdParam: number) => {
                    const response = await fetch(
                        `/test/analytics-events?prompt_run_id=${promptRunIdParam}&event_name=question_answered`,
                        {
                            headers: {
                                'X-Test-Auth': 'playwright-e2e-tests',
                            },
                        },
                    );
                    return response.json();
                },
                promptRunId,
            );

            expect(analyticsEvents.length).toBeGreaterThanOrEqual(2);

            // Verify the question indices are different
            const indices = analyticsEvents.map(
                (e: any) => e.properties.question_index,
            );
            expect(indices).toContain(0);
            expect(indices).toContain(1);
        }
    });
});

test.describe('PromptBuilder Analytics - Question Answering (Bulk Mode)', () => {
    test('should track question_answered event on blur in "View all questions" mode', async ({
        authenticatedPage,
    }) => {
        const promptRunId = await setupAndNavigateToPromptRun(
            authenticatedPage,
            '1_completed',
        );

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        const questionsTab = authenticatedPage.getByTestId(
            'tab-button-questions',
        );
        await questionsTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Click "View all questions" button
        const viewAllButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        await expect(viewAllButton).toBeVisible({ timeout: 5000 });
        await viewAllButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Find the first question textarea in bulk mode
        const firstBulkTextarea = authenticatedPage
            .locator('textarea[id^="bulk-answer-"]')
            .first();
        await expect(firstBulkTextarea).toBeVisible({ timeout: 5000 });

        // Type an answer and blur the textarea
        const testAnswer = 'This is my answer in bulk mode.';
        await firstBulkTextarea.fill(testAnswer);
        await authenticatedPage.waitForTimeout(300);

        // Blur the textarea by clicking outside
        await authenticatedPage.getByText(/clarifying questions/i).click();
        await authenticatedPage.waitForTimeout(1000); // Wait for auto-save

        // Wait for analytics batch to flush (5s batch delay + 1s buffer)
        await authenticatedPage.waitForTimeout(6000);

        // Verify the analytics event was created
        const analyticsEvents = await authenticatedPage.evaluate(
            async (promptRunIdParam: number) => {
                const response = await fetch(
                    `/test/analytics-events?prompt_run_id=${promptRunIdParam}&event_name=question_answered`,
                    {
                        headers: {
                            'X-Test-Auth': 'playwright-e2e-tests',
                        },
                    },
                );
                return response.json();
            },
            promptRunId,
        );

        // Should have at least one question_answered event
        expect(analyticsEvents.length).toBeGreaterThan(0);

        const recentEvent = analyticsEvents[analyticsEvents.length - 1];

        // Verify event properties
        expect(recentEvent.name).toBe('question_answered');
        expect(recentEvent.properties.question_index).toBe(0);
        expect(recentEvent.properties.answer_length).toBe(testAnswer.length);
        expect(recentEvent.properties.prompt_run_id).toBe(promptRunId);
    });

    test('should NOT track or save when blurring without changes', async ({
        authenticatedPage,
    }) => {
        const promptRunId = await setupAndNavigateToPromptRun(
            authenticatedPage,
            '1_completed',
        );

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        const questionsTab = authenticatedPage.getByTestId(
            'tab-button-questions',
        );
        await questionsTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Switch to bulk mode
        const viewAllButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        await viewAllButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Get initial count of question_answered events
        const initialEvents = await authenticatedPage.evaluate(
            async (promptRunIdParam: number) => {
                const response = await fetch(
                    `/test/analytics-events?prompt_run_id=${promptRunIdParam}&event_name=question_answered`,
                    {
                        headers: {
                            'X-Test-Auth': 'playwright-e2e-tests',
                        },
                    },
                );
                return response.json();
            },
            promptRunId,
        );

        const initialCount = initialEvents.length;

        // Click into the first textarea and blur without making changes
        const firstBulkTextarea = authenticatedPage
            .locator('textarea[id^="bulk-answer-"]')
            .first();
        await firstBulkTextarea.click();
        await authenticatedPage.waitForTimeout(200);

        // Blur by clicking outside
        await authenticatedPage.getByText(/clarifying questions/i).click();
        await authenticatedPage.waitForTimeout(1000);

        // Verify no new events were created
        const finalEvents = await authenticatedPage.evaluate(
            async (promptRunIdParam: number) => {
                const response = await fetch(
                    `/test/analytics-events?prompt_run_id=${promptRunIdParam}&event_name=question_answered`,
                    {
                        headers: {
                            'X-Test-Auth': 'playwright-e2e-tests',
                        },
                    },
                );
                return response.json();
            },
            promptRunId,
        );

        // Should have the same count as before
        expect(finalEvents.length).toBe(initialCount);
    });

    test('should track multiple answers on blur in bulk mode', async ({
        authenticatedPage,
    }) => {
        const promptRunId = await setupAndNavigateToPromptRun(
            authenticatedPage,
            '1_completed',
        );

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        const questionsTab = authenticatedPage.getByTestId(
            'tab-button-questions',
        );
        await questionsTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Switch to bulk mode
        const viewAllButton = authenticatedPage.getByRole('button', {
            name: /view all questions|all questions/i,
        });
        await viewAllButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Get all question textareas
        const bulkTextareas = authenticatedPage.locator(
            'textarea[id^="bulk-answer-"]',
        );
        const count = await bulkTextareas.count();

        // Answer the first two questions (if available)
        const answersToTest = Math.min(2, count);

        for (let i = 0; i < answersToTest; i++) {
            const textarea = bulkTextareas.nth(i);
            await textarea.fill(`Answer ${i + 1}`);
            await authenticatedPage.waitForTimeout(300);

            // Blur by clicking on the heading
            await authenticatedPage.getByText(/clarifying questions/i).click();
            await authenticatedPage.waitForTimeout(1000);
        }

        // Wait for analytics batch to flush (5s batch delay + 1s buffer)
        await authenticatedPage.waitForTimeout(6000);

        // Verify we have at least answersToTest events
        const analyticsEvents = await authenticatedPage.evaluate(
            async (promptRunIdParam: number) => {
                const response = await fetch(
                    `/test/analytics-events?prompt_run_id=${promptRunIdParam}&event_name=question_answered`,
                    {
                        headers: {
                            'X-Test-Auth': 'playwright-e2e-tests',
                        },
                    },
                );
                return response.json();
            },
            promptRunId,
        );

        expect(analyticsEvents.length).toBeGreaterThanOrEqual(answersToTest);

        // Verify the question indices
        const recentEvents = analyticsEvents.slice(-answersToTest);
        const indices = recentEvents.map(
            (e: any) => e.properties.question_index,
        );

        for (let i = 0; i < answersToTest; i++) {
            expect(indices).toContain(i);
        }
    });
});

test.describe('PromptBuilder Analytics - Edit Mode', () => {
    test('should NOT track or auto-save when editing answers after completion', async ({
        authenticatedPage,
    }) => {
        // Create a completed prompt run (2_completed)
        const promptRunId = await setupAndNavigateToPromptRun(
            authenticatedPage,
            '2_completed',
        );

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        const questionsTab = authenticatedPage.getByTestId(
            'tab-button-questions',
        );
        await questionsTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Click "Edit" button to enter edit mode
        const editButton = authenticatedPage.getByRole('button', {
            name: /edit/i,
        });
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await authenticatedPage.waitForTimeout(500);

        // Get initial count of question_answered events
        const initialEvents = await authenticatedPage.evaluate(
            async (promptRunIdParam: number) => {
                const response = await fetch(
                    `/test/analytics-events?prompt_run_id=${promptRunIdParam}&event_name=question_answered`,
                    {
                        headers: {
                            'X-Test-Auth': 'playwright-e2e-tests',
                        },
                    },
                );
                return response.json();
            },
            promptRunId,
        );

        const initialCount = initialEvents.length;

        // Find the first question textarea in edit mode
        const firstEditTextarea = authenticatedPage
            .locator('textarea[id^="bulk-answer-"]')
            .first();
        await expect(firstEditTextarea).toBeVisible({ timeout: 5000 });

        // Modify the answer
        await firstEditTextarea.fill('Modified answer in edit mode');
        await authenticatedPage.waitForTimeout(300);

        // Blur the textarea
        await authenticatedPage.getByText(/clarifying questions/i).click();
        await authenticatedPage.waitForTimeout(1000);

        // Verify NO new events were created (edit mode should not auto-track)
        const finalEvents = await authenticatedPage.evaluate(
            async (promptRunIdParam: number) => {
                const response = await fetch(
                    `/test/analytics-events?prompt_run_id=${promptRunIdParam}&event_name=question_answered`,
                    {
                        headers: {
                            'X-Test-Auth': 'playwright-e2e-tests',
                        },
                    },
                );
                return response.json();
            },
            promptRunId,
        );

        // Should have the same count as before (no new events in edit mode)
        expect(finalEvents.length).toBe(initialCount);
    });

    test('should show submit button in edit mode without auto-saving', async ({
        authenticatedPage,
    }) => {
        await setupAndNavigateToPromptRun(authenticatedPage, '2_completed');

        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate to Questions tab
        const questionsTab = authenticatedPage.getByTestId(
            'tab-button-questions',
        );
        await questionsTab.click();
        await authenticatedPage.waitForTimeout(500);

        // Click "Edit" button
        const editButton = authenticatedPage.getByRole('button', {
            name: /edit/i,
        });
        await expect(editButton).toBeVisible({ timeout: 5000 });
        await editButton.click();
        await authenticatedPage.waitForTimeout(1000);

        // Verify submit button is visible (for creating child run)
        // Button text is "Optimise prompt with edited answers" (British spelling)
        // There are 2 buttons with this text - one at top (may be disabled) and one at bottom
        // We want the enabled, visible one
        const submitEditedButton = authenticatedPage
            .getByRole('button', {
                name: /optimise prompt/i,
            })
            .last();
        await expect(submitEditedButton).toBeVisible({ timeout: 10000 });

        // Verify cancel button is also visible
        const cancelButton = authenticatedPage.getByRole('button', {
            name: /cancel/i,
        });
        await expect(cancelButton).toBeVisible({ timeout: 5000 });
    });
});
