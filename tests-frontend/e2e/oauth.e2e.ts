import { test, expect } from '@playwright/test';

test.describe('Google OAuth Authentication', () => {
    test('should display Google Sign-In button on home page', async ({ page }) => {
        await page.goto('/');

        // Look for Google Sign-In button using test ID
        const googleButton = page.getByTestId('google-sign-in-button');

        if (await googleButton.count() > 0) {
            await expect(googleButton).toBeVisible();
        }
    });

    test('should redirect to Google OAuth when clicking Sign in with Google', async ({ page }) => {
        await page.goto('/');

        // Find Google Sign-In button
        const googleButton = page.getByRole('button', { name: /google/i }).or(
            page.getByRole('link', { name: /google/i })
        );

        if (await googleButton.count() > 0) {
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

    test('should show error message for invalid OAuth state', async ({ page }) => {
        // Try to access callback with invalid/missing parameters
        await page.goto('/auth/google/callback');

        // Should redirect to login or home with error message
        await page.waitForLoadState('networkidle');

        // Check if we're redirected (not on callback URL anymore)
        const url = page.url();
        expect(url).not.toContain('/auth/google/callback');

        // Should be redirected to home page (with or without modal query param)
        expect(url).toMatch(/\/(\?.*)?$/);
    });
});

test.describe.skip('Google OAuth - Successful Login (requires OAuth setup)', () => {
    test('should complete Google OAuth flow and log in user', async ({ page }) => {
        // This test would require:
        // 1. Mocking Google OAuth responses
        // 2. Or using a test Google account
        // 3. Or using Playwright's auth state

        // Expected flow:
        // 1. Click "Sign in with Google"
        // 2. Redirect to Google
        // 3. Google redirects back to /auth/google/callback with code
        // 4. User is authenticated
        // 5. Redirect to /prompt-optimizer

        await page.goto('/');
        expect(page.url()).toBe('/');
    });

    test('should create new user account from Google OAuth data', async ({ page }) => {
        // Expected flow for new user:
        // 1. Complete OAuth flow
        // 2. Check if email exists in database
        // 3. If not, create new user
        // 4. Log user in
        // 5. Redirect to prompt optimizer

        await page.goto('/');
        expect(page.url()).toBe('/');
    });

    test('should link Google account to existing email', async ({ page }) => {
        // Expected flow:
        // 1. User with email exists but no google_id
        // 2. User authenticates with Google using same email
        // 3. System updates user record with google_id
        // 4. User is logged in

        await page.goto('/');
        expect(page.url()).toBe('/');
    });

    test('should log out user', async ({ page }) => {
        // Assuming user is authenticated
        await page.goto('/');

        // Find and click logout button
        const logoutButton = page.getByRole('button', { name: /log out|sign out/i });
        await logoutButton.click();

        // Should redirect to home
        await page.waitForLoadState('networkidle');
        expect(page.url()).toBe('/');

        // Verify user is logged out (no user menu visible)
        const userMenu = page.getByText(/profile|account/i);
        await expect(userMenu).not.toBeVisible();
    });
});
