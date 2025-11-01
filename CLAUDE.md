# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Inertia.js with Vue 3 + TypeScript
- **Database**: PostgreSQL
- **Cache/Queue**: Redis
- **Workflow Automation**: n8n
- **Styling**: Tailwind CSS v4
- **Testing**: Pest (PHPUnit)
- **Dev Environment**: Laravel Sail (Docker)
- **Reverse Proxy**: Caddy

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
- `php artisan queue:listen --tries=1` - Queue worker
- `php artisan pail --timeout=0` - Log viewer
- `npm run dev` - Vite dev server with HMR

### Building and Testing

```bash
# Run Pest tests
composer test
# or directly:
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Build for production (includes SSR build)
npm run build

# Lint TypeScript/Vue files
npm run lint

# Code style (Laravel Pint)
./vendor/bin/pint
```

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

The application uses Inertia.js to create a single-page app experience whilst maintaining Laravel's routing and controllers. SSR is enabled for improved performance and SEO.

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

Uses PostgreSQL with standard Laravel migrations in `database/migrations/`. A separate database is automatically created for testing.

## Project Context

This is an "AI Buddy" application that creates optimised AI prompts customised to personality types (based on 16personalities.com). Documentation in `docs/` folder contains project overview and other reference materials.
