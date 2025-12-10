# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Inertia.js with Vue 3 + TypeScript
- **Database**: PostgreSQL
- **Cache/Queue**: Redis
- **Queue Manager**: Laravel Horizon
- **WebSockets**: Laravel Reverb
- **Workflow Automation**: n8n
- **Styling**: Tailwind CSS v4
- **Testing**: Pest (PHPUnit)
- **Dev Environment**: Laravel Sail (Docker)
- **Reverse Proxy**: Caddy

## Development Conventions

### Language and Style Guidelines

- Always use British English! Only use American English when absolutely necessary i.e. Third-party API endpoints and
  integrations, CSS framework classes (Tailwind CSS), HTML attributes, External library method names.

### Naming Convention: Frontend camelCase ↔ Backend snake_case

**Critical Rule**: All client-side (Vue/TypeScript) variables and properties MUST use camelCase.

**Implementation Pattern**:
1. Frontend Vue components use camelCase for all variables, props, and data fields
2. Laravel Resources (e.g., `PromptRunResource`) convert frontend camelCase properties to snake_case for serialization
3. Form Request classes validate and transform snake_case request bodies to camelCase for TypeScript types
4. Database columns remain in snake_case (Laravel convention)

**Example**:
```typescript
// Frontend (Vue) - camelCase
const form = reactive({
  uiComplexity: 'advanced',
  personalityType: 'INTJ',
  traitPercentages: { ... }
});

// Laravel Resource - converts to snake_case
'uiComplexity' => $this->ui_complexity,
'personalityType' => $this->personality_type,
'traitPercentages' => $this->trait_percentages,

// Database - snake_case
Schema::table('users', fn(Blueprint $table) => {
  $table->string('ui_complexity')->default('advanced');
  $table->string('personality_type')->nullable();
  $table->json('trait_percentages')->nullable();
});
```

**Forms Using This Pattern**:
- `UpdateUiComplexityForm` - ui_complexity
- `UpdateLocationForm` - country, state, city, zipcode
- `UpdateProfessionalForm` - occupation, industry, workEnvironment
- `UpdateTeamForm` - teamSize, teamType, teamComposition
- `UpdateBudgetForm` - monthlyBudget, budgetCurrency
- `UpdateToolsForm` - preferredTools, toolCategories

### HTML Attributes: kebab-case for `id` and `data-testid`

**Critical Rule**: All HTML `id` and `data-testid` attributes MUST use kebab-case (lowercase with hyphens).

**Why**: HTML standards recommend kebab-case for attribute values. It provides:
- Consistency with web standards
- Better CSS selector compatibility
- Easier reading in templates and tests
- Avoiding confusion with JavaScript camelCase

**Examples**:
```html
<!-- ✓ CORRECT - kebab-case -->
<input id="user-name" />
<button id="submit-button" />
<div data-testid="task-tab" />
<form id="profile-update-form" />

<!-- ✗ WRONG - camelCase or snake_case -->
<input id="userName" />          <!-- Don't use camelCase -->
<button id="submitButton" />     <!-- Don't use camelCase -->
<div data-testid="taskTab" />    <!-- Don't use camelCase -->
<form id="profile_update_form" /><!-- Don't use snake_case -->
```

**Components Affected**:
- All Vue components: `<input id="kebab-case" />`
- Form components: `FormInput`, `FormSelect`, `FormCheckbox`, etc.
- Test IDs: `data-testid="kebab-case-name"`
- Layout components: All header, container, and page elements

## Development Commands

### Starting the Development Environment

```bash
# Start Docker containers (PostgreSQL, Redis, n8n)
./vendor/bin/sail up -d

# Run full development stack (Laravel server, queue worker, logs, Vite)
composer dev
```

The `composer dev` command runs these processes concurrently:

- `php artisan serve` - Laravel development server
- `php artisan horizon` - Queue manager with dashboard (visit `/horizon`)
- `php artisan reverb:start` - WebSocket server for real-time updates
- `php artisan pail --timeout=0` - Log viewer
- `npm run dev` - Vite dev server with HMR

### Building and Testing

```bash
# Run Pest tests (always use Sail for consistency with Docker environment)
./vendor/bin/sail test
# or to run tests directly (not recommended - use sail for consistency)
php artisan test

# Run specific test file
./vendor/bin/sail test tests/Feature/ExampleTest.php

# Build for production (includes SSR build)
npm run build

# Lint TypeScript/Vue files
npm run lint

# Code style (Laravel Pint)
./vendor/bin/pint
```

**IMPORTANT**: Always use `./vendor/bin/sail` for running tests and Laravel commands to ensure consistency with the Docker environment (PostgreSQL, Redis, etc.). Using `php artisan` directly may fail or produce different results.

### Initial Setup

```bash
composer setup
```

This runs: composer install, creates .env, generates key, runs migrations, npm install, and builds assets.

## Architecture Overview

### Frontend (Inertia.js + Vue)

- **Entry Point**: `resources/js/app.ts`
- **SSR Entry**: `resources/js/ssr.ts`
- **Pages**: Located in `resources/js/Pages/` - Inertia automatically resolves these
- **Layouts**: `resources/js/Layouts/` (AuthenticatedLayout, GuestLayout)
- **Routing**: Uses Ziggy for Laravel route helpers in Vue components

The application uses Inertia.js to create a single-page app experience whilst maintaining Laravel's routing and
controllers. SSR is enabled for improved performance and SEO.

### Backend (Laravel)

- **Services**: Custom service classes in `app/Services/`
    - `N8nClient` - Handles communication with n8n workflows via webhooks
- **Routes**:
    - `routes/web.php` - Inertia page routes
    - `routes/api.php` - API endpoints (includes n8n webhook receiver)
    - `routes/auth.php` - Laravel Breeze authentication routes
- **Authentication**: Laravel Sanctum + Breeze

### n8n Integration

The application integrates with n8n (workflow automation):

- **n8n Service**: `app/Services/N8nClient.php` - Triggers n8n workflows
- **Webhook Receiver**: `routes/api.php` - POST `/api/n8n/webhook` endpoint
    - Secured with `X-N8N-SECRET` header verification
    - Configure secret in `config/services.php` under `n8n.webhook_secret`
- **n8n Configuration**: Set these in `.env`:
    - `N8N_URL` - Base URL for n8n instance
    - `N8N_USERNAME` / `N8N_PASSWORD` - Basic auth credentials
    - `N8N_WEBHOOK_SECRET` - Secret for webhook verification

### Local Development with Caddy

Caddy acts as a reverse proxy for local development:

- `app.localhost` → Laravel application (port 80)
- `n8n.localhost` → n8n dashboard (port 5678)

The n8n dashboard is protected with basic authentication (credentials in Caddyfile).

## Database

Uses PostgreSQL with standard Laravel migrations in `database/migrations/`. A separate database is automatically created
for testing.

## Project Context

This is an "AI Buddy" application that creates optimised AI prompts customised to personality types (based on
16personalities.com). Documentation in `docs/` folder contains project overview and other reference materials.
