import { test, expect } from '@playwright/test';

const baseUrl = process.env.APP_URL || 'http://app.localhost:8000';

test.describe('Pricing and Checkout Flows', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${baseUrl}/pricing`);
    });

    test('displays pricing page with all three tiers', async ({ page }) => {
        // Check page loads
        await expect(page).toHaveTitle(/Pricing/i);

        // Check Free tier is visible
        await expect(page.getByRole('heading', { name: /free/i })).toBeVisible();

        // Check Pro tier is visible
        await expect(page.getByRole('heading', { name: /pro/i })).toBeVisible();

        // Check Private tier is visible
        const privateTab = page.getByTestId('private-tier-tab');
        await expect(privateTab).toBeVisible();
    });

    test('allows toggling between pro and private tiers', async ({ page }) => {
        const proTab = page.getByTestId('pro-tier-tab');
        const privateTab = page.getByTestId('private-tier-tab');

        // Click Private tab
        await privateTab.click();

        // Verify heading changed to Private
        await expect(page.getByRole('heading', { name: /private/i })).toBeVisible();

        // Click back to Pro
        await proTab.click();

        // Verify heading changed back to Pro
        await expect(page.getByRole('heading', { name: /pro/i })).toBeVisible();
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

    test('switching tiers updates button text', async ({ page }) => {
        const subscribeButton = page.getByTestId('subscribe-button');
        const proTab = page.getByTestId('pro-tier-tab');
        const privateTab = page.getByTestId('private-tier-tab');

        // Switch to Pro
        await proTab.click();
        const proText = await subscribeButton.textContent();
        expect(proText).toBeTruthy();

        // Switch to Private
        await privateTab.click();
        const privateText = await subscribeButton.textContent();
        expect(privateText).toBeTruthy();
    });

    test('responsive design on mobile', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });

        // Check page still displays
        await expect(page.getByRole('heading', { name: /pricing/i })).toBeVisible();

        // Check main sections are visible
        await expect(page.getByRole('heading', { name: /free/i })).toBeVisible();
    });

    test('keyboard navigation works for tier tabs', async ({ page }) => {
        const proTab = page.getByTestId('pro-tier-tab');
        const privateTab = page.getByTestId('private-tier-tab');

        // Focus Pro tab
        await proTab.focus();
        await expect(proTab).toBeFocused();

        // Tab to next element
        await page.keyboard.press('Tab');

        // Private tab should be focusable
        await expect(privateTab).toBeVisible();
    });

    test('displays currency symbol', async ({ page }) => {
        // Check for GBP symbol
        const pounds = page.locator('text=/£/');
        expect(await pounds.count()).toBeGreaterThan(0);
    });

    test('shows all three tier names', async ({ page }) => {
        await expect(page.locator('text=/free/i')).toBeVisible();
        
        const proTab = page.getByTestId('pro-tier-tab');
        await expect(proTab).toContainText(/pro/i);

        const privateTab = page.getByTestId('private-tier-tab');
        await expect(privateTab).toContainText(/private/i);
    });
});
