# Framework Selection E2E Testing

This document explains the framework selection e2e tests that analyse which prompt framework is chosen for different personality types.

## Overview

Unlike typical e2e tests that clean up after themselves, these tests **intentionally persist data** to the database for analysis purposes. They create prompt runs for all 16 personality types (with both Assertive and Turbulent subtypes) to determine:

1. Which prompt framework is selected for each personality type
2. Whether framework selection is consistent across runs
3. How personality types influence AI decision-making

## Test User

A dedicated test user is created for these tests:

- **Name:** Test User
- **Email:** test@hiddengambia.com
- **Password:** voodoo90

This user can be seeded using:

```bash
./vendor/bin/sail artisan db:seed --class=TestUserSeeder
```

## Running the Tests

### Quick Verification Test

Run a single test to verify the mechanism works:

```bash
pnpm exec playwright test framework-selection.e2e.ts -g "should persist prompt run"
```

This creates one prompt run for INTJ-A personality type.

### Full Analysis Test Suite

Run tests for all 32 personality subtypes:

```bash
pnpm exec playwright test framework-selection.e2e.ts -g "should select framework"
```

**Warning:** This will:
- Create 32 prompt runs in the database
- Take significant time (each test waits up to 60 seconds for AI processing)
- Make 32 API calls to your n8n workflow

### Running Tests with Headed Browser

To see the tests in action:

```bash
pnpm exec playwright test framework-selection.e2e.ts --headed
```

## Test Configuration

### Test Task

All tests use the same task description to ensure consistency:

```
Help me create a comprehensive marketing strategy for a new SaaS product targeting small business owners
```

### Personality Configuration

Each test:
1. Logs in as the test user
2. Sets personality type (e.g., INTJ-A)
3. Sets all trait percentages to 50%
4. Submits the test task
5. Waits for framework selection (up to 60 seconds)
6. Logs results to console

### Trait Percentages

Currently set to 50% for all traits to establish a baseline. You can modify this in the test file to test different trait configurations.

## Analysing Results

### Via Browser

1. Log in as test user at https://app.localhost
2. Navigate to Prompt History
3. Review all created prompt runs
4. Check which framework was selected for each personality type

### Via Database

Query the database directly:

```sql
SELECT
    pr.id,
    pr.personality_type,
    pr.selected_framework,
    pr.framework_reasoning,
    pr.status,
    pr.created_at
FROM prompt_runs pr
JOIN users u ON pr.user_id = u.id
WHERE u.email = 'test@hiddengambia.com'
ORDER BY pr.created_at DESC;
```

### Via Console Output

Each test logs its results:

```
[INTJ-A] Framework selection test completed
  URL: https://app.localhost/prompt-optimizer/123
  Framework found: Yes
```

## Cleaning Up Test Data

To remove test data after analysis:

```sql
-- Delete all prompt runs for test user
DELETE FROM prompt_runs
WHERE user_id = (SELECT id FROM users WHERE email = 'test@hiddengambia.com');
```

Or delete the entire test user:

```sql
-- This will cascade delete all related prompt runs
DELETE FROM users WHERE email = 'test@hiddengambia.com';
```

Then re-seed if needed:

```bash
./vendor/bin/sail artisan db:seed --class=TestUserSeeder
```

## Understanding Results

### Framework Consistency

Compare multiple runs with the same personality type to check consistency:

```sql
SELECT
    personality_type,
    selected_framework,
    COUNT(*) as occurrences
FROM prompt_runs
WHERE user_id = (SELECT id FROM users WHERE email = 'test@hiddengambia.com')
  AND task_description LIKE '%comprehensive marketing strategy%'
GROUP BY personality_type, selected_framework
ORDER BY personality_type, occurrences DESC;
```

### Framework Distribution

See which frameworks are most commonly selected:

```sql
SELECT
    selected_framework,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM prompt_runs
        WHERE user_id = (SELECT id FROM users WHERE email = 'test@hiddengambia.com')), 2) as percentage
FROM prompt_runs
WHERE user_id = (SELECT id FROM users WHERE email = 'test@hiddengambia.com')
  AND selected_framework IS NOT NULL
GROUP BY selected_framework
ORDER BY count DESC;
```

### Personality Type Patterns

Analyse if certain personality traits correlate with framework selection:

```sql
-- Group by base personality type (without A/T suffix)
SELECT
    SUBSTRING(personality_type, 1, 4) as base_type,
    selected_framework,
    COUNT(*) as count
FROM prompt_runs
WHERE user_id = (SELECT id FROM users WHERE email = 'test@hiddengambia.com')
  AND selected_framework IS NOT NULL
GROUP BY base_type, selected_framework
ORDER BY base_type, count DESC;
```

## Customising Tests

### Different Task Descriptions

Modify `TEST_TASK` in `framework-selection.e2e.ts`:

```typescript
const TEST_TASK = 'Your custom task description here';
```

### Different Trait Percentages

Modify the trait percentage logic in the test:

```typescript
// Example: Set specific traits for each personality type
for (let i = 0; i < traitCount; i++) {
    await traitInputs.nth(i).fill('75'); // or any value 0-100
}
```

### Test Specific Personality Types

Run tests for specific types only:

```bash
pnpm exec playwright test framework-selection.e2e.ts -g "INTJ"
```

## Troubleshooting

### Test Times Out

If tests timeout waiting for framework selection:

1. Check n8n workflow is running
2. Check webhook connectivity
3. Increase `maxAttempts` in the test (currently 30 = 60 seconds)

### Authentication Fails

If login fails:

1. Ensure test user is seeded: `./vendor/bin/sail artisan db:seed --class=TestUserSeeder`
2. Check password matches in helper: `tests-frontend/e2e/helpers/auth.ts`

### Database Connection Issues

If seeding fails in tests:

1. Ensure database is running: `./vendor/bin/sail ps`
2. Check database connection in `.env`

## Future Enhancements

Potential improvements to these tests:

1. **Parallel Execution:** Run multiple personality types concurrently
2. **Multiple Task Variations:** Test same personality type with different tasks
3. **Trait Percentage Matrix:** Test different trait percentage combinations
4. **Statistical Analysis:** Generate reports on framework selection patterns
5. **Comparison Reports:** Compare framework selections between test runs over time
