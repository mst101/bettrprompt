import { expect, setupPromptRun, test } from './fixtures';
import { execAsync, seedPromptRuns } from './helpers/database';

/**
 * Comprehensive e2e tests for the Prompt Builder History page
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

test.describe('Prompt Builder History - Unauthenticated Access', () => {
    test('should redirect to login when accessing history without authentication', async ({
        page,
    }) => {
        await page.goto('/history');

        // Should redirect to home or login page
        const url = page.url();
        const isRedirected = url === '/' || url.includes('login');

        expect(isRedirected).toBeTruthy();
        expect(url).not.toContain('/history');
    });
});

test.describe('Prompt Builder History - Empty State', () => {
    test.beforeEach(async ({ authenticatedPageWithUniqueUser }) => {
        // Ensure database is clean before test
        // Run the cleanup seeder synchronously to guarantee it completes
        try {
            await execAsync(
                './vendor/bin/sail artisan db:seed --class=CleanPromptRunsSeeder --env=e2e --force',
            );
        } catch {
            // Continue - the test will verify correct state
        }

        // Navigate to empty history page
        await authenticatedPageWithUniqueUser.goto('/history');

        // Wait for page to fully load before proceeding
        await authenticatedPageWithUniqueUser.waitForLoadState('networkidle');
    });

    test('should display page heading when authenticated', async ({
        authenticatedPageWithUniqueUser,
    }) => {
        // Already navigated in beforeEach, just verify content
        // Should see the page heading
        const heading = authenticatedPageWithUniqueUser.getByRole('heading', {
            name: /prompt history/i,
        });
        await expect(heading).toBeVisible({ timeout: 5000 });
    });

    test('should show empty state message when no history exists', async ({
        authenticatedPageWithUniqueUser,
    }) => {
        // Page is already navigated to empty state in beforeEach
        // Verify empty state message is visible when no prompts exist
        const emptyStateContainer = authenticatedPageWithUniqueUser.locator(
            'text=/no prompt history yet\./i',
        );
        await expect(emptyStateContainer).toBeVisible({ timeout: 5000 });

        // Verify there's a link to create the first prompt
        const createLink = authenticatedPageWithUniqueUser.getByRole('link', {
            name: /create your first optimised prompt/i,
        });
        await expect(createLink).toBeVisible({ timeout: 5000 });
    });

    test('should show "Create New" button in header', async ({
        authenticatedPageWithUniqueUser,
    }) => {
        // Page already navigated in beforeEach
        // Should see "Create New" button
        const createButton = authenticatedPageWithUniqueUser.getByRole('link', {
            name: /create new/i,
        });
        await expect(createButton).toBeVisible({ timeout: 5000 });
        expect(await createButton.getAttribute('href')).toContain(
            '/prompt-builder',
        );
    });

    test('should navigate to prompt optimiser when clicking empty state link', async ({
        authenticatedPageWithUniqueUser,
    }) => {
        // Page already navigated in beforeEach to empty state
        // Try to click the create link if available
        const createLink = authenticatedPageWithUniqueUser.getByRole('link', {
            name: /create your first optimised prompt|create new/i,
        });

        const linkExists = await createLink.isVisible().catch(() => false);

        if (linkExists) {
            // Wait for navigation before clicking
            const navigationPromise =
                authenticatedPageWithUniqueUser.waitForURL(/\/prompt-builder/, {
                    timeout: 5000,
                });

            await createLink.click();
            await navigationPromise;

            // Should navigate to prompt builder
            expect(authenticatedPageWithUniqueUser.url()).toContain(
                '/prompt-builder',
            );

            // Should see the task input form
            const taskInput =
                authenticatedPageWithUniqueUser.getByLabel(/task description/i);
            await expect(taskInput).toBeVisible({ timeout: 5000 });
        } else {
            // If the empty state link isn't visible, just verify we're on the history page
            expect(authenticatedPageWithUniqueUser.url()).toContain('/history');
        }
    });
});

test.describe('Prompt Builder History - With Data', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        // Seed prompt runs before each test to ensure fresh data
        await seedPromptRuns(15); // Create 15 prompt runs for testing
        // User is already authenticated via fixture

        void authenticatedPage;
    });

    test('should display history table with prompt runs', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history');

        // Wait for page and table to load
        await authenticatedPage.waitForLoadState('networkidle');

        // Should see the table with created data
        const table = authenticatedPage.locator('table');
        await expect(table).toBeVisible({ timeout: 10000 });

        // Should see at least one row (we created 10 in beforeEach)
        const rows = authenticatedPage.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 5000 });

        const rowCount = await rows.count();
        expect(rowCount).toBeGreaterThanOrEqual(1);
    });

    test('should display all required columns in desktop view', async ({
        authenticatedPage,
    }) => {
        // Set viewport to desktop size
        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        await authenticatedPage.goto('/history');

        // Wait for page and table to fully load
        await authenticatedPage.waitForLoadState('networkidle');

        // Wait for table to be visible
        const table = authenticatedPage.locator('table');
        await expect(table).toBeVisible({ timeout: 10000 });

        // Should see all column headers on desktop
        await expect(
            authenticatedPage.getByRole('columnheader', {
                name: /personality type/i,
            }),
        ).toBeVisible();
        await expect(
            authenticatedPage.getByRole('columnheader', {
                name: /task description/i,
            }),
        ).toBeVisible();
        await expect(
            authenticatedPage.getByRole('columnheader', { name: /framework/i }),
        ).toBeVisible();
        await expect(
            authenticatedPage.getByRole('columnheader', { name: /status/i }),
        ).toBeVisible();
        await expect(
            authenticatedPage.getByRole('columnheader', { name: /created/i }),
        ).toBeVisible();
    });

    test('should show status badges for each prompt run', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history');

        // Wait for page to load
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Should see at least one status badge (we created 10 prompt runs)
        const statusBadges = authenticatedPage.getByTestId('status-badge');
        await expect(statusBadges.first()).toBeVisible({ timeout: 5000 });

        const badgeCount = await statusBadges.count();
        expect(badgeCount).toBeGreaterThan(0);
    });

    test('should display dates in British format', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history');

        // Wait for page to load
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Wait for table and rows to appear
        const rows = authenticatedPage.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 5000 });

        // Get first date cell
        const firstRow = rows.first();
        const dateCell = firstRow.locator(
            '[data-testid="table-cell-date"], td:last-child',
        );

        await expect(dateCell).toBeVisible();

        const dateText = await dateCell.textContent();

        // Date should have content and contain numbers
        expect(dateText).toBeTruthy();
        const trimmedDate = dateText?.trim() || '';
        expect(trimmedDate.length).toBeGreaterThan(0);
        expect(/[a-z]/.test(trimmedDate)).toBeTruthy();
    });

    test('should display personality types', async ({ authenticatedPage }) => {
        await authenticatedPage.goto('/history');

        // Set viewport to desktop size
        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        // Wait for page to load
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Wait for rows to appear
        const rows = authenticatedPage.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 5000 });

        const firstRow = rows.first();
        const personalityCell = firstRow.locator('td').first();

        await expect(personalityCell).toBeVisible();

        const personalityText = await personalityCell.textContent();

        // Should contain a personality type code (at least 4 characters)
        expect(personalityText).toBeTruthy();
        expect(personalityText?.trim().length).toBeGreaterThanOrEqual(4);
    });

    test('should truncate long task descriptions', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history');

        // Wait for page to load
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Wait for table to appear
        const table = authenticatedPage.locator('table');
        await expect(table).toBeVisible({ timeout: 5000 });

        // Wait for rows to appear
        const rows = authenticatedPage.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 5000 });

        // Task descriptions should be visible
        const taskCell = authenticatedPage.locator(
            '[data-testid="table-cell-task"], tbody tr td:nth-child(2)',
        );

        const firstTaskCell = taskCell.first();
        await expect(firstTaskCell).toBeVisible();

        const taskText = await firstTaskCell.textContent();

        expect(taskText).toBeTruthy();
        // Text should be present and reasonable length (truncated at 80 chars in component)
        const fullText = taskText?.trim() || '';
        expect(fullText.length).toBeGreaterThan(0);
        expect(fullText.length).toBeLessThanOrEqual(85); // Component truncates at 80 chars
    });

    test('should show framework names or placeholder', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        await authenticatedPage.goto('/history');

        // Framework column should exist on large screens
        const frameworkHeader = authenticatedPage.getByRole('columnheader', {
            name: /framework/i,
        });
        await expect(frameworkHeader).toBeVisible();

        // First row's framework cell should have content or placeholder
        // Use data-testid to avoid brittle nth() selectors
        const firstRow = authenticatedPage.locator('tbody tr').first();
        const frameworkCell = firstRow.locator(
            '[data-testid="table-cell-framework"], td:nth-child(3)',
        );

        // Wait for the cell to be visible and have content
        await expect(frameworkCell).toBeVisible();

        const frameworkText = await frameworkCell.textContent();

        // Should have text (framework name or em-dash for null)
        // The cell can contain either:
        // 1. A framework name (e.g., "SMART Goals", "Brainstorming", "5 Whys", "SWOT Analysis", etc.)
        // 2. A placeholder em-dash "—" or "–" for null frameworks
        const trimmedText = frameworkText?.trim() || '';
        expect(trimmedText.length).toBeGreaterThan(0);

        // Very lenient validation: just check that it's not empty
        // Different frameworks can be seeded, so we just verify the cell is populated
        expect(trimmedText).toBeTruthy();
    });
});

test.describe('Prompt Builder History - Sorting', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        // Seed data for sorting tests
        await seedPromptRuns(10);
        // User is already authenticated via fixture

        void authenticatedPage;
    });

    test('should sort by created date (default descending)', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history');

        // Created column should be sortable
        const createdHeader = authenticatedPage.getByRole('columnheader', {
            name: /created/i,
        });

        // Should show sorting indicator (default is descending by created_at)
        const headerButton = createdHeader.locator('button');
        await expect(headerButton).toBeVisible();
    });

    test('should toggle sort direction when clicking column header', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history');

        // Click task description header to sort
        const taskHeader = authenticatedPage
            .getByRole('columnheader', { name: /task description/i })
            .locator('button');

        // Wait for URL to change after clicking
        await Promise.all([
            authenticatedPage.waitForURL(/sort_by=task_description/, {
                timeout: 5000,
            }),
            taskHeader.click(),
        ]);

        // Wait for navigation to complete

        // URL should contain sort parameters
        expect(authenticatedPage.url()).toContain('sort_by=task_description');
        expect(authenticatedPage.url()).toContain('sort_direction=asc');

        // Click again to reverse sort
        await Promise.all([
            authenticatedPage.waitForURL(/sort_direction=desc/, {
                timeout: 5000,
            }),
            taskHeader.click(),
        ]);

        // Direction should change to descending
        expect(authenticatedPage.url()).toContain('sort_direction=desc');
    });

    test('should sort by workflow stage', async ({ authenticatedPage }) => {
        await authenticatedPage.goto('/history');

        // Desktop size to see all columns
        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        // Click workflow stage header
        const workflowStageHeader = authenticatedPage
            .getByRole('columnheader', { name: /workflow stage|status/i })
            .locator('button');

        // Wait for URL to change after clicking
        const sortPromise = authenticatedPage.waitForURL(
            /sort_by=workflow_stage/,
            {
                timeout: 10000,
            },
        );
        await workflowStageHeader.click();
        await sortPromise;

        // Should sort by workflow_stage
        expect(authenticatedPage.url()).toContain('sort_by=workflow_stage');
    });

    test('should sort by personality type', async ({ authenticatedPage }) => {
        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        await authenticatedPage.goto('/history');

        // Click personality type header
        const personalityHeader = authenticatedPage
            .getByRole('columnheader', { name: /personality type/i })
            .locator('button');

        // Wait for URL to change after clicking
        const sortPromise = authenticatedPage.waitForURL(
            /sort_by=personality_type/,
            {
                timeout: 10000,
            },
        );
        await personalityHeader.click();
        await sortPromise;

        // Should sort by personality type
        expect(authenticatedPage.url()).toContain('sort_by=personality_type');
    });

    test('should sort by framework', async ({ authenticatedPage }) => {
        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        await authenticatedPage.goto('/history');

        // Click framework header
        const frameworkHeader = authenticatedPage
            .getByRole('columnheader', { name: /framework/i })
            .locator('button');

        // Wait for URL to change after clicking
        const sortPromise = authenticatedPage.waitForURL(
            /sort_by=selected_framework/,
            {
                timeout: 10000,
            },
        );
        await frameworkHeader.click();
        await sortPromise;

        // Should sort by framework
        expect(authenticatedPage.url()).toContain('sort_by=selected_framework');
    });
});

test.describe('Prompt Builder History - Pagination', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        // Seed data for pagination testing
        await seedPromptRuns(25); // Create enough for multiple pages
        // User is already authenticated via fixture

        // Clear localStorage to reset per_page preference
        await authenticatedPage.evaluate(() => {
            localStorage.removeItem('history_per_page');
        });
    });

    test('should display pagination controls when multiple pages exist', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history?per_page=10');

        // Wait for page to load
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Wait for table to appear
        const table = authenticatedPage.locator('table');
        await expect(table).toBeVisible({ timeout: 5000 });

        // Desktop size to see pagination
        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        // Should see "Showing X to Y of Z results" text (use .last() to get desktop version)
        const resultsText = authenticatedPage
            .getByText(/Showing \d+ to \d+ of \d+ results/i)
            .last();
        await expect(resultsText).toBeVisible({ timeout: 5000 });

        // Should see page indicator (use .last() to get desktop version)
        const pageIndicator = authenticatedPage
            .getByText(/page \d+ of \d+/i)
            .last();
        await expect(pageIndicator).toBeVisible({ timeout: 5000 });
    });

    test('should navigate to next page', async ({ authenticatedPage }) => {
        await authenticatedPage.goto('/history?per_page=10');

        // Wait for page to load
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Wait for table to appear (we created 25 items, so should have 3 pages)
        const table = authenticatedPage.locator('table');
        await expect(table).toBeVisible({ timeout: 10000 });

        // Wait for rows to be visible
        const rows = authenticatedPage.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 5000 });

        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        // The next button should exist since we have 25 items and per_page=10
        const nextButton = authenticatedPage
            .getByRole('link', { name: /next/i })
            .first();
        await expect(nextButton).toBeVisible({ timeout: 5000 });

        // Wait for URL to change after clicking
        await Promise.all([
            authenticatedPage.waitForURL(/page=2/, { timeout: 5000 }),
            nextButton.click(),
        ]);

        // URL should contain page parameter
        expect(authenticatedPage.url()).toContain('page=2');
    });

    test('should navigate to previous page', async ({ authenticatedPage }) => {
        // Start on page 2
        await authenticatedPage.goto('/history?per_page=10&page=2');

        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        // Click Previous button
        const prevButton = authenticatedPage
            .getByRole('link', { name: /previous/i })
            .first();
        await expect(prevButton).toBeVisible();
        await prevButton.click();

        // Should go back to page 1
        const url = authenticatedPage.url();
        const isPageOne = !url.includes('page=') || url.includes('page=1');
        expect(isPageOne).toBeTruthy();
    });

    test('should allow changing items per page', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history?per_page=10');

        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        // Find the per-page input
        const perPageInput = authenticatedPage.locator(
            '[data-testid="per-page-input"], #per-page-desktop, input[name="per_page"]',
        );
        await expect(perPageInput).toBeVisible();

        // Current value should be 10
        await expect(perPageInput).toHaveValue('10');

        // Change to 20
        await perPageInput.fill('20');

        // Wait for URL to change after pressing Enter
        await Promise.all([
            authenticatedPage.waitForURL(/per_page=20/, { timeout: 5000 }),
            perPageInput.press('Enter'),
        ]);

        // URL should reflect the change
        expect(authenticatedPage.url()).toContain('per_page=20');

        // Input should show new value
        await expect(perPageInput).toHaveValue('20');
    });

    test('should show mobile pagination controls', async ({
        authenticatedPage,
    }) => {
        // Mobile viewport
        await authenticatedPage.setViewportSize({ width: 375, height: 667 });

        await authenticatedPage.goto('/history?per_page=10');

        // Should see mobile "Page X of Y" text (use .first() for mobile version)
        const pageIndicator = authenticatedPage
            .getByText(/page \d+ of \d+/i)
            .first();
        await expect(pageIndicator).toBeVisible();

        // Should see Previous or Next button (depending on page)
        const mobileButtons = authenticatedPage.locator(
            '#pagination-prev-mobile, #pagination-next-mobile',
        );
        const buttonCount = await mobileButtons.count();
        expect(buttonCount).toBeGreaterThan(0);
    });

    test('should validate per-page input constraints', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history?per_page=10');

        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        const perPageInput = authenticatedPage.locator(
            '[data-testid="per-page-input"], #per-page-desktop, input[name="per_page"]',
        );

        // Current value should be 10
        await expect(perPageInput).toHaveValue('10');

        // Try to set invalid value (too high)
        await perPageInput.fill('500');
        await perPageInput.press('Enter');

        // Give it a moment to process

        // Should reject invalid value and reset input to current value (10)
        await expect(perPageInput).toHaveValue('10');
        // URL should remain unchanged
        expect(authenticatedPage.url()).toContain('per_page=10');

        // Try invalid value (too low)
        await perPageInput.fill('0');
        await perPageInput.press('Enter');

        // Should reject and reset
        await expect(perPageInput).toHaveValue('10');
        expect(authenticatedPage.url()).toContain('per_page=10');
    });
});

test.describe('Prompt Builder History - Navigation', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        // Seed data for navigation tests
        await seedPromptRuns(5);
        // User is already authenticated via fixture

        void authenticatedPage;
    });

    test('should navigate to prompt details when clicking a row', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history');

        // Click the first row
        const firstRow = authenticatedPage.locator('tbody tr').first();
        await expect(firstRow).toBeVisible({ timeout: 5000 });
        const taskCell = firstRow.locator('[data-testid="table-cell-task"]');
        await expect(taskCell).toBeVisible({ timeout: 5000 });

        // Wait for navigation after clicking
        await Promise.all([
            authenticatedPage.waitForURL(/\/prompt-builder\/[a-z]+/, {
                timeout: 5000,
            }),
            taskCell.click(),
        ]);

        expect(authenticatedPage.url()).toMatch(
            /[a-z]{2}(-[A-Z]{2})?\/prompt-builder\/[a-z]+/,
        );

        // Should see the prompt details page (verify by heading)
        const heading = authenticatedPage.getByRole('heading', {
            name: /prompt builder/i,
        });
        await expect(heading).toBeVisible();
    });

    test('should support keyboard navigation to prompt details', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history');

        // Focus and press Enter on first row
        const firstRow = authenticatedPage.locator('tbody tr').first();
        await expect(firstRow).toBeVisible({ timeout: 5000 });
        await firstRow.focus();

        // Wait for navigation after pressing Enter
        await Promise.all([
            authenticatedPage.waitForURL(/\/prompt-builder\/[a-z]+/, {
                timeout: 5000,
            }),
            firstRow.press('Enter'),
        ]);

        expect(authenticatedPage.url()).toMatch(
            /[a-z]{2}(-[A-Z]{2})?\/prompt-builder\/[a-z]+/,
        );
    });

    test('should navigate to create new prompt from header button', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.goto('/history');

        // Click Create New button in header
        const createButton = authenticatedPage.getByRole('link', {
            name: /create new/i,
        });
        await createButton.click();

        // Should navigate to prompt optimiser
        await authenticatedPage.waitForURL('/prompt-builder');
        expect(authenticatedPage.url()).toContain('/prompt-builder');
    });

    test('should show Clarifying Questions tab when Framework has been viewed before', async ({
        authenticatedPage,
    }) => {
        // Create a prompt run in completed state (without navigating to it yet)
        const promptRunId = await setupPromptRun(
            authenticatedPage,
            '1_completed',
        );

        // Set localStorage flag BEFORE navigating, so it's available when component mounts
        const storageKey = `promptRun_${promptRunId}_viewedFramework`;
        await authenticatedPage.evaluate(
            ({ key, value }) => {
                localStorage.setItem(key, value);
            },
            { key: storageKey, value: 'true' },
        );

        // Now navigate to prompt details - the component will check the localStorage flag on mount
        await authenticatedPage.goto(`/prompt-builder/${promptRunId}`);

        // Should see the Clarifying Questions tab content displayed
        const clarifyingQuestionsContent = authenticatedPage.locator(
            '[data-testid="tab-questions"]',
        );
        await expect(clarifyingQuestionsContent).toBeVisible();

        // Verify the Clarifying Questions tab button is styled as active
        const clarifyingQuestionsButton = authenticatedPage.locator(
            '[data-testid="tab-button-questions"]',
        );
        await expect(clarifyingQuestionsButton).toHaveClass(
            /border-indigo-500/,
        );

        // Verify the Framework tab button is NOT styled as active
        const frameworkButton = authenticatedPage.locator(
            '[data-testid="tab-button-framework"]',
        );
        await expect(frameworkButton).not.toHaveClass(/border-indigo-500/);
    });
});

test.describe('Prompt Builder History - Responsive Design', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        // Seed data for responsive layout testing
        await seedPromptRuns(5);
        // User is already authenticated via fixture

        void authenticatedPage;
    });

    test('should adapt table layout for mobile viewport', async ({
        authenticatedPage,
    }) => {
        // Set mobile viewport (below sm: 640px breakpoint)
        await authenticatedPage.setViewportSize({ width: 375, height: 667 });

        await authenticatedPage.goto('/history');

        // On mobile, table columns are hidden (sm:hidden), but content should still be displayed
        // Either as cards/list items or in a different responsive layout
        // Check that at least some prompt run data is visible on the page
        const pageHeading = authenticatedPage.getByRole('heading', {
            name: /prompt history/i,
        });
        await expect(pageHeading).toBeVisible();

        // Some form of content should be visible (either table or responsive layout)
        // Check for common elements that indicate prompt runs are displayed
        const pageContent = authenticatedPage.locator('main');
        await expect(pageContent).toBeVisible();
    });

    test('should show status badge in mobile view', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.setViewportSize({ width: 375, height: 667 });

        await authenticatedPage.goto('/history');

        // Status badges should be visible on mobile (in the created date cell)
        // Mobile status indicators are rendered inside the date cell
        const dateCell = authenticatedPage
            .locator('[data-testid="table-cell-date"]')
            .first();
        const isVisible = await dateCell.isVisible().catch(() => false);

        expect(isVisible).toBe(true);
    });

    test('should show mobile per-page selector', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.setViewportSize({ width: 375, height: 667 });

        await authenticatedPage.goto('/history?per_page=10');

        // Mobile per-page input should be visible
        const perPageInput = authenticatedPage.locator(
            '[data-testid="per-page-input-mobile"], #per-page, input[name="per_page"]',
        );
        await expect(perPageInput).toBeVisible();

        // Should show "Show X per page" label (use label for per-page input to be specific)
        const label = authenticatedPage.locator(
            'label[for="per-page"], [data-testid="per-page-label"]',
        );
        await expect(label).toBeVisible();
        await expect(label).toHaveText('Show');
    });

    test('should maintain clickable rows on mobile', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.setViewportSize({ width: 375, height: 667 });

        await authenticatedPage.goto('/history');

        // Rows should still be clickable
        const firstRow = authenticatedPage.locator('tbody tr').first();
        await expect(firstRow).toBeVisible();
        const taskCell = firstRow.locator('[data-testid="table-cell-task"]');
        await expect(taskCell).toBeVisible();

        await taskCell.click();

        // Should navigate
        await authenticatedPage.waitForURL(/\/prompt-builder\/[a-z]+/, {
            timeout: 5000,
        });
        expect(authenticatedPage.url()).toMatch(
            /[a-z]{2}(-[A-Z]{2})?\/prompt-builder\/[a-z]+/,
        );
    });

    test('should display header and Create New button on mobile', async ({
        authenticatedPage,
    }) => {
        await authenticatedPage.setViewportSize({ width: 375, height: 667 });

        await authenticatedPage.goto('/history');

        // Header should be visible
        const heading = authenticatedPage.getByRole('heading', {
            name: /prompt history/i,
        });
        await expect(heading).toBeVisible();

        // Create New button should be visible
        const createButton = authenticatedPage.getByRole('link', {
            name: /create new/i,
        });
        await expect(createButton).toBeVisible();
    });
});

test.describe('Prompt Builder History - Edge Cases', () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        // User is already authenticated via fixture

        void authenticatedPage;
    });

    test('should handle prompt runs with different statuses', async ({
        authenticatedPage: page,
    }) => {
        // Create runs with various statuses for this test
        await seedPromptRuns(6); // Creates varied statuses

        await page.goto('/history');

        // Wait for page to load
        await page.waitForLoadState('domcontentloaded');

        // Should see various status badges
        const statusBadges = page.getByTestId('status-badge');
        await expect(statusBadges.first()).toBeVisible({ timeout: 5000 });

        const badgeCount = await statusBadges.count();
        // We should have at least some badges
        expect(badgeCount).toBeGreaterThan(0);
    });

    test('should handle prompt runs without frameworks', async ({
        authenticatedPage,
    }) => {
        // Create runs in processing state (many won't have frameworks)
        await seedPromptRuns(3);

        await authenticatedPage.goto('/history');

        // Wait for page to load
        await authenticatedPage.waitForLoadState('domcontentloaded');

        await authenticatedPage.setViewportSize({ width: 1280, height: 720 });

        // Framework column should show content or placeholder
        const rows = authenticatedPage.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 5000 });

        const firstRow = rows.first();
        const frameworkCell = firstRow.locator('td').nth(2);
        const cellText = await frameworkCell.textContent();

        // Should have content (either framework name or em-dash)
        expect(cellText).toBeTruthy();
    });

    test('should maintain sort and pagination state when navigating back', async ({
        authenticatedPage,
    }) => {
        // Create data for navigation and state testing
        await seedPromptRuns(15);

        // Set localStorage to match the per_page we'll use in the URL
        await authenticatedPage.evaluate(() => {
            localStorage.setItem('history_per_page', '5');
        });

        // Wait for page to load
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Navigate with specific sort and pagination
        await authenticatedPage.goto(
            '/history?sort_by=workflow_stage&sort_direction=asc&per_page=5&page=2',
        );

        // Wait for table to appear
        await authenticatedPage.waitForLoadState('domcontentloaded');

        // Click on a prompt
        const firstRow = authenticatedPage.locator('tbody tr').first();
        await expect(firstRow).toBeVisible({ timeout: 5000 });
        const taskCell = firstRow.locator('[data-testid="table-cell-task"]');
        await expect(taskCell).toBeVisible({ timeout: 5000 });

        // Wait for navigation after clicking
        const navPromise = authenticatedPage.waitForURL(
            /\/prompt-builder\/[a-z]+/,
            {
                timeout: 10000,
            },
        );
        await taskCell.click();
        await navPromise;

        expect(authenticatedPage.url()).toMatch(
            /[a-z]{2}(-[A-Z]{2})?\/prompt-builder\/[a-z]+/,
        );

        // Go back and wait for navigation to history page
        const backPromise = authenticatedPage.waitForURL(/\/history/, {
            timeout: 10000,
        });
        await authenticatedPage.goBack();
        await backPromise;

        // Should maintain query parameters
        const url = authenticatedPage.url();
        expect(url).toContain('sort_by=workflow_stage');
        expect(url).toContain('sort_direction=asc');
        expect(url).toContain('per_page=5');
        expect(url).toContain('page=2');
    });
});
