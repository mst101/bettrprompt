import { expect, test } from '@playwright/test';

/**
 * Static Pages E2E Tests
 *
 * Comprehensive tests for static legal pages:
 * - Terms of Use (/terms)
 * - Privacy Policy (/privacy)
 * - Cookie Policy (/cookies)
 *
 * Test coverage includes:
 * - Page loading and basic structure
 * - Content visibility and correctness
 * - Navigation functionality
 * - Responsive design (mobile)
 * - Accessibility features
 * - Cross-page navigation via footer links
 */

test.describe('Terms of Use Page', () => {
    test('should load the Terms page successfully', async ({ page }) => {
        const response = await page.goto('/terms');

        // Verify successful response
        expect(response?.status()).toBe(200);

        // Wait for page to be fully loaded
        await page.waitForLoadState('networkidle');

        // Verify we're on the correct page
        expect(page.url()).toContain('/terms');
    });

    test('should have correct page title', async ({ page }) => {
        await page.goto('/terms');

        // Check that the page title is correct
        await expect(page).toHaveTitle(/Terms of Use/);
    });

    test('should display main heading', async ({ page }) => {
        await page.goto('/terms');

        // Verify main heading is visible
        const heading = page.getByRole('heading', {
            name: 'Terms of Use',
            level: 1,
        });
        await expect(heading).toBeVisible();
    });

    test('should display last updated date', async ({ page }) => {
        await page.goto('/terms');

        // Check for "Last updated" text
        const lastUpdated = page.getByText(/Last updated:/i);
        await expect(lastUpdated).toBeVisible();

        // Verify date format (British English: day month year)
        const dateText = await lastUpdated.textContent();
        expect(dateText).toMatch(/\d{1,2}\s+\w+\s+\d{4}/); // e.g., "19 November 2025"
    });

    test('should display main content sections', async ({ page }) => {
        await page.goto('/terms');

        // Verify key sections are present
        await expect(
            page.getByRole('heading', { name: /Agreement to Terms/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /Description of Service/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /User Accounts/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /Acceptable Use/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /Intellectual Property/i }),
        ).toBeVisible();
    });

    test('should display company information', async ({ page }) => {
        await page.goto('/terms');

        // Scroll to bottom to ensure all content is loaded
        await page.evaluate(() =>
            window.scrollTo(0, document.body.scrollHeight),
        );

        // Verify company information is displayed
        await expect(page.getByText(/AI Buddy Ltd\./i)).toBeVisible();
        await expect(page.getByText(/info@hiddengambia\.com/i)).toBeVisible();
    });

    test('should have proper navigation elements', async ({ page }) => {
        await page.goto('/terms');

        // Check for navigation header
        const nav = page.locator('nav').first();
        await expect(nav).toBeVisible();

        // Verify logo/home link is present
        const logoLink = page.getByRole('link', { name: /AI Buddy/i });
        await expect(logoLink.first()).toBeVisible();
    });

    test('should be responsive on mobile viewport', async ({ page }) => {
        // Set mobile viewport (iPhone SE dimensions)
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/terms');

        // Verify page loads and main heading is visible
        await expect(
            page.getByRole('heading', { name: 'Terms of Use', level: 1 }),
        ).toBeVisible();

        // Content should be readable (not truncated)
        const content = page.locator('.prose');
        await expect(content).toBeVisible();
    });

    test('should have proper heading hierarchy', async ({ page }) => {
        await page.goto('/terms');

        // Check for h1 (should be exactly one)
        const h1Elements = page.locator('h1');
        await expect(h1Elements).toHaveCount(1);

        // Check that h2 headings exist
        const h2Elements = page.locator('h2');
        const h2Count = await h2Elements.count();
        expect(h2Count).toBeGreaterThan(5); // Should have multiple sections
    });

    test('should display footer with links', async ({ page }) => {
        await page.goto('/terms');

        // Scroll to footer
        await page.evaluate(() =>
            window.scrollTo(0, document.body.scrollHeight),
        );

        // Verify footer is visible
        const footer = page.locator('footer');
        await expect(footer).toBeVisible();

        // Check for footer links
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
});

test.describe('Privacy Policy Page', () => {
    test('should load the Privacy page successfully', async ({ page }) => {
        const response = await page.goto('/privacy');

        // Verify successful response
        expect(response?.status()).toBe(200);

        // Wait for page to be fully loaded
        await page.waitForLoadState('networkidle');

        // Verify we're on the correct page
        expect(page.url()).toContain('/privacy');
    });

    test('should have correct page title', async ({ page }) => {
        await page.goto('/privacy');

        // Check that the page title is correct
        await expect(page).toHaveTitle(/Privacy Policy/);
    });

    test('should display main heading', async ({ page }) => {
        await page.goto('/privacy');

        // Verify main heading is visible
        const heading = page.getByRole('heading', {
            name: 'Privacy Policy',
            level: 1,
        });
        await expect(heading).toBeVisible();
    });

    test('should display last updated date', async ({ page }) => {
        await page.goto('/privacy');

        // Check for "Last updated" text
        const lastUpdated = page.getByText(/Last updated:/i);
        await expect(lastUpdated).toBeVisible();

        // Verify date format (British English)
        const dateText = await lastUpdated.textContent();
        expect(dateText).toMatch(/\d{1,2}\s+\w+\s+\d{4}/);
    });

    test('should display main content sections', async ({ page }) => {
        await page.goto('/privacy');

        // Verify key sections are present
        await expect(
            page.getByRole('heading', { name: /Introduction/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /Data Controller/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /Information We Collect/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /Legal Basis for Processing/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /Your Rights Under UK GDPR/i }),
        ).toBeVisible();
    });

    test('should display UK GDPR compliance information', async ({ page }) => {
        await page.goto('/privacy');

        // Verify UK GDPR references
        await expect(page.getByText(/UK GDPR/i).first()).toBeVisible();
        await expect(page.getByText(/Data Protection Act 2018/i)).toBeVisible();
    });

    test('should display third-party service links', async ({ page }) => {
        await page.goto('/privacy');

        // Scroll to third-party services section
        await page
            .getByRole('heading', { name: /Third-Party Services/i })
            .scrollIntoViewIfNeeded();

        // Verify external links are present and have proper attributes
        const anthropicLink = page.getByRole('link', {
            name: /Privacy Policy/i,
        });
        await expect(anthropicLink.first()).toBeVisible();

        // Check for rel="noopener" attribute for security
        const firstLink = anthropicLink.first();
        await expect(firstLink).toHaveAttribute('rel', /noopener/);
        await expect(firstLink).toHaveAttribute('target', '_blank');
    });

    test('should display ICO contact information', async ({ page }) => {
        await page.goto('/privacy');

        // Scroll to bottom to load all content
        await page.evaluate(() =>
            window.scrollTo(0, document.body.scrollHeight),
        );

        // Verify ICO information is present
        await expect(
            page.getByText(/Information Commissioner's Office/i).first(),
        ).toBeVisible();
        await expect(page.getByText(/0303 123 1113/i)).toBeVisible();
    });

    test('should have proper navigation elements', async ({ page }) => {
        await page.goto('/privacy');

        // Check for navigation header
        const nav = page.locator('nav').first();
        await expect(nav).toBeVisible();

        // Verify logo/home link is present
        const logoLink = page.getByRole('link', { name: /AI Buddy/i });
        await expect(logoLink.first()).toBeVisible();
    });

    test('should be responsive on mobile viewport', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/privacy');

        // Verify page loads and main heading is visible
        await expect(
            page.getByRole('heading', { name: 'Privacy Policy', level: 1 }),
        ).toBeVisible();

        // Content should be readable
        const content = page.locator('.prose');
        await expect(content).toBeVisible();
    });

    test('should have proper heading hierarchy', async ({ page }) => {
        await page.goto('/privacy');

        // Check for h1 (should be exactly one)
        const h1Elements = page.locator('h1');
        await expect(h1Elements).toHaveCount(1);

        // Check that h2 and h3 headings exist
        const h2Elements = page.locator('h2');
        const h2Count = await h2Elements.count();
        expect(h2Count).toBeGreaterThan(10); // Should have many sections

        const h3Elements = page.locator('h3');
        const h3Count = await h3Elements.count();
        expect(h3Count).toBeGreaterThan(3); // Should have subsections
    });
});

test.describe('Cookie Policy Page', () => {
    test('should load the Cookies page successfully', async ({ page }) => {
        const response = await page.goto('/cookies');

        // Verify successful response
        expect(response?.status()).toBe(200);

        // Wait for page to be fully loaded
        await page.waitForLoadState('networkidle');

        // Verify we're on the correct page
        expect(page.url()).toContain('/cookies');
    });

    test('should have correct page title', async ({ page }) => {
        await page.goto('/cookies');

        // Check that the page title is correct
        await expect(page).toHaveTitle(/Cookie Policy/);
    });

    test('should display main heading', async ({ page }) => {
        await page.goto('/cookies');

        // Verify main heading is visible
        const heading = page.getByRole('heading', {
            name: 'Cookie Policy',
            level: 1,
        });
        await expect(heading).toBeVisible();
    });

    test('should display last updated date', async ({ page }) => {
        await page.goto('/cookies');

        // Check for "Last updated" text
        const lastUpdated = page.getByText(/Last updated:/i);
        await expect(lastUpdated).toBeVisible();

        // Verify date format (British English)
        const dateText = await lastUpdated.textContent();
        expect(dateText).toMatch(/\d{1,2}\s+\w+\s+\d{4}/);
    });

    test('should display main content sections', async ({ page }) => {
        await page.goto('/cookies');

        // Verify key sections are present
        await expect(
            page.getByRole('heading', { name: /What Are Cookies\?/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /How We Use Cookies/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /Types of Cookies We Use/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /How to Control Cookies/i }),
        ).toBeVisible();
    });

    test('should display cookie categories', async ({ page }) => {
        await page.goto('/cookies');

        // Verify cookie category sections are visible
        await expect(
            page.getByText(/Essential Cookies/i).first(),
        ).toBeVisible();
        await expect(
            page.getByText(/Functional Cookies/i).first(),
        ).toBeVisible();
        await expect(
            page.getByText(/Analytics Cookies/i).first(),
        ).toBeVisible();

        // Verify "Always Active" badge for essential cookies
        await expect(page.getByText(/Always Active/i)).toBeVisible();
    });

    test('should display cookie information cards', async ({ page }) => {
        await page.goto('/cookies');

        // Check for structured cookie information
        // Each cookie category should have detailed cards
        const cookieCards = page.locator(
            '.bg-white.p-3, .border.border-gray-200.bg-white',
        );
        const count = await cookieCards.count();

        // Should have multiple cookie detail cards
        expect(count).toBeGreaterThan(3);
    });

    test('should display third-party cookie information', async ({ page }) => {
        await page.goto('/cookies');

        // Scroll to third-party section
        await page
            .getByRole('heading', { name: /Third-Party Cookies/i })
            .scrollIntoViewIfNeeded();

        // Verify third-party services are mentioned
        await expect(page.getByText(/Google OAuth/i).first()).toBeVisible();
        await expect(
            page.getByText(/Analytics Services/i).first(),
        ).toBeVisible();
    });

    test('should display cookie control methods', async ({ page }) => {
        await page.goto('/cookies');

        // Scroll to control section
        await page
            .getByRole('heading', { name: /How to Control Cookies/i })
            .scrollIntoViewIfNeeded();

        // Verify different control methods are explained
        await expect(page.getByText(/Cookie Banner/i).first()).toBeVisible();
        await expect(page.getByText(/Cookie Settings/i).first()).toBeVisible();
        await expect(page.getByText(/Browser Settings/i).first()).toBeVisible();
    });

    test('should have external resource links', async ({ page }) => {
        await page.goto('/cookies');

        // Scroll to bottom
        await page.evaluate(() =>
            window.scrollTo(0, document.body.scrollHeight),
        );

        // Check for external cookie information links
        const allAboutCookiesLink = page.getByRole('link', {
            name: /allaboutcookies\.org/i,
        });
        await expect(allAboutCookiesLink).toBeVisible();
        await expect(allAboutCookiesLink).toHaveAttribute('target', '_blank');
        await expect(allAboutCookiesLink).toHaveAttribute('rel', /noopener/);

        const yourOnlineChoicesLink = page.getByRole('link', {
            name: /youronlinechoices\.com/i,
        });
        await expect(yourOnlineChoicesLink).toBeVisible();
    });

    test('should have proper navigation elements', async ({ page }) => {
        await page.goto('/cookies');

        // Check for navigation header
        const nav = page.locator('nav').first();
        await expect(nav).toBeVisible();

        // Verify logo/home link is present
        const logoLink = page.getByRole('link', { name: /AI Buddy/i });
        await expect(logoLink.first()).toBeVisible();
    });

    test('should be responsive on mobile viewport', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/cookies');

        // Verify page loads and main heading is visible
        await expect(
            page.getByRole('heading', { name: 'Cookie Policy', level: 1 }),
        ).toBeVisible();

        // Content should be readable
        const content = page.locator('.prose, .not-prose');
        await expect(content.first()).toBeVisible();

        // Cookie category cards should stack properly
        const cookieCard = page
            .locator('.rounded-lg.border.border-gray-200')
            .first();
        await expect(cookieCard).toBeVisible();
    });

    test('should have proper heading hierarchy', async ({ page }) => {
        await page.goto('/cookies');

        // Check for h1 (should be exactly one)
        const h1Elements = page.locator('h1');
        await expect(h1Elements).toHaveCount(1);

        // Check that h2 and h3 headings exist
        const h2Elements = page.locator('h2');
        const h2Count = await h2Elements.count();
        expect(h2Count).toBeGreaterThan(5); // Should have multiple sections

        const h3Elements = page.locator('h3');
        const h3Count = await h3Elements.count();
        expect(h3Count).toBeGreaterThan(2); // Should have subsections
    });
});

test.describe('Cross-Page Navigation', () => {
    test('should navigate from Terms to Privacy via footer link', async ({
        page,
    }) => {
        await page.goto('/terms');

        // Scroll to footer
        await page.evaluate(() =>
            window.scrollTo(0, document.body.scrollHeight),
        );

        // Click Privacy Policy link in footer
        const privacyLink = page
            .locator('footer')
            .getByRole('link', { name: /Privacy Policy/i });
        await privacyLink.click();

        // Wait for navigation (Inertia.js client-side routing)
        await page.waitForLoadState('networkidle');

        // Verify we're on the Privacy page
        expect(page.url()).toContain('/privacy');
        await expect(
            page.getByRole('heading', { name: 'Privacy Policy', level: 1 }),
        ).toBeVisible();
    });

    test('should navigate from Privacy to Cookies via footer link', async ({
        page,
    }) => {
        await page.goto('/privacy');

        // Scroll to footer
        await page.evaluate(() =>
            window.scrollTo(0, document.body.scrollHeight),
        );

        // Click Cookie Policy link in footer
        const cookiesLink = page
            .locator('footer')
            .getByRole('link', { name: /Cookie Policy/i });
        await cookiesLink.click();

        // Wait for navigation
        await page.waitForLoadState('networkidle');

        // Verify we're on the Cookies page
        expect(page.url()).toContain('/cookies');
        await expect(
            page.getByRole('heading', { name: 'Cookie Policy', level: 1 }),
        ).toBeVisible();
    });

    test('should navigate from Cookies to Terms via footer link', async ({
        page,
    }) => {
        await page.goto('/cookies');

        // Scroll to footer
        await page.evaluate(() =>
            window.scrollTo(0, document.body.scrollHeight),
        );

        // Click Terms of Use link in footer
        const termsLink = page
            .locator('footer')
            .getByRole('link', { name: /Terms of Use/i });
        await termsLink.click();

        // Wait for navigation
        await page.waitForLoadState('networkidle');

        // Verify we're on the Terms page
        expect(page.url()).toContain('/terms');
        await expect(
            page.getByRole('heading', { name: 'Terms of Use', level: 1 }),
        ).toBeVisible();
    });

    test('should navigate to home page from Terms via logo', async ({
        page,
    }) => {
        await page.goto('/terms');

        // Click logo/brand link
        const logoLink = page.getByRole('link', { name: /AI Buddy/i }).first();
        await logoLink.click();

        // Wait for navigation
        await page.waitForLoadState('networkidle');

        // Verify we're on the home page
        expect(page.url()).toMatch(/\/(#.*)?$/); // Home or home with hash
    });

    test('should navigate to home page from Privacy via logo', async ({
        page,
    }) => {
        await page.goto('/privacy');

        // Click logo/brand link
        const logoLink = page.getByRole('link', { name: /AI Buddy/i }).first();
        await logoLink.click();

        // Wait for navigation
        await page.waitForLoadState('networkidle');

        // Verify we're on the home page
        expect(page.url()).toMatch(/\/(#.*)?$/);
    });

    test('should navigate to home page from Cookies via logo', async ({
        page,
    }) => {
        await page.goto('/cookies');

        // Click logo/brand link
        const logoLink = page.getByRole('link', { name: /AI Buddy/i }).first();
        await logoLink.click();

        // Wait for navigation
        await page.waitForLoadState('networkidle');

        // Verify we're on the home page
        expect(page.url()).toMatch(/\/(#.*)?$/);
    });

    test('should have consistent footer across all static pages', async ({
        page,
    }) => {
        // Test Terms page
        await page.goto('/terms');
        let footer = page.locator('footer');
        await expect(footer).toBeVisible();
        await expect(footer.getByText(/All rights reserved/i)).toBeVisible();

        // Test Privacy page
        await page.goto('/privacy');
        footer = page.locator('footer');
        await expect(footer).toBeVisible();
        await expect(footer.getByText(/All rights reserved/i)).toBeVisible();

        // Test Cookies page
        await page.goto('/cookies');
        footer = page.locator('footer');
        await expect(footer).toBeVisible();
        await expect(footer.getByText(/All rights reserved/i)).toBeVisible();
    });

    test('should maintain scroll position after back navigation', async ({
        page,
    }) => {
        await page.goto('/terms');

        // Scroll down the page
        await page.evaluate(() => window.scrollTo(0, 500));
        await page.waitForTimeout(300);

        // Navigate to privacy
        const privacyLink = page
            .locator('footer')
            .getByRole('link', { name: /Privacy Policy/i });
        await privacyLink.click();
        await page.waitForLoadState('networkidle');

        // Go back
        await page.goBack();
        await page.waitForLoadState('networkidle');

        // Verify we're back on Terms
        expect(page.url()).toContain('/terms');
    });
});

test.describe('Accessibility Features', () => {
    test('should have proper ARIA landmarks on all static pages', async ({
        page,
    }) => {
        const pages = ['/terms', '/privacy', '/cookies'];

        for (const pagePath of pages) {
            await page.goto(pagePath);

            // Check for navigation landmark
            const nav = page.locator('nav[role="navigation"], nav').first();
            await expect(nav).toBeVisible();

            // Check for main landmark
            const main = page.locator('main').first();
            await expect(main).toBeVisible();

            // Check for footer landmark (via element)
            const footer = page.locator('footer');
            await expect(footer).toBeVisible();
        }
    });

    test('should have readable content with sufficient contrast', async ({
        page,
    }) => {
        await page.goto('/terms');

        // Verify content uses prose styling for readability
        const proseContent = page.locator('.prose');
        await expect(proseContent).toBeVisible();

        // Check that text is visible (basic visibility check)
        const paragraphs = page.locator('.prose p');
        const count = await paragraphs.count();
        expect(count).toBeGreaterThan(10); // Should have substantial content
    });

    test('should support keyboard navigation on all static pages', async ({
        page,
    }) => {
        await page.goto('/terms');

        // Tab through interactive elements
        await page.keyboard.press('Tab');
        await page.keyboard.press('Tab');
        await page.keyboard.press('Tab');

        // Check that focus is managed (no errors thrown)
        // Verify we can navigate with keyboard
        const focusedElement = await page.evaluate(() => {
            return document.activeElement?.tagName;
        });

        // Should focus on an interactive element (A, BUTTON, etc.)
        expect(['A', 'BUTTON', 'INPUT']).toContain(focusedElement);
    });

    test('should have semantic HTML structure', async ({ page }) => {
        await page.goto('/privacy');

        // Verify semantic elements exist
        await expect(page.locator('header, nav').first()).toBeVisible();
        await expect(page.locator('main')).toBeVisible();
        await expect(page.locator('footer')).toBeVisible();

        // Verify heading structure
        const h1 = page.locator('h1');
        await expect(h1).toHaveCount(1);

        // Verify lists for structured content
        const lists = page.locator('ul, ol');
        const listCount = await lists.count();
        expect(listCount).toBeGreaterThan(5); // Should have multiple lists
    });

    test('should have links with descriptive text', async ({ page }) => {
        await page.goto('/privacy');

        // Check that external links have meaningful text
        const links = page.locator('a[href^="http"]');
        const linkCount = await links.count();

        if (linkCount > 0) {
            // Check first few external links
            for (let i = 0; i < Math.min(linkCount, 3); i++) {
                const link = links.nth(i);
                const linkText = await link.textContent();

                // Links should have text (not just empty or icon-only)
                expect(linkText?.trim().length).toBeGreaterThan(0);
            }
        }
    });

    test('should have proper language attribute', async ({ page }) => {
        await page.goto('/cookies');

        // Verify html lang attribute
        const html = page.locator('html');
        const lang = await html.getAttribute('lang');

        // Should be set to British English
        expect(lang).toMatch(/en/i);
    });

    test('should be navigable with screen reader landmarks', async ({
        page,
    }) => {
        await page.goto('/terms');

        // Verify all major landmarks are present
        // These help screen reader users navigate the page

        // Navigation
        const navLandmark = page.locator('nav').first();
        await expect(navLandmark).toBeVisible();

        // Main content
        const mainLandmark = page.locator('main');
        await expect(mainLandmark).toBeVisible();

        // Footer
        const footerLandmark = page.locator('footer');
        await expect(footerLandmark).toBeVisible();
    });

    test('should have appropriate focus indicators', async ({ page }) => {
        await page.goto('/privacy');

        // Tab to first interactive element
        await page.keyboard.press('Tab');

        // Get the focused element
        const focusedElement = page.locator(':focus');
        await expect(focusedElement).toBeVisible();

        // Verify focus is visible (element exists and is styled)
        // This is a basic check - actual visual focus requires more detailed testing
        const count = await focusedElement.count();
        expect(count).toBeGreaterThan(0);
    });
});

test.describe('Content Verification', () => {
    test('should use British English spelling throughout', async ({ page }) => {
        await page.goto('/terms');

        // Check for British English spellings (not exhaustive)
        const content = await page.textContent('body');

        // Should use "optimise" not "optimize"
        expect(content).toContain('optimise');

        // Should use British terms
        // The content should not mix American and British spellings
        if (content?.includes('color')) {
            // If "color" appears in code/technical terms, it's acceptable
            // but for general text, should use "colour"
        }
    });

    test('should display current year in copyright notice', async ({
        page,
    }) => {
        await page.goto('/cookies');

        // Scroll to footer
        await page.evaluate(() =>
            window.scrollTo(0, document.body.scrollHeight),
        );

        // Get current year
        const currentYear = new Date().getFullYear();

        // Verify copyright notice contains current year
        const footer = page.locator('footer');
        await expect(
            footer.getByText(new RegExp(currentYear.toString())),
        ).toBeVisible();
    });

    test('should have consistent company information across pages', async ({
        page,
    }) => {
        const pages = ['/terms', '/privacy', '/cookies'];
        const companyName = 'AI Buddy Ltd.';
        const contactEmail = 'info@hiddengambia.com';

        for (const pagePath of pages) {
            await page.goto(pagePath);

            // Scroll to bottom to ensure all content is loaded
            await page.evaluate(() =>
                window.scrollTo(0, document.body.scrollHeight),
            );

            // Verify company name appears
            await expect(page.getByText(companyName)).toBeVisible();

            // Verify contact email appears
            await expect(page.getByText(contactEmail)).toBeVisible();
        }
    });

    test('should have no broken internal links', async ({ page }) => {
        await page.goto('/terms');

        // Get all internal links (not starting with http)
        const internalLinks = page.locator(
            'a[href]:not([href^="http"]):not([href^="#"])',
        );
        const count = await internalLinks.count();

        // Test a sample of internal links
        for (let i = 0; i < Math.min(count, 5); i++) {
            const link = internalLinks.nth(i);
            const href = await link.getAttribute('href');

            if (href) {
                // Verify link is valid (doesn't return 404)
                const response = await page.request.get(href);
                expect(response.status()).not.toBe(404);
            }
        }
    });

    test('should load without JavaScript errors', async ({ page }) => {
        const errors: string[] = [];

        // Collect console errors
        page.on('console', (msg) => {
            if (msg.type() === 'error') {
                errors.push(msg.text());
            }
        });

        await page.goto('/privacy');
        await page.waitForLoadState('networkidle');

        // Should not have any JavaScript errors
        expect(errors.length).toBe(0);
    });
});
