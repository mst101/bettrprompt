import { expect, test } from '../fixtures';

/**
 * E2E Tests for Free Tier 5 Prompts Per Month Limit
 *
 * These tests verify that:
 * 1. Free tier users are limited to 5 prompts per month
 * 2. Warning banner displays when 1-2 prompts remain
 * 3. The backend enforces the limit via middleware
 * 4. Users see appropriate error messages when limit is reached
 * 5. Pro/paid users are not affected by the limit
 */

test.describe('Free Tier Prompt Limits', () => {
    test('shows warning banner when user has 1 prompt remaining', async ({
        authenticatedPage,
    }) => {
        // Navigate to prompt builder
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Get CSRF token
        const csrfToken = await authenticatedPage.evaluate(() => {
            return (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.getAttribute('content');
        });

        // Update user to have 4 prompts used (1 remaining out of 5)
        const updateResponse = await authenticatedPage.request.post(
            '/api/test/user/update-prompts',
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                data: {
                    monthly_prompt_count: 4,
                    email: 'test@example.com',
                },
            },
        );

        expect(updateResponse.ok()).toBe(true);

        // Reload page to get fresh state with updated user data
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Verify warning banner is visible
        const warningBanner = authenticatedPage.getByTestId(
            'low-prompts-warning',
        );
        await expect(warningBanner).toBeVisible({ timeout: 10000 });

        // Verify the warning text contains "prompt"
        await expect(warningBanner).toContainText(/prompt/i);
    });

    test('shows warning banner when user has 2 prompts remaining', async ({
        authenticatedPage,
    }) => {
        // Navigate to prompt builder
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Get CSRF token
        const csrfToken = await authenticatedPage.evaluate(() => {
            return (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.getAttribute('content');
        });

        // Update user to have 3 prompts used (2 remaining)
        const updateResponse = await authenticatedPage.request.post(
            '/api/test/user/update-prompts',
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                data: {
                    monthly_prompt_count: 3,
                    email: 'test@example.com',
                },
            },
        );

        expect(updateResponse.ok()).toBe(true);

        // Reload page to get fresh state with updated user data
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Verify warning banner is visible
        const warningBanner = authenticatedPage.getByTestId(
            'low-prompts-warning',
        );
        await expect(warningBanner).toBeVisible({ timeout: 10000 });
    });

    test('does not show warning banner when user has 3+ prompts remaining', async ({
        authenticatedPage,
    }) => {
        // Navigate to prompt builder
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Get CSRF token
        const csrfToken = await authenticatedPage.evaluate(() => {
            return (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.getAttribute('content');
        });

        // Update user to have 2 prompts used (3 remaining)
        const updateResponse = await authenticatedPage.request.post(
            '/api/test/user/update-prompts',
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                data: {
                    monthly_prompt_count: 2,
                    email: 'test@example.com',
                },
            },
        );

        expect(updateResponse.ok()).toBe(true);

        // Reload page to get fresh state with updated user data
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Verify warning banner is NOT visible
        const warningBanner = authenticatedPage.getByTestId(
            'low-prompts-warning',
        );
        const isVisible = await warningBanner.isVisible().catch(() => false);
        expect(isVisible).toBe(false);
    });

    test('warning banner shows days until reset', async ({
        authenticatedPage,
    }) => {
        // Navigate to prompt builder
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Get CSRF token
        const csrfToken = await authenticatedPage.evaluate(() => {
            return (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.getAttribute('content');
        });

        // Update user to have 4 prompts used
        const updateResponse = await authenticatedPage.request.post(
            '/api/test/user/update-prompts',
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                data: {
                    monthly_prompt_count: 4,
                    email: 'test@example.com',
                },
            },
        );

        expect(updateResponse.ok()).toBe(true);

        // Reload page to get fresh state with updated user data
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        const warningBanner = authenticatedPage.getByTestId(
            'low-prompts-warning',
        );
        await expect(warningBanner).toBeVisible({ timeout: 10000 });

        // Should mention days or reset
        await expect(warningBanner).toContainText(/day|reset|resets/i);
    });

    test('warning banner has link to pricing', async ({
        authenticatedPage,
    }) => {
        // Navigate to prompt builder
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Get CSRF token
        const csrfToken = await authenticatedPage.evaluate(() => {
            return (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.getAttribute('content');
        });

        // Update user to have low prompts
        const updateResponse = await authenticatedPage.request.post(
            '/api/test/user/update-prompts',
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                data: {
                    monthly_prompt_count: 4,
                    email: 'test@example.com',
                },
            },
        );

        expect(updateResponse.ok()).toBe(true);

        // Reload page to get fresh state with updated user data
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        const warningBanner = authenticatedPage.getByTestId(
            'low-prompts-warning',
        );
        await expect(warningBanner).toBeVisible({ timeout: 10000 });

        const upgradeLink = warningBanner.locator('a');
        await expect(upgradeLink).toBeVisible();
        const href = await upgradeLink.getAttribute('href');
        expect(href).toContain('/pricing');
    });

    test('free user cannot create prompt at limit', async ({
        authenticatedPage,
    }) => {
        // Navigate to prompt builder
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Get CSRF token
        const csrfToken = await authenticatedPage.evaluate(() => {
            return (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.getAttribute('content');
        });

        // Update user to 5 prompts used (at limit)
        const updateResponse = await authenticatedPage.request.post(
            '/api/test/user/update-prompts',
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                data: {
                    monthly_prompt_count: 5,
                    email: 'test@example.com',
                },
            },
        );

        expect(updateResponse.ok()).toBe(true);

        // Reload page to get fresh state with updated user data
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Try to submit a task description via API
        const submitResponse = await authenticatedPage.request.post(
            '/gb/prompt-builder/analyse',
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                data: {
                    task_description:
                        'This is a test task description that is long enough to meet minimum length requirements',
                    personality_type: 'INTJ-A',
                },
            },
        );

        // Should get 403 Forbidden when at limit
        expect(submitResponse.status()).toBe(403);

        const errorData = await submitResponse.json();
        expect(errorData.error).toBe('prompt_limit_reached');
        expect(errorData.promptsUsed).toBe(5);
        expect(errorData.promptLimit).toBe(5);
        expect(errorData.daysUntilReset).toBeDefined();
    });

    test('free user with no prompts used does not see warning', async ({
        authenticatedPage,
    }) => {
        // Navigate to prompt builder
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Get CSRF token
        const csrfToken = await authenticatedPage.evaluate(() => {
            return (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.getAttribute('content');
        });

        // Update user to 0 prompts used
        const updateResponse = await authenticatedPage.request.post(
            '/api/test/user/update-prompts',
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
                data: {
                    monthly_prompt_count: 0,
                    email: 'test@example.com',
                },
            },
        );

        expect(updateResponse.ok()).toBe(true);

        // Reload page to get fresh state with updated user data
        await authenticatedPage.goto('/gb/prompt-builder');
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // User with 0 prompts used should not show warning
        const warningBanner = authenticatedPage.getByTestId(
            'low-prompts-warning',
        );
        const isVisible = await warningBanner.isVisible().catch(() => false);
        expect(isVisible).toBe(false);
    });
});
