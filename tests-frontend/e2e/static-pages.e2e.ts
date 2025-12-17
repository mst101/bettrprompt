import { expect, test } from '@playwright/test';

/**
 * Static Pages E2E Tests (Optimized)
 *
 * Comprehensive tests for static legal pages using parameterised testing:
 * - Terms of Use (/terms)
 * - Privacy Policy (/privacy)
 * - Cookie Policy (/cookies)
 *
 * Tests verify page structure, content, navigation, and accessibility.
 * Uses parameterised testing for efficient coverage across all static pages.
 */

// Parameterised test data for static pages
const staticPages = [
    {
        path: '/terms',
        title: 'Terms of Use',
        heading: 'Terms of Use',
        contentSections: [
            /Agreement to Terms/i,
            /Description of Service/i,
            /User Accounts/i,
            /Acceptable Use/i,
            /Intellectual Property/i,
        ],
    },
    {
        path: '/privacy',
        title: 'Privacy Policy',
        heading: 'Privacy Policy',
        contentSections: [
            /Introduction/i,
            /Data Controller/i,
            /Information We Collect/i,
            /Legal Basis for Processing/i,
            /Your Rights Under UK GDPR/i,
        ],
    },
    {
        path: '/cookies',
        title: 'Cookie Policy',
        heading: 'Cookie Policy',
        contentSections: [
            /What Are Cookies\?/i,
            /How We Use Cookies/i,
            /Types of Cookies We Use/i,
            /How to Control Cookies/i,
        ],
    },
];

// Parameterised basic page tests
for (const page of staticPages) {
    test.describe(`${page.title} Page`, () => {
        test('should load with correct structure and content', async ({
            page: browserPage,
        }) => {
            // Navigate and verify successful response
            const response = await browserPage.goto(page.path);
            expect(response?.status()).toBe(200);
            expect(browserPage.url()).toContain(page.path);
            await expect(browserPage).toHaveTitle(new RegExp(page.title, 'i'));

            // Verify main heading and semantic structure
            const heading = browserPage.getByRole('heading', {
                name: page.heading,
                level: 1,
            });
            await expect(heading).toBeVisible();

            // Verify navigation and footer
            await expect(browserPage.locator('nav').first()).toBeVisible();
            await expect(browserPage.locator('footer')).toBeVisible();

            // Verify key content sections are present
            for (const section of page.contentSections) {
                await expect(
                    browserPage.getByRole('heading', { name: section }),
                ).toBeVisible();
            }
        });

        test('should have proper heading hierarchy and navigation links', async ({
            page: browserPage,
        }) => {
            await browserPage.goto(page.path);

            // Check heading hierarchy
            const h1Elements = browserPage.locator('h1');
            await expect(h1Elements).toHaveCount(1);

            const h2Elements = browserPage.locator('h2');
            const h2Count = await h2Elements.count();
            expect(h2Count).toBeGreaterThan(3);

            // Verify logo link exists (can be "bettr" or "#bettrprompt")
            const logoLink = browserPage
                .locator('a[href="/"]')
                .filter({ has: browserPage.locator('text=/bettr|prompt/i') });
            await expect(logoLink.first()).toBeVisible();

            // Verify footer links
            const footer = browserPage.locator('footer');
            await expect(
                footer.getByRole('link', { name: /Terms of Use/i }),
            ).toBeVisible();
            await expect(
                footer.getByRole('link', { name: /Privacy Policy/i }),
            ).toBeVisible();
            await expect(
                footer.getByRole('link', { name: /Cookie Policy/i }),
            ).toBeVisible();
        });

        test('should be responsive and accessible', async ({
            page: browserPage,
        }) => {
            await browserPage.goto(page.path);

            // Test desktop view - verify footer links
            const footer = browserPage.locator('footer');
            await expect(footer).toBeVisible();

            // Test mobile responsiveness
            await browserPage.setViewportSize({ width: 375, height: 667 });
            await browserPage.goto(page.path);

            const mobileHeading = browserPage.getByRole('heading', {
                name: page.heading,
                level: 1,
            });
            await expect(mobileHeading).toBeVisible();

            // Content should be readable on mobile
            const content = browserPage.locator(
                '[data-testid="prose-content"], .prose',
            );
            await expect(content.first()).toBeVisible();

            // Verify semantic HTML and language attribute
            const html = browserPage.locator('html');
            const lang = await html.getAttribute('lang');
            expect(lang).toMatch(/en/i);
        });
    });
}

test.describe('Cross-Page Navigation', () => {
    test('should navigate between static pages via footer links', async ({
        page,
    }) => {
        // Start at Terms page
        await page.goto('/terms');
        await expect(
            page.getByRole('heading', { name: 'Terms of Use', level: 1 }),
        ).toBeVisible();

        // Navigate to Privacy page - dismiss cookie banner first if present
        const cookieBannerButton = page
            .locator('[aria-label="Cookie consent banner"]')
            .getByRole('button')
            .first();
        const cookieBannerVisible = await cookieBannerButton
            .isVisible()
            .catch(() => false);
        if (cookieBannerVisible) {
            await cookieBannerButton.click();
        }

        await page
            .locator('footer')
            .getByRole('link', { name: /Privacy Policy/i })
            .click();
        await expect(
            page.getByRole('heading', { name: 'Privacy Policy', level: 1 }),
        ).toBeVisible();
        expect(page.url()).toContain('/privacy');

        // Navigate to Cookies page
        await page
            .locator('footer')
            .getByRole('link', { name: /Cookie Policy/i })
            .click();
        await expect(
            page.getByRole('heading', { name: 'Cookie Policy', level: 1 }),
        ).toBeVisible();
        expect(page.url()).toContain('/cookies');
    });

    test('should have logo link on all static pages', async ({ page }) => {
        // Test that logo link exists on each static page
        const staticPagePaths = ['/terms', '/privacy', '/cookies'];

        for (const pagePath of staticPagePaths) {
            await page.goto(pagePath);

            // Verify logo link exists (can be "bettr" or "#bettrprompt")
            const logoLink = page
                .locator('a[href="/"]')
                .filter({ has: page.locator('text=/bettr|prompt/i') });
            await expect(logoLink.first()).toBeVisible();
        }
    });

    test('should have consistent footer with copyright on all pages', async ({
        page,
    }) => {
        const staticPagePaths = ['/terms', '/privacy', '/cookies'];

        for (const pagePath of staticPagePaths) {
            await page.goto(pagePath);

            const footer = page.locator('footer');
            await expect(footer).toBeVisible();
            await expect(
                footer.getByText(/All rights reserved/i),
            ).toBeVisible();

            // Verify copyright year
            const currentYear = new Date().getFullYear();
            await expect(
                footer.getByText(new RegExp(currentYear.toString())),
            ).toBeVisible();
        }
    });
});

test.describe('Accessibility and Content Quality', () => {
    test('should support keyboard navigation on static pages', async ({
        page,
    }) => {
        await page.goto('/terms');

        // Tab through interactive elements
        await page.keyboard.press('Tab');
        await page.keyboard.press('Tab');

        // Check that focus is on an interactive element
        const focusedElement = await page.evaluate(() => {
            return document.activeElement?.tagName;
        });
        expect(['A', 'BUTTON', 'INPUT']).toContain(focusedElement);
    });

    test('should have page-specific content and proper formatting', async ({
        page,
    }) => {
        // Privacy page should have GDPR references
        await page.goto('/privacy');
        await expect(page.getByText(/UK GDPR/i).first()).toBeVisible();
        await expect(page.getByText(/Data Protection Act 2018/i)).toBeVisible();

        // Cookies page should have cookie type categories
        await page.goto('/cookies');
        await expect(
            page.getByText(/Essential Cookies/i).first(),
        ).toBeVisible();
        await expect(
            page.getByText(/Functional Cookies/i).first(),
        ).toBeVisible();
        await expect(
            page.getByText(/Analytics Cookies/i).first(),
        ).toBeVisible();

        // Terms page should have readable prose content
        await page.goto('/terms');
        const proseContent = page.locator(
            '[data-testid="prose-content"], .prose',
        );
        await expect(proseContent).toBeVisible();

        const paragraphs = proseContent.locator('p');
        const count = await paragraphs.count();
        expect(count).toBeGreaterThan(5);
    });

    test('should not have JavaScript console errors on static pages', async ({
        page,
    }) => {
        const errors: string[] = [];

        // Collect console errors while navigating all pages
        page.on('console', (msg) => {
            if (msg.type() === 'error') {
                errors.push(msg.text());
            }
        });

        const staticPagePaths = ['/terms', '/privacy', '/cookies'];
        for (const pagePath of staticPagePaths) {
            await page.goto(pagePath);
        }

        // Should not have any JavaScript errors
        expect(errors.length).toBe(0);
    });
});
