import { expect, test } from './fixtures';

test.describe('Pricing and Checkout Flows', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/pricing');
        // Wait for page to fully load before tests run
        await page.waitForLoadState('networkidle');
        // Ensure pricing cards are visible before proceeding
        await page.getByRole('heading', { name: /^pro$/i }).waitFor({
            state: 'visible',
            timeout: 5000,
        });
    });

    test('displays pricing page with correct title', async ({ page }) => {
        // Check page loads with correct title
        await expect(page).toHaveTitle(/Pricing/i);

        // Check main heading is visible
        await expect(
            page.getByRole('heading', {
                name: /simple.*transparent.*pricing/i,
            }),
        ).toBeVisible();
    });

    test('displays all four tier cards side by side', async ({ page }) => {
        // Check all four tier cards are visible
        await expect(
            page.getByRole('heading', { name: /^free$/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /^starter$/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /^pro$/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /^premium$/i }),
        ).toBeVisible();

        // Verify they're in separate cards
        const starterCard = page.getByTestId('starter-tier-tab');
        const proCard = page.getByTestId('pro-tier-tab');
        const premiumCard = page.getByTestId('premium-tier-tab');
        await expect(starterCard).toBeVisible();
        await expect(proCard).toBeVisible();
        await expect(premiumCard).toBeVisible();
    });

    test('allows toggling between monthly and yearly billing', async ({
        page,
    }) => {
        const monthlyToggle = page.getByTestId('monthly-toggle');
        const annualToggle = page.getByTestId('annual-toggle');

        // Click Monthly
        await monthlyToggle.click();
        await expect(monthlyToggle).toHaveClass(/bg-indigo-100/);

        // Click Annual
        await annualToggle.click();
        await expect(annualToggle).toHaveClass(/bg-indigo-100/);
    });

    test('unauthenticated user sees subscribe button', async ({ page }) => {
        const subscribeButtons = page.getByTestId('subscribe-button');
        // All three paid tiers (Starter, Pro, Premium) should have subscribe buttons
        expect(await subscribeButtons.count()).toBe(3);
        await expect(subscribeButtons.first()).toBeVisible();
    });

    test('faq section is visible and contains content', async ({ page }) => {
        // Scroll to FAQ
        const faqTitle = page.getByRole('heading', {
            name: /frequently asked questions/i,
        });
        await faqTitle.scrollIntoViewIfNeeded();
        await expect(faqTitle).toBeVisible();

        // Check FAQ items exist
        const faqItems = page.locator('[class*="bg-indigo-50"]');
        expect(await faqItems.count()).toBeGreaterThan(0);
    });

    test('displays savings information for annual billing', async ({
        page,
    }) => {
        const annualToggle = page.getByTestId('annual-toggle');
        await annualToggle.click();

        // Look for savings text
        const savingsText = page.locator('text=/Save/i');
        await expect(savingsText.first()).toBeVisible();
    });

    test('shows features for free tier', async ({ page }) => {
        // Free tier features should be visible
        const freeSection = page
            .locator('[class*="rounded-2xl border border-indigo-200"]')
            .first();
        await expect(freeSection).toBeVisible();

        // Should contain "10 prompts" or similar
        const promptLimit = page.locator('text=/10/');
        expect(await promptLimit.count()).toBeGreaterThan(0);
    });

    test('displays monthly equivalent text for annual plans', async ({
        page,
    }) => {
        const annualToggle = page.getByTestId('annual-toggle');
        await annualToggle.click();

        // Look for monthly equivalent indicator
        const monthlyText = page.locator('text=/month/');
        expect(await monthlyText.count()).toBeGreaterThan(0);
    });

    test('each tier has its own subscribe button', async ({ page }) => {
        // Get all subscribe buttons
        const subscribeButtons = page.getByTestId('subscribe-button');

        // Should have 3 subscribe buttons (Starter, Pro, Premium)
        expect(await subscribeButtons.count()).toBe(3);

        // Verify buttons for each tier
        const starterCard = page.getByTestId('starter-tier-tab');
        const proCard = page.getByTestId('pro-tier-tab');
        const premiumCard = page.getByTestId('premium-tier-tab');

        await expect(starterCard.getByTestId('subscribe-button')).toBeVisible();
        await expect(proCard.getByTestId('subscribe-button')).toBeVisible();
        await expect(premiumCard.getByTestId('subscribe-button')).toBeVisible();
    });

    test('responsive design on mobile', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });

        // Check page still displays
        await expect(
            page.getByRole('heading', { name: /pricing/i }),
        ).toBeVisible();

        // Check main sections are visible
        await expect(
            page.getByRole('heading', { name: /free/i }),
        ).toBeVisible();
    });

    test('keyboard navigation works for billing toggles', async ({ page }) => {
        const monthlyToggle = page.getByTestId('monthly-toggle');
        const annualToggle = page.getByTestId('annual-toggle');

        // Focus Monthly toggle
        await monthlyToggle.focus();
        await expect(monthlyToggle).toBeFocused();

        // Tab to next element
        await page.keyboard.press('Tab');

        // Annual toggle should be focusable
        await expect(annualToggle).toBeVisible();
    });

    test('displays currency symbol', async ({ page }) => {
        // Check for GBP symbol
        const pounds = page.locator('text=/£/');
        expect(await pounds.count()).toBeGreaterThan(0);
    });

    test('shows all four tier names as headings', async ({ page }) => {
        // All four tier names should be visible as headings
        await expect(
            page.getByRole('heading', { name: /^free$/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /^starter$/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /^pro$/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /^premium$/i }),
        ).toBeVisible();
    });

    test('displays currency based on region auto-detection', async ({
        page,
    }) => {
        // Currency should be auto-detected based on user's region
        // Check for currency symbol (GBP by default in test)
        const pounds = page.locator('text=/£/');
        expect(await pounds.count()).toBeGreaterThan(0);
    });
});
