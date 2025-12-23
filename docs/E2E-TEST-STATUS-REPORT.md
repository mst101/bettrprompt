# E2E Test Status Report

**Date:** 2 December 2025
**Database:** Now using separate `bettrprompt_e2e` database ✅
**Total Tests:** 234
**Passed:** 128 (54.7%)
**Failed:** 45 (19.2%)
**Skipped:** 61 (26.1%)

---

## Executive Summary

The E2E test suite has been successfully configured to use a separate test database (`bettrprompt_e2e`), which prevents
data pollution in the development database. However, there are **45 failing tests** across three main categories:

1. **Framework Selection Tests** (12 failures) - Personality type not being saved correctly
2. **Prompt Builder History Tests** (28 failures) - Tests requiring authentication and database state
3. **Static Page Navigation Tests** (3 failures) - Navigation links not working as expected
4. **Prompt Builder Journey Tests** (2 failures) - Full journey tests with authentication

---

## Test Results by Category

### ✅ Passing Tests (128)

- **Authentication Tests** (8/8) - All auth modal and routing tests passing
- **Feedback Tests** (15/27) - Basic form validation and display tests passing
- **Static Pages** (42/45) - Most static page content and accessibility tests passing
- **Framework Selection** (1 test) - Basic persistence verification passing

### ❌ Failing Tests (45)

#### 1. Framework Selection Tests (12 failures)

**Problem:** Personality type selection in profile page is not persisting correctly during tests.

**Symptoms:**

- Tests select a personality type (e.g., INTJ-A)
- After saving and reloading, the saved value is always ESTJ-A
- Tests retry 3 times but still fail
- Error message: `"Personality type not saved correctly after 3 attempts. Expected: INTJ, Got: ESTJ"`

**Affected Personality Types:**

- INTJ-A (Architect Assertive)
- INTP-A (Logician Assertive)
- ENTJ-A (Commander Assertive)
- ENTP-A (Debater Assertive)
- INFJ-A (Advocate Assertive)
- INFP-A (Mediator Assertive)
- ENFJ-A (Protagonist Assertive)
- ENFP-A (Campaigner Assertive)
- ISTJ-A (Logistician Assertive)
- ISFJ-A (Defender Assertive)
- ISFP-A (Adventurer Assertive)
- ISTP-A (Virtuoso Assertive)

**Only ESTJ-A passes**, suggesting either:

- Race condition in form submission
- Database transaction not committing before reload
- Frontend state not updating correctly
- Validation or save logic issue

**Test Location:** `tests-frontend/e2e/framework-selection.e2e.ts:61-209`

---

#### 2. Prompt Builder History Tests (28 failures)

**Problem:** Tests expect authenticated user to have prompt run history, but database is empty after fresh migrations.

**Symptoms:**

- Tests navigate to `/prompt-builder/history`
- Expect to find prompt runs in the database
- Tests fail because no data exists

**Root Cause:** Tests assume pre-existing data but the test database is freshly migrated (empty).

**Affected Tests:**

- Empty State tests (expecting heading)
- With Data tests (table display, columns, badges, dates, personality types, descriptions, frameworks)
- Sorting tests (date, status, personality, framework)
- Pagination tests (controls, navigation, per-page, mobile, validation)
- Navigation tests (row clicks, keyboard navigation)
- Responsive Design tests (mobile layout, badges, selectors, clickable rows)
- Edge Cases tests (different statuses, missing frameworks, state persistence)

**Test Location:** `tests-frontend/e2e/history.e2e.ts`

---

#### 3. Static Page Navigation Tests (3 failures)

**Problem:** Footer links and logo clicks are not navigating correctly.

**Failing Tests:**

1. **Terms → Privacy footer link**
    - Expected: Navigate to `/privacy`
    - Actual: Stays on `/terms`

2. **Cookies → Terms footer link**
    - Expected: Navigate to `/terms`
    - Actual: Stays on `/cookies`

3. **Terms → Home via logo**
    - Expected: Navigate to `/` (home)
    - Actual: Stays on `/terms`

**Test Location:** `tests-frontend/e2e/static-pages.e2e.ts:521-612`

---

#### 4. Prompt Builder Journey Tests (2 failures)

**Problem:** Full end-to-end journey tests failing, likely due to authentication or database state issues.

**Failing Tests:**

1. Should submit a prompt and navigate to show page
2. Should display task information on show page

**Test Location:** `tests-frontend/e2e/prompt-builder.e2e.ts:71, 547`

---

## Recommendations

### High Priority 🔴

#### 1. Fix Personality Type Persistence Issue

**Investigation Needed:**

- Check if personality type save endpoint is working in e2e environment
- Verify database transactions are committing before page reload
- Add debugging to see what's being saved vs. what's expected
- Check if there's a race condition between save and reload

**Suggested Actions:**

```typescript
// In tests: Add more explicit waits after save
await page.waitForResponse(resp =>
    resp.url().includes('/profile') && resp.status() === 200
);

// In backend: Check ProfileController.update() for transaction issues
// Ensure personality_type and trait_percentages are being saved to users table
```

**Files to Check:**

- `app/Http/Controllers/ProfileController.php`
- `resources/js/Pages/Profile/Partials/UpdatePersonalityTypeForm.vue`
- `tests-frontend/e2e/framework-selection.e2e.ts` (lines 148-210)

---

#### 2. Add Test Data Seeders for History Tests

**Problem:** Tests expect data but database is empty after fresh migrations.

**Solution:** Create a test-specific seeder that runs after migrations in global setup.

**Implementation:**

1. **Create E2E Test Seeder:**

```php
// database/seeders/E2eTestSeeder.php
class E2eTestSeeder extends Seeder
{
    public function run(): void
    {
        // Create test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'personality_type' => 'INTJ',
        ]);

        // Create prompt runs for history tests
        PromptRun::factory()->count(20)->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        // Create some with different statuses
        PromptRun::factory()->count(5)->create([
            'user_id' => $user->id,
            'status' => 'submitted',
        ]);
    }
}
```

2. **Update Global Setup:**

```typescript
// tests-frontend/e2e/global-setup.ts
execSync('./vendor/bin/sail artisan db:seed --env=e2e --class=E2eTestSeeder', {
    stdio: 'inherit',
});
```

3. **Alternative: Use beforeEach to seed data per test:**

```typescript
test.beforeEach(async ({ request }) => {
    // Call Laravel endpoint to seed test data
    await request.post('/api/test/seed-prompt-runs', {
        data: { count: 20, userId: testUserId }
    });
});
```

---

### Medium Priority 🟡

#### 3. Fix Static Page Navigation

**Problem:** Footer links and logo not navigating correctly.

**Investigation:**

- Check if footer component uses proper Inertia Link components
- Verify route definitions match expected paths
- Test manually in browser to see if issue is test-specific or real bug

**Files to Check:**

- Footer component (likely in `resources/js/Components/` or `resources/js/Layouts/`)
- Routes in `routes/web.php`
- Test assertions in `tests-frontend/e2e/static-pages.e2e.ts`

**Quick Fix:**
If links are using `<a href>` instead of Inertia `<Link>`, they may cause full page reloads. Update to use Inertia Link:

```vue
<!-- Wrong -->
<a href="/terms">Terms</a>

<!-- Correct -->
<Link href="/terms">Terms</Link>
```

---

#### 4. Fix Prompt Builder Journey Tests

**Dependencies:** These tests likely depend on:

1. Personality type persistence (High Priority #1)
2. Database seeding (High Priority #2)

**Action:** Fix High Priority issues first, then re-run these tests.

---

### Low Priority 🟢

#### 5. Review and Update Skipped Tests (61)

**Reason for Skipping:** Many tests were likely skipped due to:

- Authentication requirements
- Data dependencies
- Incomplete features
- Known issues

**Action:** After fixing high-priority issues:

1. Review each skipped test
2. Determine if it can be re-enabled
3. Update test descriptions if features have changed
4. Remove obsolete tests

---

## Test Environment Improvements

### ✅ Completed

- [x] Separate test database (`bettrprompt_e2e`)
- [x] Fresh migrations before each test run
- [x] Test environment configuration (`.env.e2e`)
- [x] Global setup script for database preparation

### 🔄 Recommended Next Steps

1. **Add Test Data Seeders** (High Priority)
    - Create factories for all models
    - Seed realistic test data
    - Document seed data structure

2. **Add Database Reset Between Tests**
    - Consider truncating tables between test files
    - Or use transactions that rollback after each test
    - Balance between test isolation and performance

3. **Improve Test Reliability**
    - Add more explicit waits for async operations
    - Use Playwright's built-in retry mechanisms
    - Add better error messages for debugging

4. **Add CI/CD Integration**
    - Set up GitHub Actions to run tests on PR
    - Generate test reports
    - Track test coverage over time

---

## How to Run Tests

### Run All Tests

```bash
pnpm test:e2e
# or
npx playwright test
```

### Run Specific Test File

```bash
npx playwright test framework-selection.e2e.ts
npx playwright test history.e2e.ts
npx playwright test static-pages.e2e.ts
```

### Run Tests with UI

```bash
npx playwright test --ui
```

### Debug Failing Test

```bash
npx playwright test --debug framework-selection.e2e.ts
```

### View Test Report

```bash
npx playwright show-report
```

---

## Database Management

### Manual Database Operations

```bash
# Connect to test database
./vendor/bin/sail exec pgsql psql -U sail -d bettrprompt_e2e

# Run migrations
./vendor/bin/sail artisan migrate --env=e2e

# Fresh migrations
./vendor/bin/sail artisan migrate:fresh --env=e2e

# Seed test data
./vendor/bin/sail artisan db:seed --env=e2e --class=E2eTestSeeder

# Drop test database
./vendor/bin/sail exec pgsql psql -U sail -d personality -c "DROP DATABASE bettrprompt_e2e;"
```

---

## Summary of Required Changes

### Immediate Actions (Before Next Test Run)

1. **Fix Personality Type Persistence**
    - [ ] Debug ProfileController.update() method
    - [ ] Check database transaction handling
    - [ ] Add better error handling and logging
    - [ ] Update tests with more explicit waits

2. **Create E2E Test Seeder**
    - [ ] Create `E2eTestSeeder.php` with test data
    - [ ] Update `global-setup.ts` to run seeder
    - [ ] Document test data structure

3. **Fix Static Page Navigation**
    - [ ] Check footer component implementation
    - [ ] Verify Inertia Link usage
    - [ ] Test navigation manually

### Follow-up Actions

4. **Review Prompt Builder Journey Tests**
    - After fixing #1 and #2, re-run and assess

5. **Review Skipped Tests**
    - Audit why tests are skipped
    - Re-enable or remove as appropriate

---

## Success Metrics

After implementing recommendations:

- **Target Pass Rate:** >95% (220+ passing tests)
- **Failed Tests:** <5% (10 or fewer)
- **Skipped Tests:** Minimal (only for valid reasons)
- **Test Reliability:** No flaky tests (100% pass rate on re-runs)

---

## Conclusion

The E2E test suite is now properly configured with a separate test database, which is excellent progress. The main
blockers are:

1. **Personality type persistence issue** - Needs investigation and fix
2. **Missing test data** - Needs E2E-specific seeder
3. **Minor navigation issues** - Quick fixes

Once these are addressed, the test suite should provide reliable coverage for the application's core functionality.
