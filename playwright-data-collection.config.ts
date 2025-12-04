import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright Data Collection Test Configuration
 * @see https://playwright.dev/docs/test-configuration
 *
 * This configuration is specifically for data collection tests which:
 * - Run separately from functional tests
 * - Use a persistent database (personality_data_collection)
 * - Preserve framework selection data for analysis
 */
export default defineConfig({
    // Global setup script to prepare data collection database
    globalSetup: './tests-frontend/e2e/data-collection/global-setup.ts',

    // Look for test files in the data-collection directory
    testDir: './tests-frontend/e2e/data-collection',

    // Match test files
    testMatch: '**/*.e2e.{ts,js}',

    // Run tests in files in parallel
    fullyParallel: true,

    // Fail the build on CI if you accidentally left test.only in the source code
    forbidOnly: !!process.env.CI,

    // Retry on CI only
    retries: process.env.CI ? 2 : 0,

    // Parallel workers: use all available CPUs locally, single worker on CI
    // Set via PW_WORKERS env var or CLI flag: npx playwright test --workers=4
    workers: process.env.CI ? 1 : undefined,

    // Reporter to use
    reporter: [
        ['html', { open: 'never' }],
        ['list'],
        process.env.CI ? ['github'] : ['list'],
    ],

    // Shared settings for all the projects below
    use: {
        // Base URL to use in actions like `await page.goto('/')`
        baseURL: process.env.APP_URL || 'https://app.localhost',

        // Ignore HTTPS certificate errors (for local development with self-signed certs)
        ignoreHTTPSErrors: true,

        // Collect trace when retrying the failed test
        trace: 'on-first-retry',

        // Screenshot on failure
        screenshot: 'only-on-failure',

        // Video on failure
        video: 'retain-on-failure',

        // Maximum time each action such as `click()` can take
        actionTimeout: 10000,

        // Emulate British English locale
        locale: 'en-GB',

        // Timezone
        timezoneId: 'Europe/London',
    },

    // Configure projects for major browsers
    projects: [
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                // Use headless by default (can be overridden with --headed flag)
                headless: true,
            },
        },
    ],

    // Global timeout for each test (90 seconds for data collection - longer due to n8n workflows)
    // Data collection tests wait for async workflow processing
    timeout: 90 * 1000,

    // Global setup timeout
    globalTimeout: 10 * 60 * 1000, // 10 minutes

    // Folder for test artifacts such as screenshots, videos, traces, etc.
    outputDir: 'tests-frontend/e2e/results',

    // ===== Performance Optimizations =====

    // Disable animations to speed up tests
    reducedMotion: 'reduce',

    // Expect timeout for individual assertions
    expect: {
        timeout: 5000,
    },

    // Configure slow test detection
    // Tests taking longer than this will be marked as slow in the report
    slow: 10000, // 10 seconds = slow test (data collection tests are inherently slower)
});
