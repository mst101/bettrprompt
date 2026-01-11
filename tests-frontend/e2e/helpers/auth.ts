import type { Page } from '@playwright/test';
import { expect } from '@playwright/test';
import { getDefaultCountryUrl } from './country';

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
    // This tells Laravel to use the bettrprompt_e2e database via UseE2eDatabase middleware
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
    await page.goto(getDefaultCountryUrl(), { waitUntil: 'domcontentloaded' });

    const userMenu = page.getByRole('button', { name: /user menu/i });
    const isAlreadyLoggedIn = await userMenu
        .first()
        .waitFor({ state: 'attached', timeout: 3000 })
        .then(() => true)
        .catch(() => false);

    if (isAlreadyLoggedIn) {
        // Already logged in, no need to go through login process
        return;
    }

    // Use the test-only login endpoint via browser's fetch API
    // This ensures cookies are properly handled by the browser context
    await page.evaluate(async (email: string) => {
        const csrfToken = (
            document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
        )?.getAttribute('content');

        const response = await fetch('/test/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Test-Auth': 'playwright-e2e-tests',
            },
            body: JSON.stringify({ email }),
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error(
                `Login failed: ${response.status} ${response.statusText}`,
            );
        }

        return response.json();
    }, TEST_USER.email);

    // Navigate away and back to trigger Inertia to reload with auth
    await page.goto(getDefaultCountryUrl(), { waitUntil: 'networkidle' });
    // Give Vue time to hydrate after page load
    await page.waitForTimeout(500);

    // Verify we're logged in by checking for the user menu button
    const userMenuAfterLogin = page.getByRole('button', {
        name: /user menu/i,
    });

    // Retry logic for user menu visibility
    let isLoggedIn = false;
    for (let attempt = 0; attempt < 3; attempt++) {
        isLoggedIn = await userMenuAfterLogin
            .first()
            .waitFor({ state: 'visible', timeout: 3000 })
            .then(() => true)
            .catch(() => false);

        if (isLoggedIn) {
            break;
        }

        // If not visible, reload and check again
        if (attempt < 2) {
            await page.reload({ waitUntil: 'networkidle' });
            await page.waitForTimeout(300);
        }
    }

    if (!isLoggedIn) {
        throw new Error(
            'Login failed - user menu not found after 3 attempts. Check credentials or form validation.',
        );
    }
}

/**
 * Login as test user and set a specific personality type
 *
 * This is more efficient than manually navigating through the profile page
 * Uses the test-only API endpoint to set personality type directly
 */
export async function loginWithPersonalityType(
    page: Page,
    personalityCode: string,
): Promise<void> {
    // First do regular login
    await loginAsTestUser(page);

    // Then set personality type via test endpoint
    await page.evaluate(async (code: string) => {
        const [baseType, identity] = code.split('-');

        const response = await fetch('/test/set-personality', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Test-Auth': 'playwright-e2e-tests',
            },
            body: JSON.stringify({
                personality_type: baseType,
                identity: identity === 'A' ? 'assertive' : 'turbulent',
                traits: {
                    extraversion: 50,
                    intuition: 50,
                    feeling: 50,
                    perceiving: 50,
                },
            }),
            credentials: 'include',
        });

        if (!response.ok) {
            // Log response details for debugging
            const body = await response.text().catch(() => '<no body>');
            console.error(
                `[DEBUG] Failed to set personality - Status: ${response.status}, StatusText: ${response.statusText}, Body: ${body}`,
            );
            throw new Error(
                `Failed to set personality type: ${response.status} ${response.statusText}`,
            );
        }

        return response.json();
    }, personalityCode);
}

/**
 * Login with a unique email and name
 * Useful for tests that need isolated data or empty state
 */
export async function loginWithUniqueName(
    page: Page,
    email: string,
    name: string,
): Promise<void> {
    await acceptCookies(page);

    // Check if already logged in
    await page.goto(getDefaultCountryUrl(), { waitUntil: 'domcontentloaded' });

    const userMenu = page.getByRole('button', { name: /user menu/i });
    const isAlreadyLoggedIn = await userMenu
        .first()
        .waitFor({ state: 'attached', timeout: 3000 })
        .then(() => true)
        .catch(() => false);

    if (isAlreadyLoggedIn) {
        // Check if it's the same user, if not we need to log out and log in with new user
        // For now, just return since we're already logged in
        return;
    }

    // Use the test-only login endpoint via browser's fetch API
    await page.evaluate(
        async ({
            email: userEmail,
            name: userName,
        }: {
            email: string;
            name: string;
        }) => {
            const csrfToken = (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.getAttribute('content');

            const response = await fetch('/test/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                body: JSON.stringify({ email: userEmail, name: userName }),
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error(
                    `Login failed: ${response.status} ${response.statusText}`,
                );
            }

            return response.json();
        },
        { email, name },
    );

    // Navigate away and back to trigger Inertia to reload with auth
    await page.goto(getDefaultCountryUrl(), { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);

    // Verify we're logged in
    const userMenuAfterLogin = page.getByRole('button', {
        name: /user menu/i,
    });

    let isLoggedIn = false;
    for (let attempt = 0; attempt < 3; attempt++) {
        isLoggedIn = await userMenuAfterLogin
            .first()
            .waitFor({ state: 'visible', timeout: 3000 })
            .then(() => true)
            .catch(() => false);

        if (isLoggedIn) {
            break;
        }

        if (attempt < 2) {
            await page.reload({ waitUntil: 'networkidle' });
            await page.waitForTimeout(300);
        }
    }

    if (!isLoggedIn) {
        throw new Error(
            'Login failed - user menu not found after 3 attempts with unique user.',
        );
    }
}

/**
 * Login via mock OAuth endpoint
 *
 * Simulates Google OAuth flow without requiring actual Google credentials
 * Creates or updates user with google_id
 */
export async function loginWithMockOAuth(
    page: Page,
    email: string = 'oauth-test@example.com',
    name: string = 'OAuth Test User',
): Promise<void> {
    await acceptCookies(page);

    // First navigate to home page to establish base URL context
    await page.goto(getDefaultCountryUrl(), { waitUntil: 'domcontentloaded' });

    // Use the test-only OAuth endpoint via Playwright's API request
    // Manually add X-Test-Auth header since page.request doesn't go through page.route() interceptors
    const baseURL = new URL(page.url()).origin;
    const response = await page.request.post(`${baseURL}/test/oauth-login`, {
        headers: {
            'X-Test-Auth': 'playwright-e2e-tests',
        },
        data: {
            email,
            name,
        },
    });

    if (!response.ok()) {
        throw new Error(
            `OAuth login failed: ${response.status()} ${response.statusText()}`,
        );
    }

    // Navigate to trigger Inertia to reload with auth
    await page.goto(getDefaultCountryUrl(), { waitUntil: 'domcontentloaded' });

    // Verify we're logged in
    const userMenu = page.getByRole('button', { name: /user menu/i });
    await expect(userMenu)
        .toBeVisible({ timeout: 5000 })
        .catch(() => {
            throw new Error('OAuth login failed - user menu not visible');
        });
}
