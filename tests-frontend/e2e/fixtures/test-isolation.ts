import type { Browser, BrowserContext, Page } from '@playwright/test';

/**
 * Test Isolation Utilities
 * Ensures tests don't interfere with each other through shared state
 * Implements patterns for:
 * - Session isolation (separate browser contexts)
 * - Data isolation (separate test data)
 * - Storage isolation (localStorage, sessionStorage, cookies)
 */

/**
 * Clear all browser storage (localStorage, sessionStorage, indexedDB)
 * Ensures test data doesn't leak between tests
 */
export async function clearBrowserStorage(page: Page): Promise<void> {
    await page.evaluate(() => {
        // Clear localStorage
        localStorage.clear();

        // Clear sessionStorage
        sessionStorage.clear();

        // Clear indexedDB
        if (window.indexedDB) {
            const dbs = await window.indexedDB.databases();
            for (const db of dbs) {
                if (db.name) {
                    window.indexedDB.deleteDatabase(db.name);
                }
            }
        }
    });
}

/**
 * Clear all cookies to ensure session isolation
 */
export async function clearCookies(page: Page): Promise<void> {
    const cookies = await page.context().cookies();
    for (const cookie of cookies) {
        await page.context().clearCookies({
            name: cookie.name,
            domain: cookie.domain,
            path: cookie.path,
        });
    }
}

/**
 * Clear all browser storage and cookies
 * Use at the start or end of each test for complete isolation
 */
export async function clearAllStorage(page: Page): Promise<void> {
    await clearBrowserStorage(page);
    await clearCookies(page);
}

/**
 * Reset page to clean state
 * Reloads the page and clears all storage
 */
export async function resetPageState(
    page: Page,
    url: string = '/',
): Promise<void> {
    // Clear all storage first
    await clearAllStorage(page);

    // Navigate to the URL to start fresh
    await page.goto(url, { waitUntil: 'domcontentloaded' });
}

/**
 * Test isolation context manager
 * Automatically isolates tests by clearing storage before and after
 */
export class TestIsolationContext {
    constructor(private page: Page) {}

    /**
     * Run a test with automatic cleanup before and after
     */
    async run<T>(
        testFn: (page: Page) => Promise<T>,
        options?: { clearBefore?: boolean; clearAfter?: boolean },
    ): Promise<T> {
        const { clearBefore = true, clearAfter = true } = options || {};

        if (clearBefore) {
            await clearAllStorage(this.page);
        }

        try {
            return await testFn(this.page);
        } finally {
            if (clearAfter) {
                await clearAllStorage(this.page);
            }
        }
    }

    /**
     * Clear storage at start of test
     */
    async setUp(): Promise<void> {
        await clearAllStorage(this.page);
    }

    /**
     * Clear storage at end of test
     */
    async tearDown(): Promise<void> {
        await clearAllStorage(this.page);
    }
}

/**
 * Create a new isolated browser context (separate session)
 * Useful for testing multi-user scenarios or session conflicts
 */
export async function createIsolatedContext(
    browser: Browser,
    contextOptions?: any,
): Promise<BrowserContext> {
    return browser.newContext({
        ...contextOptions,
        // Avoid sharing cookies between contexts
        storageState: undefined,
    });
}

/**
 * Ensure test isolation in parallel execution
 * Prevents race conditions and data conflicts
 */
export class TestDataIsolation {
    private testId: string;

    constructor() {
        // Each test gets a unique ID to prevent data conflicts
        this.testId = `test-${Date.now()}-${Math.random()}`;
    }

    /**
     * Get a unique value for this test
     * Use for creating unique test data that won't conflict with other parallel tests
     */
    unique(prefix: string = ''): string {
        return `${prefix}${this.testId}`;
    }

    /**
     * Get a unique email address for this test
     */
    uniqueEmail(): string {
        return `test-${this.testId}@example.com`;
    }

    /**
     * Get a unique username for this test
     */
    uniqueUsername(): string {
        return `testuser-${this.testId}`;
    }

    /**
     * Get the test ID
     */
    getId(): string {
        return this.testId;
    }
}

/**
 * Recommended test structure for proper isolation:
 *
 * import { test } from '@/fixtures';
 * import { clearAllStorage, TestDataIsolation } from '@/fixtures/test-isolation';
 *
 * test.beforeEach(async ({ page }) => {
 *     // Clear all storage at the start of each test
 *     await clearAllStorage(page);
 * });
 *
 * test('test with isolated data', async ({ page }) => {
 *     const isolation = new TestDataIsolation();
 *
 *     // Create unique test data
 *     const uniqueEmail = isolation.uniqueEmail();
 *
 *     // ... rest of test
 * });
 *
 * test.afterEach(async ({ page }) => {
 *     // Clean up after test
 *     await clearAllStorage(page);
 * });
 */
