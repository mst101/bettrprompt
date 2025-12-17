import { expect, test } from '@playwright/test';

test.describe('Home Page', () => {
    test('should load the home page successfully', async ({ page }) => {
        await page.goto('/');

        // Wait for page to be fully loaded

        // Check that the page title is correct
        await expect(page).toHaveTitle(/Welcome to BettrPrompt/);

        // Verify main heading is visible
        await expect(
            page.getByRole('heading', { name: /Optimise AI Prompts for/ }),
        ).toBeVisible();
    });

    test('should display hero section content', async ({ page }) => {
        await page.goto('/');

        // Check for the main heading with gradient text
        const heading = page.getByRole('heading', {
            name: /Optimise AI Prompts for/,
        });
        await expect(heading).toBeVisible();

        // Verify "Your Personality" span exists (using test ID instead of CSS classes)
        const gradientText = page.getByTestId('hero-gradient-text');
        await expect(gradientText).toBeVisible();
        await expect(gradientText).toContainText('Your Personality');
    });

    test('should have navigation elements', async ({ page }) => {
        await page.goto('/');

        // Check for navigation (header should contain links)
        // Use .first() since there may be multiple nav elements
        const nav = page.locator('nav').first();
        await expect(nav).toBeVisible();
    });

    test('should display feature cards', async ({ page }) => {
        await page.goto('/');

        // Scroll to ensure all content is loaded
        await page.evaluate(() =>
            window.scrollTo(0, document.body.scrollHeight),
        );

        // Wait a bit for any lazy-loaded content

        // Verify feature cards are actually displayed
        // Cards should have proper semantic structure (role="article" or data-testid)
        const featureCards = page.locator(
            '[data-testid="feature-card"], [role="article"]',
        );
        await expect(featureCards.first()).toBeVisible();

        // Verify at least 2 cards are displayed (features section typically shows multiple)
        const cardCount = await featureCards.count();
        expect(cardCount).toBeGreaterThanOrEqual(2);
    });

    test('should be responsive on mobile viewport', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/');

        // Verify page still loads and main heading is visible
        await expect(
            page.getByRole('heading', { name: /Optimise AI Prompts for/ }),
        ).toBeVisible();
    });

    test('should have valid meta tags', async ({ page }) => {
        await page.goto('/');

        // Check for viewport meta tag (important for responsive design)
        const viewport = page.locator('meta[name="viewport"]');
        await expect(viewport).toHaveAttribute('content', /width=device-width/);
    });
});
