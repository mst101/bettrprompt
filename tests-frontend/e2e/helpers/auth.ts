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
 * Pre-accept cookies to prevent cookie banner from blocking interactions
 * Should be called before navigating to any page in tests
 */
export async function acceptCookies(page: Page): Promise<void> {
    await page.context().addCookies([
        {
            name: 'cookie_consent',
            value: encodeURIComponent(
                JSON.stringify({
                    essential: true,
                    functional: true,
                    analytics: true,
                }),
            ),
            domain: 'app.localhost',
            path: '/',
            expires: Math.floor(Date.now() / 1000) + 365 * 24 * 60 * 60, // 1 year
            httpOnly: false,
            secure: false,
            sameSite: 'Strict',
        },
    ]);
}

/**
 * Log in as test user via the login modal
 */
export async function loginAsTestUser(page: Page): Promise<void> {
    // Pre-accept cookies by setting the cookie_consent cookie before navigation
    // This prevents the cookie banner modal from appearing and blocking interactions
    await acceptCookies(page);

    // Check if already logged in to avoid unnecessary login attempts
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    // Wait a bit for any dynamic content to load
    await page.waitForTimeout(500);

    const userMenu = page.getByRole('button', { name: /user menu/i });
    const isAlreadyLoggedIn = await userMenu
        .isVisible({ timeout: 3000 })
        .catch(() => false);

    if (isAlreadyLoggedIn) {
        // Already logged in, no need to go through login process
        return;
    }

    // Navigate to login page with modal parameter
    await page.goto('/?modal=login');
    await page.waitForLoadState('networkidle');

    // Wait for modal to appear and animation to complete
    await page.waitForTimeout(500);

    // Wait for email input to be visible (confirms modal is open)
    const emailInput = page.getByLabel(/^email/i).first();
    await emailInput.waitFor({ state: 'visible', timeout: 10000 });

    // Fill in login form
    await emailInput.fill(TEST_USER.email);

    const passwordInput = page.getByLabel(/^password/i).first();
    await passwordInput.fill(TEST_USER.password);

    // Submit the form by clicking the submit button
    // Find the submit button within the modal dialog (type="submit")
    const loginButton = page.locator('button[type="submit"]', {
        hasText: /log in/i,
    });
    await loginButton.waitFor({ state: 'visible', timeout: 5000 });

    // Click and wait for navigation
    await Promise.all([
        page.waitForLoadState('networkidle'),
        loginButton.click(),
    ]);

    // Additional wait for authentication to complete
    await page.waitForTimeout(1500);

    // Verify we're logged in by checking for the user menu button
    // (Inertia clears query params after login, so we can't rely on URL)
    const userMenuAfterLogin = page.getByRole('button', {
        name: /user menu/i,
    });

    // Retry logic for user menu visibility
    let isLoggedIn = false;
    for (let attempt = 0; attempt < 3; attempt++) {
        isLoggedIn = await userMenuAfterLogin
            .isVisible({ timeout: 2000 })
            .catch(() => false);

        if (isLoggedIn) {
            break;
        }

        // If not visible, reload and check again
        if (attempt < 2) {
            await page.reload();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(1000);
        }
    }

    if (!isLoggedIn) {
        throw new Error(
            'Login failed - user menu not found after 3 attempts. Check credentials or form validation.',
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
