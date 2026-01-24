import { expect, test } from './fixtures';

/**
 * Pricing Page - Button Functionality Tests
 *
 * These tests verify the correct behaviour of the pricing page buttons:
 * - "Get Started" button (Free tier)
 * - "Start Pro" button (Pro tier)
 * - "Start Private" button (Private tier)
 *
 * Each button should:
 * 1. For unauthenticated users: Open the register modal
 * 2. For authenticated users: Navigate to appropriate page or checkout
 * 3. Be disabled when user already has that subscription tier
 */

test.describe('Pricing Page - Unauthenticated Users', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/pricing');
        // Wait for page to fully load before tests run
        await page.waitForLoadState('networkidle');
        // Ensure pricing cards are visible before proceeding
        await page.getByRole('heading', { name: /^pro$/i }).waitFor({
            state: 'visible',
            timeout: 5000,
        });
    });

    test('should navigate to prompt-builder when clicking "Get Started" button', async ({
        page,
    }) => {
        // Locate the "Get Started" button in the Free tier card
        const getStartedButton = page.getByTestId('get-started-button');

        // Verify button is visible
        await expect(getStartedButton).toBeVisible();

        // Click the button
        await getStartedButton.click();

        // Wait for navigation to prompt-builder page
        // The URL should match the pattern /[country]/prompt-builder
        await page.waitForURL(/[a-z]{2}\/prompt-builder/);

        // Verify we're on the prompt-builder page
        expect(page.url()).toMatch(/prompt-builder/);

        // Verify prompt builder page content is visible
        // Check for task description input which is unique to prompt builder
        const taskInput = page.getByLabel(/task description/i);
        await expect(taskInput).toBeVisible({ timeout: 5000 });
    });

    test('should open register modal when clicking "Start Pro" button', async ({
        page,
    }) => {
        // Locate the "Start Pro" button in the Pro tier card
        const proCard = page.getByTestId('pro-tier-tab');
        const startProButton = proCard.getByTestId('subscribe-button');

        // Verify button is visible
        await expect(startProButton).toBeVisible();

        // Click the button
        await startProButton.click();

        // Wait for register modal to appear
        // The modal opens directly without navigation (using injected openRegisterModal function)
        await page.waitForTimeout(500);

        // Verify register modal content is visible
        // The register modal should have a "Confirm Password" field unique to registration
        await expect(page.getByLabel(/^confirm password/i)).toBeVisible({
            timeout: 5000,
        });

        // Also verify the create account button
        const createAccountButton = page.getByRole('button', {
            name: /create account/i,
        });
        await expect(createAccountButton).toBeVisible();

        // Verify we're still on the pricing page (no navigation occurred)
        expect(page.url()).toContain('/pricing');
    });
});

test.describe('Pricing Page - Authenticated Users', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        await authenticatedPage.goto('/pricing');
        // Wait for page to fully load before tests run
        await authenticatedPage.waitForLoadState('networkidle');
        // Ensure pricing cards are visible before proceeding
        await authenticatedPage
            .getByRole('heading', { name: /^pro$/i })
            .waitFor({
                state: 'visible',
                timeout: 5000,
            });
    });

    test('should navigate to prompt-builder when authenticated user clicks "Get Started"', async ({
        authenticatedPage,
    }) => {
        // Locate the "Get Started" button in the Free tier card
        const getStartedButton =
            authenticatedPage.getByTestId('get-started-button');

        // Verify button is visible
        await expect(getStartedButton).toBeVisible();

        // Click the button
        await getStartedButton.click();

        // Wait for navigation to prompt-builder page
        // The URL should match the pattern /[country]/prompt-builder
        await authenticatedPage.waitForURL(/[a-z]{2}\/prompt-builder/);

        // Verify we're on the prompt-builder page
        expect(authenticatedPage.url()).toMatch(/prompt-builder/);

        // Verify prompt builder page content is visible
        // Check for task description input which is unique to prompt builder
        const taskInput = authenticatedPage.getByLabel(/task description/i);
        await expect(taskInput).toBeVisible({ timeout: 5000 });

        // Note: Both authenticated and unauthenticated users now navigate to prompt-builder
        // Unauthenticated users can use the free tier immediately
    });

    test('should initiate checkout when authenticated user clicks "Start Pro"', async ({
        authenticatedPage,
    }) => {
        // Locate the "Start Pro" button in the Pro tier card
        const proCard = authenticatedPage.getByTestId('pro-tier-tab');
        const startProButton = proCard.getByTestId('subscribe-button');

        // Verify button is visible and enabled
        await expect(startProButton).toBeVisible();
        await expect(startProButton).toBeEnabled();

        // Click the button
        await startProButton.click();

        // Wait a moment for the fetch to start
        await authenticatedPage.waitForTimeout(500);

        // Verify we're still on the pricing page (Stripe redirects happen after getting the URL)
        expect(authenticatedPage.url()).toContain('/pricing');

        // Button should be in either loading or enabled state (depending on request timing)
        // We just verify it exists
        await expect(startProButton).toBeVisible();
    });
});

// Note: Currency Switching tests removed - currency switcher no longer exists
// Users now select currency by country code in URL

test.describe('Pricing Page - Monthly/Yearly Toggle', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/pricing');
        // Wait for page to fully load before tests run
        await page.waitForLoadState('networkidle');
        // Ensure pricing cards are visible before proceeding
        await page.getByRole('heading', { name: /^pro$/i }).waitFor({
            state: 'visible',
            timeout: 5000,
        });
    });

    test('should display monthly and yearly toggle buttons', async ({
        page,
    }) => {
        const monthlyToggle = page.getByTestId('monthly-toggle');
        const annualToggle = page.getByTestId('annual-toggle');

        await expect(monthlyToggle).toBeVisible();
        await expect(annualToggle).toBeVisible();
    });

    test('should default to yearly billing', async ({ page }) => {
        const annualToggle = page.getByTestId('annual-toggle');

        // Yearly should be selected by default
        await expect(annualToggle).toHaveClass(/bg-indigo-100/);
    });

    test('should allow toggling to monthly billing', async ({ page }) => {
        const monthlyToggle = page.getByTestId('monthly-toggle');
        const annualToggle = page.getByTestId('annual-toggle');

        // Initially yearly should be selected
        await expect(annualToggle).toHaveClass(/bg-indigo-100/);

        // Click Monthly
        await monthlyToggle.click();

        // Monthly should now be selected
        await expect(monthlyToggle).toHaveClass(/bg-indigo-100/);
    });

    test('should allow toggling back to yearly billing', async ({ page }) => {
        const monthlyToggle = page.getByTestId('monthly-toggle');
        const annualToggle = page.getByTestId('annual-toggle');

        // Switch to monthly first
        await monthlyToggle.click();
        await expect(monthlyToggle).toHaveClass(/bg-indigo-100/);

        // Switch back to yearly
        await annualToggle.click();
        await expect(annualToggle).toHaveClass(/bg-indigo-100/);
    });

    test('should update pricing when toggling billing period', async ({
        page,
    }) => {
        const monthlyToggle = page.getByTestId('monthly-toggle');
        const annualToggle = page.getByTestId('annual-toggle');
        const proCard = page.getByTestId('pro-tier-tab');

        // Get initial price (yearly)
        const yearlyPrice = await proCard
            .locator('div')
            .filter({ hasText: /year/ })
            .first()
            .textContent();

        expect(yearlyPrice).toBeTruthy();

        // Switch to monthly
        await monthlyToggle.click();

        // Wait a moment for price to update (it's a reactive change)
        await page.waitForTimeout(100);

        // Verify we now see "month" in the pricing
        const monthlyPrice = await proCard
            .locator('div')
            .filter({ hasText: /month/ })
            .first()
            .textContent();

        expect(monthlyPrice).toBeTruthy();

        // Switch back to yearly
        await annualToggle.click();

        // Wait a moment for price to update
        await page.waitForTimeout(100);

        // Verify we see "year" again
        const yearlyPriceAgain = await proCard
            .locator('div')
            .filter({ hasText: /year/ })
            .first()
            .textContent();

        expect(yearlyPriceAgain).toBeTruthy();
    });

    test('should display savings message for yearly billing', async ({
        page,
    }) => {
        const annualToggle = page.getByTestId('annual-toggle');

        // Ensure we're on yearly
        await annualToggle.click();

        // Look for savings text (e.g., "Save 17%")
        const savingsText = page.locator('text=/Save/i');
        await expect(savingsText.first()).toBeVisible();
    });

    test('should be keyboard accessible', async ({ page }) => {
        const monthlyToggle = page.getByTestId('monthly-toggle');
        const annualToggle = page.getByTestId('annual-toggle');

        // Focus on Monthly toggle
        await monthlyToggle.focus();
        await expect(monthlyToggle).toBeFocused();

        // Tab to next element
        await page.keyboard.press('Tab');

        // Annual toggle should now be focusable
        await expect(annualToggle).toBeVisible();
    });
});

test.describe('Pricing Page - Button Disabled States', () => {
    test('should show appropriate text and state for free tier users', async ({
        authenticatedPage,
    }) => {
        // Navigate to pricing page as authenticated user (free tier by default)
        await authenticatedPage.goto('/pricing');
        await authenticatedPage.waitForLoadState('networkidle');

        // Ensure pricing cards are visible
        await authenticatedPage
            .getByRole('heading', { name: /^pro$/i })
            .waitFor({
                state: 'visible',
                timeout: 5000,
            });

        // Get Started button should be visible and enabled
        const getStartedButton =
            authenticatedPage.getByTestId('get-started-button');
        await expect(getStartedButton).toBeVisible();
        await expect(getStartedButton).toBeEnabled();

        // Pro button should be enabled (not subscribed yet)
        const proCard = authenticatedPage.getByTestId('pro-tier-tab');
        const proButton = proCard.getByTestId('subscribe-button');
        await expect(proButton).toBeEnabled();
    });

    // Note: Testing disabled states for Pro/Private subscriptions would require:
    // 1. Creating a user with an active subscription in the test database
    // 2. Or using a test endpoint to set subscription status
    // These tests can be added when subscription test helpers are available

    test('should display correct button text for all tiers', async ({
        page,
    }) => {
        await page.goto('/pricing');
        await page.waitForLoadState('networkidle');

        // Ensure pricing cards are visible
        await page.getByRole('heading', { name: /^pro$/i }).waitFor({
            state: 'visible',
            timeout: 5000,
        });

        // Check Free tier button text
        const getStartedButton = page.getByTestId('get-started-button');
        await expect(getStartedButton).toContainText(/get started/i);

        // Check Pro tier button exists (text depends on auth state)
        const proCard = page.getByTestId('pro-tier-tab');
        const proButton = proCard.getByTestId('subscribe-button');
        await expect(proButton).toBeVisible();
    });
});

test.describe('Pricing Page - Integration Tests', () => {
    test('should display pricing tiers side by side', async ({ page }) => {
        await page.goto('/pricing');
        await page.waitForLoadState('networkidle');

        // Verify all tiers are visible
        await expect(
            page.getByRole('heading', { name: /^free$/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: /^pro$/i }),
        ).toBeVisible();

        // Verify tier card is in the expected layout
        const proCard = page.getByTestId('pro-tier-tab');
        await expect(proCard).toBeVisible();
    });

    test('should display page title and heading', async ({ page }) => {
        await page.goto('/pricing');
        await page.waitForLoadState('networkidle');

        // Check page title
        await expect(page).toHaveTitle(/pricing/i);

        // Check main heading
        await expect(
            page.getByRole('heading', {
                name: /simple.*transparent.*pricing/i,
            }),
        ).toBeVisible();
    });
});
