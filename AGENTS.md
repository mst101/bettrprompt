# Repository Guidelines

Laravel 12 API plus an Inertia-powered Vue 3 front end. Use these notes to move fast while staying aligned with existing patterns.

## Project Structure & Module Organization
- Backend lives in `app/`; routes in `routes/web.php` and `routes/api.php`; config in `config/`; migrations/seeders in `database/`.
- Frontend entry points sit in `resources/js` (Vue components, Pinia stores, composables, SSR bootstrap) with styles in `resources/css` and Blade shells in `resources/views`.
- Tests: PHP feature/unit suites under `tests/`; frontend unit/component/e2e suites in `tests-frontend/`. Public assets and Vite output live in `public/`; runtime artifacts in `storage/`.

## Build, Test, and Development Commands
- Install: `composer install` and `pnpm install` (pnpm preferred). Create env with `cp .env.example .env && php artisan key:generate`.
- Local stack: `composer dev` to run the Laravel server, queue listener, pail logs, and Vite dev server together. For a front-end-only loop, use `pnpm dev`.
- Database: `php artisan migrate --seed` after updating `.env` (SQLite lives at `database/database.sqlite` for a quick start).
- Build: `pnpm build` (runs `vue-tsc` type check plus Vite client + SSR).
- Tests: `composer test` for backend (Pest), `pnpm test:unit` or `pnpm test:watch` for Vue unit/component, `pnpm test:e2e` (Playwright) for browser flows.

## Coding Style & Naming Conventions
- PHP follows PSR-12 via Laravel Pint; run `./vendor/bin/pint` on `app`, `config`, `database`, `routes`, `tests`.
- JS/TS uses ESLint + Prettier (single quotes, import/tailwind plugins). Run `pnpm lint` and `pnpm format` before pushing.
- Components/Layouts in `resources/js/Components` and `Layouts` are PascalCase; composables in `Composables` use `useSomething`; Pinia stores in `stores` use camelCase files exporting `useXStore`.
- Keep Vite/Inertia pages colocated in `resources/js/Pages`.

## Testing Guidelines
- PHP tests live in `tests/Feature/*Test.php` and `tests/Unit/*Test.php`; prefer Pest-style expectations and avoid hitting real network calls.
- Frontend unit/component specs end with `.test.ts` under `tests-frontend/unit` or `component`; fixtures in `tests-frontend/helpers`. E2E specs end with `.e2e.ts` in `tests-frontend/e2e` and expect the dev server running.
- Record what you ran in PRs (e.g., `composer test`, `pnpm test:e2e`). Use `pnpm test:coverage` for UI coverage on critical flows.

## Commit & Pull Request Guidelines
- Commit messages are short, sentence-case imperatives (e.g., `Refactor frontend: improve type safety`). Group related changes; avoid WIP spam.
- PRs include a concise summary, linked issue or task ID, screenshots for UI updates, notes on migrations/env changes, and a checklist of tests executed.
- Avoid committing anything from `storage/` or `.env*`; scrub logs and secrets before review.
