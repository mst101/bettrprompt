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
 * Log in as test user via the login modal
 */
export async function loginAsTestUser(page: Page): Promise<void> {
    // Navigate to login page with modal parameter
    await page.goto('/?modal=login');
    await page.waitForLoadState('networkidle');

    // Wait for modal to appear and animation to complete
    await page.waitForTimeout(500);

    // Wait for email input to be visible (confirms modal is open)
    const emailInput = page.getByLabel(/^email/i).first();
    await emailInput.waitFor({ state: 'visible', timeout: 5000 });

    // Fill in login form
    await emailInput.fill(TEST_USER.email);

    const passwordInput = page.getByLabel(/^password/i).first();
    await passwordInput.fill(TEST_USER.password);

    // Submit the form by clicking the submit button
    const loginButton = page
        .getByRole('button', { name: /^log in$/i, exact: false })
        .first();
    await loginButton.waitFor({ state: 'visible', timeout: 5000 });

    // Click and wait for navigation
    await Promise.all([
        page.waitForLoadState('networkidle'),
        loginButton.click(),
    ]);

    // Additional wait for authentication to complete
    await page.waitForTimeout(1000);

    // Verify we're logged in by checking URL changed
    const currentUrl = page.url();
    if (currentUrl.includes('modal=login')) {
        throw new Error(
            'Login failed - still on login page. Check credentials or form validation.',
        );
    }
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
