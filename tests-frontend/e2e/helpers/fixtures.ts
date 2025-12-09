import type { Page } from '@playwright/test';
import { test as base } from '@playwright/test';
import { N8nMockService } from '../mocks/n8n-mock-service';
import { acceptCookies, loginAsTestUser } from './auth';
import { createTestPromptRun, waitForEchoConnection } from './broadcast';

/**
 * Shared test fixtures for E2E tests
 *
 * Provides common setup/teardown and utilities to reduce boilerplate code
 * across test files. This includes:
 * - Pre-authenticated test user
 * - Automatic n8n webhook mocking (all tests are isolated from real n8n)
 * - Helper functions for common operations
 * - Consistent setup with cookies and login
 */

export interface TestFixtures {
    authenticatedPage: Page;
    promptRunId: number;
}

/**
 * Extended test with shared fixtures
 *
 * Usage:
 * ```typescript
 * import { test } from './helpers/fixtures';
 *
 * test('example test', async ({ authenticatedPage, promptRunId }) => {
 *     await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);
 * });
 * ```
 *
 * NOTE: All tests automatically have n8n webhook mocking enabled.
 * This prevents any real calls to n8n workflows during tests.
 * To use a specific scenario, create your own N8nMockService in the test.
 */
export const test = base.extend<TestFixtures>({
    /**
     * Base page fixture with n8n mocking
     *
     * Overriding the default page fixture to enable n8n mocking for
     * ALL tests, even unauthenticated ones.
     */

    page: async ({ page }, use) => {
        // Enable n8n mocking for unauthenticated pages as well
        // This prevents any real n8n calls regardless of auth state
        const n8nMock = new N8nMockService(page);
        await n8nMock.enableMocking({
            scenario: 'success',
            responseDelay: 100,
        });

        await use(page);
    },
    /**
     * authenticatedPage fixture
     *
     * Provides a pre-authenticated page with all necessary cookies and headers
     * set up. Use this instead of page when you need a logged-in user.
     *
     * Benefits:
     * - Eliminates boilerplate loginAsTestUser() calls
     * - Consistent authentication across all tests
     * - Automatic cookie acceptance
     * - n8n webhook mocking already enabled via page fixture
     */
    authenticatedPage: async ({ page }, use) => {
        // Set up authentication
        await acceptCookies(page);
        await loginAsTestUser(page);

        // Note: n8n mocking is already enabled via the page fixture above
        // No need to set it up again here

        // Use the authenticated page for the test
        await use(page);
    },

    /**
     * promptRunId fixture
     *
     * Creates a test prompt run in '1_processing' state and provides its ID.
     *
     * Benefits:
     * - Common setup for tests that need prompt data
     * - Consistent state across tests
     * - Reduces test setup boilerplate
     *
     * Example:
     * ```typescript
     * test('example', async ({ authenticatedPage, promptRunId }) => {
     *     await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);
     * });
     * ```
     */
    promptRunId: async ({ authenticatedPage }, use) => {
        // Create a test prompt run in analysis processing state
        const id = await createTestPromptRun(authenticatedPage, '1_processing');

        // Provide it to the test
        await use(id);
    },
});

/**
 * Common test setup helpers
 *
 * These utilities can be imported alongside the test fixture to perform
 * common setup operations in a more functional style.
 */

/**
 * Setup test with specific prompt run workflow stage
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
