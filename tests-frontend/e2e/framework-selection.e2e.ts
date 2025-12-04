import { expect, test } from './fixtures/personality-user';

/**
 * Framework Selection Tests
 *
 * These tests are designed to persist data to the database to analyse which
 * prompt framework is selected for each of the 16 personality types.
 *
 * Unlike typical e2e tests, these tests intentionally persist data for analysis purposes.
 */

// All 16 MBTI personality types with their subtypes
const PERSONALITY_TYPES = [
    { code: 'INTJ-A', name: 'Architect (Assertive)' },
    // { code: 'INTJ-T', name: 'Architect (Turbulent)' },
    { code: 'INTP-A', name: 'Logician (Assertive)' },
    // { code: 'INTP-T', name: 'Logician (Turbulent)' },
    // { code: 'ENTJ-A', name: 'Commander (Assertive)' },
    // { code: 'ENTJ-T', name: 'Commander (Turbulent)' },
    // { code: 'ENTP-A', name: 'Debater (Assertive)' },
    // { code: 'ENTP-T', name: 'Debater (Turbulent)' },
    // { code: 'INFJ-A', name: 'Advocate (Assertive)' },
    // { code: 'INFJ-T', name: 'Advocate (Turbulent)' },
    // { code: 'INFP-A', name: 'Mediator (Assertive)' },
    // { code: 'INFP-T', name: 'Mediator (Turbulent)' },
    // { code: 'ENFJ-A', name: 'Protagonist (Assertive)' },
    // { code: 'ENFJ-T', name: 'Protagonist (Turbulent)' },
    // { code: 'ENFP-A', name: 'Campaigner (Assertive)' },
    // { code: 'ENFP-T', name: 'Campaigner (Turbulent)' },
    // { code: 'ISTJ-A', name: 'Logistician (Assertive)' },
    // { code: 'ISTJ-T', name: 'Logistician (Turbulent)' },
    // { code: 'ISFJ-A', name: 'Defender (Assertive)' },
    // { code: 'ISFJ-T', name: 'Defender (Turbulent)' },
    // { code: 'ESTJ-A', name: 'Executive (Assertive)' },
    // { code: 'ESTJ-T', name: 'Executive (Turbulent)' },
    // { code: 'ESFJ-A', name: 'Consul (Assertive)' },
    // { code: 'ESFJ-T', name: 'Consul (Turbulent)' },
    // { code: 'ISTP-A', name: 'Virtuoso (Assertive)' },
    // { code: 'ISTP-T', name: 'Virtuoso (Turbulent)' },
    // { code: 'ISFP-A', name: 'Adventurer (Assertive)' },
    // { code: 'ISFP-T', name: 'Adventurer (Turbulent)' },
    // { code: 'ESTP-A', name: 'Entrepreneur (Assertive)' },
    // { code: 'ESTP-T', name: 'Entrepreneur (Turbulent)' },
    // { code: 'ESFP-A', name: 'Entertainer (Assertive)' },
    // { code: 'ESFP-T', name: 'Entertainer (Turbulent)' },
];

// Sample task to test framework selection consistency
const TEST_TASK =
    'Help me create a comprehensive marketing strategy for a new SaaS product targeting small business owners';

test.describe.skip('Framework Selection Analysis', () => {
    // Run a test for each personality type
    for (const personalityType of PERSONALITY_TYPES) {
        test(`should select framework for ${personalityType.name} (${personalityType.code})`, async ({
            page,
            personalityUser,
        }) => {
            test.setTimeout(90000); // 1.5 minutes per test (includes 45s wait for framework)

            // Login and set personality type in one call using the fixture
            console.log(
                `\n[${personalityType.code}] Setting up user with personality type...`,
            );
            await personalityUser(personalityType.code);

            // Navigate to prompt builder
            console.log(
                `[${personalityType.code}] Navigating to prompt builder...`,
            );
            await page.goto('/prompt-builder');

            // Fill in task description
            const taskInput = page.getByLabel(/task description/i);
            await expect(taskInput).toBeVisible();
            await taskInput.fill(TEST_TASK);

            console.log(
                `[${personalityType.code}] Submitting task: "${TEST_TASK.substring(0, 50)}..."`,
            );

            // Submit the form
            const submitButton = page.getByRole('button', {
                name: /optimise.*prompt/i,
            });

            // Wait for form submission and navigation
            try {
                await Promise.all([
                    page.waitForURL(/\/prompt-builder\/\d+/, {
                        timeout: 10000,
                    }),
                    submitButton.click(),
                ]);
            } catch (error) {
                // Log debugging info if navigation fails
                console.error(
                    `\n[${personalityType.code}] Navigation failed:`,
                    error,
                );
                console.error('Current URL:', page.url());

                // Check for validation errors
                const errorMessages = await page
                    .locator('.text-red-600, .text-red-500')
                    .allTextContents();
                if (errorMessages.length > 0) {
                    console.error('Validation errors:', errorMessages);
                }

                throw error;
            }

            // Check if framework has been selected
            // Wait for framework tab appearance, but don't block forever
            const maxWaitTime = 45000; // Wait up to 45 seconds for framework
            const startTime = Date.now();

            console.log(
                `\n[${personalityType.code}] Waiting for framework selection...`,
            );

            try {
                await page.waitForFunction(
                    async () => {
                        // Reload page to get latest state from backend
                        await page.reload();

                        // Check for all tabs on the page
                        // Tabs are buttons in a nav with aria-label="Tabs"
                        const allTabs = await page
                            .locator('nav[aria-label="Tabs"] button')
                            .allTextContents();

                        const hasFrameworkTab = allTabs.some((text) =>
                            /framework/i.test(text),
                        );

                        // Check workflow stage indicators
                        const hasProcessingIndicator = await page
                            .getByText(/selecting optimal framework/i)
                            .isVisible()
                            .catch(() => false);

                        // Check for loading states
                        const hasGeneratingIndicator = await page
                            .getByText(/generating.*prompt/i)
                            .isVisible()
                            .catch(() => false);

                        const frameworkFound =
                            hasFrameworkTab &&
                            !hasProcessingIndicator &&
                            !hasGeneratingIndicator;

                        const elapsed = Math.round(
                            (Date.now() - startTime) / 1000,
                        );

                        if (frameworkFound) {
                            console.log(
                                `  ✓ Framework selected after ${elapsed}s`,
                            );
                            console.log(
                                `  Tabs found: [${allTabs.join(', ')}]`,
                            );
                        } else if (elapsed % 6 === 0 || elapsed === 3) {
                            // Debug logging every 6 seconds
                            console.log(
                                `  [${elapsed}s] Tabs: [${allTabs.join(', ')}] | Processing: ${hasProcessingIndicator} | Generating: ${hasGeneratingIndicator}`,
                            );
                        }

                        return frameworkFound;
                    },
                    { timeout: maxWaitTime },
                );
            } catch {
                const elapsed = Math.round((Date.now() - startTime) / 1000);
                console.log(
                    `  ⚠ Framework not selected within ${elapsed}s (max: ${maxWaitTime / 1000}s), moving to next test`,
                );
                const promptId = page.url().match(/\d+$/)?.[0];
                console.log(
                    `  Check prompt run ID ${promptId} later in database`,
                );
            }

            console.log(`  URL: ${page.url()}`);
            console.log(
                `[${personalityType.code}] Test completed, moving to next personality type\n`,
            );
        });
    }
});

test.describe.skip('Framework Selection - Quick Verification', () => {
    // This is a simpler test to verify the mechanism works without waiting for all types
    test('should persist prompt run for test user', async ({
        page,
        personalityUser,
    }) => {
        test.setTimeout(60000); // 1 minute

        // Setup user with INTJ-A personality
        await personalityUser('INTJ-A');

        // Go to prompt builder
        await page.goto('/prompt-builder');

        // Fill task
        const taskInput = page.getByLabel(/task description/i);
        await taskInput.fill('Test task for framework selection verification');

        // Submit
        const submitButton = page.getByRole('button', {
            name: /optimise.*prompt/i,
        });

        // Wait for navigation
        await Promise.all([
            page.waitForURL(/\/prompt-builder\/\d+/, { timeout: 10000 }),
            submitButton.click(),
        ]);

        // Verify we can see status
        console.log('\nTest prompt run created successfully');
        console.log(`URL: ${page.url()}`);
    });
});
