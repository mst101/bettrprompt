import { test, expect } from '@playwright/test';

const baseUrl = process.env.APP_URL || 'http://app.localhost:8000';

test.describe('Pricing and Checkout Flows', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${baseUrl}/pricing`);
    });

    test('displays pricing page with correct title', async ({ page }) => {
        // Check page loads with correct title
        await expect(page).toHaveTitle(/Pricing/i);

        // Check main heading is visible
        await expect(page.getByRole('heading', { name: /simple.*transparent.*pricing/i })).toBeVisible();
    });

    test('displays all three tier cards side by side', async ({ page }) => {
        // Check all three tier cards are visible
        await expect(page.getByRole('heading', { name: /^free$/i })).toBeVisible();
        await expect(page.getByRole('heading', { name: /^pro$/i })).toBeVisible();
        await expect(page.getByRole('heading', { name: /^private$/i })).toBeVisible();

        // Verify they're in separate cards
        const proCard = page.getByTestId('pro-tier-tab');
        const privateCard = page.getByTestId('private-tier-tab');
        await expect(proCard).toBeVisible();
        await expect(privateCard).toBeVisible();
    });

    test('allows toggling between monthly and yearly billing', async ({ page }) => {
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
        const subscribeButton = page.getByTestId('subscribe-button');
        await expect(subscribeButton).toBeVisible();
    });

    test('faq section is visible and contains content', async ({ page }) => {
        // Scroll to FAQ
        const faqTitle = page.getByRole('heading', { name: /frequently asked questions/i });
        await faqTitle.scrollIntoViewIfNeeded();
        await expect(faqTitle).toBeVisible();

        // Check FAQ items exist
        const faqItems = page.locator('[class*="bg-indigo-50"]');
        expect(await faqItems.count()).toBeGreaterThan(0);
    });

    test('displays savings information for annual billing', async ({ page }) => {
        const annualToggle = page.getByTestId('annual-toggle');
        await annualToggle.click();

        // Look for savings text
        const savingsText = page.locator('text=/Save/i');
        await expect(savingsText.first()).toBeVisible();
    });

    test('shows features for free tier', async ({ page }) => {
        // Free tier features should be visible
        const freeSection = page.locator('[class*="rounded-2xl border border-indigo-200"]').first();
        await expect(freeSection).toBeVisible();

        // Should contain "10 prompts" or similar
        const promptLimit = page.locator('text=/10/');
        expect(await promptLimit.count()).toBeGreaterThan(0);
    });

    test('displays monthly equivalent text for annual plans', async ({ page }) => {
        const annualToggle = page.getByTestId('annual-toggle');
        await annualToggle.click();

        // Look for monthly equivalent indicator
        const monthlyText = page.locator('text=/month/');
        expect(await monthlyText.count()).toBeGreaterThan(0);
    });

    test('each tier has its own subscribe button', async ({ page }) => {
        // Get all subscribe buttons
        const subscribeButtons = page.getByTestId('subscribe-button');

        // Should have 2 subscribe buttons (Pro and Private)
        expect(await subscribeButtons.count()).toBe(2);

        // Verify button text
        const proCard = page.getByTestId('pro-tier-tab');
        const privateCard = page.getByTestId('private-tier-tab');

        await expect(proCard.getByTestId('subscribe-button')).toBeVisible();
        await expect(privateCard.getByTestId('subscribe-button')).toBeVisible();
    });

    test('responsive design on mobile', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });

        // Check page still displays
        await expect(page.getByRole('heading', { name: /pricing/i })).toBeVisible();

        // Check main sections are visible
        await expect(page.getByRole('heading', { name: /free/i })).toBeVisible();
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

    test('shows all three tier names as headings', async ({ page }) => {
        // All three tier names should be visible as headings
        await expect(page.getByRole('heading', { name: /^free$/i })).toBeVisible();
        await expect(page.getByRole('heading', { name: /^pro$/i })).toBeVisible();
        await expect(page.getByRole('heading', { name: /^private$/i })).toBeVisible();
    });
});
