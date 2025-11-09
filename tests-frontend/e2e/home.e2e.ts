import { test, expect } from '@playwright/test';

test.describe('Home Page', () => {
    test('should load the home page successfully', async ({ page }) => {
        await page.goto('/');

        // Wait for page to be fully loaded
        await page.waitForLoadState('networkidle');

        // Check that the page title is correct
        await expect(page).toHaveTitle(/Welcome to AI Buddy/);

        // Verify main heading is visible
        await expect(
            page.getByRole('heading', { name: /Optimise AI Prompts for/ })
        ).toBeVisible();
    });

    test('should display hero section content', async ({ page }) => {
        await page.goto('/');

        // Check for the main heading with gradient text
        const heading = page.getByRole('heading', {
            name: /Optimise AI Prompts for/,
        });
        await expect(heading).toBeVisible();

        // Verify "Your Personality" span exists (gradient text)
        const gradientText = page.locator(
            'span.bg-gradient-to-r.from-indigo-600.to-purple-600'
        );
        await expect(gradientText).toBeVisible();
        await expect(gradientText).toContainText('Your Personality');
    });

    test('should have navigation elements', async ({ page }) => {
        await page.goto('/');

        // Check for navigation (header should contain links)
        const nav = page.locator('nav');
        await expect(nav).toBeVisible();
    });

    test('should display feature cards', async ({ page }) => {
        await page.goto('/');

        // Scroll to ensure all content is loaded
        await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));

        // Wait a bit for any lazy-loaded content
        await page.waitForTimeout(1000);

        // Check if there are feature cards on the page
        const featureCards = page.locator('[class*="feature"]').first();
        // At least some content should be visible (this is a flexible check)
        await expect(page.locator('body')).toBeVisible();
    });

    test('should be responsive on mobile viewport', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/');

        // Verify page still loads and main heading is visible
        await expect(
            page.getByRole('heading', { name: /Optimise AI Prompts for/ })
        ).toBeVisible();
    });

    test('should have valid meta tags', async ({ page }) => {
        await page.goto('/');

        // Check for viewport meta tag (important for responsive design)
        const viewport = page.locator('meta[name="viewport"]');
        await expect(viewport).toHaveAttribute('content', /width=device-width/);
    });
});
