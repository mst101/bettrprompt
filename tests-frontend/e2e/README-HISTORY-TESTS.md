# Prompt Optimiser History E2E Tests

Comprehensive end-to-end tests for the Prompt Optimiser History feature.

## Test File

- `/home/mark/repos/personality/tests-frontend/e2e/prompt-optimizer-history.e2e.ts`

## Coverage

The test suite covers the following scenarios:

### 1. Unauthenticated Access
- Redirects to login when accessing history without authentication

### 2. Empty State (No History)
- Displays page heading when authenticated
- Shows empty state message when no history exists
- Shows "Create New" button in header
- Navigates to prompt optimiser when clicking empty state link

### 3. With Data (History Table)
- Displays history table with prompt runs
- Shows all required columns (Personality Type, Task Description, Framework, Status, Created)
- Displays status badges for each prompt run
- Displays dates in British format
- Shows personality types (full names on large screens, codes on smaller)
- Truncates long task descriptions (80 chars max)
- Shows framework names or em-dash placeholder for null values

### 4. Sorting Functionality
- Default sort by created date (descending)
- Toggle sort direction when clicking column headers
- Sort by:
  - Task Description
  - Status
  - Personality Type
  - Framework
  - Created date

### 5. Pagination
- Displays pagination controls when multiple pages exist
- Shows "Showing X to Y of Z results" text
- Navigates to next/previous page
- Allows changing items per page (1-100 range)
- Validates per-page input constraints
- Shows mobile pagination controls
- Maintains sort and pagination state when navigating back

### 6. Navigation
- Navigates to prompt details when clicking a row
- Supports keyboard navigation (Enter key)
- Navigates to create new prompt from header button

### 7. Responsive Design
- Adapts table layout for mobile viewport
- Hides personality type column on mobile
- Shows status badge in mobile view
- Shows mobile per-page selector
- Maintains clickable rows on mobile
- Displays header and Create New button on mobile

### 8. Edge Cases
- Handles prompt runs with different statuses (completed, processing, failed, pending)
- Handles prompt runs without frameworks
- Maintains state when navigating back from detail page

## Test Setup

The tests use the following helper functions:

### Authentication Helpers
```typescript
import { loginAsTestUser, seedTestUser } from './helpers/auth';
```

- `seedTestUser()` - Creates the test user in the database
- `loginAsTestUser(page)` - Logs in the test user via the login modal

### Database Helpers
```typescript
import { execAsync, seedPromptRuns } from './helpers/database';
```

- `seedPromptRuns(count, status?)` - Seeds prompt runs for the test user
- `execAsync(command)` - Executes shell commands

## Database Seeders

The tests rely on Laravel seeders:

### TestUserSeeder
Creates the test user (test@hiddengambia.com)

```bash
./vendor/bin/sail artisan db:seed --class=TestUserSeeder
```

### TestPromptRunsSeeder
Creates prompt runs for the test user with configurable count and status

```bash
# Default (5 runs with mixed statuses)
./vendor/bin/sail artisan db:seed --class=TestPromptRunsSeeder

# With specific count
SEED_COUNT=15 ./vendor/bin/sail artisan db:seed --class=TestPromptRunsSeeder

# With specific status
SEED_COUNT=10 SEED_STATUS=completed ./vendor/bin/sail artisan db:seed --class=TestPromptRunsSeeder
```

Valid statuses: `completed`, `processing`, `failed`, `pending`

### CleanPromptRunsSeeder
Removes all prompt runs for the test user

```bash
./vendor/bin/sail artisan db:seed --class=CleanPromptRunsSeeder
```

## Running the Tests

```bash
# Run all history tests
npm run test:e2e tests-frontend/e2e/prompt-optimizer-history.e2e.ts

# Run specific test group
npm run test:e2e tests-frontend/e2e/prompt-optimizer-history.e2e.ts -g "Empty State"

# Run in headed mode (see browser)
npm run test:e2e tests-frontend/e2e/prompt-optimizer-history.e2e.ts -- --headed

# Run with debug
npm run test:e2e tests-frontend/e2e/prompt-optimizer-history.e2e.ts -- --debug
```

## Test Structure

Each test group follows this pattern:

```typescript
test.describe('Feature Name', () => {
    test.beforeAll(async () => {
        // Seed test user and data
        await seedTestUser();
        await seedPromptRuns(15);
    });

    test.beforeEach(async ({ page }) => {
        // Log in before each test
        await loginAsTestUser(page);
    });

    test('should test specific behaviour', async ({ page }) => {
        // Arrange
        await page.goto('/prompt-optimizer-history');
        await page.waitForLoadState('networkidle');

        // Act
        const element = page.getByRole('button', { name: /click me/i });
        await element.click();

        // Assert
        await expect(element).toBeVisible();
    });
});
```

## Best Practices

1. **Use British English** in test descriptions and assertions
2. **Wait for network idle** after navigation: `await page.waitForLoadState('networkidle')`
3. **Use accessible selectors** (getByRole, getByLabel, getByTestId)
4. **Test responsiveness** by setting viewport sizes
5. **Verify navigation** by checking URLs: `expect(page.url()).toContain(...)`
6. **Test keyboard navigation** where applicable
7. **Handle async operations** with proper waits
8. **Clean up state** between test groups (use seeders)

## Maintenance Notes

- Tests use British date format expectations
- Pagination defaults to 10 items per page (configurable in component via localStorage)
- Table shows different columns based on viewport size (mobile: hidden columns)
- Status badges use `data-testid="status-badge"` for testing
- All sortable columns use the TableHeaderSortable component
