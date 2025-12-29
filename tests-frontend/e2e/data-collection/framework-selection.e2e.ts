import { expect, test } from './fixtures/data-collection-user';

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
    // { code: 'INTJ-A', name: 'Architect (Assertive)' },
    // { code: 'INTJ-T', name: 'Architect (Turbulent)' },
    // { code: 'INTP-A', name: 'Logician (Assertive)' },
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
    { code: 'ESFJ-T', name: 'Consul (Turbulent)' },
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
// Made precise to avoid triggering pre-analysis questions
// const TEST_TASK =
//     'Write a project management system for a healthcare clinic. The system must include: ' +
//     '1) Patient appointment scheduling with automatic reminders via SMS and email; ' +
//     '2) Electronic medical records storage with HIPAA compliance; ' +
//     '3) Integration with existing billing systems; ' +
//     '4) Role-based access control for doctors, nurses, and administrators; ' +
//     '5) Real-time patient wait time tracking; ' +
//     '6) Prescription management with pharmacy integration. ' +
//     'All components must be scalable to support 500+ daily patients across multiple clinics. ' +
//     'Use modern technology stack with microservices architecture. ' +
//     'Ensure 99.9% uptime SLA with comprehensive error handling and logging.';

const TEST_TASK =
    'I want to buy a second-hand, small, petrol car for commuting to work which is 20 miles away from home in Bromsgrove, UK. It will only be used by me. My main concerns are to have a car that is reliable and has low maintenance';

test.describe.serial('Framework Selection Analysis', () => {
    // Run a test for each personality type
    // Sets personality type via test fixture before submitting task
    for (const personalityType of PERSONALITY_TYPES) {
        test(`should select framework for ${personalityType.name} (${personalityType.code})`, async ({
            page,
            setPersonality,
        }) => {
            test.setTimeout(90000); // 1.5 minutes per test (includes 45s wait for framework)

            // Extract base type and identity from code (e.g., "INTJ-A" -> "INTJ", "A")
            const [baseType, identitySuffix] = personalityType.code.split('-');
            const identity =
                identitySuffix.toLowerCase() === 'a'
                    ? 'assertive'
                    : 'turbulent';

            // Set personality type for this test session
            console.log(
                `[${personalityType.code}] Setting personality type...`,
            );
            await setPersonality(baseType, identity, {
                extraversion: 65,
                intuition: 65,
                thinking: 65,
                judging: 65,
                identity: 65,
            });

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

                // Also check for form errors in the actual form elements
                const formErrors = await page
                    .locator('[role="alert"], .error, [aria-invalid="true"]')
                    .allTextContents();
                if (formErrors.length > 0) {
                    console.error('Form errors found:', formErrors);
                }

                throw error;
            }

            // Wait for workflow to complete and capture framework data
            const maxWaitTime = 60000; // Wait up to 60 seconds for workflow
            const startTime = Date.now();
            const promptId = page.url().match(/\d+$/)?.[0];

            console.log(
                `\n[${personalityType.code}] Waiting for workflow completion (Prompt Run ID: ${promptId})...`,
            );

            let frameworkData: Record<string, unknown> | null = null;
            let preAnalysisData: Record<string, unknown> | null = null;

            try {
                // Poll for workflow completion every 2 seconds
                let workflowComplete = false;
                let pollCount = 0;
                const maxPolls = Math.floor(maxWaitTime / 2000); // Each poll is 2 seconds

                while (!workflowComplete && pollCount < maxPolls) {
                    // Reload to get latest state
                    await page.reload();

                    // Check if framework tab is visible (button with Framework text in tabs)
                    const frameworkTabButton = page.locator(
                        '[data-testid="prompt-builder-tabs"] button:has-text("Framework"), [data-testid="prompt-builder-tabs"] button:has-text("Selected Framework")',
                    );
                    const hasFrameworkTab = await frameworkTabButton
                        .first()
                        .isVisible()
                        .catch(() => false);

                    // Check if we have pre-analysis questions displayed
                    const preAnalysisQuestions = page.locator(
                        '[data-testid="pre-analysis"], text=/answer.*question|clarify/i',
                    );
                    const hasPreAnalysisQuestions = await preAnalysisQuestions
                        .first()
                        .isVisible()
                        .catch(() => false);

                    // Check if there's still a loading state
                    const loadingState = page.locator(
                        '[data-testid*="loading"]',
                    );
                    const isStillLoading = await loadingState
                        .first()
                        .isVisible()
                        .catch(() => false);

                    // Extract framework name if visible
                    const frameworkNameElement = page.locator(
                        '[data-testid="selected-framework-name"]',
                    );
                    const frameworkName = await frameworkNameElement
                        .first()
                        .textContent()
                        .catch(() => null);

                    // Workflow is complete when loading is gone AND we have framework or questions
                    workflowComplete =
                        !isStillLoading &&
                        (hasFrameworkTab || hasPreAnalysisQuestions);

                    const elapsed = Math.round((Date.now() - startTime) / 1000);

                    if (workflowComplete) {
                        if (hasPreAnalysisQuestions && !hasFrameworkTab) {
                            console.log(
                                `  ✓ Pre-analysis questions received after ${elapsed}s`,
                            );
                            preAnalysisData = {
                                hasQuestions: true,
                                detectedAt: elapsed,
                            };
                        }
                        if (hasFrameworkTab) {
                            console.log(
                                `  ✓ Framework selected after ${elapsed}s`,
                            );
                            if (frameworkName) {
                                console.log(`  Framework: ${frameworkName}`);
                            }
                            frameworkData = {
                                selected: true,
                                name: frameworkName,
                                detectedAt: elapsed,
                            };
                        }
                    } else {
                        if (pollCount % 3 === 0) {
                            // Log every 6 seconds
                            console.log(
                                `  [${elapsed}s] Waiting... (loading: ${isStillLoading}, questions: ${hasPreAnalysisQuestions}, framework: ${hasFrameworkTab})`,
                            );
                        }
                        // Wait 2 seconds before polling again
                        await page.waitForTimeout(2000);
                    }

                    pollCount++;
                }

                if (!workflowComplete) {
                    const elapsed = Math.round((Date.now() - startTime) / 1000);
                    console.log(
                        `  ⚠ Workflow did not complete within ${elapsed}s (max: ${maxWaitTime / 1000}s)`,
                    );
                }

                console.log(
                    `  Data collected: Framework=${frameworkData?.selected}, PreAnalysis=${preAnalysisData?.hasQuestions}`,
                );
            } catch (error) {
                const elapsed = Math.round((Date.now() - startTime) / 1000);
                console.log(
                    `  ⚠ Error waiting for workflow completion after ${elapsed}s: ${error}`,
                );
                console.log(`  Prompt Run ID: ${promptId}`);
                console.log(
                    `  Framework data: ${JSON.stringify(frameworkData)}`,
                );
                console.log(
                    `  Pre-analysis data: ${JSON.stringify(preAnalysisData)}`,
                );
            }

            console.log(`  Final URL: ${page.url()}`);
            console.log(`[${personalityType.code}] Test completed\n`);
        });
    }
});
