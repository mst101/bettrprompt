import type { Locator, Page } from '@playwright/test';
import { expect } from '@playwright/test';

/**
 * Page Object Model for Profile pages
 * Encapsulates interactions and assertions for profile-related routes
 */
export class ProfilePage {
    constructor(readonly page: Page) {}

    // ===== Navigation =====

    async goto(): Promise<void> {
        await this.page.goto('/profile');
    }

    async gotoLocation(): Promise<void> {
        await this.page.goto('/profile/location');
    }

    async gotoProfessional(): Promise<void> {
        await this.page.goto('/profile/professional');
    }

    async gotoTeam(): Promise<void> {
        await this.page.goto('/profile/team');
    }

    async gotoBudget(): Promise<void> {
        await this.page.goto('/profile/budget');
    }

    async gotoTools(): Promise<void> {
        await this.page.goto('/profile/tools');
    }

    // ===== Form Field Helpers =====

    /**
     * Get a form input field by its label text
     */
    getInputByLabel(labelPattern: string | RegExp): Locator {
        return this.page.getByLabel(labelPattern).first();
    }

    /**
     * Get a select field by its label text
     */
    getSelectByLabel(labelPattern: string | RegExp): Locator {
        return this.page.getByLabel(labelPattern).first();
    }

    /**
     * Get a checkbox field by its label text
     */
    getCheckboxByLabel(labelPattern: string | RegExp): Locator {
        return this.page.getByRole('checkbox', { name: labelPattern });
    }

    /**
     * Get the save button (typically "Save" or "Update")
     */
    getSaveButton(): Locator {
        return this.page.getByRole('button', { name: /^save$/i });
    }

    /**
     * Get success message
     */
    getSuccessMessage(): Locator {
        return this.page.getByText(/saved|success/i);
    }

    /**
     * Get the back button
     */
    getBackButton(): Locator {
        return this.page.getByRole('button', { name: /back|< back/i });
    }

    // ===== Form Interaction =====

    /**
     * Fill a form field and optionally save
     */
    async fillField(
        labelPattern: string | RegExp,
        value: string,
        shouldSave: boolean = false,
    ): Promise<void> {
        const field = this.getInputByLabel(labelPattern);
        await field.fill(value);

        if (shouldSave) {
            await this.save();
        }
    }

    /**
     * Save the form and wait for success message
     */
    async save(timeout: number = 5000): Promise<void> {
        const saveButton = this.getSaveButton();
        const successMessage = this.getSuccessMessage();

        await saveButton.click();
        await expect(successMessage).toBeVisible({ timeout });
    }

    /**
     * Save the form without waiting for message
     */
    async saveWithoutWaiting(): Promise<void> {
        const saveButton = this.getSaveButton();
        await saveButton.click();
    }

    /**
     * Navigate back to previous page
     */
    async goBack(): Promise<void> {
        const backButton = this.getBackButton();
        if (await backButton.isVisible().catch(() => false)) {
            await backButton.click();
        } else {
            await this.page.goBack();
        }
    }

    // ===== Section Locators =====

    /**
     * Get a section by its heading text
     */
    getSectionByHeading(headingPattern: string | RegExp): Locator {
        return this.page.locator('section').filter({ hasText: headingPattern });
    }

    /**
     * Get fields within a specific section
     */
    getFieldsInSection(sectionPattern: string | RegExp): {
        inputs: Locator;
        checkboxes: Locator;
        saveButton: Locator;
    } {
        const section = this.getSectionByHeading(sectionPattern);
        return {
            inputs: section.getByRole('textbox'),
            checkboxes: section.getByRole('checkbox'),
            saveButton: section.getByRole('button', { name: /^save$/i }),
        };
    }

    // ===== Assertions =====

    async expectPageLoaded(): Promise<void> {
        const heading = this.page.getByRole('heading').first();
        await expect(heading).toBeVisible();
    }

    async expectFieldVisible(labelPattern: string | RegExp): Promise<void> {
        const field = this.getInputByLabel(labelPattern);
        await expect(field).toBeVisible();
    }

    async expectSectionVisible(sectionPattern: string | RegExp): Promise<void> {
        const section = this.getSectionByHeading(sectionPattern);
        await expect(section).toBeVisible();
    }

    async expectSuccessMessage(timeout: number = 5000): Promise<void> {
        const message = this.getSuccessMessage();
        await expect(message).toBeVisible({ timeout });
    }

    // ===== Utilities =====

    /**
     * Get the current field value
     */
    async getFieldValue(labelPattern: string | RegExp): Promise<string> {
        const field = this.getInputByLabel(labelPattern);
        return await field.inputValue();
    }

    /**
     * Check if a checkbox is checked
     */
    async isCheckboxChecked(labelPattern: string | RegExp): Promise<boolean> {
        const checkbox = this.getCheckboxByLabel(labelPattern);
        return await checkbox.isChecked();
    }

    /**
     * Toggle a checkbox
     */
    async toggleCheckbox(labelPattern: string | RegExp): Promise<void> {
        const checkbox = this.getCheckboxByLabel(labelPattern);
        await checkbox.click();
    }

    /**
     * Get the current URL
     */
    getCurrentUrl(): string {
        return this.page.url();
    }

    /**
     * Check if on a specific profile sub-page
     */
    isOnSubPage(subPage: string): boolean {
        const url = this.getCurrentUrl();
        return url.includes(`/profile/${subPage}`);
    }
}
