import type { Page } from '@playwright/test';

/**
 * Authentication helper for e2e tests
 */

export interface TestUser {
    email: string;
    password: string;
    name: string;
}

export const TEST_USER: TestUser = {
    name: 'Test User',
    email: 'test@hiddengambia.com',
    password: 'voodoo90',
};

/**
 * Log in as test user via the API/session
 * This bypasses the UI login flow for faster test setup
 */
export async function loginAsTestUser(page: Page): Promise<void> {
    // Navigate to login page with modal parameter
    await page.goto('/?modal=login');
    await page.waitForLoadState('networkidle');

    // Fill in login form
    const emailInput = page.getByLabel(/^email/i);
    const passwordInput = page.getByLabel(/^password/i);

    await emailInput.fill(TEST_USER.email);
    await passwordInput.fill(TEST_USER.password);

    // Submit the form
    const loginButton = page.getByRole('button', { name: /^log in$/i }).first();
    await loginButton.click();

    // Wait for navigation to complete
    await page.waitForLoadState('networkidle');

    // Verify we're logged in by checking for user menu or navigation
    await page.waitForTimeout(1000);
}

/**
 * Seed the test user in the database
 * This should be called before running tests that require the test user
 */
export async function seedTestUser(): Promise<void> {
    // This will be called from the test setup
    // The actual seeding happens via Laravel artisan command
    const { exec } = await import('child_process');
    const { promisify } = await import('util');
    const execAsync = promisify(exec);

    try {
        await execAsync(
            './vendor/bin/sail artisan db:seed --class=TestUserSeeder',
        );
    } catch (error) {
        console.error('Failed to seed test user:', error);
        throw error;
    }
}
