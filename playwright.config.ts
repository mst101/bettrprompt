import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright E2E Test Configuration
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
    // Global setup script to prepare test database
    globalSetup: './tests-frontend/e2e/global-setup.ts',

    // Look for test files in the "tests-frontend/e2e" directory
    testDir: './tests-frontend/e2e',

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

        // Uncomment to test on Firefox
        // {
        //     name: 'firefox',
        //     use: { ...devices['Desktop Firefox'] },
        // },

        // Uncomment to test on WebKit (Safari)
        // {
        //     name: 'webkit',
        //     use: { ...devices['Desktop Safari'] },
        // },

        // Mobile viewports
        // {
        //     name: 'Mobile Chrome',
        //     use: { ...devices['Pixel 5'] },
        // },
        // {
        //     name: 'Mobile Safari',
        //     use: { ...devices['iPhone 12'] },
        // },
    ],

    // Note: When running locally with Sail/Caddy, ensure the server is running
    // The webServer option is commented out because we use Laravel Sail + Caddy
    // Uncomment if you need Playwright to auto-start a server (e.g., in CI)
    // webServer: {
    //     command: 'php artisan serve',
    //     url: 'http://localhost:8000',
    //     reuseExistingServer: !process.env.CI,
    //     stdout: 'ignore',
    //     stderr: 'pipe',
    //     timeout: 120 * 1000,
    // },

    // Global timeout for each test (60 seconds)
    // Increased from 30s to handle parallel test execution and async n8n workflow processing
    timeout: 60 * 1000,

    // Global setup timeout
    globalTimeout: 10 * 60 * 1000, // 10 minutes

    // Folder for test artifacts such as screenshots, videos, traces, etc.
    outputDir: 'tests-frontend/e2e/results',

    // ===== Performance Optimizations =====

    // Disable animations to speed up tests
    reducedMotion: 'reduce',

    // Use --headed flag to run with UI, default is headless for speed
    // Command: npx playwright test --headed

    // Expect timeout for individual assertions
    expect: {
        timeout: 5000,
    },

    // Configure slow test detection
    // Tests taking longer than this will be marked as slow in the report
    slow: 5000, // 5 seconds = slow test

    // Use --debug flag to run in debug mode with inspector
    // Command: npx playwright test --debug
});
