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
    { code: 'INTJ-T', name: 'Architect (Turbulent)' },
    { code: 'INTP-A', name: 'Logician (Assertive)' },
    { code: 'INTP-T', name: 'Logician (Turbulent)' },
    { code: 'ENTJ-A', name: 'Commander (Assertive)' },
    { code: 'ENTJ-T', name: 'Commander (Turbulent)' },
    { code: 'ENTP-A', name: 'Debater (Assertive)' },
    { code: 'ENTP-T', name: 'Debater (Turbulent)' },
    { code: 'INFJ-A', name: 'Advocate (Assertive)' },
    { code: 'INFJ-T', name: 'Advocate (Turbulent)' },
    { code: 'INFP-A', name: 'Mediator (Assertive)' },
    { code: 'INFP-T', name: 'Mediator (Turbulent)' },
    { code: 'ENFJ-A', name: 'Protagonist (Assertive)' },
    { code: 'ENFJ-T', name: 'Protagonist (Turbulent)' },
    { code: 'ENFP-A', name: 'Campaigner (Assertive)' },
    { code: 'ENFP-T', name: 'Campaigner (Turbulent)' },
    { code: 'ISTJ-A', name: 'Logistician (Assertive)' },
    { code: 'ISTJ-T', name: 'Logistician (Turbulent)' },
    { code: 'ISFJ-A', name: 'Defender (Assertive)' },
    { code: 'ISFJ-T', name: 'Defender (Turbulent)' },
    { code: 'ESTJ-A', name: 'Executive (Assertive)' },
    { code: 'ESTJ-T', name: 'Executive (Turbulent)' },
    { code: 'ESFJ-A', name: 'Consul (Assertive)' },
    { code: 'ESFJ-T', name: 'Consul (Turbulent)' },
    { code: 'ISTP-A', name: 'Virtuoso (Assertive)' },
    { code: 'ISTP-T', name: 'Virtuoso (Turbulent)' },
    { code: 'ISFP-A', name: 'Adventurer (Assertive)' },
    { code: 'ISFP-T', name: 'Adventurer (Turbulent)' },
    { code: 'ESTP-A', name: 'Entrepreneur (Assertive)' },
    { code: 'ESTP-T', name: 'Entrepreneur (Turbulent)' },
    { code: 'ESFP-A', name: 'Entertainer (Assertive)' },
    { code: 'ESFP-T', name: 'Entertainer (Turbulent)' },
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
        test(`should select framework for ${personalityType.name} (${personalityType.code})`, async ({
            page,
        }) => {
            // Login as test user
            await loginAsTestUser(page);

            // Navigate to prompt optimiser
            await page.goto('/prompt-optimizer');
            await page.waitForLoadState('networkidle');

            // Update personality type
            // First, check if personality form is already visible or needs to be expanded
            const personalityHeading = page.getByText(/personality type/i);
            await expect(personalityHeading).toBeVisible();

            // Look for personality type selector
            const personalitySelect = page.getByLabel(/personality type/i);
            await expect(personalitySelect).toBeVisible();

            // Select the personality type
            await personalitySelect.selectOption(personalityType.code);

            // Set default trait percentages (50% for each trait)
            const traitInputs = page.locator('input[type="number"]');
            const traitCount = await traitInputs.count();

            for (let i = 0; i < traitCount; i++) {
                await traitInputs.nth(i).fill('50');
            }

            // Save personality type
            const savePersonalityButton = page.getByRole('button', {
                name: /save personality/i,
            });
            await savePersonalityButton.click();

            // Wait for save to complete
            await page.waitForTimeout(1000);

            // Fill in task description
            const taskInput = page.getByLabel(/task description/i);
            await expect(taskInput).toBeVisible();
            await taskInput.fill(TEST_TASK);

            // Submit the form
            const submitButton = page.getByRole('button', {
                name: /optimise.*prompt/i,
            });
            await submitButton.click();

            // Wait for navigation to show page
            await page.waitForLoadState('networkidle');

            // Wait for framework selection (may take time for AI processing)
            // Allow up to 60 seconds for n8n workflow to complete
            await page.waitForTimeout(2000);

            // Check if we're on the show page
            expect(page.url()).toMatch(/\/prompt-optimizer\/\d+/);

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
        });
    }
});

test.describe('Framework Selection - Quick Verification', () => {
    // This is a simpler test to verify the mechanism works without waiting for all types
    test('should persist prompt run for test user', async ({ page }) => {
        await seedTestUser();
        await loginAsTestUser(page);

        await page.goto('/prompt-optimizer');
        await page.waitForLoadState('networkidle');

        // Set personality type to INTJ-A
        const personalitySelect = page.getByLabel(/personality type/i);
        await personalitySelect.selectOption('INTJ-A');

        // Set trait percentages
        const traitInputs = page.locator('input[type="number"]');
        const traitCount = await traitInputs.count();
        for (let i = 0; i < traitCount; i++) {
            await traitInputs.nth(i).fill('50');
        }

        // Save personality
        const saveButton = page.getByRole('button', {
            name: /save personality/i,
        });
        await saveButton.click();
        await page.waitForTimeout(1000);

        // Fill task
        const taskInput = page.getByLabel(/task description/i);
        await taskInput.fill('Test task for framework selection verification');

        // Submit
        const submitButton = page.getByRole('button', {
            name: /optimise.*prompt/i,
        });
        await submitButton.click();

        // Verify navigation
        await page.waitForLoadState('networkidle');
        expect(page.url()).toMatch(/\/prompt-optimizer\/\d+/);

        // Verify we can see status
        await page.waitForTimeout(1000);

        console.log('\nTest prompt run created successfully');
        console.log(`URL: ${page.url()}`);
    });
});
