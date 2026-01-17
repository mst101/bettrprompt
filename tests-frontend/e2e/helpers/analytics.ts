import type { Page } from '@playwright/test';

/**
 * E2E Test Helpers for Analytics Testing
 *
 * Provides convenient methods for testing analytics functionality,
 * reducing boilerplate and making E2E tests more readable.
 */

/**
 * Get current rating state for a question
 *
 * Useful for verifying ratings without navigating the DOM directly.
 */
export async function getQuestionRatingState(page: Page): Promise<{
    rating: number | null;
    explanation: string | null;
    isSaved: boolean;
}> {
    return await page.evaluate(() => {
        // This would need to be adapted to your actual component structure
        // This is a placeholder implementation
        return {
            rating: null,
            explanation: null,
            isSaved: false,
        };
    });
}

/**
 * Rate a question with stars
 *
 * Simulates user clicking on a star rating.
 */
export async function rateQuestion(
    page: Page,
    rating: number,
    questionIndex: number = 0,
): Promise<void> {
    if (rating < 1 || rating > 5) {
        throw new Error('Rating must be between 1 and 5');
    }

    const starButton = page
        .locator('button[aria-label*="Rate"][aria-label*="stars"]')
        .filter({ hasText: new RegExp(`${rating}`, 'i') });

    // Get the Nth instance if multiple questions
    await starButton.nth(questionIndex).click();

    // Wait for auto-save
    await page.waitForTimeout(500);
}

/**
 * Add explanation to a question rating
 *
 * Fills in and submits an explanation for a question rating.
 */
export async function addExplanation(
    page: Page,
    explanation: string,
    questionIndex: number = 0,
): Promise<void> {
    const textarea = page
        .locator('textarea')
        .filter({ hasText: /add.*explanation|explanation/i });

    await textarea.nth(questionIndex).fill(explanation);
    await page.waitForTimeout(300);

    const submitButton = page.getByRole('button', {
        name: /add explanation/i,
    });

    await submitButton.nth(questionIndex).click();
    await page.waitForTimeout(500);
}

/**
 * Update an existing explanation
 *
 * Modifies and resubmits an explanation for a question rating.
 */
export async function updateExplanation(
    page: Page,
    newExplanation: string,
    questionIndex: number = 0,
): Promise<void> {
    const textarea = page
        .locator('textarea')
        .filter({ hasText: /update.*explanation/i });

    await textarea.nth(questionIndex).fill(newExplanation);
    await page.waitForTimeout(300);

    const updateButton = page.getByRole('button', {
        name: /update explanation/i,
    });

    await updateButton.nth(questionIndex).click();
    await page.waitForTimeout(500);
}

/**
 * Switch to bulk/all questions view
 *
 * Navigates from single-question mode to bulk questions view.
 */
export async function switchToBulkView(page: Page): Promise<void> {
    const viewAllButton = page.getByRole('button', {
        name: /view all questions|all questions/i,
    });

    await viewAllButton.click();
    await page.waitForTimeout(500);
}

/**
 * Switch back to single question view
 *
 * Navigates from bulk view back to single-question mode.
 */
export async function switchToSingleView(page: Page): Promise<void> {
    const backButton = page.getByRole('button', {
        name: /back.*single|one.*question|single.*question/i,
    });

    if (await backButton.isVisible().catch(() => false)) {
        await backButton.click();
        await page.waitForTimeout(500);
    }
}

/**
 * Toggle rating UI for a question in bulk view
 *
 * Shows/hides the rating interface for a specific question in bulk mode.
 */
export async function toggleQuestionRatingUI(
    page: Page,
    questionIndex: number = 0,
): Promise<void> {
    const toggleButton = page
        .getByRole('button', { name: /rate this question|hide rating/i })
        .nth(questionIndex);

    await toggleButton.click();
    await page.waitForTimeout(300);
}

/**
 * Rate a question in bulk view
 *
 * Toggles rating UI and rates a question in bulk questions view.
 */
export async function rateQuestionInBulk(
    page: Page,
    rating: number,
    questionIndex: number = 0,
): Promise<void> {
    // Toggle rating UI open
    const toggleButton = page
        .getByRole('button', { name: /rate this question/i })
        .nth(questionIndex);

    const isExpanded = await page
        .getByRole('button', { name: /hide rating/i })
        .nth(questionIndex)
        .isVisible()
        .catch(() => false);

    if (!isExpanded) {
        await toggleButton.click();
        await page.waitForTimeout(300);
    }

    // Need to find the correct star within the visible rating UI
    const allStars = page.locator(
        'button[aria-label*="Rate"][aria-label*="stars"]',
    );
    const ratingIndex = questionIndex * 5 + (rating - 1); // Rough estimate

    await allStars.nth(ratingIndex).click();
    await page.waitForTimeout(500);
}

/**
 * Navigate to questions tab
 *
 * Switches to the questions section of the prompt builder.
 */
export async function navigateToQuestionsTab(page: Page): Promise<void> {
    const questionsTab = page.getByTestId('tab-button-questions');
    await questionsTab.click();
    await page.waitForTimeout(500);
}

/**
 * Navigate to task/prompt tab
 *
 * Switches to the task/prompt section of the prompt builder.
 */
export async function navigateToTaskTab(page: Page): Promise<void> {
    const taskTab = page.getByTestId('tab-button-task');
    await taskTab.click();
    await page.waitForTimeout(500);
}

/**
 * Wait for rating to be saved
 *
 * Polls the test endpoint to verify a rating was persisted.
 */
export async function waitForRatingToSave(
    page: Page,
    promptRunId: number,
    expectedRating: number,
    timeoutMs: number = 5000,
): Promise<void> {
    const endTime = Date.now() + timeoutMs;

    while (Date.now() < endTime) {
        const rating = await page.evaluate(async (id: number) => {
            try {
                const response = await fetch(`/test/question-analytics/${id}`, {
                    headers: {
                        'X-Test-Auth': 'playwright-e2e-tests',
                    },
                });
                const data = await response.json();
                return data[0]?.user_rating;
            } catch {
                return null;
            }
        }, promptRunId);

        if (rating === expectedRating) {
            return;
        }

        await page.waitForTimeout(500);
    }

    throw new Error(
        `Rating not saved within ${timeoutMs}ms. Expected: ${expectedRating}`,
    );
}

interface AnalyticsRecord {
    [key: string]: unknown;
}

/**
 * Get analytics data for a prompt run
 *
 * Fetches all analytics records for a specific prompt run from test endpoint.
 */
export async function getPromptRunAnalytics(
    page: Page,
    promptRunId: number,
): Promise<AnalyticsRecord[]> {
    return await page.evaluate(async (id: number) => {
        try {
            const response = await fetch(`/test/question-analytics/${id}`, {
                headers: {
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
            });
            return await response.json();
        } catch {
            return [];
        }
    }, promptRunId);
}

/**
 * Verify question was rated with specific value
 *
 * Assertion helper to check a question has the expected rating.
 */
export async function assertQuestionRating(
    page: Page,
    promptRunId: number,
    questionId: string,
    expectedRating: number,
): Promise<void> {
    const analytics = await getPromptRunAnalytics(page, promptRunId);
    const record = analytics.find((a) => a.question_id === questionId);

    if (!record) {
        throw new Error(`No analytics found for question: ${questionId}`);
    }

    if (record.user_rating !== expectedRating) {
        throw new Error(
            `Expected rating ${expectedRating}, got ${record.user_rating}`,
        );
    }
}

/**
 * Verify question has explanation
 *
 * Assertion helper to check a question rating has an explanation.
 */
export async function assertQuestionHasExplanation(
    page: Page,
    promptRunId: number,
    questionId: string,
    expectedExplanation?: string,
): Promise<void> {
    const analytics = await getPromptRunAnalytics(page, promptRunId);
    const record = analytics.find((a) => a.question_id === questionId);

    if (!record?.rating_explanation) {
        throw new Error(`No explanation found for question: ${questionId}`);
    }

    if (
        expectedExplanation &&
        record.rating_explanation !== expectedExplanation
    ) {
        throw new Error(
            `Expected explanation "${expectedExplanation}", got "${record.rating_explanation}"`,
        );
    }
}

/**
 * Check if thank you message is visible
 *
 * Verifies that the thank you message appears after rating submission.
 */
export async function hasThankYouMessage(page: Page): Promise<boolean> {
    const thankYouText = page.getByText(/thank you|grateful|appreciate/i);
    return await thankYouText.isVisible().catch(() => false);
}

/**
 * Wait for thank you message to disappear
 *
 * Waits for the auto-hide timer to dismiss the thank you message.
 */
export async function waitForThankYouMessageToDisappear(
    page: Page,
    timeoutMs: number = 6000,
): Promise<void> {
    const thankYouText = page.getByText(/thank you|grateful|appreciate/i);

    await page.waitForFunction(
        async () => {
            return !(await thankYouText.isVisible().catch(() => true));
        },
        { timeout: timeoutMs },
    );
}

/**
 * Get user's display mode preference
 *
 * Fetches the current display mode preference from user settings.
 */
export async function getDisplayModePreference(page: Page): Promise<string> {
    return await page.evaluate(async () => {
        try {
            const response = await fetch('/api/user/preferences');
            const data = await response.json();
            return data.display_mode || 'single';
        } catch {
            return 'single';
        }
    });
}

/**
 * Verify rating UI visibility
 *
 * Checks that the rating UI elements are visible in the current view.
 */
export async function isRatingUIVisible(page: Page): Promise<boolean> {
    const starButtons = page.locator(
        'button[aria-label*="Rate"][aria-label*="stars"]',
    );
    return await starButtons
        .first()
        .isVisible()
        .catch(() => false);
}

/**
 * Get visible question count in bulk view
 *
 * Returns the number of questions visible in bulk/all questions view.
 */
export async function getVisibleQuestionCount(page: Page): Promise<number> {
    const bulkTextareas = page.locator('textarea[id^="bulk-answer-"]');
    return await bulkTextareas.count();
}

/**
 * Answer a question in single view
 *
 * Fills in the answer textarea in single-question mode.
 */
export async function answerQuestion(
    page: Page,
    answer: string,
): Promise<void> {
    const textarea = page.locator('textarea[placeholder*="Type your answer"]');
    await textarea.first().fill(answer);
    await page.waitForTimeout(300);
}

/**
 * Answer all questions in bulk view
 *
 * Fills in answers for all visible questions in bulk mode.
 */
export async function answerAllQuestionsInBulk(
    page: Page,
    answerText: string,
): Promise<void> {
    const textareas = page.locator('textarea[id^="bulk-answer-"]');
    const count = await textareas.count();

    for (let i = 0; i < count; i++) {
        await textareas.nth(i).fill(`${answerText} ${i + 1}`);
        await page.waitForTimeout(200);
    }
}

/**
 * Submit questions
 *
 * Clicks the submit/continue button to complete question answering.
 */
export async function submitQuestions(page: Page): Promise<void> {
    const submitButton = page.getByRole('button', {
        name: /submit|continue|next|optimise/i,
    });

    await submitButton.click();
    await page.waitForTimeout(1000);
}
