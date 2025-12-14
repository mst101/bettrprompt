import { expect, test } from '../tests-frontend/e2e/fixtures';
import { TEST_USER } from '../tests-frontend/e2e/helpers/auth';

test.describe('Profile - Unauthenticated Access', () => {
    test('should redirect to login when accessing profile without auth', async ({
        page,
    }) => {
        await page.goto('/profile');

        // Should be redirected to home or login
        const url = page.url();
        expect(url).toMatch(/\/((\?modal=login)|login)?$/);
    });
});

test.describe('Profile - Authenticated User', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        // User is already authenticated via fixture

        void authenticatedPage;
    });

    test('should load profile edit page', async ({ page }) => {
        await page.goto('/profile');

        // Should see Profile heading from HeaderPage component
        const heading = page.getByRole('heading', { name: /^profile$/i });
        await expect(heading).toBeVisible();

        // Verify all sections are visible
        await expect(
            page.getByRole('heading', { name: /your personality type/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /profile information/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /update password/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /delete account/i }),
        ).toBeVisible();
    });

    test('should display current user information', async ({ page }) => {
        await page.goto('/profile');

        // Should see name and email fields populated with TEST_USER data
        // Labels may include asterisk for required fields: "Name *"
        const nameInput = page.getByLabel(/name/i);
        const emailInput = page.getByLabel(/email/i);

        await expect(nameInput).toBeVisible();
        await expect(emailInput).toBeVisible();

        // Check that fields have values matching TEST_USER
        const nameValue = await nameInput.inputValue();
        const emailValue = await emailInput.inputValue();

        expect(nameValue).toBe(TEST_USER.name);
        expect(emailValue).toBe(TEST_USER.email);
        expect(emailValue).toMatch(/@/);
    });

    test('should update profile information', async ({ page }) => {
        await page.goto('/profile');

        // Scroll to profile section to ensure elements are visible
        const profileSection = page
            .locator('section')
            .filter({ hasText: 'Profile Information' });
        await profileSection.scrollIntoViewIfNeeded();

        // Find and update the name field
        const nameInput = profileSection.getByLabel(/name/i);
        const originalName = await nameInput.inputValue();
        const newName = 'Updated Test Name';

        await nameInput.fill(newName);

        // Submit the form - find Save button in Profile Information section
        const saveButton = profileSection.getByRole('button', {
            name: /^save$/i,
        });

        // Check for success message - "Saved." appears briefly after successful update
        // Must check BEFORE waiting for networkidle as the message fades out quickly
        const successMessage = profileSection.getByText(/saved\./i);
        await saveButton.click();
        await expect(successMessage).toBeVisible({ timeout: 5000 });

        // Verify the name was updated in the form
        await expect(nameInput).toHaveValue(newName);

        // Restore original name for subsequent tests
        await nameInput.fill(originalName);
        const restoreMessage = profileSection.getByText(/saved\./i);
        await saveButton.click();
        await expect(restoreMessage).toBeVisible({ timeout: 5000 });
        // Extra wait to ensure database write completes
    });

    test('should display personality type section', async ({ page }) => {
        await page.goto('/profile');

        // Look for personality type section
        const personalityHeading = page.getByRole('heading', {
            name: /your personality type/i,
        });
        await expect(personalityHeading).toBeVisible();

        // Should see personality type dropdown (may have asterisk for required)
        const personalitySelect = page.getByLabel(/personality type/i);
        await expect(personalitySelect).toBeVisible();

        // Select a personality type to reveal identity options
        await personalitySelect.selectOption({ index: 1 }); // Select first actual option

        // Wait for identity radio buttons to appear

        // Should see identity radio buttons
        const assertiveRadio = page.getByLabel(/assertive \(a\)/i);
        const turbulentRadio = page.getByLabel(/turbulent \(t\)/i);

        await expect(assertiveRadio).toBeVisible();
        await expect(turbulentRadio).toBeVisible();

        // Check for trait percentages toggle button
        const toggleButton = page.getByRole('button', {
            name: /trait percentages/i,
        });
        await expect(toggleButton).toBeVisible();
    });

    test('should update personality type traits', async ({ page }) => {
        await page.goto('/profile');

        const personalitySection = page
            .locator('section')
            .filter({ hasText: 'Your Personality Type' });

        // Select personality type - INTJ (Architect)
        const personalitySelect =
            personalitySection.getByLabel(/personality type/i);
        await personalitySelect.selectOption('INTJ');

        // Wait for identity options to appear

        // Select identity - Assertive
        const assertiveRadio =
            personalitySection.getByLabel(/assertive \(a\)/i);
        await assertiveRadio.check();

        // Expand trait percentages section
        const toggleButton = personalitySection.getByRole('button', {
            name: /\+ add trait percentages/i,
        });

        // Click toggle if trait percentages are hidden
        if (await toggleButton.isVisible()) {
            await toggleButton.click();
        }

        // Fill in trait percentage inputs
        const mindInput = personalitySection.getByLabel(
            /mind \(introversion\/extraversion\)/i,
        );
        const energyInput = personalitySection.getByLabel(
            /energy \(intuitive\/observant\)/i,
        );
        const natureInput = personalitySection.getByLabel(
            /nature \(thinking\/feeling\)/i,
        );
        const tacticsInput = personalitySection.getByLabel(
            /tactics \(judging\/prospecting\)/i,
        );
        const identityInput = personalitySection.getByLabel(
            /identity \(assertive\/turbulent\)/i,
        );

        // Fill in trait percentages (0-100)
        await mindInput.fill('75');
        await energyInput.fill('80');
        await natureInput.fill('70');
        await tacticsInput.fill('85');
        await identityInput.fill('65');

        // Submit personality form
        const saveButton = personalitySection.getByRole('button', {
            name: /^save$/i,
        });

        // The UpdatePersonalityTypeForm shows a CTA button after successful save
        // Must check BEFORE waiting for networkidle as we need to catch it appearing
        const taskCtaButton = personalitySection.getByRole('link', {
            name: /enter your task/i,
        });
        await saveButton.click();
        await expect(taskCtaButton).toBeVisible({ timeout: 5000 });
    });

    test('should change password successfully', async ({ page }) => {
        await page.goto('/profile');

        const passwordSection = page
            .locator('section')
            .filter({ hasText: 'Update Password' });

        // Scroll to password section
        await passwordSection.scrollIntoViewIfNeeded();

        // Fill password fields with valid credentials (labels may include asterisks)
        await passwordSection
            .getByLabel(/current password/i)
            .fill(TEST_USER.password);
        await passwordSection.getByLabel(/new password/i).fill('newpass123');
        await passwordSection
            .getByLabel(/confirm password/i)
            .fill('newpass123');

        // Submit password form
        const saveButton = passwordSection.getByRole('button', {
            name: /^save$/i,
        });

        // Check for success message - "Saved." appears briefly after successful update
        // Must check BEFORE waiting for networkidle as the message fades out quickly
        const successMessage = passwordSection.getByText(/saved\./i);
        await saveButton.click();
        await expect(successMessage).toBeVisible({ timeout: 5000 });

        // Change password back to original for subsequent tests
        await passwordSection
            .getByLabel(/current password/i)
            .fill('newpass123');
        await passwordSection
            .getByLabel(/new password/i)
            .fill(TEST_USER.password);
        await passwordSection
            .getByLabel(/confirm password/i)
            .fill(TEST_USER.password);
        await saveButton.click();
    });

    test('should show validation errors for mismatched passwords', async ({
        page,
    }) => {
        await page.goto('/profile');

        const passwordSection = page
            .locator('section')
            .filter({ hasText: 'Update Password' });

        // Scroll to section
        await passwordSection.scrollIntoViewIfNeeded();

        // Fill password fields with mismatched confirmation
        await passwordSection
            .getByLabel(/current password/i)
            .fill(TEST_USER.password);
        await passwordSection.getByLabel(/new password/i).fill('password123');
        await passwordSection
            .getByLabel(/confirm password/i)
            .fill('different-password');

        // Submit password form
        const saveButton = passwordSection.getByRole('button', {
            name: /^save$/i,
        });
        await saveButton.click();

        // Wait for validation error

        // Laravel validation error appears near the passwordConfirmation field
        // Check for error text related to password confirmation
        const errorMessage = passwordSection.getByText(
            /password.*confirmation.*match|passwords.*match/i,
        );
        await expect(errorMessage).toBeVisible({ timeout: 3000 });
    });

    test('should delete account with confirmation modal', async ({ page }) => {
        await page.goto('/profile');

        const deleteSection = page
            .locator('section')
            .filter({ hasText: 'Delete Account' });

        // Scroll to delete section
        await deleteSection.scrollIntoViewIfNeeded();

        // Find and click delete account button
        const deleteButton = deleteSection.getByRole('button', {
            name: /delete account/i,
        });
        await expect(deleteButton).toBeVisible();
        await deleteButton.click();

        // Wait for modal to appear

        // Should show confirmation modal with warning text
        const modal = page.getByRole('dialog');
        await expect(modal).toBeVisible({ timeout: 3000 });

        const confirmText = modal.getByText(
            /are you sure you want to delete your account/i,
        );
        await expect(confirmText).toBeVisible();

        // Verify modal contains warning about permanent deletion
        // Scope to modal to avoid matching text in the section description
        const permanentDeleteText = modal.getByText(/permanently deleted/i);
        await expect(permanentDeleteText).toBeVisible();

        // Cancel the deletion (don't actually delete the account)
        const cancelButton = page.getByRole('button', {
            name: /^cancel$/i,
        });
        await cancelButton.click();

        // Modal should close
        await expect(confirmText).not.toBeVisible({ timeout: 3000 });

        // Should still be on profile page
        expect(page.url()).toContain('/profile');
    });

    test('should display user avatar if present', async ({ page }) => {
        await page.goto('/profile');

        // Check for avatar image (may not be implemented yet)
        const avatar = page
            .locator(
                'img[alt*="avatar"], img[alt*="profile"], img[alt*="user"]',
            )
            .first();

        // This is a flexible test - avatar may not be implemented
        const isVisible = await avatar.isVisible().catch(() => false);

        if (isVisible) {
            // If avatar is present, verify it has a src attribute
            const src = await avatar.getAttribute('src');
            expect(src).toBeTruthy();
        }
        // If no avatar is present, test passes (avatar is optional)
    });

    test('should validate required fields in profile information', async ({
        page,
    }) => {
        await page.goto('/profile');

        const profileSection = page
            .locator('section')
            .filter({ hasText: 'Profile Information' });

        // Scroll to section
        await profileSection.scrollIntoViewIfNeeded();

        // Clear required name field
        const nameInput = profileSection.getByLabel(/name/i);
        await nameInput.fill('');

        // Try to submit
        const saveButton = profileSection.getByRole('button', {
            name: /^save$/i,
        });
        await saveButton.click();

        // Should show validation error for required name field
        // HTML5 validation or Laravel validation should prevent submission
        const errorMessage = profileSection.locator('text=/required/i').first();
        const hasError =
            (await errorMessage.isVisible().catch(() => false)) ||
            // Or check if the name input has invalid state
            (await nameInput
                .evaluate((el) => el.matches(':invalid'))
                .catch(() => false));

        expect(hasError).toBeTruthy();
    });

    test('should validate email format', async ({ page }) => {
        await page.goto('/profile');

        const profileSection = page
            .locator('section')
            .filter({ hasText: 'Profile Information' });

        // Scroll to section
        await profileSection.scrollIntoViewIfNeeded();

        // Enter invalid email format
        const emailInput = profileSection.getByLabel(/email/i);
        await emailInput.fill('invalid-email-format');

        // Try to submit
        const saveButton = profileSection.getByRole('button', {
            name: /^save$/i,
        });
        await saveButton.click();

        // HTML5 validation should catch invalid email format
        const isInvalid = await emailInput.evaluate((el) =>
            el.matches(':invalid'),
        );
        expect(isInvalid).toBeTruthy();

        // Restore valid email
        await emailInput.fill(TEST_USER.email);
    });

    test('should require both personality type and identity', async ({
        page,
    }) => {
        await page.goto('/profile');

        const personalitySection = page
            .locator('section')
            .filter({ hasText: 'Your Personality Type' });

        // Select personality type but not identity
        const personalitySelect =
            personalitySection.getByLabel(/personality type/i);
        await personalitySelect.selectOption('INTJ');

        // Wait for identity options

        // HTML5 validation should prevent submission due to required identity radio
        // Check that at least one radio in the identity group has the required attribute
        const assertiveRadio = personalitySection.getByRole('radio', {
            name: /assertive/i,
        });
        const isRequired = await assertiveRadio.getAttribute('required');
        // HTML required attribute returns "" when present (which is truthy but fails toBeTruthy)
        expect(isRequired).not.toBeNull();

        // Try to submit without selecting identity - should fail HTML5 validation
        const saveButton = personalitySection.getByRole('button', {
            name: /^save$/i,
        });
        await saveButton.click();

        // Should still be on profile page (form didn't submit)
        expect(page.url()).toContain('/profile');
    });

    test('should handle responsive design on mobile viewport', async ({
        page,
    }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });

        await page.goto('/profile');

        // All sections should still be visible and usable
        await expect(
            page.getByRole('heading', { name: /^profile$/i }),
        ).toBeVisible();

        // Check that forms are still accessible
        const nameInput = page.getByLabel(/name/i);
        await expect(nameInput).toBeVisible();

        // Check that buttons are visible and clickable
        const saveButtons = page.getByRole('button', { name: /^save$/i });
        const firstSaveButton = saveButtons.first();
        await expect(firstSaveButton).toBeVisible();

        // Verify button is within viewport (can be clicked)
        const boundingBox = await firstSaveButton.boundingBox();
        expect(boundingBox).toBeTruthy();
        expect(boundingBox!.y).toBeGreaterThanOrEqual(0);
    });
});
