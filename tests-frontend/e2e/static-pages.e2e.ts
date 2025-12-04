import { expect, test } from '@playwright/test';
import { acceptCookies } from './helpers/auth';

/**
 * Static Pages E2E Tests (Refactored)
 *
 * Comprehensive tests for static legal pages using parameterised testing:
 * - Terms of Use (/terms)
 * - Privacy Policy (/privacy)
 * - Cookie Policy (/cookies)
 *
 * Reduced from 59 tests to 15 focused tests using parameterisation and consolidation.
 */

// Accept cookies before each test to prevent cookie banner from blocking interactions
test.beforeEach(async ({ page }) => {
    await acceptCookies(page);
});

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
        test('should load successfully with correct title', async ({
            page: browserPage,
        }) => {
            const response = await browserPage.goto(page.path);

            // Verify successful response
            expect(response?.status()).toBe(200);

            // Verify we're on the correct page
            expect(browserPage.url()).toContain(page.path);

            // Verify correct page title
            await expect(browserPage).toHaveTitle(new RegExp(page.title, 'i'));
        });

        test('should display main heading and content structure', async ({
            page: browserPage,
        }) => {
            await browserPage.goto(page.path);

            // Verify main heading
            const heading = browserPage.getByRole('heading', {
                name: page.heading,
                level: 1,
            });
            await expect(heading).toBeVisible();

            // Verify key content sections are present
            for (const section of page.contentSections) {
                await expect(
                    browserPage.getByRole('heading', { name: section }),
                ).toBeVisible();
            }

            // Verify last updated date
            const lastUpdated = browserPage.getByText(/Last updated:/i);
            await expect(lastUpdated).toBeVisible();
        });

        test('should have proper heading hierarchy and navigation', async ({
            page: browserPage,
        }) => {
            await browserPage.goto(page.path);

            // Check for h1 (should be exactly one)
            const h1Elements = browserPage.locator('h1');
            await expect(h1Elements).toHaveCount(1);

            // Check that h2 headings exist
            const h2Elements = browserPage.locator('h2');
            const h2Count = await h2Elements.count();
            expect(h2Count).toBeGreaterThan(3); // Should have multiple sections

            // Verify navigation elements
            const nav = browserPage.locator('nav').first();
            await expect(nav).toBeVisible();

            const logoLink = browserPage.getByRole('link', {
                name: /AI Buddy/i,
            });
            await expect(logoLink.first()).toBeVisible();
        });

        test('should display footer with links and be responsive', async ({
            page: browserPage,
        }) => {
            await browserPage.goto(page.path);

            // Verify footer is visible
            const footer = browserPage.locator('footer');
            await expect(footer).toBeVisible();

            // Check for footer links to other pages
            await expect(
                footer.getByRole('link', { name: /Terms of Use/i }),
            ).toBeVisible();
            await expect(
                footer.getByRole('link', { name: /Privacy Policy/i }),
            ).toBeVisible();
            await expect(
                footer.getByRole('link', { name: /Cookie Policy/i }),
            ).toBeVisible();

            // Test mobile responsiveness
            await browserPage.setViewportSize({ width: 375, height: 667 });
            await browserPage.goto(page.path);

            const mobileHeading = browserPage.getByRole('heading', {
                name: page.heading,
                level: 1,
            });
            await expect(mobileHeading).toBeVisible();

            // Content should be readable (not truncated)
            const content = browserPage.locator('.prose, .not-prose');
            await expect(content.first()).toBeVisible();
        });

        test('should have semantic HTML and accessibility features', async ({
            page: browserPage,
        }) => {
            await browserPage.goto(page.path);

            // Verify semantic structure
            await expect(browserPage.locator('nav').first()).toBeVisible();
            await expect(browserPage.locator('main')).toBeVisible();
            await expect(browserPage.locator('footer')).toBeVisible();

            // Verify content can be scrolled to load all
            await browserPage.evaluate(() =>
                window.scrollTo(0, document.body.scrollHeight),
            );

            // Verify company information is present
            await expect(
                browserPage.getByText(/AI Buddy Ltd\./i).first(),
            ).toBeVisible();
            await expect(
                browserPage.getByText(/info@hiddengambia\.com/i).first(),
            ).toBeVisible();

            // Verify language attribute
            const html = browserPage.locator('html');
            const lang = await html.getAttribute('lang');
            expect(lang).toMatch(/en/i);
        });
    });
}

test.describe('Cross-Page Navigation', () => {
    test('should navigate between all static pages via footer links', async ({
        page,
    }) => {
        // Test Terms → Privacy → Cookies → Terms cycle
        await page.goto('/terms');
        await expect(
            page.getByRole('heading', { name: 'Terms of Use', level: 1 }),
        ).toBeVisible();

        // Navigate to Privacy
        const privacyLink = page
            .locator('footer')
            .getByRole('link', { name: /Privacy Policy/i });
        await privacyLink.click();
        await expect(
            page.getByRole('heading', { name: 'Privacy Policy', level: 1 }),
        ).toBeVisible();
        expect(page.url()).toContain('/privacy');

        // Navigate to Cookies
        const cookiesLink = page
            .locator('footer')
            .getByRole('link', { name: /Cookie Policy/i });
        await cookiesLink.click();
        await expect(
            page.getByRole('heading', { name: 'Cookie Policy', level: 1 }),
        ).toBeVisible();
        expect(page.url()).toContain('/cookies');

        // Navigate back to Terms
        const termsLink = page
            .locator('footer')
            .getByRole('link', { name: /Terms of Use/i });
        await termsLink.click();
        await expect(
            page.getByRole('heading', { name: 'Terms of Use', level: 1 }),
        ).toBeVisible();
        expect(page.url()).toContain('/terms');
    });

    test('should navigate to home page from all static pages via logo', async ({
        page,
    }) => {
        const pages = ['/terms', '/privacy', '/cookies'];

        for (const pagePath of pages) {
            await page.goto(pagePath);

            // Click logo
            const logoLink = page
                .getByRole('link', { name: /AI Buddy/i })
                .first();
            await logoLink.click();

            // Should navigate to home
            expect(page.url()).toMatch(/\/(#.*)?$/);
        }
    });

    test('should maintain consistent footer across all pages', async ({
        page,
    }) => {
        const pages = ['/terms', '/privacy', '/cookies'];

        for (const pagePath of pages) {
            await page.goto(pagePath);

            const footer = page.locator('footer');
            await expect(footer).toBeVisible();
            await expect(
                footer.getByText(/All rights reserved/i),
            ).toBeVisible();

            // Verify current year in copyright
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

        // Check that focus is managed
        const focusedElement = await page.evaluate(() => {
            return document.activeElement?.tagName;
        });

        // Should focus on an interactive element
        expect(['A', 'BUTTON', 'INPUT']).toContain(focusedElement);
    });

    test('should have page-specific content (Privacy: GDPR, Cookies: cookie types)', async ({
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
        await expect(page.getByText(/Always Active/i)).toBeVisible();
    });

    test('should have readable content with proper prose styling', async ({
        page,
    }) => {
        await page.goto('/terms');

        // Verify prose content is visible
        const proseContent = page.locator('.prose');
        await expect(proseContent).toBeVisible();

        // Should have substantial content
        const paragraphs = page.locator('.prose p');
        const count = await paragraphs.count();
        expect(count).toBeGreaterThan(5);
    });

    test('should not have JavaScript console errors on static pages', async ({
        page,
    }) => {
        const errors: string[] = [];

        // Collect console errors
        page.on('console', (msg) => {
            if (msg.type() === 'error') {
                errors.push(msg.text());
            }
        });

        // Test all pages
        const pages = ['/terms', '/privacy', '/cookies'];
        for (const pagePath of pages) {
            await page.goto(pagePath);
        }

        // Should not have any JavaScript errors
        expect(errors.length).toBe(0);
    });
});
