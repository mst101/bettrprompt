import type { Locator, Page } from '@playwright/test';
import { expect } from '@playwright/test';

/**
 * Page Object Model for Advanced Prompt Builder features
 * Encapsulates interactions and assertions for advanced prompt builder routes
 */
export class PromptBuilderAdvancedPage {
    constructor(readonly page: Page) {}

    // ===== Navigation =====

    async gotoPromptRun(id: number): Promise<void> {
        await this.page.goto(`/en-GB/prompt-builder/${id}`);
    }

    async gotoCreateChildFromTask(parentId: number): Promise<void> {
        await this.page.goto(
            `/prompt-builder/${parentId}/create-child-from-task`,
        );
    }

    async gotoCreateChildFromAnswers(parentId: number): Promise<void> {
        await this.page.goto(
            `/prompt-builder/${parentId}/create-child-from-answers`,
        );
    }

    async gotoCreateChildWithFramework(promptId: number): Promise<void> {
        await this.page.goto(
            `/prompt-builder/${promptId}/create-child-with-framework`,
        );
    }

    // ===== URL Utilities =====

    /**
     * Extract prompt ID from current URL
     */
    getPromptIdFromUrl(): number | null {
        const url = this.page.url();
        const match = url.match(/\/prompt-builder\/(\d+)/);
        return match ? parseInt(match[1], 10) : null;
    }

    /**
     * Check if currently on a specific prompt builder route
     */
    isOnRoute(routePattern: string | RegExp): boolean {
        const url = this.page.url();
        return typeof routePattern === 'string'
            ? url.includes(routePattern)
            : routePattern.test(url);
    }

    /**
     * Check if on prompt show page (not a child creation/switch page)
     */
    isOnShowPage(): boolean {
        const url = this.page.url();
        return /\/prompt-builder\/\d+$/.test(url);
    }

    /**
     * Check if on create-child-from-task page
     */
    isOnCreateChildFromTaskPage(): boolean {
        return this.isOnRoute('create-child-from-task');
    }

    /**
     * Check if on create-child-from-answers page
     */
    isOnCreateChildFromAnswersPage(): boolean {
        return this.isOnRoute('create-child-from-answers');
    }

    /**
     * Check if on create-child-with-framework page
     */
    isOnCreateChildWithFrameworkPage(): boolean {
        return this.isOnRoute('create-child-with-framework');
    }

    // ===== Common Form Elements =====

    /**
     * Get framework select field
     */
    getFrameworkSelect(): Locator {
        return this.page.getByLabel(/framework/i).first();
    }

    /**
     * Get framework select options
     */
    getFrameworkOptions(): Locator {
        return this.getFrameworkSelect().locator('option');
    }

    /**
     * Get create/submit button
     */
    getCreateButton(): Locator {
        return this.page.getByRole('button', {
            name: /^create|submit|continue/i,
        });
    }

    /**
     * Get switch/confirm button
     */
    getSwitchButton(): Locator {
        return this.page.getByRole('button', {
            name: /^switch|apply|confirm/i,
        });
    }

    /**
     * Get copy button
     */
    getCopyButton(): Locator {
        return this.page.getByRole('button', { name: /copy/i });
    }

    /**
     * Get refine/improve button
     */
    getRefinementButton(): Locator {
        return this.page
            .getByRole('button', {
                name: /refine|improve|adjust|try again|different/i,
            })
            .or(this.page.getByRole('link', { name: /refine/i }));
    }

    /**
     * Get create child button
     */
    getCreateChildButton(): Locator {
        return this.page
            .getByRole('button', { name: /create.*child|follow.?up|refine/i })
            .or(this.page.getByRole('link', { name: /child|follow.?up/i }));
    }

    /**
     * Get textbox field (for answers, questions, etc.)
     */
    getTextbox(index: number = 0): Locator {
        return this.page.getByRole('textbox').nth(index);
    }

    /**
     * Get all textbox fields
     */
    getAllTextboxes(): Locator {
        return this.page.getByRole('textbox');
    }

    /**
     * Get copy success message
     */
    getCopySuccessMessage(): Locator {
        return this.page.getByText(/copied/i);
    }

    // ===== Child Prompt Creation =====

    /**
     * Select a framework from the dropdown (by index)
     */
    async selectFrameworkByIndex(index: number): Promise<void> {
        const options = this.getFrameworkOptions();
        const count = await options.count();

        if (count > index) {
            await options.nth(index).click();
        }
    }

    /**
     * Select a different framework (typically second option)
     */
    async selectDifferentFramework(): Promise<void> {
        const options = this.getFrameworkOptions();
        const count = await options.count();

        if (count > 1) {
            await options.nth(1).click();
        }
    }

    /**
     * Create child prompt with selected framework
     */
    async createChildWithFramework(): Promise<void> {
        await this.selectDifferentFramework();
        const button = this.getCreateButton();

        if (await button.isVisible().catch(() => false)) {
            await button.click();
        }
    }

    /**
     * Create child from answers (fill text and submit)
     */
    async createChildFromAnswer(answerText: string): Promise<void> {
        const firstField = this.getTextbox(0);

        if (await firstField.isVisible().catch(() => false)) {
            await firstField.fill(answerText);

            const submitBtn = this.getCreateButton();
            if (await submitBtn.isVisible().catch(() => false)) {
                await submitBtn.click();
            }
        }
    }

    // ===== Framework Switching =====

    /**
     * Switch to a different framework
     */
    async switchFramework(): Promise<void> {
        await this.selectDifferentFramework();
        const button = this.getSwitchButton();

        if (await button.isVisible().catch(() => false)) {
            await button.click();
        }
    }

    // ===== Prompt Actions =====

    /**
     * Copy the optimised prompt
     */
    async copyPrompt(): Promise<void> {
        const button = this.getCopyButton();

        if (await button.isVisible().catch(() => false)) {
            await button.click();
        }
    }

    /**
     * Wait for copy success message
     */
    async waitForCopySuccess(timeout: number = 5000): Promise<void> {
        const message = this.getCopySuccessMessage();
        await expect(message).toBeVisible({ timeout });
    }

    /**
     * Answer a pre-analysis question
     */
    async answerPreAnalysisQuestion(
        index: number,
        answerText: string,
    ): Promise<void> {
        const field = this.getTextbox(index);

        if (await field.isVisible().catch(() => false)) {
            await field.fill(answerText);
        }
    }

    // ===== Assertions =====

    async expectPageLoaded(): Promise<void> {
        const heading = this.page.getByRole('heading').first();
        await expect(heading).toBeVisible();
    }

    async expectFrameworkSelectVisible(): Promise<void> {
        const select = this.getFrameworkSelect();
        await expect(select).toBeVisible();
    }

    async expectCreateChildButtonVisible(): Promise<void> {
        const button = this.getCreateChildButton();
        const visible = await button.isVisible().catch(() => false);
        expect(visible).toBe(true);
    }

    async expectCopyButtonVisible(): Promise<void> {
        const button = this.getCopyButton();
        await expect(button).toBeVisible();
    }

    async expectOnPage(routePattern: string | RegExp): Promise<void> {
        const onPage = this.isOnRoute(routePattern);
        expect(onPage).toBe(true);
    }

    // ===== Utilities =====

    /**
     * Wait for URL change to specific pattern
     */
    async waitForNavigation(
        routePattern: string | RegExp,
        timeout: number = 10000,
    ): Promise<void> {
        const startTime = Date.now();

        while (Date.now() - startTime < timeout) {
            if (this.isOnRoute(routePattern)) {
                return;
            }
            await this.page.waitForTimeout(100);
        }

        throw new Error(`Navigation to ${routePattern} timed out`);
    }

    /**
     * Get count of textbox fields
     */
    async getTextboxCount(): Promise<number> {
        return await this.getAllTextboxes().count();
    }

    /**
     * Get count of framework options
     */
    async getFrameworkOptionsCount(): Promise<number> {
        return await this.getFrameworkOptions().count();
    }

    /**
     * Check if button is available and visible
     */
    async isButtonAvailable(buttonPattern: string | RegExp): Promise<boolean> {
        const button = this.page.getByRole('button', {
            name: buttonPattern,
        });
        return await button.isVisible().catch(() => false);
    }
}
