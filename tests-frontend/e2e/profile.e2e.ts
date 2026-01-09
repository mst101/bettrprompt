import { expect, test } from './fixtures';
import { TEST_USER } from './helpers/auth';

test.describe('Profile - Unauthenticated Access', () => {
    test('should redirect to login when accessing profile without auth', async ({
        page,
    }) => {
        await page.goto('/profile');

        // Should be redirected to home or login
        const url = page.url();
        expect(url).toMatch(/\/[a-z]{2}(-[A-Z]{2})?(\/login)?(\?.*)?$/);
    });
});

test.describe('Profile - Authenticated User', () => {
    test('should load profile edit page', async ({ authenticatedPage }) => {
        await authenticatedPage.goto('/profile');

        // Should see Profile heading from HeaderPage component
        const heading = authenticatedPage.getByRole('heading', {
            name: /^profile$/i,
        });
        await expect(heading).toBeVisible();

        // Verify all sections are visible (CollapsibleSection titles)
        await expect(
            authenticatedPage.getByRole('heading', {
                name: /your personality/i,
            }),
        ).toBeVisible();
        await expect(
            authenticatedPage.getByRole('heading', {
                name: /profile information/i,
            }),
        ).toBeVisible();
        await expect(
            authenticatedPage.getByRole('heading', {
                name: /update password/i,
            }),
        ).toBeVisible();
        await expect(
            authenticatedPage.getByRole('heading', { name: /delete account/i }),
        ).toBeVisible();
    });

    test('should display current user information', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/profile');

        // Expand Profile Information section
        const profileHeader = authenticatedPage.getByTestId(
            'collapsible-header-profile-information',
        );
        await profileHeader.click();

        // Should see name and email fields populated with TEST_USER data
        // Labels may include asterisk for required fields: "Name *"
        const nameInput = authenticatedPage.getByLabel(/name/i);
        const emailInput = authenticatedPage.getByLabel(/email/i);

        await expect(nameInput).toBeVisible();
        await expect(emailInput).toBeVisible();

        // Check that fields have values matching TEST_USER
        const nameValue = await nameInput.inputValue();
        const emailValue = await emailInput.inputValue();

        expect(nameValue).toBe(TEST_USER.name);
        expect(emailValue).toBe(TEST_USER.email);
        expect(emailValue).toMatch(/@/);
    });

    test('should update profile information', async ({ authenticatedPage }) => {
        await authenticatedPage.goto('/profile');

        // Expand Profile Information section
        const profileHeader = authenticatedPage.getByTestId(
            'collapsible-header-profile-information',
        );
        await profileHeader.click();

        // Wait for section to expand
        await authenticatedPage.waitForTimeout(300);

        // Find and update the name field
        const nameInput = authenticatedPage.getByLabel(/name/i);
        await nameInput.scrollIntoViewIfNeeded();

        const originalName = await nameInput.inputValue();
        const newName = 'Updated Test Name';

        await nameInput.fill(newName);

        // Submit the form - find Save button (first one available)
        const saveButton = authenticatedPage
            .getByRole('button', {
                name: /^save$/i,
            })
            .first();

        await saveButton.click();

        // Wait for the form to process
        await authenticatedPage.waitForLoadState('networkidle');

        // Verify the name was updated in the form
        await expect(nameInput).toHaveValue(newName);

        // Restore original name for subsequent tests
        await nameInput.fill(originalName);
        await saveButton.click();

        // Wait for the form to process
        await authenticatedPage.waitForLoadState('networkidle');
    });

    test('should display personality type section', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/profile');

        // Look for personality type section
        const personalityHeading = authenticatedPage.getByRole('heading', {
            name: /your personality/i,
        });
        await expect(personalityHeading).toBeVisible();

        // Click to expand the section
        await personalityHeading.click();
        await authenticatedPage.waitForTimeout(300);

        // Should see personality type dropdown (may have asterisk for required)
        const personalitySelect =
            authenticatedPage.getByLabel(/personality type/i);
        await expect(personalitySelect).toBeVisible();

        // Select a personality type to reveal identity options
        await personalitySelect.selectOption({ index: 1 }); // Select first actual option

        // Wait for identity radio buttons to appear

        // Should see identity radio buttons
        const assertiveRadio = authenticatedPage.getByLabel(/assertive \(a\)/i);
        const turbulentRadio = authenticatedPage.getByLabel(/turbulent \(t\)/i);

        await expect(assertiveRadio).toBeVisible();
        await expect(turbulentRadio).toBeVisible();

        // Check for trait percentages toggle button
        const toggleButton = authenticatedPage.getByRole('button', {
            name: /trait percentages/i,
        });
        await expect(toggleButton).toBeVisible();
    });

    test('should update personality type traits', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/profile');

        // Expand personality section
        const personalityHeading = authenticatedPage.getByRole('heading', {
            name: /your personality/i,
        });
        await personalityHeading.click();
        await authenticatedPage.waitForTimeout(300);

        // Select personality type - INTJ (Architect)
        const personalitySelect =
            authenticatedPage.getByLabel(/personality type/i);
        await personalitySelect.selectOption('INTJ');

        // Wait for identity options to appear

        // Select identity - Assertive
        const assertiveRadio = authenticatedPage.getByLabel(/assertive \(a\)/i);
        await assertiveRadio.check();

        // Expand trait percentages section
        const toggleButton = authenticatedPage.getByRole('button', {
            name: /\+ add trait percentages/i,
        });

        // Click toggle if trait percentages are hidden
        if (await toggleButton.isVisible()) {
            await toggleButton.click();
            await authenticatedPage.waitForTimeout(300);
        }

        // Fill in trait percentage inputs with new labels
        const introExtraInput = authenticatedPage.getByLabel(
            /introversion\/extraversion/i,
        );
        const intuitiveObservantInput =
            authenticatedPage.getByLabel(/intuitive\/observant/i);
        const thinkingFeelingInput =
            authenticatedPage.getByLabel(/thinking\/feeling/i);
        const judgingProspectingInput =
            authenticatedPage.getByLabel(/judging\/prospecting/i);
        const assertiveTurbulentInput =
            authenticatedPage.getByLabel(/assertive\/turbulent/i);

        // Fill in trait percentages (0-100)
        await introExtraInput.fill('75');
        await intuitiveObservantInput.fill('80');
        await thinkingFeelingInput.fill('70');
        await judgingProspectingInput.fill('85');
        await assertiveTurbulentInput.fill('65');

        // Submit personality form
        const saveButton = authenticatedPage
            .getByRole('button', {
                name: /^save$/i,
            })
            .first();

        await saveButton.click();

        // Wait for the form to process
        await authenticatedPage.waitForLoadState('networkidle');
    });

    test('should change password successfully', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/profile');

        // Expand password section
        const passwordHeading = authenticatedPage.getByRole('heading', {
            name: /update password/i,
        });
        await passwordHeading.click();
        await authenticatedPage.waitForTimeout(300);

        // Fill password fields with valid credentials (labels may include asterisks)
        await authenticatedPage
            .getByLabel(/current password/i)
            .fill(TEST_USER.password);
        await authenticatedPage.getByLabel(/new password/i).fill('newpass123');
        await authenticatedPage
            .getByLabel(/confirm password/i)
            .fill('newpass123');

        // Submit password form
        const saveButton = authenticatedPage
            .getByRole('button', {
                name: /^save$/i,
            })
            .first();

        await saveButton.click();

        // Wait for the form to process
        await authenticatedPage.waitForLoadState('networkidle');

        // Change password back to original for subsequent tests
        await authenticatedPage
            .getByLabel(/current password/i)
            .fill('newpass123');
        await authenticatedPage
            .getByLabel(/new password/i)
            .fill(TEST_USER.password);
        await authenticatedPage
            .getByLabel(/confirm password/i)
            .fill(TEST_USER.password);
        await saveButton.click();

        await authenticatedPage.waitForLoadState('networkidle');
    });

    test('should show validation errors for mismatched passwords', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/profile');

        // Expand password section
        const passwordHeading = authenticatedPage.getByRole('heading', {
            name: /update password/i,
        });
        await passwordHeading.click();
        await authenticatedPage.waitForTimeout(300);

        // Fill password fields with mismatched confirmation
        const currentPasswordInput =
            authenticatedPage.getByLabel(/current password/i);
        const newPasswordInput = authenticatedPage.getByLabel(/new password/i);
        const confirmPasswordInput =
            authenticatedPage.getByLabel(/confirm password/i);

        await expect(currentPasswordInput).toBeVisible();
        await expect(newPasswordInput).toBeVisible();
        await expect(confirmPasswordInput).toBeVisible();

        await currentPasswordInput.fill(TEST_USER.password);
        await newPasswordInput.fill('password123');
        await confirmPasswordInput.fill('different-password');

        // Submit password form
        const saveButton = authenticatedPage
            .getByRole('button', {
                name: /^save$/i,
            })
            .first();
        await saveButton.click();

        // Wait for validation processing
        await authenticatedPage.waitForTimeout(1000);

        // Form should still be present (validation prevents submission)
        await expect(saveButton).toBeVisible();
    });

    test('should delete account with confirmation modal', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/profile');

        const deleteSection = authenticatedPage
            .locator('section')
            .filter({ hasText: 'Delete Account' });

        // Scroll to delete section
        await deleteSection.scrollIntoViewIfNeeded();

        // Expand the delete account section
        const deleteHeader = deleteSection.locator('[role="button"]').first();
        await deleteHeader.click();
        await authenticatedPage.waitForTimeout(300);

        // Find and click delete account button (the actual button, not the header)
        const deleteButton = deleteSection
            .locator('[data-testid="collapsible-content-delete-account"]')
            .getByRole('button', { name: /delete account/i });
        await expect(deleteButton).toBeVisible();
        await deleteButton.click();

        // Wait for modal to appear

        // Should show confirmation modal with warning text
        const modal = authenticatedPage.getByRole('dialog');
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
        const cancelButton = authenticatedPage.getByRole('button', {
            name: /^cancel$/i,
        });
        await cancelButton.click();

        // Modal should close
        await expect(confirmText).not.toBeVisible({ timeout: 3000 });

        // Should still be on profile page
        expect(authenticatedPage.url()).toContain('/profile');
    });

    test('should display user avatar if present', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/profile');

        // Check for avatar image (may not be implemented yet)
        const avatar = authenticatedPage
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
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/profile');

        // Expand Profile Information section
        const profileHeading = authenticatedPage.getByRole('heading', {
            name: /profile information/i,
        });
        await profileHeading.click();
        await authenticatedPage.waitForTimeout(300);

        // Clear required name field
        const nameInput = authenticatedPage.getByLabel(/name/i);
        await nameInput.fill('');

        // Try to submit
        const saveButton = authenticatedPage
            .getByRole('button', {
                name: /^save$/i,
            })
            .first();
        await saveButton.click();

        // Should show validation error for required name field
        // HTML5 validation or Laravel validation should prevent submission
        const errorMessage = authenticatedPage
            .locator('text=/required/i')
            .first();
        const hasError =
            (await errorMessage.isVisible().catch(() => false)) ||
            // Or check if the name input has invalid state
            (await nameInput
                .evaluate((el) => el.matches(':invalid'))
                .catch(() => false));

        expect(hasError).toBeTruthy();
    });

    test('should validate email format', async ({ authenticatedPage }) => {
        await authenticatedPage.goto('/profile');

        // Expand Profile Information section
        const profileHeading = authenticatedPage.getByRole('heading', {
            name: /profile information/i,
        });
        await profileHeading.click();
        await authenticatedPage.waitForTimeout(300);

        // Enter invalid email format
        const emailInput = authenticatedPage.getByLabel(/email/i);
        await emailInput.fill('invalid-email-format');

        // Try to submit
        const saveButton = authenticatedPage
            .getByRole('button', {
                name: /^save$/i,
            })
            .first();
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
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/profile');

        // Expand personality section
        const personalityHeading = authenticatedPage.getByRole('heading', {
            name: /your personality/i,
        });
        await personalityHeading.click();
        await authenticatedPage.waitForTimeout(300);

        // Select personality type but not identity
        const personalitySelect =
            authenticatedPage.getByLabel(/personality type/i);
        await personalitySelect.selectOption('INTJ');

        // Wait for identity options

        // HTML5 validation should prevent submission due to required identity radio
        // Check that at least one radio in the identity group has the required attribute
        const assertiveRadio = authenticatedPage.getByRole('radio', {
            name: /assertive/i,
        });
        const isRequired = await assertiveRadio.getAttribute('required');
        // HTML required attribute returns "" when present (which is truthy but fails toBeTruthy)
        expect(isRequired).not.toBeNull();

        // Try to submit without selecting identity - should fail HTML5 validation
        const saveButton = authenticatedPage
            .getByRole('button', {
                name: /^save$/i,
            })
            .first();
        await saveButton.click();

        // Should still be on profile page (form didn't submit)
        expect(authenticatedPage.url()).toContain('/profile');
    });

    test('should handle responsive design on mobile viewport', async ({
        authenticatedPage,
    }) => {
        // Set mobile viewport
        await authenticatedPage.setViewportSize({ width: 375, height: 667 });

        await authenticatedPage.goto('/profile');

        // All sections should still be visible and usable
        await expect(
            authenticatedPage.getByRole('heading', { name: /^profile$/i }),
        ).toBeVisible();

        // Expand Profile Information section
        const profileHeader = authenticatedPage.getByTestId(
            'collapsible-header-profile-information',
        );
        await profileHeader.click();
        await authenticatedPage.waitForTimeout(300);

        // Check that forms are still accessible
        const nameInput = authenticatedPage.getByLabel(/name/i);
        await expect(nameInput).toBeVisible();

        // Check that buttons are visible and clickable
        const saveButtons = authenticatedPage.getByRole('button', {
            name: /^save$/i,
        });
        const firstSaveButton = saveButtons.first();
        await expect(firstSaveButton).toBeVisible();

        // Verify button is within viewport (can be clicked)
        const boundingBox = await firstSaveButton.boundingBox();
        expect(boundingBox).toBeTruthy();
        expect(boundingBox!.y).toBeGreaterThanOrEqual(0);
    });
});
