import type { Page } from '@playwright/test';
import { test as base, expect } from '@playwright/test';
import {
    acceptCookies,
    loginAsTestUser,
    loginWithUniqueName,
} from '../helpers/auth';
import {
    createTestPromptRun,
    waitForEchoConnection,
} from '../helpers/broadcast';
import { withCountryCode } from '../helpers/country';
import { AuthPage } from '../pages/AuthPage';
import { ProfilePage } from '../pages/ProfilePage';
import { PromptBuilderAdvancedPage } from '../pages/PromptBuilderAdvancedPage';
import { PromptBuilderPage } from '../pages/PromptBuilderPage';
import { StaticPage } from '../pages/StaticPage';

/**
 * Custom Playwright Test Fixtures
 * Provides pre-configured page objects and authentication states with n8n mocking
 * Usage: test('test name', async ({ authPage, page }) => { ... })
 *
 * IMPORTANT: All tests using this fixture automatically have n8n mocking enabled.
 * This prevents any real calls to n8n workflows during tests.
 * To use a specific scenario, create your own N8nMockService in the test.
 */

type AuthenticatedPageFixture = {
    /**
     * Pre-authenticated page (user is logged in)
     * Automatically logs in before the test runs
     * NOTE: n8n mocking is automatically enabled
     */
    authenticatedPage: Page;
    /**
     * Pre-authenticated page with a unique user per test
     * Useful for tests that need an empty state or isolated data
     * NOTE: n8n mocking is automatically enabled
     */
    authenticatedPageWithUniqueUser: Page;
};

type PageObjectsFixture = {
    authPage: AuthPage;
    promptBuilderPage: PromptBuilderPage;
    promptBuilderAdvancedPage: PromptBuilderAdvancedPage;
    profilePage: ProfilePage;
    staticPage: StaticPage;
};

type TestFixtures = AuthenticatedPageFixture & PageObjectsFixture;

/**
 * Extend Playwright's test with custom fixtures
 * Includes automatic n8n mocking on the page fixture
 */
export const test = base.extend<TestFixtures>({
    /**
     * Base page fixture with n8n test mode enabled and country-code routing
     * Automatically injects country codes into URLs (e.g. /pricing => /gb/pricing)
     * Sets an environment flag that the backend can use to return mock responses
     */
    page: async ({ page }, use) => {
        const originalGoto = page.goto.bind(page);
        const originalWaitForURL = page.waitForURL.bind(page);

        const pageWithCountry = page as Page & {
            goto: typeof page.goto;
            waitForURL: typeof page.waitForURL;
        };

        pageWithCountry.goto = ((url, options) => {
            if (typeof url === 'string') {
                return originalGoto(withCountryCode(url), options);
            }

            return originalGoto(url, options);
        }) as typeof page.goto;

        pageWithCountry.waitForURL = ((url, options) => {
            if (typeof url === 'string') {
                return originalWaitForURL(withCountryCode(url), options);
            }

            return originalWaitForURL(url, options);
        }) as typeof page.waitForURL;

        // Set the test flag that the backend will check
        // The backend's N8nWorkflowClient should check for this
        // and return mock responses instead of calling real n8n
        await page.addInitScript(() => {
            // This runs in the browser context and sets a test flag
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (window as any).__E2E_TEST__ = true;
        });

        // Also intercept requests to n8n domain to see if they're happening
        await page.on('request', (request) => {
            if (
                request.url().includes('n8n') &&
                !request.url().includes('localhost')
            ) {
                console.warn(
                    '[E2E WARNING] Real n8n HTTP request detected (should be mocked):',
                    request.method(),
                    request.url(),
                );
            }
        });

        // CRITICAL: Add X-Test-Auth header to all requests so middleware routes to test database
        // Without this header, page navigations would hit the production database!
        await page.setExtraHTTPHeaders({
            'X-Test-Auth': 'playwright-e2e-tests',
        });

        await use(page);
    },
    /**
     * Fixture: Pre-authenticated page
     * Logs in the test user before each test
     * Useful for tests that require authentication
     */
    authenticatedPage: async ({ page }, use) => {
        // Accept cookies first
        await acceptCookies(page);

        // Grant analytics consent so events are sent to backend during tests
        await page.context().addCookies([
            {
                name: 'cookie_consent',
                value: JSON.stringify({
                    essential: true,
                    functional: false,
                    analytics: true,
                }),
                domain: 'app.localhost',
                path: '/',
                sameSite: 'Strict',
            },
        ]);

        // Log in as test user
        await loginAsTestUser(page);

        // Verify login was successful
        const isLoggedIn = await page
            .getByRole('button', { name: /user menu/i })
            .first()
            .waitFor({ state: 'attached', timeout: 5000 })
            .then(() => true)
            .catch(() => false);

        if (!isLoggedIn) {
            throw new Error('Failed to authenticate - user menu not visible');
        }

        // Use the fixture - provide the authenticated page
        await use(page);
    },

    /**
     * Fixture: Pre-authenticated page with unique user
     * Logs in a unique test user (different per test)
     * Useful for tests that need empty state or isolated data
     */
    authenticatedPageWithUniqueUser: async ({ page }, use) => {
        // Accept cookies first
        await acceptCookies(page);

        // Grant analytics consent so events are sent to backend during tests
        await page.context().addCookies([
            {
                name: 'cookie_consent',
                value: JSON.stringify({
                    essential: true,
                    functional: false,
                    analytics: true,
                }),
                domain: 'app.localhost',
                path: '/',
                sameSite: 'Strict',
            },
        ]);

        // Generate unique email for this test
        const uniqueSuffix = Math.random().toString(36).substring(2, 8);
        const uniqueEmail = `test-${uniqueSuffix}@example.com`;
        const uniqueName = `Test User ${uniqueSuffix}`;

        // Log in with unique user
        await loginWithUniqueName(page, uniqueEmail, uniqueName);

        // Verify login was successful
        const isLoggedIn = await page
            .getByRole('button', { name: /user menu/i })
            .first()
            .waitFor({ state: 'attached', timeout: 5000 })
            .then(() => true)
            .catch(() => false);

        if (!isLoggedIn) {
            throw new Error('Failed to authenticate - user menu not visible');
        }

        // Use the fixture - provide the authenticated page
        await use(page);
    },

    /**
     * Fixture: AuthPage page object
     * Provides convenient methods for authentication interactions
     */
    authPage: async ({ page }, use) => {
        const authPage = new AuthPage(page);
        await use(authPage);
    },

    /**
     * Fixture: PromptBuilderPage page object
     * Provides convenient methods for prompt builder interactions
     */
    promptBuilderPage: async ({ page }, use) => {
        const promptBuilderPage = new PromptBuilderPage(page);
        await use(promptBuilderPage);
    },

    /**
     * Fixture: StaticPage page object
     * Provides convenient methods for static page interactions
     */
    staticPage: async ({ page }, use) => {
        const staticPage = new StaticPage(page);
        await use(staticPage);
    },

    /**
     * Fixture: PromptBuilderAdvancedPage page object
     * Provides convenient methods for advanced prompt builder interactions
     * Uses authenticatedPage to ensure user is logged in
     */
    promptBuilderAdvancedPage: async ({ authenticatedPage }, use) => {
        const promptBuilderAdvancedPage = new PromptBuilderAdvancedPage(
            authenticatedPage,
        );
        await use(promptBuilderAdvancedPage);
    },

    /**
     * Fixture: ProfilePage page object
     * Provides convenient methods for profile page interactions
     * Uses authenticatedPage to ensure user is logged in
     */
    profilePage: async ({ authenticatedPage }, use) => {
        const profilePage = new ProfilePage(authenticatedPage);
        await use(profilePage);
    },
});

/**
 * Re-export expect for use in tests
 */
export { expect };

/**
 * Helper Functions
 * Common test setup utilities for reducing boilerplate
 */

/**
 * Create a test prompt run with specific workflow stage
 *
 * Example:
 * ```typescript
 * test('example', async ({ authenticatedPage }) => {
 *     const promptRunId = await setupPromptRun(authenticatedPage, '1_completed');
 *     await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);
 * });
 * ```
 */
export async function setupPromptRun(
    page: Page,
    state: '1_processing' | '1_completed' | '2_completed' = '1_processing',
): Promise<number> {
    return await createTestPromptRun(page, state);
}

/**
 * Setup with prompt run navigation
 *
 * Creates a prompt run and navigates to it in one call.
 *
 * Example:
 * ```typescript
 * test('example', async ({ authenticatedPage }) => {
 *     await setupAndNavigateToPromptRun(
 *         authenticatedPage,
 *         '1_completed',
 *     );
 * });
 * ```
 */
export async function setupAndNavigateToPromptRun(
    page: Page,
    state: '1_processing' | '1_completed' | '2_completed' = '1_processing',
): Promise<number> {
    const id = await createTestPromptRun(page, state);
    await page.goto(`/prompt-builder/${id}`);
    return id;
}

/**
 * Wait for UI to be stable (navigation complete and Echo connected)
 *
 * Useful when you need to ensure both page navigation and real-time
 * connections are ready before proceeding.
 *
 * Example:
 * ```typescript
 * test('example', async ({ authenticatedPage }) => {
 *     await authenticatedPage.goto(`/prompt-builder/${id}`);
 *     await waitForUIReady(authenticatedPage);
 * });
 * ```
 */
export async function waitForUIReady(page: Page): Promise<void> {
    // Wait for page navigation
    await page.waitForLoadState('domcontentloaded');

    // Try to establish Echo connection (non-blocking timeout)
    await waitForEchoConnection(page, 3000).catch(() => {
        // If Echo fails, that's OK - we have fallback polling
    });
}

/**
 * Common test setup pattern for realtime tests
 *
 * Combines navigation and UI ready waiting.
 *
 * Example:
 * ```typescript
 * test('realtime example', async ({ authenticatedPage }) => {
 *     const id = await setupRealtimeTest(authenticatedPage, '1_processing');
 *     // Now ready to test realtime updates
 * });
 * ```
 */
export async function setupRealtimeTest(
    page: Page,
    state: '1_processing' | '1_completed' | '2_completed' = '1_processing',
): Promise<number> {
    const id = await setupAndNavigateToPromptRun(page, state);
    await waitForUIReady(page);
    return id;
}

/**
 * Example usage in a test:
 *
 * import { test, expect } from '@/fixtures';
 *
 * test('user can log in', async ({ authPage, page }) => {
 *     await page.goto('/?modal=login');
 *     await expect(authPage.emailInput).toBeVisible();
 *     await authPage.login('test@example.com', 'password');
 *     await expect(page.getByText(/welcome/i)).toBeVisible();
 * });
 *
 * test('authenticated user can create prompt', async ({ authenticatedPage, promptBuilderPage }) => {
 *     await promptBuilderPage.goto();
 *     await promptBuilderPage.enterAndSubmitTask('Write a poem');
 *     await promptBuilderPage.waitForOptimization();
 *     await promptBuilderPage.expectOptimizedPromptVisible();
 * });
 */
