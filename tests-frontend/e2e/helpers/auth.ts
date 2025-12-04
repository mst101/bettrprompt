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
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const userMenu = page.getByRole('button', { name: /user menu/i });
    const isAlreadyLoggedIn = await userMenu
        .isVisible({ timeout: 3000 })
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
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    // Verify we're logged in by checking for the user menu button
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
            await page.reload({ waitUntil: 'domcontentloaded' });
        }
    }

    if (!isLoggedIn) {
        throw new Error(
            'Login failed - user menu not found after 3 attempts. Check credentials or form validation.',
        );
    }
}
