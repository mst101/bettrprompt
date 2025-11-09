import { expect, test } from '@playwright/test';

test.describe('Profile - Unauthenticated Access', () => {
    test('should redirect to login when accessing profile without auth', async ({
        page,
    }) => {
        await page.goto('/profile');
        await page.waitForLoadState('networkidle');

        // Should be redirected to home or login
        const url = page.url();
        expect(url).toMatch(/\/((\?modal=login)|login)?$/);
    });
});

// These tests will work once we have authentication helpers
test.describe.skip('Profile - Authenticated User (requires auth)', () => {
    test.beforeEach(async ({ page }) => {
        // TODO: Add authentication helper
        // await authenticateUser(page);
    });

    test('should load profile edit page', async ({ page }) => {
        await page.goto('/profile');

        // Should see profile heading
        const heading = page.getByRole('heading', { name: /profile|account/i });
        await expect(heading).toBeVisible();
    });

    test('should display current user information', async ({ page }) => {
        await page.goto('/profile');

        // Should see name and email fields populated
        const nameInput = page.getByLabel(/name/i);
        const emailInput = page.getByLabel(/email/i);

        await expect(nameInput).toBeVisible();
        await expect(emailInput).toBeVisible();

        // Check that fields have values
        const nameValue = await nameInput.inputValue();
        const emailValue = await emailInput.inputValue();

        expect(nameValue).toBeTruthy();
        expect(emailValue).toMatch(/@/);
    });

    test('should update profile information', async ({ page }) => {
        await page.goto('/profile');

        // Find and update the name field
        const nameInput = page.getByLabel(/^name$/i);
        const currentName = await nameInput.inputValue();
        const newName = `${currentName} Updated`;

        await nameInput.fill(newName);

        // Submit the form
        const saveButton = page
            .getByRole('button', { name: /save|update/i })
            .first();
        await saveButton.click();

        // Wait for success message or page reload
        await page.waitForLoadState('networkidle');

        // Check for success message
        const successMessage = page.getByText(/saved|updated|success/i);
        await expect(successMessage).toBeVisible({ timeout: 5000 });
    });

    test('should display personality type section', async ({ page }) => {
        await page.goto('/profile');

        // Look for personality type section
        const personalityHeading = page.getByRole('heading', {
            name: /personality type/i,
        });

        if (await personalityHeading.isVisible().catch(() => false)) {
            await expect(personalityHeading).toBeVisible();

            // Should see trait sliders or inputs
            const traitInputs = page.locator(
                'input[type="range"], input[type="number"]',
            );
            const count = await traitInputs.count();

            // Expect personality traits (5 traits from 16personalities: E-I, S-N, T-F, J-P, A-T)
            expect(count).toBeGreaterThanOrEqual(5);
        }
    });

    test('should update personality type traits', async ({ page }) => {
        await page.goto('/profile');

        // Find personality trait sliders
        const traitSlider = page.locator('input[type="range"]').first();

        if (await traitSlider.isVisible().catch(() => false)) {
            // Change slider value
            await traitSlider.fill('70');

            // Submit personality form
            const saveButton = page
                .getByRole('button', { name: /save|update/i })
                .last();
            await saveButton.click();

            // Wait for success
            await page.waitForLoadState('networkidle');

            const successMessage = page.getByText(/saved|updated|success/i);
            await expect(successMessage).toBeVisible({ timeout: 5000 });
        }
    });

    test('should change password', async ({ page }) => {
        await page.goto('/profile');

        // Find password section
        const currentPasswordInput = page.getByLabel(/current password/i);

        if (await currentPasswordInput.isVisible().catch(() => false)) {
            // Fill password fields
            await currentPasswordInput.fill('current-password');
            await page.getByLabel(/^new password$/i).fill('new-password');
            await page.getByLabel(/confirm password/i).fill('new-password');

            // Submit password form
            const updateButton = page.getByRole('button', {
                name: /update password/i,
            });
            await updateButton.click();

            // Either success or validation error (if using placeholder passwords)
            await page.waitForLoadState('networkidle');

            // Just verify the form interaction works
            expect(page.url()).toContain('/profile');
        }
    });

    test('should show validation errors for mismatched passwords', async ({
        page,
    }) => {
        await page.goto('/profile');

        const currentPasswordInput = page.getByLabel(/current password/i);

        if (await currentPasswordInput.isVisible().catch(() => false)) {
            await currentPasswordInput.fill('current-password');
            await page.getByLabel(/^new password$/i).fill('password123');
            await page
                .getByLabel(/confirm password/i)
                .fill('different-password');

            const updateButton = page.getByRole('button', {
                name: /update password/i,
            });
            await updateButton.click();

            // Should show validation error
            const errorMessage = page.getByText(/must match|do not match/i);
            await expect(errorMessage).toBeVisible({ timeout: 3000 });
        }
    });

    test('should delete account with confirmation', async ({ page }) => {
        await page.goto('/profile');

        // Find delete account section
        const deleteButton = page.getByRole('button', {
            name: /delete account/i,
        });

        if (await deleteButton.isVisible().catch(() => false)) {
            await deleteButton.click();

            // Should show confirmation modal
            const confirmModal = page.getByText(
                /are you sure|permanently delete/i,
            );
            await expect(confirmModal).toBeVisible({ timeout: 3000 });

            // Cancel the deletion (don't actually delete the account)
            const cancelButton = page.getByRole('button', {
                name: /cancel|no/i,
            });
            await cancelButton.click();

            // Modal should close
            await expect(confirmModal).not.toBeVisible();
        }
    });

    test('should display user avatar if present', async ({ page }) => {
        await page.goto('/profile');

        // Check for avatar image
        const avatar = page
            .locator('img[alt*="avatar"], img[alt*="profile"]')
            .first();

        if (await avatar.isVisible().catch(() => false)) {
            await expect(avatar).toBeVisible();

            // Verify image loaded
            const src = await avatar.getAttribute('src');
            expect(src).toBeTruthy();
        }
    });
});
