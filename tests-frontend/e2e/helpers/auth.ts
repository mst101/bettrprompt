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
    email: 'test@example.com',
    password: 'password',
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

    // Intercept all requests to add the X-Test-Auth header
    // This tells Laravel to use the personality_e2e database via UseE2eDatabase middleware
    await page.route('**/*', async (route) => {
        const headers = {
            ...route.request().headers(),
            'X-Test-Auth': 'playwright-e2e-tests',
        };
        await route.continue({ headers });
    });
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

    // Use the test-only login endpoint (only available in e2e environment)
    // This bypasses the modal form which has issues with Playwright input

    // First, navigate to get a CSRF token
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    // Get CSRF token from the page
    const csrfToken = await page.evaluate(() => {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : null;
    });

    // Make the login request - the endpoint will redirect to home
    // Use page.request.post so cookies are properly handled
    const loginResponse = await page.request.post(
        'https://app.localhost/test/login',
        {
            data: {
                email: TEST_USER.email,
            },
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Test-Auth': 'playwright-e2e-tests',
                'Content-Type': 'application/json',
            },
        },
    );

    if (!loginResponse.ok()) {
        const body = await loginResponse.text();
        throw new Error(
            `Test login failed: ${loginResponse.status()} ${loginResponse.statusText()}\nBody: ${body}`,
        );
    }

    // After successful login, navigate to home to pick up the session
    await page.goto('/', { waitUntil: 'networkidle' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

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
 *
 * NOTE: As of the E2E database setup, test data is seeded globally via
 * global-setup.ts which runs E2eTestSeeder. This function is now a no-op
 * for backwards compatibility with existing tests.
 */
export async function seedTestUser(): Promise<void> {
    // No-op: Test data is seeded globally via global-setup.ts
    // which runs E2eTestSeeder before all tests
}
