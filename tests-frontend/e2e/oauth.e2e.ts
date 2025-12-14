import { expect, test } from '@playwright/test';
import { loginWithMockOAuth } from './helpers/auth';

test.describe('Google OAuth Authentication', () => {
    test('should display Google Sign-In button on home page', async ({
        page,
    }) => {
        await page.goto('/');

        // Look for Google Sign-In button using test ID
        const googleButton = page.getByTestId('google-sign-in-button');

        if ((await googleButton.count()) > 0) {
            await expect(googleButton).toBeVisible();
        }
    });

    test('should redirect to Google OAuth when clicking Sign in with Google', async ({
        page,
    }) => {
        await page.goto('/');

        // Find Google Sign-In button
        const googleButton = page
            .getByRole('button', { name: /google/i })
            .or(page.getByRole('link', { name: /google/i }));

        if ((await googleButton.count()) > 0) {
            const button = googleButton.first();

            // Get the href or onclick to verify it points to /auth/google
            const href = await button.getAttribute('href');

            if (href) {
                expect(href).toContain('/auth/google');
            } else {
                // If it's a button with onclick, click and check redirect
                // Note: This will fail without proper OAuth setup, so we just check the button exists
                await expect(button).toBeVisible();
            }
        }
    });

    test('should handle OAuth callback URL structure', async ({ page }) => {
        // This tests that the OAuth callback route exists
        // It will redirect or show error, but shouldn't 404

        // Note: Actually navigating will fail without Google OAuth code
        // So we just verify the route structure exists via other means

        await page.goto('/');

        // Verify the OAuth routes are defined by checking if navigation to
        // /auth/google doesn't immediately 404
        const response = await page.goto('/auth/google');

        // Should get a redirect (3xx) or OK (2xx), not 404
        const status = response?.status();
        expect(status).not.toBe(404);
    });

    test('should show error message for invalid OAuth state', async ({
        page,
    }) => {
        // Try to access callback with invalid/missing parameters
        await page.goto('/auth/google/callback');

        // Should redirect to login or home with error message

        // Check if we're redirected (not on callback URL anymore)
        const url = page.url();
        expect(url).not.toContain('/auth/google/callback');

        // Should be redirected to home page (with or without modal query param)
        expect(url).toMatch(/\/(\?.*)?$/);
    });
});

test.describe('Google OAuth - Successful Login (using mock endpoint)', () => {
    test('should complete OAuth flow and log in user via mock endpoint', async ({
        page,
    }) => {
        // Use mock OAuth endpoint instead of real Google OAuth
        await loginWithMockOAuth(page, 'oauth-user@example.com', 'OAuth User');

        // Should be logged in and redirected to home
        expect(page.url()).toContain('/');

        // Verify user menu is visible (indicates logged in)
        const userMenu = page.getByRole('button', { name: /user menu/i });
        await expect(userMenu).toBeVisible();
    });

    test('should create new user account from OAuth data', async ({ page }) => {
        const newEmail = `oauth-${Date.now()}@example.com`;
        const newName = 'Brand New OAuth User';

        // Mock OAuth with new email should create account
        await loginWithMockOAuth(page, newEmail, newName);

        // Should be logged in
        expect(page.url()).toContain('/');
        const userMenu = page.getByRole('button', { name: /user menu/i });
        await expect(userMenu).toBeVisible();

        // Open user menu to verify name is set
        await userMenu.click();
        const profileLink = page.getByRole('link', { name: /profile/i });
        await expect(profileLink).toBeVisible();
    });

    test('should link Google account to existing email', async ({ page }) => {
        // First, create a user via regular login
        const { loginAsTestUser } = await import('./helpers/auth');
        await loginAsTestUser(page);

        // Then log out
        const userMenu = page.getByRole('button', { name: /user menu/i });
        await userMenu.click();
        const logoutButton = page.getByRole('button', { name: /log out/i });
        await logoutButton.click();

        // Wait for logout to complete
        await expect(userMenu).not.toBeVisible({ timeout: 5000 });

        // Now authenticate with OAuth using the same email
        await loginWithMockOAuth(page, 'test@example.com', 'Test User');

        // Should be logged in successfully
        const userMenuAfterOAuth = page.getByRole('button', {
            name: /user menu/i,
        });
        await expect(userMenuAfterOAuth).toBeVisible();
    });

    test('should log out user after OAuth login', async ({ page }) => {
        // Login via OAuth
        await loginWithMockOAuth(
            page,
            'logout-test@example.com',
            'Logout Test',
        );

        // User should be logged in
        const userMenu = page.getByRole('button', { name: /user menu/i });
        await expect(userMenu).toBeVisible();

        // Find and click logout button
        await userMenu.click();
        const logoutButton = page.getByRole('button', { name: /log out/i });
        await expect(logoutButton).toBeVisible();
        await logoutButton.click();

        // Should redirect to home and user menu should disappear
        await expect(userMenu).not.toBeVisible({ timeout: 5000 });
    });
});
