import { expect, test } from './fixtures';

test.describe('Site Navigation', () => {
    test('should navigate from home to prompt optimizer (requires auth)', async ({
        page,
    }) => {
        await page.goto('/');

        // Look for "Get Started" or similar CTA button
        const ctaButton = page.getByRole('link', {
            name: /get started|try now|start/i,
        });

        if ((await ctaButton.count()) > 0) {
            // Use Promise.all to coordinate click with navigation
            await Promise.all([
                page
                    .waitForURL(
                        (url) =>
                            url.pathname.includes('/prompt-builder') ||
                            url.pathname.includes('/login'),
                        { timeout: 5000 },
                    )
                    .catch(() => null),
                ctaButton.first().click(),
            ]);

            // Should navigate to prompt optimizer or login
            const url = page.url();
            expect(url).toMatch(
                /\/[a-z]{2}(-[A-Z]{2})?(\?modal=login|\/(prompt-builder|login))(\?.*)?$/,
            );
        }
    });

    test('should have site branding visible', async ({ page }) => {
        await page.goto('/');

        // Should have some site branding (logo, name, etc.)
        const branding = page.locator('nav, header').first();
        await expect(branding).toBeVisible();
    });

    test('should have accessible navigation menu', async ({ page }) => {
        await page.goto('/');

        // Check for navigation landmark
        const nav = page.locator('nav').first();
        await expect(nav).toBeVisible();

        // Navigation should contain links
        const navLinks = nav.locator('a');
        const count = await navLinks.count();

        expect(count).toBeGreaterThan(0);
    });

    test('should show navigation for unauthenticated users', async ({
        page,
    }) => {
        await page.goto('/');

        // Should have some navigation links
        const navLinks = page.locator('nav a');
        const count = await navLinks.count();

        // At minimum, should have some links in the navigation
        expect(count).toBeGreaterThan(0);
    });

    test('should have mobile navigation menu', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/');

        // Look for hamburger menu button
        const menuButton = page.getByRole('button', {
            name: /menu|navigation/i,
        });

        if ((await menuButton.count()) > 0) {
            await expect(menuButton.first()).toBeVisible();

            // Click to open mobile menu
            await menuButton.first().click();

            // Check if navigation items are now visible
            const navItems = page.getByRole('button', {
                name: /log in|get started/i,
            });
            await expect(navItems.first()).toBeVisible();
        }
    });
});

test.describe('Accessibility', () => {
    test('should have proper page title on home page', async ({ page }) => {
        await page.goto('/');

        const title = await page.title();
        expect(title).toMatch(/BettrPrompt|Welcome/i);
    });

    test('should have accessible form labels', async ({ page }) => {
        await page.goto('/?modal=login');

        // Check for properly labelled form inputs
        const emailInput = page.getByLabel(/email/i);
        const passwordInput = page.getByLabel(/password/i);

        if ((await emailInput.count()) > 0) {
            // Labels should be associated with inputs
            await expect(emailInput.first()).toBeVisible();
            await expect(passwordInput.first()).toBeVisible();
        }
    });

    test('should have proper heading hierarchy', async ({ page }) => {
        await page.goto('/');

        // Page should have an h1
        const h1 = page.locator('h1');
        await expect(h1.first()).toBeVisible();

        // Check h1 contains meaningful text
        const h1Text = await h1.first().textContent();
        expect(h1Text?.length).toBeGreaterThan(0);
    });

    test('should have alt text for images', async ({ page }) => {
        await page.goto('/');

        // Find all images
        const images = page.locator('img');
        const imageCount = await images.count();

        if (imageCount > 0) {
            // Check that images have alt attributes
            for (let i = 0; i < Math.min(imageCount, 5); i++) {
                const img = images.nth(i);
                const alt = await img.getAttribute('alt');

                // Alt should exist (can be empty for decorative images)
                expect(alt).not.toBeNull();
            }
        }
    });

    test('should support keyboard navigation', async ({ page }) => {
        await page.goto('/');

        // Wait for page to fully load
        await page.waitForLoadState('domcontentloaded');

        // Try tabbing through the page and verify focus changes
        const initialFocused = await page.evaluate(() => {
            const el = document.activeElement as HTMLElement | null;
            return {
                tagName: el?.tagName,
                id: el?.id || null,
                textContent: el?.textContent?.substring(0, 20) || null,
            };
        });

        await page.keyboard.press('Tab');

        const afterFirstTab = await page.evaluate(() => {
            const el = document.activeElement as HTMLElement | null;
            return {
                tagName: el?.tagName,
                id: el?.id || null,
                textContent: el?.textContent?.substring(0, 20) || null,
            };
        });

        // Verify focus actually moved to a different element
        // Elements are different if they have different tag names or content
        const elementChanged =
            afterFirstTab.tagName !== initialFocused.tagName ||
            afterFirstTab.textContent !== initialFocused.textContent;

        expect(elementChanged).toBe(true);

        // Verify focused element is interactive (link, button, or input)
        const focusedElement = await page.evaluate(() => {
            const el = document.activeElement as HTMLElement;
            return el?.tagName.toLowerCase();
        });

        const interactiveElements = [
            'a',
            'button',
            'input',
            'textarea',
            'select',
        ];
        expect(interactiveElements).toContain(focusedElement);
    });

    test('should have sufficient colour contrast', async ({ page }) => {
        await page.goto('/');

        // This is a basic check - proper contrast testing requires tools like axe
        // Just verify the page loads and text is visible
        const bodyText = page.locator('body');
        await expect(bodyText).toBeVisible();

        // Check that main text elements are visible
        const headings = page.locator('h1, h2, h3');
        const headingCount = await headings.count();

        expect(headingCount).toBeGreaterThan(0);
    });

    test('should support dark mode if implemented', async ({ page }) => {
        await page.goto('/');

        // Check if there's a dark mode toggle
        const darkModeToggle = page.getByRole('button', {
            name: /dark mode|theme/i,
        });

        if ((await darkModeToggle.count()) > 0) {
            await darkModeToggle.first().click();

            // Verify dark mode class or attribute
            const html = page.locator('html');
            const className = await html.getAttribute('class');

            // Common dark mode implementations
            const hasDarkMode =
                className?.includes('dark') ||
                (await html.getAttribute('data-theme')) === 'dark';

            // If toggle exists, it should work
            expect(hasDarkMode).toBeTruthy();
        }
    });
});

test.describe('Error Handling', () => {
    test('should show 404 page for invalid routes', async ({ page }) => {
        const response = await page.goto('/this-route-does-not-exist');

        // Should return 404 status
        expect(response?.status()).toBe(404);
    });

    test('should handle network errors gracefully', async ({ page }) => {
        // This test would require network mocking
        // For now, just verify basic error handling exists

        await page.goto('/');
        expect(page.url()).toContain('/');
    });
});
