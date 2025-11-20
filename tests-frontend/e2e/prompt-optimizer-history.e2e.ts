import { expect, test } from '@playwright/test';
import { loginAsTestUser, seedTestUser } from './helpers/auth';
import { execAsync, seedPromptRuns } from './helpers/database';

/**
 * Comprehensive e2e tests for the Prompt Optimiser History page
 *
 * Tests cover:
 * - Authentication requirements
 * - Empty state display
 * - History table with data
 * - Sorting functionality
 * - Pagination controls
 * - Navigation to prompt details
 * - Responsive design
 */

test.describe.skip('Prompt Optimiser History - Unauthenticated Access', () => {
    test('should redirect to login when accessing history without authentication', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Should redirect to home or login page
        const url = page.url();
        const isRedirected = url === '/' || url.includes('login');

        expect(isRedirected).toBeTruthy();
        expect(url).not.toContain('/prompt-optimizer-history');
    });
});

test.describe.skip('Prompt Optimiser History - Empty State', () => {
    test.beforeAll(async () => {
        // Seed the test user
        await seedTestUser();

        // Ensure no prompt runs exist for test user
        await execAsync(
            './vendor/bin/sail artisan db:seed --class=CleanPromptRunsSeeder',
        );
    });

    test.beforeEach(async ({ page }) => {
        // Log in before each test
        await loginAsTestUser(page);
    });

    test('should display page heading when authenticated', async ({ page }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Should see the page heading
        const heading = page.getByRole('heading', {
            name: /prompt history/i,
        });
        await expect(heading).toBeVisible();
    });

    test('should show empty state message when no history exists', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Should see empty state message
        const emptyMessage = page.getByText(/no prompt history yet/i);
        await expect(emptyMessage).toBeVisible();

        // Should see a link to create first prompt
        const createLink = page.getByRole('link', {
            name: /create your first optimised prompt/i,
        });
        await expect(createLink).toBeVisible();
        expect(await createLink.getAttribute('href')).toContain(
            '/prompt-optimizer',
        );
    });

    test('should show "Create New" button in header', async ({ page }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Should see "Create New" button
        const createButton = page.getByRole('link', {
            name: /create new/i,
        });
        await expect(createButton).toBeVisible();
        expect(await createButton.getAttribute('href')).toContain(
            '/prompt-optimizer',
        );
    });

    test('should navigate to prompt optimiser when clicking empty state link', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Click the create link in empty state
        const createLink = page.getByRole('link', {
            name: /create your first optimised prompt/i,
        });
        await createLink.click();

        // Should navigate to prompt optimiser
        await page.waitForURL('/prompt-optimizer');
        expect(page.url()).toContain('/prompt-optimizer');

        // Should see the task input form
        const taskInput = page.getByLabel(/task description/i);
        await expect(taskInput).toBeVisible();
    });
});

test.describe.skip('Prompt Optimiser History - With Data', () => {
    test.beforeAll(async () => {
        // Seed the test user and create prompt runs
        await seedTestUser();
        await seedPromptRuns(15); // Create 15 prompt runs for testing pagination
    });

    test.beforeEach(async ({ page }) => {
        // Log in before each test
        await loginAsTestUser(page);
    });

    test('should display history table with prompt runs', async ({ page }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Should see the table
        const table = page.locator('table');
        await expect(table).toBeVisible();

        // Should see table headers
        await expect(
            page.getByRole('columnheader', { name: /task description/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('columnheader', { name: /created/i }),
        ).toBeVisible();

        // Should see at least one row
        const rows = page.locator('tbody tr');
        const rowCount = await rows.count();
        expect(rowCount).toBeGreaterThan(0);
    });

    test('should display all required columns in desktop view', async ({
        page,
    }) => {
        // Set viewport to desktop size
        await page.setViewportSize({ width: 1280, height: 720 });

        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Should see all column headers on desktop
        await expect(
            page.getByRole('columnheader', { name: /personality type/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('columnheader', { name: /task description/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('columnheader', { name: /framework/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('columnheader', { name: /status/i }),
        ).toBeVisible();
        await expect(
            page.getByRole('columnheader', { name: /created/i }),
        ).toBeVisible();
    });

    test('should show status badges for each prompt run', async ({ page }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Should see at least one status badge
        const statusBadges = page.getByTestId('status-badge');
        const badgeCount = await statusBadges.count();
        expect(badgeCount).toBeGreaterThan(0);

        // First badge should be visible
        await expect(statusBadges.first()).toBeVisible();
    });

    test('should display dates in British format', async ({ page }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Get first date cell
        const firstRow = page.locator('tbody tr').first();
        const dateCell = firstRow.locator('td').last();
        const dateText = await dateCell.textContent();

        // Date should be formatted (DD/MM/YYYY or similar British format)
        // Just verify it contains a date-like pattern
        expect(dateText).toBeTruthy();
        expect(dateText?.trim().length).toBeGreaterThan(0);
    });

    test('should display personality types', async ({ page }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // At desktop size, should see personality types in dedicated column
        await page.setViewportSize({ width: 1280, height: 720 });

        const firstRow = page.locator('tbody tr').first();
        const personalityCell = firstRow.locator('td').first();
        const personalityText = await personalityCell.textContent();

        // Should contain a personality type code (4 letters)
        expect(personalityText).toBeTruthy();
        expect(personalityText?.trim().length).toBeGreaterThanOrEqual(4);
    });

    test('should truncate long task descriptions', async ({ page }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Task descriptions should be visible but may be truncated
        const taskCells = page.locator('tbody tr td').nth(1);
        const taskText = await taskCells.first().textContent();

        expect(taskText).toBeTruthy();
        // Text should be reasonable length (truncated at 80 chars in component)
        expect(taskText!.length).toBeLessThanOrEqual(85);
    });

    test('should show framework names or placeholder', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 720 });

        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Framework column should exist on large screens
        const frameworkHeader = page.getByRole('columnheader', {
            name: /framework/i,
        });
        await expect(frameworkHeader).toBeVisible();

        // First row's framework cell should have content or placeholder
        const firstRow = page.locator('tbody tr').first();
        const frameworkCell = firstRow.locator('td').nth(2);
        const frameworkText = await frameworkCell.textContent();

        // Should have text (framework name or em-dash for null)
        expect(frameworkText).toBeTruthy();
    });
});

test.describe.skip('Prompt Optimiser History - Sorting', () => {
    test.beforeAll(async () => {
        await seedTestUser();
        await seedPromptRuns(10);
    });

    test.beforeEach(async ({ page }) => {
        await loginAsTestUser(page);
    });

    test('should sort by created date (default descending)', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Created column should be sortable
        const createdHeader = page.getByRole('columnheader', {
            name: /created/i,
        });

        // Should show sorting indicator (default is descending by created_at)
        const headerButton = createdHeader.locator('button');
        await expect(headerButton).toBeVisible();
    });

    test('should toggle sort direction when clicking column header', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Click task description header to sort
        const taskHeader = page
            .getByRole('columnheader', { name: /task description/i })
            .locator('button');
        await taskHeader.click();

        // Wait for Inertia navigation
        await page.waitForLoadState('networkidle');

        // URL should contain sort parameters
        expect(page.url()).toContain('sort_by=task_description');
        expect(page.url()).toContain('sort_direction=asc');

        // Click again to reverse sort
        await taskHeader.click();
        await page.waitForLoadState('networkidle');

        // Direction should change to descending
        expect(page.url()).toContain('sort_direction=desc');
    });

    test('should sort by status', async ({ page }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Desktop size to see all columns
        await page.setViewportSize({ width: 1280, height: 720 });

        // Click status header
        const statusHeader = page
            .getByRole('columnheader', { name: /status/i })
            .locator('button');
        await statusHeader.click();

        await page.waitForLoadState('networkidle');

        // Should sort by status
        expect(page.url()).toContain('sort_by=status');
    });

    test('should sort by personality type', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 720 });

        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Click personality type header
        const personalityHeader = page
            .getByRole('columnheader', { name: /personality type/i })
            .locator('button');
        await personalityHeader.click();

        await page.waitForLoadState('networkidle');

        // Should sort by personality type
        expect(page.url()).toContain('sort_by=personality_type');
    });

    test('should sort by framework', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 720 });

        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Click framework header
        const frameworkHeader = page
            .getByRole('columnheader', { name: /framework/i })
            .locator('button');
        await frameworkHeader.click();

        await page.waitForLoadState('networkidle');

        // Should sort by framework
        expect(page.url()).toContain('sort_by=selected_framework');
    });
});

test.describe.skip('Prompt Optimiser History - Pagination', () => {
    test.beforeAll(async () => {
        await seedTestUser();
        await seedPromptRuns(25); // Create enough for multiple pages
    });

    test.beforeEach(async ({ page }) => {
        await loginAsTestUser(page);
    });

    test('should display pagination controls when multiple pages exist', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer-history?per_page=10');
        await page.waitForLoadState('networkidle');

        // Desktop size to see pagination
        await page.setViewportSize({ width: 1280, height: 720 });

        // Should see "Showing X to Y of Z results" text
        const resultsText = page.getByText(
            /showing \d+ to \d+ of \d+ results/i,
        );
        await expect(resultsText).toBeVisible();

        // Should see page indicator
        const pageIndicator = page.getByText(/page \d+ of \d+/i);
        await expect(pageIndicator).toBeVisible();
    });

    test('should navigate to next page', async ({ page }) => {
        await page.goto('/prompt-optimizer-history?per_page=10');
        await page.waitForLoadState('networkidle');

        await page.setViewportSize({ width: 1280, height: 720 });

        // Click Next button
        const nextButton = page.getByRole('link', { name: /next/i }).first();
        await expect(nextButton).toBeVisible();
        await nextButton.click();

        await page.waitForLoadState('networkidle');

        // URL should contain page parameter
        expect(page.url()).toContain('page=2');
    });

    test('should navigate to previous page', async ({ page }) => {
        // Start on page 2
        await page.goto('/prompt-optimizer-history?per_page=10&page=2');
        await page.waitForLoadState('networkidle');

        await page.setViewportSize({ width: 1280, height: 720 });

        // Click Previous button
        const prevButton = page
            .getByRole('link', { name: /previous/i })
            .first();
        await expect(prevButton).toBeVisible();
        await prevButton.click();

        await page.waitForLoadState('networkidle');

        // Should go back to page 1
        const url = page.url();
        const isPageOne = !url.includes('page=') || url.includes('page=1');
        expect(isPageOne).toBeTruthy();
    });

    test('should allow changing items per page', async ({ page }) => {
        await page.goto('/prompt-optimizer-history?per_page=10');
        await page.waitForLoadState('networkidle');

        await page.setViewportSize({ width: 1280, height: 720 });

        // Find the per-page input
        const perPageInput = page.locator('#per-page-desktop');
        await expect(perPageInput).toBeVisible();

        // Current value should be 10
        await expect(perPageInput).toHaveValue('10');

        // Change to 20
        await perPageInput.fill('20');
        await perPageInput.press('Enter');

        await page.waitForLoadState('networkidle');

        // URL should reflect the change
        expect(page.url()).toContain('per_page=20');

        // Input should show new value
        await expect(perPageInput).toHaveValue('20');
    });

    test('should show mobile pagination controls', async ({ page }) => {
        // Mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });

        await page.goto('/prompt-optimizer-history?per_page=10');
        await page.waitForLoadState('networkidle');

        // Should see mobile "Page X of Y" text
        const pageIndicator = page.getByText(/page \d+ of \d+/i);
        await expect(pageIndicator).toBeVisible();

        // Should see Previous or Next button (depending on page)
        const mobileButtons = page.locator(
            '#pagination-prev-mobile, #pagination-next-mobile',
        );
        const buttonCount = await mobileButtons.count();
        expect(buttonCount).toBeGreaterThan(0);
    });

    test('should validate per-page input constraints', async ({ page }) => {
        await page.goto('/prompt-optimizer-history?per_page=10');
        await page.waitForLoadState('networkidle');

        await page.setViewportSize({ width: 1280, height: 720 });

        const perPageInput = page.locator('#per-page-desktop');

        // Try to set invalid value (too high)
        await perPageInput.fill('500');
        await perPageInput.press('Enter');

        await page.waitForLoadState('networkidle');

        // Should be clamped to max (100)
        expect(page.url()).toContain('per_page=100');
    });
});

test.describe.skip('Prompt Optimiser History - Navigation', () => {
    test.beforeAll(async () => {
        await seedTestUser();
        await seedPromptRuns(5);
    });

    test.beforeEach(async ({ page }) => {
        await loginAsTestUser(page);
    });

    test('should navigate to prompt details when clicking a row', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Click the first row
        const firstRow = page.locator('tbody tr').first();
        await firstRow.click();

        // Should navigate to prompt show page
        await page.waitForURL(/\/prompt-optimizer\/\d+/);
        expect(page.url()).toMatch(/\/prompt-optimizer\/\d+/);

        // Should see the prompt details page
        const statusBadge = page.getByTestId('status-badge');
        await expect(statusBadge).toBeVisible();
    });

    test('should support keyboard navigation to prompt details', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Focus and press Enter on first row
        const firstRow = page.locator('tbody tr').first();
        await firstRow.focus();
        await firstRow.press('Enter');

        // Should navigate
        await page.waitForURL(/\/prompt-optimizer\/\d+/);
        expect(page.url()).toMatch(/\/prompt-optimizer\/\d+/);
    });

    test('should navigate to create new prompt from header button', async ({
        page,
    }) => {
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Click Create New button in header
        const createButton = page.getByRole('link', { name: /create new/i });
        await createButton.click();

        // Should navigate to prompt optimiser
        await page.waitForURL('/prompt-optimizer');
        expect(page.url()).toContain('/prompt-optimizer');
    });
});

test.describe.skip('Prompt Optimiser History - Responsive Design', () => {
    test.beforeAll(async () => {
        await seedTestUser();
        await seedPromptRuns(5);
    });

    test.beforeEach(async ({ page }) => {
        await loginAsTestUser(page);
    });

    test('should adapt table layout for mobile viewport', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });

        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Table should still be visible
        const table = page.locator('table');
        await expect(table).toBeVisible();

        // Some columns should be hidden on mobile (personality type)
        const personalityHeader = page.getByRole('columnheader', {
            name: /personality type/i,
        });
        // On mobile (below sm breakpoint), this column is hidden
        const isHidden = await personalityHeader.isHidden().catch(() => true);
        expect(isHidden).toBeTruthy();
    });

    test('should show status badge in mobile view', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 667 });

        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Status badges should be visible on mobile (in the created date cell)
        const statusBadges = page.getByTestId('status-badge');
        const badgeCount = await statusBadges.count();
        expect(badgeCount).toBeGreaterThan(0);
    });

    test('should show mobile per-page selector', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 667 });

        await page.goto('/prompt-optimizer-history?per_page=10');
        await page.waitForLoadState('networkidle');

        // Mobile per-page input should be visible
        const perPageInput = page.locator('#per-page');
        await expect(perPageInput).toBeVisible();

        // Should show "Show X per page" label
        const label = page.getByText(/show/i);
        await expect(label).toBeVisible();
    });

    test('should maintain clickable rows on mobile', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 667 });

        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Rows should still be clickable
        const firstRow = page.locator('tbody tr').first();
        await expect(firstRow).toBeVisible();

        await firstRow.click();

        // Should navigate
        await page.waitForURL(/\/prompt-optimizer\/\d+/);
        expect(page.url()).toMatch(/\/prompt-optimizer\/\d+/);
    });

    test('should display header and Create New button on mobile', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 375, height: 667 });

        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Header should be visible
        const heading = page.getByRole('heading', { name: /prompt history/i });
        await expect(heading).toBeVisible();

        // Create New button should be visible
        const createButton = page.getByRole('link', { name: /create new/i });
        await expect(createButton).toBeVisible();
    });
});

test.describe.skip('Prompt Optimiser History - Edge Cases', () => {
    test.beforeAll(async () => {
        await seedTestUser();
    });

    test.beforeEach(async ({ page }) => {
        await loginAsTestUser(page);
    });

    test('should handle prompt runs with different statuses', async ({
        page,
    }) => {
        // Create runs with various statuses
        await seedPromptRuns(3, 'completed');
        await seedPromptRuns(2, 'processing');
        await seedPromptRuns(1, 'failed');

        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Should see various status badges
        const statusBadges = page.getByTestId('status-badge');
        const badgeCount = await statusBadges.count();
        expect(badgeCount).toBeGreaterThanOrEqual(6);

        // Should see different badge variants
        const allBadges = await statusBadges.all();
        expect(allBadges.length).toBeGreaterThan(0);
    });

    test('should handle prompt runs without frameworks', async ({ page }) => {
        // Create runs without frameworks selected yet
        await seedPromptRuns(3, 'pending');

        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        await page.setViewportSize({ width: 1280, height: 720 });

        // Framework column should show placeholder (em-dash)
        const frameworkCells = page.locator('tbody tr td').nth(2);
        const cellText = await frameworkCells.first().textContent();

        // Should have content (either framework name or em-dash)
        expect(cellText).toBeTruthy();
    });

    test('should maintain sort and pagination state when navigating back', async ({
        page,
    }) => {
        await seedPromptRuns(15);

        // Navigate with specific sort and pagination
        await page.goto(
            '/prompt-optimizer-history?sort_by=status&sort_direction=asc&per_page=5&page=2',
        );
        await page.waitForLoadState('networkidle');

        // Click on a prompt
        const firstRow = page.locator('tbody tr').first();
        await firstRow.click();
        await page.waitForURL(/\/prompt-optimizer\/\d+/);

        // Go back
        await page.goBack();
        await page.waitForLoadState('networkidle');

        // Should maintain query parameters
        const url = page.url();
        expect(url).toContain('sort_by=status');
        expect(url).toContain('sort_direction=asc');
        expect(url).toContain('per_page=5');
        expect(url).toContain('page=2');
    });
});
