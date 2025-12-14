import { expect, test } from '@playwright/test';

test.describe('Authentication', () => {
    test('should show login modal when clicking login button', async ({
        page,
    }) => {
        await page.goto('/');

        // Look for the login button in the navigation (it's a button, not a link)
        // Use .first() to get the desktop version (mobile is hidden by default)
        const loginButton = page
            .getByRole('button', { name: /^log in$/i })
            .first();

        // Verify button exists and is visible
        await expect(loginButton).toBeVisible();

        // Click the login button
        await loginButton.click();

        // Wait for the login modal content to appear
        // Note: The dialog element gets 'open' attribute immediately, but content
        // transitions in over 300ms. We wait for actual content visibility.
        // Use pattern that handles asterisk for required fields
        await expect(page.getByLabel(/^email/i)).toBeVisible();
        await expect(page.getByLabel(/^password/i)).toBeVisible();
    });

    test('should allow navigation to register from login', async ({ page }) => {
        // Navigate to home page
        await page.goto('/');

        // Dismiss cookie banner if it appears
        const acceptButton = page.getByRole('button', { name: /accept all/i });
        if (await acceptButton.isVisible().catch(() => false)) {
            await acceptButton.click();
        }

        // Click the login button to open the modal
        const loginButton = page
            .getByRole('button', { name: /^log in$/i })
            .first();
        await expect(loginButton).toBeVisible();
        await loginButton.click();

        // Wait for login modal content to appear
        // Use getByLabel with a pattern that handles the asterisk for required fields
        await expect(page.getByLabel(/^email/i)).toBeVisible();

        // Look for the "Need an account?" button in the login modal
        const switchToRegisterButton = page.getByRole('button', {
            name: /need an account/i,
        });

        // Verify button exists and click it
        await expect(switchToRegisterButton).toBeVisible();
        await switchToRegisterButton.click();

        // Verify the register modal opened (check for register-specific field)
        // "Confirm Password" is unique to the register modal
        // Use pattern that handles asterisk for required fields
        await expect(page.getByLabel(/^confirm password/i)).toBeVisible();

        // Also verify other register fields are present
        await expect(page.getByLabel(/^name/i)).toBeVisible();

        // Verify it's NOT the login modal by checking the submit button text
        const registerSubmitButton = page.getByRole('button', {
            name: /^register$/i,
        });
        await expect(registerSubmitButton).toBeVisible();
    });
});

test.describe('Protected Routes', () => {
    test('should redirect unauthenticated users from profile page', async ({
        page,
    }) => {
        // Try to access the profile page without authentication
        await page.goto('/profile');

        // Should be redirected to login or home
        const url = page.url();
        const isRedirected = url.includes('login') || url === '/';

        expect(isRedirected).toBeTruthy();
    });

    test('should allow unauthenticated users to access prompt optimizer', async ({
        page,
    }) => {
        // Unauthenticated users can now use the prompt optimizer
        await page.goto('/prompt-builder');

        // Should NOT be redirected - should stay on prompt optimizer
        const url = page.url();
        expect(url).toContain('/prompt-builder');

        // Should see the prompt optimizer form
        const taskInput = page.getByLabel(/task description/i);
        await expect(taskInput).toBeVisible();
    });
});
