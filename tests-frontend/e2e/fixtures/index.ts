import { test as base, expect } from '@playwright/test';
import { acceptCookies, loginAsTestUser } from '../helpers/auth';
import { AuthPage } from '../pages/AuthPage';
import { PromptBuilderPage } from '../pages/PromptBuilderPage';
import { StaticPage } from '../pages/StaticPage';

/**
 * Custom Playwright Test Fixtures
 * Provides pre-configured page objects and authentication states
 * Usage: test('test name', async ({ authPage, page }) => { ... })
 */

type AuthenticatedPageFixture = {
    /**
     * Pre-authenticated page (user is logged in)
     * Automatically logs in before the test runs
     */
    authenticatedPage: void;
};

type PageObjectsFixture = {
    authPage: AuthPage;
    promptBuilderPage: PromptBuilderPage;
    staticPage: StaticPage;
};

type TestFixtures = AuthenticatedPageFixture & PageObjectsFixture;

/**
 * Extend Playwright's test with custom fixtures
 */
export const test = base.extend<TestFixtures>({
    /**
     * Fixture: Pre-authenticated page
     * Logs in the test user before each test
     * Useful for tests that require authentication
     */
    authenticatedPage: async ({ page }, use) => {
        // Accept cookies first
        await acceptCookies(page);

        // Log in as test user
        await loginAsTestUser(page);

        // Verify login was successful
        const isLoggedIn = await page
            .getByRole('button', { name: /user menu/i })
            .isVisible({ timeout: 5000 })
            .catch(() => false);

        if (!isLoggedIn) {
            throw new Error('Failed to authenticate - user menu not visible');
        }

        // Use the fixture
        await use();
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
});

/**
 * Re-export expect for use in tests
 */
export { expect };

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
