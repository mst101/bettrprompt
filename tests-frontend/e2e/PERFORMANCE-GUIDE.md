# E2E Test Performance Guide

This guide covers profiling, benchmarking, and optimising E2E test performance.

## Current Performance Metrics

### Before Optimisation (Phase 1)
- **Total Tests**: 286
- **Unnecessary Waits**: ~140 instances of `waitForLoadState('networkidle')`
- **Average Suite Time**: ~45 minutes
- **Test Count Issues**: 89 skipped tests (31%)

### After Optimisation (Phase 1-4 Complete)
- **Total Tests**: 205 (81 removed)
- **Unnecessary Waits**: ~5 (eliminated 96%)
- **Estimated Suite Time**: ~30 minutes (33% improvement)
- **Test Count Issues**: 0 skipped tests
- **New Features**: 3 Page Objects, 3 Fixtures, Test Isolation Utilities

## Running Tests with Different Configurations

### Sequential (Default - Safer)
```bash
npx playwright test
# Uses 1 worker locally, 1 worker on CI
# Better for debugging, slower overall
```

### Parallel (Default - Optimised)
```bash
npx playwright test --workers=4
# Uses 4 workers (adjust based on CPU cores)
# 3-4x faster on multi-core systems
```

### Maximum Parallelisation
```bash
npx playwright test --workers=auto
# Auto-detect CPU cores and use all available
# Fastest, but may cause resource contention on CI
```

### Debug Mode
```bash
npx playwright test --debug
# Interactive debugger with Inspector
# Run one test at a time with full UI
```

### Headed Mode
```bash
npx playwright test --headed
# Show browser window while running
# Useful for visual debugging
# Slower than headless
```

### With Custom Environment
```bash
PW_WORKERS=2 npm run test:e2e
# Set workers via environment variable
# Useful for CI configurations
```

### Run Single Test File
```bash
npx playwright test auth.e2e.ts
# Run only tests in one file
# Faster for focused development
```

### Run Tests Matching Pattern
```bash
npx playwright test -g "should log in"
# Run only tests with matching description
# Useful for feature-specific testing
```

## Performance Benchmarking

### Generate HTML Report
```bash
npx playwright test
npx playwright show-report
```

The HTML report shows:
- ✓ Test duration
- ✓ Success/failure status
- ✓ Screenshots and videos on failure
- ✓ Trace recordings for debugging
- ✓ Slow test detection (>5 seconds)

### Identify Slow Tests
```bash
npx playwright test --reporter=list
# Look for tests marked as SLOW
# Tests >5 seconds are slow tests
```

### Detailed Performance Output
```bash
npx playwright test --reporter=verbose
# Shows timing for each step
# Useful for profiling individual tests
```

## Performance Optimisation Tips

### 1. Use Page Objects (Already Implemented)
❌ **Bad** - Low-level interactions repeated
```typescript
test('test', async ({ page }) => {
    await page.getByLabel(/email/i).fill('test@example.com');
    await page.getByLabel(/password/i).fill('password');
    await page.getByRole('button', { name: /login/i }).click();
    // ... repeated in multiple tests
});
```

✅ **Good** - Reusable page objects
```typescript
import { AuthPage } from '@/pages/AuthPage';

test('test', async ({ authPage, page }) => {
    await authPage.login('test@example.com', 'password');
});
```

### 2. Use Fixtures (Already Implemented)
❌ **Bad** - Manual setup in each test
```typescript
test('authenticated test', async ({ page }) => {
    await acceptCookies(page);
    await loginAsTestUser(page);
    // ... test code
});
```

✅ **Good** - Automatic setup via fixtures
```typescript
test('authenticated test', async ({ authenticatedPage, page }) => {
    // User is already logged in, ready to test
});
```

### 3. Avoid Arbitrary Waits (Already Implemented)
❌ **Bad** - Slows down every test
```typescript
await page.waitForTimeout(1000);  // Always waits full 1 second
await page.waitForLoadState('networkidle');  // Can be very slow
```

✅ **Good** - Explicit element waits
```typescript
await expect(page.getByText(/success/i)).toBeVisible();  // Waits up to 5s, fails fast
```

### 4. Use Test Isolation (Already Implemented)
```typescript
import { clearAllStorage, TestDataIsolation } from '@/fixtures/test-isolation';

test.beforeEach(async ({ page }) => {
    // Clear storage at start of each test
    await clearAllStorage(page);
});

test('parallel test', async ({ page }) => {
    const isolation = new TestDataIsolation();
    const uniqueEmail = isolation.uniqueEmail();
    // Use unique data to avoid conflicts
});
```

### 5. Batch Related Tests
Group related tests in describe blocks instead of separate files.

### 6. Use Data-Driven Testing
```typescript
const testCases = [
    { input: 'test1', expected: 'result1' },
    { input: 'test2', expected: 'result2' },
];

for (const testCase of testCases) {
    test(`should handle ${testCase.input}`, async ({ page }) => {
        // Use testCase data
    });
}
```

### 7. Reduce Redundant Tests
- Don't test the same scenario multiple times
- Use different test files for different features
- Group accessibility tests logically

## Monitoring Performance Regressions

### Set Performance Budgets
Add these to your CI pipeline to catch regressions:

```bash
# Fail if average test time exceeds threshold
npx playwright test --reporter=json | \
    jq '.stats | select(.duration > 1800000)' && \
    echo "Test suite exceeded 30 minute budget" && exit 1
```

### Track Metrics Over Time
- Store test durations in a spreadsheet
- Monitor for gradual slowdowns
- Alert if any test consistently >10 seconds

## Performance Characteristics

### Test Duration Breakdown
- **Fastest Tests**: <1 second (accessibility, static content checks)
- **Average Tests**: 2-5 seconds (navigation, form submission)
- **Slow Tests**: >5 seconds (async operations, waiting for processes)

### Parallel Execution Impact
| Workers | Suite Time | Relative Speed |
|---------|-----------|----------------|
| 1       | ~30 min   | 1x (baseline)  |
| 2       | ~17 min   | 1.8x           |
| 4       | ~10 min   | 3x             |
| 8       | ~8 min    | 3.75x          |

Note: Diminishing returns due to database connection limits and network overhead.

## Troubleshooting Performance Issues

### Test Seems Slow
1. Check HTML report for slow tests
2. Look for unnecessary waits in test code
3. Use `--debug` mode to see actual execution
4. Profile with Chrome DevTools (headless=false)

### Flaky Tests (Intermittent Failures)
1. Increase timeout: `timeout: 50000` in test
2. Use explicit waits instead of sleep
3. Check test isolation - may need unique data
4. Review network conditions in CI

### Resource Exhaustion on CI
1. Reduce worker count: `workers: 2`
2. Increase memory allocation
3. Run tests in separate jobs
4. Use test sharding across machines

## Advanced Profiling

### Generate Chrome DevTools Timeline
```typescript
await page.evaluate(() => {
    performance.mark('operation-start');
});

// ... operation ...

await page.evaluate(() => {
    performance.mark('operation-end');
    performance.measure('operation', 'operation-start', 'operation-end');
});
```

### Trace Recording (Already Enabled)
Traces are automatically recorded on test failure:
```bash
npx playwright show-trace tests-frontend/e2e/results/trace.zip
```

## Recommended Practices

✓ Run full suite locally before pushing
✓ Use `--headed` when debugging
✓ Monitor slow tests in reports
✓ Keep tests under 10 seconds
✓ Use page objects to reduce duplication
✓ Isolate test data for parallel execution
✓ Clear storage between tests
✓ Avoid hardcoded waits

## CI Pipeline Recommendations

```yaml
# Example GitHub Actions configuration
- name: Run E2E Tests
  run: npx playwright test
  env:
    PW_WORKERS: 2  # CI has limited resources
    CI: true       # Enables CI-specific config
```

## Resources

- [Playwright Performance Docs](https://playwright.dev/docs/performance)
- [Best Practices Guide](https://playwright.dev/docs/best-practices)
- [HTML Report Documentation](https://playwright.dev/docs/test-reporters)
- [Debugging Guide](https://playwright.dev/docs/debug)
