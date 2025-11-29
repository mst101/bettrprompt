# Prompt Optimiser History E2E Tests - Implementation Summary

## Files Created

### 1. E2E Test Suite

**File:** `/home/mark/repos/personality/tests-frontend/e2e/prompt-builder-history.e2e.ts`

Comprehensive test suite with 30+ tests covering:

- Unauthenticated access (1 test)
- Empty state (4 tests)
- Data display (7 tests)
- Sorting functionality (5 tests)
- Pagination (6 tests)
- Navigation (3 tests)
- Responsive design (5 tests)
- Edge cases (3 tests)

### 2. Database Helper Functions

**File:** `/home/mark/repos/personality/tests-frontend/e2e/helpers/database.ts`

Helper functions for test data management:

- `execAsync(command)` - Execute shell commands
- `seedPromptRuns(count, status?)` - Seed prompt runs for testing

### 3. Laravel Seeders

#### TestPromptRunsSeeder

**File:** `/home/mark/repos/personality/database/seeders/TestPromptRunsSeeder.php`

Creates prompt runs for the test user with configurable count and status.

Usage:

```bash
# Default (5 runs with mixed statuses)
./vendor/bin/sail artisan db:seed --class=TestPromptRunsSeeder

# With specific count
SEED_COUNT=15 ./vendor/bin/sail artisan db:seed --class=TestPromptRunsSeeder

# With specific status
SEED_COUNT=10 SEED_STATUS=completed ./vendor/bin/sail artisan db:seed --class=TestPromptRunsSeeder
```

Valid statuses: `completed`, `processing`, `failed`, `pending`

#### CleanPromptRunsSeeder

**File:** `/home/mark/repos/personality/database/seeders/CleanPromptRunsSeeder.php`

Removes all prompt runs for the test user to ensure clean state for empty state tests.

Usage:

```bash
./vendor/bin/sail artisan db:seed --class=CleanPromptRunsSeeder
```

### 4. Documentation

**File:** `/home/mark/repos/personality/tests-frontend/e2e/README-HISTORY-TESTS.md`

Comprehensive documentation covering:

- Test coverage details
- Test setup and prerequisites
- Database seeders usage
- Running the tests
- Best practices
- Maintenance notes

## Test Coverage Details

### Authentication & Access Control

- Redirects unauthenticated users to login
- Allows authenticated access to history page

### Empty State

- Shows appropriate message when no history exists
- Displays call-to-action to create first prompt
- Navigation works correctly from empty state

### Data Display

- Table shows all required columns (responsive)
- Status badges display correctly
- Dates formatted in British format (DD/MM/YYYY)
- Personality types shown (full names on desktop, codes on mobile)
- Task descriptions truncated at 80 characters
- Framework names or placeholders shown correctly

### Sorting

- Default sort by created date (descending)
- Sortable columns:
    - Task Description
    - Status
    - Personality Type
    - Framework
    - Created date
- Toggle between ascending/descending

### Pagination

- Shows pagination controls when needed
- Displays "Showing X to Y of Z results"
- Next/Previous navigation works
- Per-page selector (1-100 items)
- Mobile pagination controls
- State preserved on navigation

### Navigation

- Click row to view prompt details
- Keyboard navigation support (Enter key)
- "Create New" button navigation

### Responsive Design

- Desktop: All columns visible
- Mobile: Selected columns hidden
- Status badge positioning changes
- Per-page selector adapts
- Table remains usable on all screen sizes

### Edge Cases

- Different status types handled correctly
- Null framework values displayed as em-dash
- State maintained when using browser back button
- Multiple statuses can coexist in list

## Running the Tests

### Prerequisites

1. Ensure Laravel Sail is running
2. Application available at https://app.localhost
3. Database is migrated

### Seed Test Data

```bash
# Seed test user first
./vendor/bin/sail artisan db:seed --class=TestUserSeeder

# Seed prompt runs (optional - tests do this automatically)
SEED_COUNT=15 ./vendor/bin/sail artisan db:seed --class=TestPromptRunsSeeder
```

### Run Tests

```bash
# Run all history tests
npm run test:e2e tests-frontend/e2e/prompt-builder-history.e2e.ts

# Run specific test group
npm run test:e2e tests-frontend/e2e/prompt-builder-history.e2e.ts -g "Sorting"

# Run in headed mode (see browser)
npm run test:e2e tests-frontend/e2e/prompt-builder-history.e2e.ts -- --headed

# Run with debug
npm run test:e2e tests-frontend/e2e/prompt-builder-history.e2e.ts -- --debug
```

## Key Features

### Test Organisation

Tests are organised into logical describe blocks:

- Unauthenticated Access
- Empty State
- With Data
- Sorting
- Pagination
- Navigation
- Responsive Design
- Edge Cases

### Test Isolation

Each test group:

- Seeds required data in `beforeAll()`
- Authenticates user in `beforeEach()`
- Can run independently

### British English

All tests use British English:

- Test descriptions
- Assertions
- Date format expectations
- User-facing text expectations

### Accessibility

Tests use accessible selectors:

- `getByRole()` for semantic elements
- `getByLabel()` for form inputs
- `getByTestId()` for test-specific elements
- British date format (DD/MM/YYYY) expected

### Best Practices

- Explicit waits (networkidle) instead of arbitrary timeouts
- Meaningful test names describing expected behaviour
- Page Object Model pattern (via helpers)
- Responsive design testing at multiple viewport sizes
- Keyboard navigation testing

## Maintenance

### Adding New Tests

1. Add to appropriate describe block
2. Follow Arrange-Act-Assert pattern
3. Use British English
4. Include accessibility selectors
5. Test responsive behaviour where applicable

### Updating Seeders

If prompt run structure changes:

1. Update TestPromptRunsSeeder factory calls
2. Update status-specific updates
3. Test both empty and populated states

### Common Issues

- **Test user not found**: Run TestUserSeeder first
- **No data showing**: Check seeder ran successfully
- **Authentication fails**: Verify credentials in auth helper match TestUserSeeder
- **Pagination not visible**: Ensure enough data seeded (>10 items)

## Related Files

### Frontend

- `/home/mark/repos/personality/resources/js/Pages/PromptBuilder/History.vue` - History page component
- `/home/mark/repos/personality/resources/js/Components/StatusBadge.vue` - Status badge component
- `/home/mark/repos/personality/resources/js/Components/TableHeaderSortable.vue` - Sortable header component

### Backend

- `/home/mark/repos/personality/app/Http/Controllers/PromptBuilderController.php` - History endpoint
- `/home/mark/repos/personality/routes/web.php` - Route definition
- `/home/mark/repos/personality/tests/Feature/PromptBuilderHistoryTest.php` - Backend tests

### Test Infrastructure

- `/home/mark/repos/personality/playwright.config.ts` - Playwright configuration
- `/home/mark/repos/personality/tests-frontend/e2e/helpers/auth.ts` - Authentication helpers
- `/home/mark/repos/personality/database/factories/PromptRunFactory.php` - Prompt run factory
