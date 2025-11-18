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

test.describe('Framework Selection Analysis', () => {
    // Seed test user before all tests
    test.beforeAll(async () => {
        await seedTestUser();
    });

    // Run a test for each personality type
    for (const personalityType of PERSONALITY_TYPES) {
        test(
            `should select framework for ${personalityType.name} (${personalityType.code})`,
            {
                timeout: 120000, // 2 minutes per test (includes AI processing)
            },
            async ({ page }) => {
                // Login as test user
                await loginAsTestUser(page);

                // Navigate to prompt optimiser
                await page.goto('/prompt-optimizer');
                await page.waitForLoadState('networkidle');

                // For authenticated users, need to go to profile to set personality type
                // Navigate to profile edit page
                await page.goto('/profile');
                await page.waitForLoadState('networkidle');

                // Look for personality type selector in the profile page
                const personalitySelect = page
                    .getByLabel(/personality type/i)
                    .first();
                await personalitySelect.waitFor({
                    state: 'visible',
                    timeout: 5000,
                });

                // Extract base type (e.g., "INTJ") and identity (e.g., "A")
                const [baseType, identityType] =
                    personalityType.code.split('-');

                // Get current value to check if we need to change it
                const currentValue = await personalitySelect.inputValue();

                // Always select the personality type (even if same, to trigger form state)
                await personalitySelect.selectOption(baseType);

                // Wait for identity radio buttons to appear
                await page.waitForTimeout(300);

                // Select identity (Assertive or Turbulent)
                const identityRadio =
                    identityType === 'A'
                        ? page.getByLabel(/assertive.*\(a\)/i)
                        : page.getByLabel(/turbulent.*\(t\)/i);
                await identityRadio.click();

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
                await savePersonalityButton.click();

                // Wait for save to complete and success message
                await page.waitForTimeout(1000);

                console.log(
                    `[${personalityType.code}] Personality saved, navigating to prompt optimiser...`,
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

                // Wait for framework selection (may take time for AI processing)
                // Allow up to 60 seconds for n8n workflow to complete
                await page.waitForTimeout(2000);

                // Wait for framework to be selected
                // We'll check periodically for up to 60 seconds
                let frameworkFound = false;
                let attempts = 0;
                const maxAttempts = 30; // 30 attempts * 2 seconds = 60 seconds

                while (!frameworkFound && attempts < maxAttempts) {
                    await page.reload();
                    await page.waitForLoadState('networkidle');

                    // Look for framework tab or framework name
                    const frameworkTab = page.getByRole('tab', {
                        name: /framework/i,
                    });
                    frameworkFound = await frameworkTab
                        .isVisible()
                        .catch(() => false);

                    if (!frameworkFound) {
                        attempts++;
                        await page.waitForTimeout(2000);
                    }
                }

                // Log result for analysis
                console.log(
                    `\n[${personalityType.code}] Framework selection test completed`,
                );
                console.log(`  URL: ${page.url()}`);
                console.log(
                    `  Framework found: ${frameworkFound ? 'Yes' : 'No (may still be processing)'}`,
                );

                // Note: We don't assert framework was found because AI processing may take longer
                // The data is persisted for manual analysis in the database
            },
        );
    }
});

test.describe('Framework Selection - Quick Verification', () => {
    // This is a simpler test to verify the mechanism works without waiting for all types
    test(
        'should persist prompt run for test user',
        {
            timeout: 120000, // 2 minutes
        },
        async ({ page }) => {
            await seedTestUser();
            await loginAsTestUser(page);

            // Go to profile to set personality type
            await page.goto('/profile');
            await page.waitForLoadState('networkidle');

            // Set personality type to INTJ-A
            const personalitySelect = page
                .getByLabel(/personality type/i)
                .first();
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
            await taskInput.fill(
                'Test task for framework selection verification',
            );

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
        },
    );
});
