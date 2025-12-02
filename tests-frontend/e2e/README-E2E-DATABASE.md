# E2E Test Database Setup

This project uses a separate PostgreSQL database for end-to-end (e2e) tests to avoid polluting the main development database.

## How It Works

1. **Separate Database**: E2E tests use `personality_e2e` database instead of `personality`
2. **Automatic Setup**: The global setup script (`global-setup.ts`) automatically:
   - Creates the test database if it doesn't exist
   - Runs fresh migrations before tests
3. **Environment Configuration**: Tests use `.env.e2e` file with `APP_ENV=e2e`

## Setup Instructions

### 1. Create .env.e2e File

Copy the provided `.env.e2e.example` or create a new `.env.e2e` file with:

```env
APP_ENV=e2e
DB_DATABASE=personality_e2e
# ... other settings from .env but optimised for testing
```

Key differences from `.env`:
- `APP_ENV=e2e` (not `local`)
- `DB_DATABASE=personality_e2e` (not `personality`)
- `BCRYPT_ROUNDS=4` (faster for tests)
- `CACHE_STORE=array` (in-memory)
- `QUEUE_CONNECTION=sync` (immediate execution)
- `BROADCAST_CONNECTION=null` (no WebSockets)
- `MAIL_MAILER=array` (no real emails)
- `LOG_LEVEL=error` (less noise)

### 2. Run E2E Tests

```bash
# The global setup will automatically create and migrate the test database
pnpm test:e2e

# Or with Playwright CLI
npx playwright test
```

### 3. Manual Database Management

If you need to manually interact with the test database:

```bash
# Access the test database
./vendor/bin/sail exec pgsql psql -U sail -d personality_e2e

# Run migrations manually
./vendor/bin/sail artisan migrate --env=e2e

# Fresh migrations
./vendor/bin/sail artisan migrate:fresh --env=e2e

# Seed test data
./vendor/bin/sail artisan db:seed --env=e2e

# Drop the test database
./vendor/bin/sail exec pgsql psql -U sail -d personality -c "DROP DATABASE personality_e2e;"
```

## Architecture

### Files
- `.env.e2e` - Environment configuration for e2e tests (gitignored)
- `tests-frontend/e2e/global-setup.ts` - Playwright global setup script
- `playwright.config.ts` - References the global setup

### Database Isolation

Each test run:
1. Global setup creates/migrates `personality_e2e` database
2. Tests run against the test database
3. Main `personality` database remains untouched

### Benefits

- **No data pollution**: Development data stays intact
- **Predictable state**: Fresh migrations ensure consistent test environment
- **Parallel-safe**: Can run tests while developing
- **Production-like**: Separate database mimics production environment

## Troubleshooting

### Database already exists error
```bash
# Drop and recreate
./vendor/bin/sail exec pgsql psql -U sail -d personality -c "DROP DATABASE IF EXISTS personality_e2e;"
```

### Migration errors
```bash
# Check which database you're on
./vendor/bin/sail artisan env
# Should show: e2e

# Run migrations manually
./vendor/bin/sail artisan migrate:fresh --env=e2e --force
```

### Tests connecting to wrong database
- Verify `.env.e2e` has `DB_DATABASE=personality_e2e`
- Ensure `APP_ENV=e2e` in `.env.e2e`
- Check Laravel is reading the correct env file

## CI/CD Integration

For CI pipelines, ensure:
1. `.env.e2e` is created with proper credentials
2. PostgreSQL service is available
3. Database creation permissions exist
4. Global setup runs before tests

Example GitHub Actions:
```yaml
- name: Create test database
  run: |
    cp .env.e2e.example .env.e2e
    php artisan key:generate --env=e2e

- name: Run E2E tests
  run: pnpm test:e2e
```
