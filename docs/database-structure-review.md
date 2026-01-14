# Database Structure Review

## Overview
The schema (see `database/migrations/*.php`) is centred on four clusters:

1. **Identity & tracking** – `users`, `visitors` (`2025_01_01_000020_create_visitors_table.php`), and the analytics tables (`2025_02_01_000010_create_analytics_sessions_table.php`, `2025_03_01_000010_create_analytics_events_table.php`). `visitors.id` is a UUID linked to `prompt_runs.visitor_id`, `analytics_events.visitor_id`, and `analytics_sessions.visitor_id`.
2. **Prompt processing** – `prompt_runs` (`2025_02_01_000020_create_prompt_runs_table.php`) captures every workflow stage and references `visitors`, `users`, and itself for parent/child chains.
3. **Question bank & frameworks** – `questions`/`question_variants` (`2026_01_15_100000_create_questions_table.php`, `2026_01_15_100001_create_question_variants_table.php`) sit alongside auxiliary lookup tables `frameworks`, `task_categories`, `cognitive_requirements`, and their pivot tables from `2025_05_01` migrations.
4. **Commerce & givens** – subscription, pricing, funnel, alert, and email tables keep billing/notifications separate.

Together they support workflows, analytics, personalization, and experiment tracking, but there remain areas where the structure could be tightened for consistency, integrity, and maintainability.

## Observations

### 1. Referential integrity is partial
- `analytics_sessions` stores `visitor_id` and `user_id` but only indexes them; there is no foreign key constraint to `visitors.id`, unlike `prompt_runs.visitor_id` (`constrained('visitors')`). `analytics_events` follows the same pattern (UUID columns with indexes but no `foreign` call). Orphaned analytics data can arise if visitors/sessions are purged.
- `questions.framework` and `questions.category` are raw strings, yet dimension tables `frameworks.code` and `task_categories.code` already exist. The only relationship is semantic.

### 2. Heavy JSON usage hinders analytics
- `prompt_runs` uses JSON for `cognitive_requirements`, `selected_framework`, `framework_questions`, etc., which makes filtering/counting difficult and leaves the relationship between a question and the run implicit.
- `questions.cognitive_requirements` is stored as JSONB even though there exists `cognitive_requirements` + pivot tables (`framework_cognitive_requirements`, `task_category_cognitive_requirements`) to model these entities.

### 3. Index coverage is inconsistent
- Many columns queried (`analytics_sessions.converted`, `analytics_events.type`) already have indexes, but others such as `analytics_events.prompt_run_id` or `questions.display_order` may benefit from composite indexes when filtered together. The new `visitors_archive` table (`2025_10_01_000020_create_visitors_archive_table.php`) caches historical data and should mirror the active indexes or be partitioned.

## Recommendations

### 1. Enforce FK constraints for analytics tables
Add explicit foreign keys so that an analytics session/event cannot reference a missing visitor or prompt run. For example:

```php
$table->uuid('visitor_id')->constrained('visitors')->cascadeOnDelete();
$table->foreign('session_id')->references('id')->on('analytics_sessions')->cascadeOnDelete();
$table->foreignId('prompt_run_id')->nullable()->constrained()->nullOnDelete();
```

This keeps the analytics tables clean after visitor truncation and guarantees `ProcessAnalyticsEvents` always refers to valid rows.

### 2. Normalize question metadata via lookup tables
- Replace `questions.category` with `category_code` (FK to `task_categories.code`) and `questions.framework` with `framework_code` (FK to `frameworks.code`). This enforces domain integrity and avoids typos. Keep the legacy string columns (if needed for display) via computed views or stored lookups.
- Introduce a small `priority_levels` table (`code`, `label`) if the current enum set ever expands; in the meantime, index `priority` → `questions` already has index but tie it to a lookup ensures consistent values.

### 3. Model question–requirement relationships with join tables
Replace `questions.cognitive_requirements` JSON with a normalized pivot (`question_cognitive_requirements`) referencing `cognitive_requirements.code`. The pivot can store a `strength` or `role` column if future weighting is needed. This makes it easy to find all questions aligned to a trait or to count how often each requirement is suggested.

### 4. Review retention/partition strategy for high-volume tables
- `analytics_events` can grow fast. Consider partitioning (`daily`/`monthly`) or moving older rows to an append-only history table (similar to `visitors_archive`). The existing retention indexes (`2025_10_01_000030_add_retention_indexes.php`) cover `occurred_at` but not `visitor_id`.
- Mirror the indexes from `visitors` onto `visitors_archive` so queries filtering by user/country behave the same.

### 5. Document relationships and add tests
Create an ER diagram and include it in `docs/` (or update this file), and add integration tests that assert cascade deletes/ FK constraints (e.g., deleting a visitor removes analytics sessions). This ensures future changes respect the topology.

Implementing these changes will keep tracking reliable, simplify analytics-friendly joins, and make the “question bank → frameworks/tasks → cognitive requirements” graph explicit rather than implied via JSON blobs.
