# E2E Test Setup Documentation

This document explains how the end-to-end (E2E) tests work in this application, including database setup,
authentication, and the test-specific infrastructure.

## Table of Contents

1. [Overview](#overview)
2. [Test Database Setup](#test-database-setup)
3. [Authentication Flow](#authentication-flow)
4. [Test-Specific Infrastructure](#test-specific-infrastructure)
5. [Running Tests](#running-tests)
6. [Troubleshooting](#troubleshooting)

## Overview

The E2E tests use Playwright to run browser-based tests against the application. To ensure tests are isolated and don't
affect development data, we use:

- A **separate test database** (`bettrprompt_e2e`)
- **Test-specific middleware** to detect and handle test requests
- A **test-only authentication endpoint** to bypass the login modal
- **Global setup** to prepare the database before tests run

## Test Database Setup

### Database Isolation

We maintain two separate PostgreSQL databases:

- **`personality`** - Development database (for local development)
- **`bettrprompt_e2e`** - Test database (for E2E tests only)

This separation ensures:

- Tests don't pollute development data
- Development work doesn't interfere with test runs
- Tests can be run repeatedly with predictable state

### Configuration Files

#### `.env.e2e`

Located at the project root, this file configures Laravel to use the test database:

```bash
APP_ENV=e2e
DB_DATABASE=bettrprompt_e2e
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
# ... other test-specific settings
```

Key differences from `.env`:

- `APP_ENV=e2e` - Identifies this as the test environment
- `DB_DATABASE=bettrprompt_e2e` - Points to the test database
- `CACHE_STORE=array` - Uses in-memory cache (no Redis dependency)
- `QUEUE_CONNECTION=sync` - Processes queues synchronously
- `MAIL_MAILER=array` - Doesn't send actual emails

### Global Setup Process

Before any tests run, Playwright executes the global setup script:

**File:** `tests-frontend/e2e/global-setup.ts`

**What it does:**

1. **Creates the test database** (if it doesn't exist)
   ```bash
   CREATE DATABASE bettrprompt_e2e
   ```

2. **Runs all migrations** on the test database
   ```bash
   php artisan migrate:fresh --env=e2e
   ```

3. **Seeds initial test data** via `E2eTestSeeder`
   ```bash
   php artisan db:seed --class=E2eTestSeeder --env=e2e
   ```

**When it runs:** Once at the start of the entire test suite, not before each test.

### Test Data Seeding

#### E2eTestSeeder (Global)

**File:** `database/seeders/E2eTestSeeder.php`

**Runs:** Once during global setup

**Creates:**

- Test user account (`test@example.com` / `password`)
- 25 varied prompt runs with different:
    - Statuses (submitted, framework_selected, completed, failed)
    - Personality types (INTJ-A, ENTJ-A, etc.)
    - Frameworks (STAR Method, PSB, FAB, etc.)
    - All with `task_classification` field set for controller filtering

**Example test user:**

```php
User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => Hash::make('password'),
    'personality_type' => 'INTJ-A',
]);
```

#### TestPromptRunsSeeder (Per-Test)

**File:** `database/seeders/TestPromptRunsSeeder.php`

**Runs:** When tests call `seedPromptRuns(count, status?)` helper

**Purpose:** Add additional test data for specific test scenarios

**Usage:**

```typescript
// In a test file
await seedPromptRuns(15); // Create 15 prompt runs with mixed statuses
await seedPromptRuns(10, 'completed'); // Create 10 completed prompt runs
```

**Important:** This seeder uses the E2E database via:

```bash
./vendor/bin/sail bash -c "SEED_COUNT=15 php artisan db:seed --class=TestPromptRunsSeeder --env=e2e"
```

The `bash -c` wrapper ensures environment variables reach the Docker container.

### Database Lifecycle

```
Test Run Start
    ↓
Global Setup (once)
    ├─ Drop all tables in bettrprompt_e2e
    ├─ Run migrations
    └─ Seed with E2eTestSeeder (25 prompt runs)
    ↓
Test Suite Execution
    ├─ Test 1
    │   ├─ beforeAll: seedPromptRuns(15) (optional)
    │   ├─ beforeEach: loginAsTestUser()
    │   └─ test assertions
    ├─ Test 2
    │   └─ ...
    └─ Test N
    ↓
Test Run End
```

**Key Points:**

- Database is **reset once** at the start (not between tests)
- Tests **share the same database state** (accumulated data)
- Each test group can add more data via `seedPromptRuns()`
- Tests should be written to handle existing data

## Authentication Flow

### The Challenge

The application uses a Vue modal for login, which has issues with Playwright automation:

- Modal inputs don't accept programmatic input reliably
- Vue reactivity causes input focus issues
- Modal state management interferes with automated testing

### The Solution: Test-Only Login Endpoint

We created a special endpoint that bypasses the modal entirely.

#### Test Login Endpoint

**File:** `routes/web.php`

**Route:** `POST /test/login`

**Security:** Protected by `X-Test-Auth` header

```php
Route::post('/test/login', function (Request $request) {
    // Only allow if request has the special header
    if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
        abort(403, 'Unauthorized');
    }

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    Auth::login($user);

    return response()->json(['success' => true]);
});
```

**Why it's safe:**

- Requires exact header match: `X-Test-Auth: playwright-e2e-tests`
- Only accessible in test environment
- Protected from CSRF (exempted in `bootstrap/app.php`)

### Authentication Helper

**File:** `tests-frontend/e2e/helpers/auth.ts`

**Function:** `loginAsTestUser(page: Page)`

**Process:**

1. **Accept cookies** (prevent cookie banner from blocking interactions)
   ```typescript
   await acceptCookies(page);
   ```

2. **Check if already logged in** (via user menu visibility)
   ```typescript
   const isAlreadyLoggedIn = await userMenu.isVisible();
   if (isAlreadyLoggedIn) return;
   ```

3. **Call test login endpoint** via browser's fetch API
   ```typescript
   await page.evaluate(async (email: string) => {
       const csrfToken = document.querySelector('meta[name="csrf-token"]')
           ?.getAttribute('content');

       await fetch('/test/login', {
           method: 'POST',
           headers: {
               'Content-Type': 'application/json',
               'X-CSRF-TOKEN': csrfToken || '',
               'X-Test-Auth': 'playwright-e2e-tests',
           },
           body: JSON.stringify({ email }),
           credentials: 'include', // Important: ensures cookies are set
       });
   }, TEST_USER.email);
   ```

4. **Navigate to home page** to trigger Inertia reload
   ```typescript
   await page.goto('/');
   ```

5. **Verify login** by checking user menu visibility

**Why use `page.evaluate()`?**

Using the browser's native `fetch()` API with `credentials: 'include'` ensures:

- Session cookies are properly set in the browser context
- Cookies persist across subsequent page navigations
- Laravel's session management works correctly

## Test-Specific Infrastructure

### UseE2eDatabase Middleware

**File:** `app/Http/Middleware/UseE2eDatabase.php`

**Purpose:** Automatically switch to the test database when Playwright makes requests

**How it works:**

1. **Detects test requests** via `X-Test-Auth` header
2. **Switches database configuration** to `bettrprompt_e2e`
3. **Logs the switch** for debugging

```php
public function handle(Request $request, Closure $next): Response
{
    if ($request->header('X-Test-Auth') === 'playwright-e2e-tests') {
        // Switch to E2E database
        Config::set('database.connections.pgsql.database', 'bettrprompt_e2e');

        // Reconnect to apply the new database setting
        app('db')->purge('pgsql');

        Log::info('UseE2eDatabase middleware: Switched to bettrprompt_e2e database', [
            'url' => $request->url(),
        ]);
    }

    return $next($request);
}
```

**Registered globally** in `bootstrap/app.php`:

```php
$middleware->append(\App\Http\Middleware\UseE2eDatabase::class);
```

**Why this is needed:**

Without this middleware:

- Laravel server uses `DB_DATABASE=bettrprompt` (from `.env`)
- Tests create data in `bettrprompt_e2e`
- Requests from Playwright would query the wrong database
- No data would be found, tests would fail

With this middleware:

- All requests with `X-Test-Auth` header use `bettrprompt_e2e`
- Tests and the application see the same data
- Database isolation is maintained

### Request Header Injection

**File:** `tests-frontend/e2e/helpers/auth.ts`

**Function:** `acceptCookies(page: Page)`

All test requests include the special header via route interception:

```typescript
await page.route('**/*', async (route) => {
    const headers = {
        ...route.request().headers(),
        'X-Test-Auth': 'playwright-e2e-tests', // Add to all requests
    };
    await route.continue({ headers });
});
```

This ensures:

- Every HTTP request from the test browser includes the header
- Middleware detects all test requests
- Database switching happens automatically

### CSRF Protection Exemption

**File:** `bootstrap/app.php`

The test login endpoint is exempted from CSRF protection:

```php
$middleware->validateCsrfTokens(except: [
    'test/login',
]);
```

**Why?**

- The endpoint is protected by the `X-Test-Auth` header instead
- CSRF tokens can be problematic in test contexts
- Header-based authentication is simpler for automation

## Running Tests

### Running All Tests

```bash
npx playwright test
```

This will:

1. Run global setup (create database, migrate, seed)
2. Execute all test files in parallel
3. Generate an HTML report

### Running Specific Tests

**Single test file:**

```bash
npx playwright test tests-frontend/e2e/history.e2e.ts
```

**Single test by name:**

```bash
npx playwright test --grep "should display history table"
```

**Run in headed mode (see browser):**

```bash
npx playwright test --headed
```

**Run in debug mode (step through):**

```bash
npx playwright test --debug
```

### Running via Playwright UI

```bash
npx playwright test --ui
```

This opens the Playwright UI where you can:

- Select individual tests to run
- Watch tests execute in real-time
- Debug failing tests
- View screenshots and videos

### Test Configuration

**File:** `playwright.config.ts`

Key settings:

- `globalSetup: './tests-frontend/e2e/global-setup.ts'` - Runs before all tests
- `use.baseURL: 'https://app.localhost'` - Test against local server
- `workers: 1` - Run tests sequentially (important for shared database)

## Troubleshooting

### Test Data Not Appearing

**Symptom:** Tests show "No data found" or empty states

**Possible causes:**

1. **Missing `task_classification` field**
    - The history controller filters: `whereNotNull('task_classification')`
    - Ensure seeders set this field:
      ```php
      'task_classification' => ['type' => 'prompt_builder', 'source' => 'web']
      ```

2. **Wrong database**
    - Check logs for "Switched to bettrprompt_e2e database"
    - Verify `X-Test-Auth` header is being sent
    - Restart Laravel server after middleware changes

3. **Seeder not running**
    - Check global setup output for "Created 25 prompt runs"
    - Verify `.env.e2e` has correct database name

### Authentication Failures

**Symptom:** Tests redirect to login page or show "Unauthenticated"

**Solutions:**

1. **Check session cookies**
    - Ensure `credentials: 'include'` in fetch call
    - Verify `page.evaluate()` is used (not `page.request`)

2. **Verify test user exists**
    - Check database: `SELECT * FROM users WHERE email = 'test@example.com'`
    - Ensure E2eTestSeeder ran successfully

3. **Check middleware**
    - Laravel server must be restarted after middleware changes
    - Run: `php artisan config:cache && php artisan route:cache`

### JavaScript Errors

**Symptom:** Page loads but content is blank

**Check browser console:**

Add to test:

```typescript
page.on('console', msg => console.log(msg.text()));
page.on('pageerror', error => console.log(error.message));
```

Common issues:

- Null reference errors (use optional chaining: `obj?.prop`)
- Missing props from Inertia
- Vue component rendering errors

### Database Connection Issues

**Symptom:** "Database does not exist" or connection errors

**Solutions:**

1. **Create database manually:**
   ```bash
   ./vendor/bin/sail exec pgsql psql -U sail -c "CREATE DATABASE bettrprompt_e2e;"
   ```

2. **Reset database:**
   ```bash
   ./vendor/bin/sail artisan migrate:fresh --env=e2e --force
   ./vendor/bin/sail artisan db:seed --class=E2eTestSeeder --env=e2e --force
   ```

3. **Check Docker containers:**
   ```bash
   ./vendor/bin/sail ps
   ```

### Seeder Writing to Wrong Database

**Symptom:** Running `sail artisan db:seed --class=E2eTestSeeder --env=e2e` creates data in `personality` instead of
`bettrprompt_e2e`

**Cause:** Laravel's configuration cache prevents the `--env=e2e` flag from loading the correct database configuration
from `.env.e2e`

**Solution:** Clear the configuration cache first:

```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan db:seed --class=E2eTestSeeder --env=e2e --force
```

**Verification:** Check the seeder output - it should show "Seeding using connection 'pgsql' (bettrprompt_e2e)" not "(
personality)"

### Environment Variable Issues

**Symptom:** `SEED_COUNT` or other env vars not respected

**Cause:** Environment variables don't propagate into Docker containers

**Solution:** Use `bash -c` wrapper:

```typescript
await execAsync(
    `./vendor/bin/sail bash -c "SEED_COUNT=15 php artisan db:seed --class=TestPromptRunsSeeder --env=e2e"`
);
```

## Best Practices

### Writing Tests

1. **Use the shared database state**
    - Tests run sequentially and share data
    - Don't assume database is empty
    - Use assertions that work with existing data

2. **Add test-specific data when needed**
   ```typescript
   test.beforeAll(async () => {
       await seedPromptRuns(10, 'completed');
   });
   ```

3. **Always log in before testing authenticated pages**
   ```typescript
   test.beforeEach(async ({ page }) => {
       await loginAsTestUser(page);
   });
   ```

4. **Wait for network idle**
   ```typescript
   await page.goto('/some-page');
   await page.waitForLoadState('networkidle');
   ```

### Debugging Tips

1. **Use headed mode** to watch tests
   ```bash
   npx playwright test --headed --debug
   ```

2. **Add screenshots** on failure (already configured)
    - Check `tests-frontend/e2e/results/` for screenshots

3. **Log Inertia props**
   ```typescript
   const props = await page.evaluate(() => (window as any).$page);
   console.log('Props:', props);
   ```

4. **Check Laravel logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     Playwright Test                          │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Test Browser (Chromium)                             │  │
│  │  - Cookies: cookie_consent, session                  │  │
│  │  - Headers: X-Test-Auth: playwright-e2e-tests       │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────┬───────────────────────────────────┘
                          │ HTTPS Request
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                   Laravel Application                        │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  UseE2eDatabase Middleware                           │  │
│  │  - Detects: X-Test-Auth header                       │  │
│  │  - Switches: personality → bettrprompt_e2e           │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Controllers / Routes                                 │  │
│  │  - /test/login (test-only)                           │  │
│  │  - /history                           │  │
│  │  - Other application routes                          │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────┬───────────────────────────────────┘
                          │ Database Query
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                    PostgreSQL                                │
│  ┌─────────────────────┐  ┌─────────────────────────────┐  │
│  │  personality        │  │  bettrprompt_e2e             │  │
│  │  (Development)      │  │  (Tests)                     │  │
│  │                     │  │                              │  │
│  │  - Real user data   │  │  - Test user                 │  │
│  │  - Dev testing      │  │  - 25+ prompt runs           │  │
│  │                     │  │  - Isolated test data        │  │
│  └─────────────────────┘  └─────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

## Summary

The E2E test setup provides:

✅ **Isolation** - Separate test database prevents data pollution
✅ **Automation** - Global setup creates database and seeds data automatically
✅ **Reliability** - Test-only login endpoint bypasses modal issues
✅ **Simplicity** - Middleware automatically switches databases
✅ **Debugging** - Comprehensive logging and error handling

The key insight: Use a **separate database + special header + middleware** to automatically route test requests to test
data while keeping development data safe.
