import type { Locator, Page } from '@playwright/test';
import { expect } from '@playwright/test';

/**
 * Page Object Model for Prompt Builder pages
 * Encapsulates interactions and assertions for the /prompt-builder route
 */
export class PromptBuilderPage {
    constructor(readonly page: Page) {}

    // ===== Locators =====

    get taskDescriptionInput(): Locator {
        return this.page.getByLabel(/task description/i);
    }

    get submitButton(): Locator {
        return this.page.getByRole('button', {
            name: /submit|optimis|generate|analyse/i,
        });
    }

    get optimizedPromptCard(): Locator {
        return this.page.locator('[data-testid="tab-prompt"]');
    }

    get frameworkCard(): Locator {
        return this.page.locator('[data-testid="tab-framework"]');
    }

    get questionsCard(): Locator {
        return this.page.locator('[data-testid="tab-questions"]');
    }

    get frameworkTab(): Locator {
        return this.page
            .locator('nav[aria-label="Tabs"]')
            .getByRole('button', { name: /Framework/i })
            .first();
    }

    get optimisedPromptTab(): Locator {
        return this.page
            .locator('nav[aria-label="Tabs"]')
            .getByRole('button', { name: /Optimised Prompt/i })
            .first();
    }

    get loadingSpinner(): Locator {
        return this.page.locator('[data-testid*="loading"]');
    }

    get copyButton(): Locator {
        return this.page.getByRole('button', { name: /copy|clipboard/i });
    }

    get locationLanguageModal(): Locator {
        return this.page.locator('[role="dialog"]');
    }

    get continueWithoutChangesButton(): Locator {
        return this.page.getByRole('button', {
            name: /continue without changes/i,
        });
    }

    // ===== Navigation =====

    async goto(): Promise<void> {
        await this.page.goto('/gb/prompt-builder');
    }

    async gotoPromptRun(id: number): Promise<void> {
        await this.page.goto(`/gb/prompt-builder/${id}`);
    }

    // ===== Task Input Methods =====

    async enterTaskDescription(description: string): Promise<void> {
        await this.taskDescriptionInput.fill(description);
    }

    async submitTask(): Promise<void> {
        await this.submitButton.click();
    }

    async enterAndSubmitTask(description: string): Promise<void> {
        await this.enterTaskDescription(description);
        await this.submitTask();
    }

    async dismissLocationLanguageModal(): Promise<void> {
        // Check if the location/language confirmation modal is visible
        const modal = this.locationLanguageModal;
        const isVisible = await modal.isVisible().catch(() => false);

        if (isVisible) {
            // Click "Continue without changes" to dismiss the modal
            await this.continueWithoutChangesButton.click();
            // Wait for the modal to disappear
            await modal.waitFor({ state: 'hidden', timeout: 5000 });
        }
    }

    // ===== Framework Selection =====

    async selectFramework(frameworkName: string): Promise<void> {
        const button = this.page.getByRole('button', {
            name: new RegExp(frameworkName, 'i'),
        });
        await button.click();
    }

    async getSelectedFrameworkName(): Promise<string | null> {
        const frameworkBadge = this.frameworkCard.getByRole('heading').first();
        return frameworkBadge.textContent();
    }

    // ===== Question Answering =====

    async answerQuestion(index: number, answer: string): Promise<void> {
        const textareas = this.page.locator('textarea');
        const textarea = textareas.nth(index);
        await textarea.fill(answer);
    }

    async submitAnswers(): Promise<void> {
        const submitButton = this.page.getByRole('button', {
            name: /submit|next/i,
        });
        await submitButton.click();
    }

    // ===== Tab Navigation Waiting =====

    async waitForFrameworkTab(): Promise<void> {
        await this.frameworkTab
            .waitFor({ state: 'visible', timeout: 5000 })
            .catch(() => null);
    }

    async waitForOptimisedPromptTab(): Promise<void> {
        await this.optimisedPromptTab
            .waitFor({ state: 'visible', timeout: 5000 })
            .catch(() => null);
    }

    // ===== Results Interaction =====

    async getOptimizedPrompt(): Promise<string | null> {
        const promptContent =
            this.optimizedPromptCard.locator('.prose, pre, code');
        return promptContent.first().textContent();
    }

    async copyOptimizedPrompt(): Promise<void> {
        await this.copyButton.click();
    }

    async waitForOptimization(): Promise<void> {
        await this.page.waitForFunction(
            () => {
                const prompt = document.querySelector(
                    '[data-testid="optimized-prompt"]',
                );
                return prompt !== null;
            },
            { timeout: 60000 },
        );
    }

    // ===== Assertions =====

    async expectTaskInputVisible(): Promise<void> {
        await expect(this.taskDescriptionInput).toBeVisible();
    }

    async expectOptimizedPromptVisible(): Promise<void> {
        await expect(this.optimizedPromptCard).toBeVisible();
    }

    async expectFrameworkSelected(): Promise<void> {
        await expect(this.frameworkCard).toBeVisible();
    }

    async expectLoadingState(): Promise<void> {
        const loading = this.page.getByText(/analyzing|generating|optimising/i);
        await expect(loading.first()).toBeVisible();
    }

    async expectErrorMessage(pattern: RegExp = /error|failed/i): Promise<void> {
        await expect(this.page.getByText(pattern)).toBeVisible({
            timeout: 5000,
        });
    }

    // ===== Utilities =====

    async isAnalysing(): Promise<boolean> {
        const text = await this.page.textContent('body');
        return /analyzing|analysing/.test(text || '');
    }

    async isOptimizing(): Promise<boolean> {
        const text = await this.page.textContent('body');
        return /optimising|generating|generating your prompt/.test(text || '');
    }

    async getPromptRunId(): Promise<number | null> {
        const url = this.page.url();
        const match = url.match(/\/prompt-builder\/(\d+)/);
        return match ? parseInt(match[1], 10) : null;
    }
}
