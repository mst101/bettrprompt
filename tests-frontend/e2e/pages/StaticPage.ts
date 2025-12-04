import type { Locator, Page } from '@playwright/test';
import { expect } from '@playwright/test';

/**
 * Page Object Model for Static Pages (Terms, Privacy, Cookies)
 * Encapsulates common interactions for static legal pages
 */
export class StaticPage {
    constructor(readonly page: Page) {}

    // ===== Page Navigation =====

    async gotoTerms(): Promise<void> {
        await this.page.goto('/terms');
    }

    async gotoPrivacy(): Promise<void> {
        await this.page.goto('/privacy');
    }

    async gotoCookies(): Promise<void> {
        await this.page.goto('/cookies');
    }

    async goto(path: '/terms' | '/privacy' | '/cookies'): Promise<void> {
        await this.page.goto(path);
    }

    // ===== Locators =====

    get mainHeading(): Locator {
        return this.page.locator('h1').first();
    }

    get footer(): Locator {
        return this.page.locator('footer');
    }

    get navigationBar(): Locator {
        return this.page.locator('nav').first();
    }

    get lastUpdatedText(): Locator {
        return this.page.getByText(/Last updated:/i);
    }

    get companyName(): Locator {
        return this.page.getByText(/AI Buddy Ltd\./i);
    }

    get contactEmail(): Locator {
        return this.page.getByText(/info@hiddengambia\.com/i);
    }

    getHeadingByName(name: string | RegExp): Locator {
        return this.page.getByRole('heading', { name });
    }

    getFooterLink(name: string | RegExp): Locator {
        return this.footer.getByRole('link', { name });
    }

    // ===== Navigation Methods =====

    async clickLogoToHome(): Promise<void> {
        const logoLink = this.page
            .getByRole('link', { name: /AI Buddy/i })
            .first();
        await logoLink.click();
    }

    async navigateToPage(
        pageName: 'Terms' | 'Privacy' | 'Cookies',
    ): Promise<void> {
        const link = this.getFooterLink(new RegExp(pageName, 'i'));
        await link.click();
    }

    // ===== Content Verification =====

    async hasHeadingHierarchy(): Promise<boolean> {
        const h1Count = await this.page.locator('h1').count();
        const h2Count = await this.page.locator('h2').count();
        return h1Count === 1 && h2Count > 2;
    }

    async getH1Text(): Promise<string | null> {
        return this.mainHeading.textContent();
    }

    async hasContentSection(sectionName: string | RegExp): Promise<boolean> {
        const heading = this.getHeadingByName(sectionName);
        return heading.isVisible({ timeout: 5000 }).catch(() => false);
    }

    async allContentSectionsPresent(
        sections: (string | RegExp)[],
    ): Promise<boolean> {
        for (const section of sections) {
            const exists = await this.hasContentSection(section);
            if (!exists) return false;
        }
        return true;
    }

    // ===== Accessibility Checks =====

    async hasSemanticStructure(): Promise<boolean> {
        const nav = await this.navigationBar.isVisible().catch(() => false);
        const main = await this.page
            .locator('main')
            .isVisible()
            .catch(() => false);
        const footer = await this.footer.isVisible().catch(() => false);
        return nav && main && footer;
    }

    async supportsKeyboardNavigation(): Promise<boolean> {
        await this.page.keyboard.press('Tab');
        const focusedElement = await this.page.evaluate(() => {
            return document.activeElement?.tagName;
        });
        return ['A', 'BUTTON', 'INPUT'].includes(focusedElement || '');
    }

    async hasProperLanguageAttribute(): Promise<boolean> {
        const lang = await this.page.locator('html').getAttribute('lang');
        return /en/i.test(lang || '');
    }

    // ===== Responsive Design =====

    async setMobileViewport(): Promise<void> {
        await this.page.setViewportSize({ width: 375, height: 667 });
    }

    async setDesktopViewport(): Promise<void> {
        await this.page.setViewportSize({ width: 1920, height: 1080 });
    }

    async isResponsive(): Promise<boolean> {
        const mobileContent = await this.mainHeading
            .isVisible()
            .catch(() => false);
        return mobileContent;
    }

    // ===== Footer Verification =====

    async hasFooterLinks(): Promise<boolean> {
        const termsLink = await this.getFooterLink(/Terms/i)
            .isVisible()
            .catch(() => false);
        const privacyLink = await this.getFooterLink(/Privacy/i)
            .isVisible()
            .catch(() => false);
        const cookiesLink = await this.getFooterLink(/Cookies?/i)
            .isVisible()
            .catch(() => false);
        return termsLink && privacyLink && cookiesLink;
    }

    async getFooterCopyrightYear(): Promise<string | null> {
        const text = await this.footer.textContent();
        const match = text?.match(/©\s*(\d{4})/);
        return match ? match[1] : null;
    }

    async footerHasCurrentYear(): Promise<boolean> {
        const year = await this.getFooterCopyrightYear();
        const currentYear = new Date().getFullYear().toString();
        return year === currentYear;
    }

    // ===== Content Checks =====

    async hasCompanyInfo(): Promise<boolean> {
        const hasName = await this.companyName.isVisible().catch(() => false);
        const hasEmail = await this.contactEmail.isVisible().catch(() => false);
        return hasName && hasEmail;
    }

    async hasLastUpdatedDate(): Promise<boolean> {
        return this.lastUpdatedText
            .isVisible({ timeout: 5000 })
            .catch(() => false);
    }

    async getLastUpdatedText(): Promise<string | null> {
        return this.lastUpdatedText.textContent();
    }

    // ===== Assertions =====

    async expectMainHeadingVisible(
        expectedName?: string | RegExp,
    ): Promise<void> {
        const heading = expectedName
            ? this.getHeadingByName(expectedName)
            : this.mainHeading;
        await expect(heading).toBeVisible();
    }

    async expectFooterVisible(): Promise<void> {
        await expect(this.footer).toBeVisible();
    }

    async expectSemanticStructure(): Promise<void> {
        await expect(this.navigationBar).toBeVisible();
        await expect(this.page.locator('main')).toBeVisible();
        await expect(this.footer).toBeVisible();
    }

    async expectNoConsoleErrors(): Promise<void> {
        const errors: string[] = [];
        this.page.on('console', (msg) => {
            if (msg.type() === 'error') {
                errors.push(msg.text());
            }
        });
        // Allow time for errors to occur
        await this.page.waitForTimeout(500);
        expect(errors).toHaveLength(0);
    }
}
