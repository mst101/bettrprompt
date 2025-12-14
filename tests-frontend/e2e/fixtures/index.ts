import type { Page } from '@playwright/test';
import { test as base, expect } from '@playwright/test';
import {
    acceptCookies,
    loginAsTestUser,
    loginWithUniqueName,
} from '../helpers/auth';
import { AuthPage } from '../pages/AuthPage';
import { ProfilePage } from '../pages/ProfilePage';
import { PromptBuilderAdvancedPage } from '../pages/PromptBuilderAdvancedPage';
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
    authenticatedPage: Page;
    /**
     * Pre-authenticated page with a unique user per test
     * Useful for tests that need an empty state or isolated data
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

        // Generate unique email for this test
        const uniqueSuffix = Math.random().toString(36).substring(2, 8);
        const uniqueEmail = `test-${uniqueSuffix}@example.com`;
        const uniqueName = `Test User ${uniqueSuffix}`;

        // Log in with unique user
        await loginWithUniqueName(page, uniqueEmail, uniqueName);

        // Verify login was successful
        const isLoggedIn = await page
            .getByRole('button', { name: /user menu/i })
            .isVisible({ timeout: 5000 })
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
