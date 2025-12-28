# CLAUDE.md

## Project Overview

BettrPrompt creates personality-calibrated AI prompts using 16personalities.com framework. Users complete personality assessments, then receive prompts optimised for their type via a 3-stage n8n workflow system.

**Tech Stack:** Laravel 12, Inertia.js, Vue 3 + TypeScript, PostgreSQL, Redis, n8n, Tailwind CSS v4, Pest, pnpm, Laravel Sail (Docker)

## Core Domain: 3-Stage Workflow

Prompt generation follows these stages (stored in `workflow_stage` column):

**Workflow 0 (Pre-analysis):** `0_processing` → `0_completed` / `0_failed`
**Workflow 1 (Main analysis):** `1_processing` → `1_completed` / `1_failed`
**Workflow 2 (Prompt generation):** `2_processing` → `2_completed` / `2_failed`

- Only `2_completed` means fully successful
- Use `PromptRun` helper methods: `isProcessing()`, `isPending()`, `isCompleted()`, `isFailed()`
- See `docs/workflow_stages.md` for full lifecycle details

## Naming Conventions (Critical)

### Frontend camelCase ↔ Backend snake_case

- **Vue/TypeScript:** camelCase for all variables/props (`uiComplexity`, `personalityType`)
- **Database:** snake_case columns (`ui_complexity`, `personality_type`)
- **Resources:** Transform camelCase → snake_case when serialising
- **Form Requests:** Validate snake_case, transform to camelCase for TypeScript

```typescript
// Vue
const form = reactive({ uiComplexity: 'advanced' });

// Resource
'uiComplexity' => $this->ui_complexity,

// Database
$table->string('ui_complexity');
```

### HTML Attributes: kebab-case

All `id` and `data-testid` attributes MUST use kebab-case:

```html
<!-- ✓ CORRECT -->
<input id="user-name" data-testid="submit-button" />

<!-- ✗ WRONG -->
<input id="userName" data-testid="submitButton" />
```

## Development Commands

```bash
# Start environment
./vendor/bin/sail up -d
composer dev  # Runs: serve, horizon, reverb, pail, vite

# Testing (ALWAYS use Sail for consistency with Docker environment)
./vendor/bin/sail test
./vendor/bin/sail test tests/Feature/ExampleTest.php

# Frontend
pnpm dev          # Vite dev server (included in composer dev)
pnpm build        # Production build with SSR
pnpm lint         # ESLint + Prettier
pnpm test:unit    # Vitest
pnpm test:e2e     # Playwright

# Code style
./vendor/bin/pint

# Initial setup
composer setup    # Install deps, setup .env, migrate, build assets
```

**CRITICAL:** Always use `./vendor/bin/sail` for Laravel commands/tests. Using `php artisan` directly may fail or produce different results due to Docker environment (PostgreSQL, Redis, etc.).

## Key Services

- `N8nClient` - Triggers n8n workflows via webhooks
- `PersonalityTypeService` - Personality calculations and type determination
- `PromptFrameworkService` - Framework selection and prompt generation
- `GeolocationService` - MaxMind geolocation for users

## Testing Patterns

- **Always test:** New controllers, form requests, services, n8n endpoints
- **Use factories:** `User::factory()->create()`, `PromptRun::factory()->...`
- **Mock n8n:** `Http::fake(['n8n.localhost/*' => ...])`
- Test validation: Submit forms with missing/invalid data

## Architecture Notes

- **Frontend:** Inertia.js pages in `resources/js/Pages/`, SSR enabled
- **Backend:** Services in `app/Services/`, n8n webhook receiver in `routes/api.php`
- **n8n Integration:** Secured with `X-N8N-SECRET` header verification
- **Dev proxy:** Caddy (dev-only) serves `app.localhost` and `n8n.localhost` (production uses Nginx)

## Common Workflows

**Adding a profile field:**
1. Migration (snake_case column)
2. Add to `User` model `$fillable`
3. Create Form Request (validate snake_case, transform to camelCase)
4. Add to `UserResource` (transform camelCase → snake_case)
5. Update Vue component (use camelCase)
6. Write tests

**n8n integration:**
1. Create workflow in n8n dashboard (`n8n.localhost`)
2. Add webhook URL to `config/services.php`
3. Update `N8nClient` with new method
4. Test with `Http::fake()` in tests

**Docs:** `docs/workflow_stages.md` (workflows), `docs/n8n_integrations.md` (n8n setup), `docs/caddy-https-setup.md` (HTTPS certificates), `docs/deployment/` (production), `docs/E2E-TEST-SETUP.md` (e2e testing)
