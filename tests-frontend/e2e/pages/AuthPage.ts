import type { Locator, Page } from '@playwright/test';
import { expect } from '@playwright/test';

/**
 * Page Object Model for Authentication
 * Encapsulates login and registration interactions
 */
export class AuthPage {
    constructor(readonly page: Page) {}

    // ===== Locators =====

    private get modal(): Locator {
        return this.page.locator('[role="dialog"]');
    }

    get emailInput(): Locator {
        return this.page.getByLabel(/^email/i);
    }

    get passwordInput(): Locator {
        return this.page.getByLabel(/^password/i);
    }

    get confirmPasswordInput(): Locator {
        return this.page.getByLabel(/^confirm password/i);
    }

    get nameInput(): Locator {
        return this.page.getByLabel(/^name/i);
    }

    get loginButton(): Locator {
        return this.page
            .getByRole('button', { name: /log in|sign in/i })
            .first();
    }

    get registerButton(): Locator {
        return this.page.getByRole('button', { name: /^register$/i }).first();
    }

    get switchToRegisterButton(): Locator {
        return this.page.getByRole('button', { name: /need an account/i });
    }

    get switchToLoginButton(): Locator {
        return this.page.getByRole('button', {
            name: /already have an account|sign in/i,
        });
    }

    get googleSignInButton(): Locator {
        return this.page.getByRole('button', { name: /google/i });
    }

    get submitButton(): Locator {
        return this.page.getByRole('button', {
            name: /log in|sign in|register/i,
        });
    }

    get closeButton(): Locator {
        return this.modal.getByRole('button', { name: /close|cancel|×/i });
    }

    private get userMenu(): Locator {
        return this.page.getByRole('button', { name: /user menu|profile/i });
    }

    // ===== Navigation =====

    async openLoginModal(): Promise<void> {
        const loginNavButton = this.page
            .getByRole('button', { name: /^log in$/i })
            .first();
        await loginNavButton.click();
    }

    async openLoginModalViaUrl(): Promise<void> {
        await this.page.goto('/gb/?modal=login');
    }

    async openRegisterModal(): Promise<void> {
        await this.openLoginModal();
        await this.switchToRegister();
    }

    // ===== Login =====

    async login(email: string, password: string): Promise<void> {
        await this.emailInput.fill(email);
        await this.passwordInput.fill(password);
        await this.submitButton.click();
    }

    async fillLoginForm(email: string, password: string): Promise<void> {
        await this.emailInput.fill(email);
        await this.passwordInput.fill(password);
    }

    async submitLogin(): Promise<void> {
        await this.loginButton.click();
    }

    // ===== Registration =====

    async register(
        name: string,
        email: string,
        password: string,
    ): Promise<void> {
        await this.nameInput.fill(name);
        await this.emailInput.fill(email);
        await this.passwordInput.fill(password);
        await this.confirmPasswordInput.fill(password);
        await this.submitButton.click();
    }

    async fillRegisterForm(
        name: string,
        email: string,
        password: string,
    ): Promise<void> {
        await this.nameInput.fill(name);
        await this.emailInput.fill(email);
        await this.passwordInput.fill(password);
        await this.confirmPasswordInput.fill(password);
    }

    async submitRegister(): Promise<void> {
        await this.registerButton.click();
    }

    // ===== Modal Switching =====

    async switchToRegister(): Promise<void> {
        await this.switchToRegisterButton.click();
    }

    async switchToLogin(): Promise<void> {
        await this.switchToLoginButton.click();
    }

    async closeModal(): Promise<void> {
        await this.closeButton.click();
    }

    // ===== OAuth =====

    async hasGoogleSignIn(): Promise<boolean> {
        return this.googleSignInButton
            .isVisible({ timeout: 5000 })
            .catch(() => false);
    }

    async clickGoogleSignIn(): Promise<void> {
        await this.googleSignInButton.click();
    }

    // ===== Status Checks =====

    async isLoggedIn(): Promise<boolean> {
        return this.userMenu.isVisible({ timeout: 3000 }).catch(() => false);
    }

    async isLoginModalOpen(): Promise<boolean> {
        return this.modal.isVisible({ timeout: 3000 }).catch(() => false);
    }

    async isRegisterFormShown(): Promise<boolean> {
        const confirmPassword = await this.confirmPasswordInput
            .isVisible()
            .catch(() => false);
        return confirmPassword;
    }

    async isLoginFormShown(): Promise<boolean> {
        const email = await this.emailInput.isVisible().catch(() => false);
        const password = await this.passwordInput
            .isVisible()
            .catch(() => false);
        const confirm = await this.confirmPasswordInput
            .isVisible()
            .catch(() => false);
        return email && password && !confirm;
    }

    // ===== Error Handling =====

    async getErrorMessage(): Promise<string | null> {
        const error = this.page.getByText(/error|invalid|failed|incorrect/i);
        try {
            return await error.first().textContent();
        } catch {
            return null;
        }
    }

    async hasErrorMessage(): Promise<boolean> {
        const error = this.page.getByText(/error|invalid|failed|incorrect/i);
        return error
            .first()
            .isVisible({ timeout: 3000 })
            .catch(() => false);
    }

    // ===== Assertions =====

    async expectLoginModalOpen(): Promise<void> {
        await expect(this.emailInput).toBeVisible();
        await expect(this.passwordInput).toBeVisible();
    }

    async expectRegisterModalOpen(): Promise<void> {
        await expect(this.nameInput).toBeVisible();
        await expect(this.emailInput).toBeVisible();
        await expect(this.passwordInput).toBeVisible();
        await expect(this.confirmPasswordInput).toBeVisible();
    }

    async expectLoggedIn(): Promise<void> {
        await expect(this.userMenu).toBeVisible();
    }

    async expectLoggedOut(): Promise<void> {
        const notLoggedIn = !!(await this.isLoggedIn());
        expect(notLoggedIn).toBeFalsy();
    }

    async expectSwitchToRegisterVisible(): Promise<void> {
        await expect(this.switchToRegisterButton).toBeVisible();
    }

    async expectSwitchToLoginVisible(): Promise<void> {
        await expect(this.switchToLoginButton).toBeVisible();
    }
}
