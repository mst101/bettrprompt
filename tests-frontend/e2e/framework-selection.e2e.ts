import { expect, test } from '@playwright/test';
import { loginAsTestUser, seedTestUser } from './helpers/auth';

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
    { code: 'ENTJ-A', name: 'Commander (Assertive)' },
    // { code: 'ENTJ-T', name: 'Commander (Turbulent)' },
    { code: 'ENTP-A', name: 'Debater (Assertive)' },
    // { code: 'ENTP-T', name: 'Debater (Turbulent)' },
    { code: 'INFJ-A', name: 'Advocate (Assertive)' },
    // { code: 'INFJ-T', name: 'Advocate (Turbulent)' },
    { code: 'INFP-A', name: 'Mediator (Assertive)' },
    // { code: 'INFP-T', name: 'Mediator (Turbulent)' },
    { code: 'ENFJ-A', name: 'Protagonist (Assertive)' },
    // { code: 'ENFJ-T', name: 'Protagonist (Turbulent)' },
    { code: 'ENFP-A', name: 'Campaigner (Assertive)' },
    // { code: 'ENFP-T', name: 'Campaigner (Turbulent)' },
    { code: 'ISTJ-A', name: 'Logistician (Assertive)' },
    // { code: 'ISTJ-T', name: 'Logistician (Turbulent)' },
    { code: 'ISFJ-A', name: 'Defender (Assertive)' },
    // { code: 'ISFJ-T', name: 'Defender (Turbulent)' },
    { code: 'ESTJ-A', name: 'Executive (Assertive)' },
    // { code: 'ESTJ-T', name: 'Executive (Turbulent)' },
    { code: 'ESFJ-A', name: 'Consul (Assertive)' },
    // { code: 'ESFJ-T', name: 'Consul (Turbulent)' },
    { code: 'ISTP-A', name: 'Virtuoso (Assertive)' },
    // { code: 'ISTP-T', name: 'Virtuoso (Turbulent)' },
    { code: 'ISFP-A', name: 'Adventurer (Assertive)' },
    // { code: 'ISFP-T', name: 'Adventurer (Turbulent)' },
    { code: 'ESTP-A', name: 'Entrepreneur (Assertive)' },
    // { code: 'ESTP-T', name: 'Entrepreneur (Turbulent)' },
    { code: 'ESFP-A', name: 'Entertainer (Assertive)' },
    // { code: 'ESFP-T', name: 'Entertainer (Turbulent)' },
];

// Sample task to test framework selection consistency
const TEST_TASK =
    'Help me create a comprehensive marketing strategy for a new SaaS product targeting small business owners';

test.describe.skip('Framework Selection Analysis', () => {
    // Seed test user before all tests
    test.beforeAll(async () => {
        await seedTestUser();
    });

    // Run a test for each personality type
    for (const personalityType of PERSONALITY_TYPES) {
        test(`should select framework for ${personalityType.name} (${personalityType.code})`, async ({
            page,
        }) => {
            test.setTimeout(90000); // 1.5 minutes per test (includes 45s wait for framework)

            // Login as test user
            await loginAsTestUser(page);

            // Navigate to prompt optimiser
            // await page.goto('/prompt-optimizer');
            // await page.waitForLoadState('networkidle');

            // For authenticated users, need to go to profile to set personality type
            // Navigate to profile edit page
            await page.goto('/profile');
            await page.waitForLoadState('networkidle');

            // Additional wait to ensure any previous requests have completed
            await page.waitForTimeout(500);

            // Look for personality type selector in the profile page
            const personalitySelect = page
                .getByLabel(/personality type/i)
                .first();
            await personalitySelect.waitFor({
                state: 'visible',
                timeout: 5000,
            });

            // Extract base type (e.g., "INTJ") and identity (e.g., "A")
            const [baseType, identityType] = personalityType.code.split('-');

            // Get current value to check if we need to change it
            const currentValue = await personalitySelect.inputValue();

            // Always select the personality type (even if same, to trigger form state)
            await personalitySelect.selectOption(baseType);

            // Wait for identity radio buttons to appear and form to update
            await page.waitForTimeout(500);

            // Select identity (Assertive or Turbulent)
            const identityRadio =
                identityType === 'A'
                    ? page.getByLabel(/assertive.*\(a\)/i)
                    : page.getByLabel(/turbulent.*\(t\)/i);

            // Wait for radio to be available and click it
            await identityRadio.waitFor({
                state: 'visible',
                timeout: 5000,
            });
            await identityRadio.click();

            // Wait for form state to update after clicking radio
            await page.waitForTimeout(300);

            // Log what we're setting
            console.log(
                `\n[${personalityType.code}] Setting personality type (was: ${currentValue || 'none'})`,
            );

            // Expand trait percentages section if not already visible
            const toggleTraitsButton = page.getByRole('button', {
                name: /add.*trait percentages/i,
            });

            // Check if trait inputs are already visible
            const traitInputsVisible = await page
                .locator('input[type="number"]')
                .first()
                .isVisible()
                .catch(() => false);

            if (!traitInputsVisible) {
                await toggleTraitsButton.click();
                await page.waitForTimeout(300);
            }

            // Set default trait percentages (50% for each trait)
            const traitInputs = page.locator('input[type="number"]');
            const traitCount = await traitInputs.count();

            for (let i = 0; i < traitCount; i++) {
                await traitInputs.nth(i).fill('50');
            }

            // Save personality type
            // Target the Save button in the personality type section specifically
            const personalitySection = page.locator('section').filter({
                hasText: /your personality type/i,
            });
            const savePersonalityButton = personalitySection.getByRole(
                'button',
                {
                    name: /^save$/i,
                },
            );

            // Wait for save button to be enabled (in case form validation is running)
            await savePersonalityButton.waitFor({
                state: 'visible',
                timeout: 5000,
            });
            await page.waitForTimeout(500);

            await savePersonalityButton.click();

            // Wait for save to complete and success message
            await page.waitForTimeout(3000);

            // Verify the personality type was saved by reloading and checking
            // Retry up to 3 times to handle race conditions
            let savedValue = '';
            let saveVerified = false;

            for (let attempt = 0; attempt < 3; attempt++) {
                await page.reload();
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(1000);

                const savedPersonalitySelect = page
                    .getByLabel(/personality type/i)
                    .first();
                savedValue = await savedPersonalitySelect.inputValue();

                if (savedValue === baseType) {
                    saveVerified = true;
                    break;
                }

                console.log(
                    `  [Attempt ${attempt + 1}/3] Save verification: expected ${baseType}, got ${savedValue}, retrying...`,
                );

                // Wait before retry
                if (attempt < 2) {
                    await page.waitForTimeout(2000);
                }
            }

            if (!saveVerified) {
                console.error(
                    `[${personalityType.code}] ERROR: Personality type not saved correctly after 3 attempts. Expected: ${baseType}, Got: ${savedValue}`,
                );
                throw new Error(
                    `Personality type save failed: expected ${baseType}, got ${savedValue}`,
                );
            }

            console.log(
                `[${personalityType.code}] Personality saved and verified (${baseType}-${identityType}), navigating to prompt optimiser...`,
            );

            // Navigate back to prompt optimiser
            await page.goto('/prompt-optimizer');
            await page.waitForLoadState('networkidle');

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
                    page.waitForURL(/\/prompt-optimizer\/\d+/, {
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
            // Poll for framework tab appearance, but don't block forever
            let frameworkFound = false;
            const maxWaitTime = 45000; // Wait up to 45 seconds for framework
            const pollInterval = 3000; // Check every 3 seconds
            const startTime = Date.now();

            console.log(
                `\n[${personalityType.code}] Waiting for framework selection...`,
            );

            while (!frameworkFound && Date.now() - startTime < maxWaitTime) {
                // Reload page to get latest state from backend
                await page.reload();
                await page.waitForLoadState('networkidle');

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

                frameworkFound =
                    hasFrameworkTab &&
                    !hasProcessingIndicator &&
                    !hasGeneratingIndicator;

                const elapsed = Math.round((Date.now() - startTime) / 1000);

                if (frameworkFound) {
                    console.log(`  ✓ Framework selected after ${elapsed}s`);
                    console.log(`  Tabs found: [${allTabs.join(', ')}]`);
                    break;
                }

                // Debug logging every 6 seconds
                if (elapsed % 6 === 0 || elapsed === 3) {
                    console.log(
                        `  [${elapsed}s] Tabs: [${allTabs.join(', ')}] | Processing: ${hasProcessingIndicator} | Generating: ${hasGeneratingIndicator}`,
                    );
                }

                // Wait before checking again
                await page.waitForTimeout(pollInterval);
            }

            if (!frameworkFound) {
                console.log(
                    `  ⚠ Framework not selected within ${maxWaitTime / 1000}s, moving to next test`,
                );
                console.log(
                    `  Check prompt run ID ${page.url().match(/\d+$/)?.[0]} later in database`,
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
    test('should persist prompt run for test user', async ({ page }) => {
        test.setTimeout(60000); // 1 minute

        await seedTestUser();
        await loginAsTestUser(page);

        // Go to profile to set personality type
        await page.goto('/profile');
        await page.waitForLoadState('networkidle');

        // Set personality type to INTJ-A
        const personalitySelect = page.getByLabel(/personality type/i).first();
        await personalitySelect.waitFor({
            state: 'visible',
            timeout: 5000,
        });
        await personalitySelect.selectOption('INTJ');

        // Wait for identity radio to appear
        await page.waitForTimeout(300);

        // Select Assertive
        const assertiveRadio = page.getByLabel(/assertive.*\(a\)/i);
        await assertiveRadio.click();

        // Expand trait percentages
        const toggleTraitsButton = page.getByRole('button', {
            name: /\+ add.*trait percentages/i,
        });
        await toggleTraitsButton.click();
        await page.waitForTimeout(300);

        // Set trait percentages
        const traitInputs = page.locator('input[type="number"]');
        const traitCount = await traitInputs.count();
        for (let i = 0; i < traitCount; i++) {
            await traitInputs.nth(i).fill('50');
        }

        // Save personality
        const personalitySection = page.locator('section').filter({
            hasText: /your personality type/i,
        });
        const saveButton = personalitySection.getByRole('button', {
            name: /^save$/i,
        });
        await saveButton.click();
        await page.waitForTimeout(1000);

        // Go to prompt optimiser
        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        // Fill task
        const taskInput = page.getByLabel(/task description/i);
        await taskInput.fill('Test task for framework selection verification');

        // Submit
        const submitButton = page.getByRole('button', {
            name: /optimise.*prompt/i,
        });

        // Wait for navigation
        await Promise.all([
            page.waitForURL(/\/prompt-optimizer\/\d+/, { timeout: 10000 }),
            submitButton.click(),
        ]);

        // Verify we can see status
        await page.waitForTimeout(1000);

        console.log('\nTest prompt run created successfully');
        console.log(`URL: ${page.url()}`);
    });
});
