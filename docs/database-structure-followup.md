# Database Structure Follow-Up (No Changes Applied Yet)

This document captures concrete, actionable proposals based on the migration review. It is **advisory only**; no schema changes have been made.

## Priority Fixes (Integrity & Safety)

### 1. Fix `prompt_runs.visitor_id` delete behavior
- Current: `foreignUuid('visitor_id')->constrained('visitors')->onDelete('set null')`
- Issue: column is not nullable, so deleting a visitor can fail.
- Options:
  - **Option A (retain runs):** make `prompt_runs.visitor_id` nullable.
  - **Option B (hard cleanup):** change to `cascadeOnDelete`.
- File: `database/migrations/2025_02_01_000020_create_prompt_runs_table.php`

### 2. Add missing foreign keys
- `subscriptions.user_id` → `users.id` (cascade or restrict)
- `subscription_items.subscription_id` → `subscriptions.id` (cascade)
- `question_analytics.question_id` → `questions.id` (cascade)
- `framework_selections.user_id` / `question_analytics.user_id` → `users.id` (nullOnDelete)
- `sessions.user_id` → `users.id` (nullOnDelete)
- Files:
  - `database/migrations/2025_07_01_000010_create_subscriptions_table.php`
  - `database/migrations/2025_07_01_000020_create_subscription_items_table.php`
  - `database/migrations/2025_04_01_000010_create_domain_analytics_tables.php`
  - `database/migrations/2025_01_01_000010_create_users_table.php`

## Data Model Consistency

### 3. Align categorical fields with enums or lookup tables
Candidates for enums:
- `users.subscription_tier` (e.g., free, pro, private)
- `subscriptions.stripe_status` (e.g., active, trialing, canceled)
- `subscriptions.type` (e.g., default)
- `email_events.event_type` (delivered, opened, clicked, bounced, complained, unsubscribed)
- `alert_notifications.status` (pending, sent, failed)

Candidates for FK lookup tables:
- `question_analytics.question_category` → `task_categories.code`
- `framework_selections.recommended_framework` / `chosen_framework` → `frameworks.code`

Files:
- `database/migrations/2025_01_01_000010_create_users_table.php`
- `database/migrations/2025_07_01_000010_create_subscriptions_table.php`
- `database/migrations/2025_06_01_000010_create_email_events_table.php`
- `database/migrations/2025_09_01_000020_create_alert_tables.php`
- `database/migrations/2025_04_01_000010_create_domain_analytics_tables.php`

### 4. Reduce duplication between `users` and `visitors`
Both store location/personality fields. Decide whether:
- `visitors` are source-of-truth for guest data only, and upon conversion those fields should be copied once into `users`; or
- keep both and accept drift (document and automate sync).

Files:
- `database/migrations/2025_01_01_000010_create_users_table.php`
- `database/migrations/2025_01_01_000020_create_visitors_table.php`

## JSON/JSONB Normalization

### 5. Normalize `prompt_runs` Q&A
Current fields:
- `prompt_runs.framework_questions`
- `prompt_runs.clarifying_answers`

Proposal:
- Create `prompt_run_questions` with:
  - `prompt_run_id`
  - `question_id`
  - `answer`
  - `shown_at`, `answered_at`, `skipped_at`
  - `personality_variant`

Benefits:
- analytics and attribution without JSON parsing
- easier joining with `questions` and `question_variants`

File:
- `database/migrations/2025_02_01_000020_create_prompt_runs_table.php`

### 6. Standardize JSON vs JSONB usage
If frequently querying JSON fields in Postgres, prefer `jsonb`:
- `analytics_events.properties`
- `prompt_runs.selected_framework`, `framework_used`, `task_classification`
- `framework_daily_stats.by_personality_type` / `by_task_category`

If used solely as payload snapshots, keep `json`.

## Data Type Fixes

### 7. Align `prices.amount` with stored units
- Comment says "minor units" but `decimal(10,2)` suggests major units.
- Decide: 
  - **minor units** → use `unsignedInteger` (e.g., 1200 for £12.00)
  - **major units** → update comment to avoid confusion
- File: `database/migrations/2025_08_01_000010_create_prices_table.php`

### 8. Match IP types across `visitors` and `visitors_archive`
- `visitors.ip_address` uses `ipAddress`, archive uses `string`.
- Proposal: use `ipAddress` in archive too (or cast both to string consistently).
- File: `database/migrations/2025_10_01_000010_create_visitors_archive_table.php`

## Indexing Opportunities

Suggested indexes (only if query patterns justify them):
- `analytics_events.prompt_run_id`
- `prompt_runs.workflow_stage`, `prompt_runs.completed_at`
- `email_events.user_id + event_type`
- `alert_history.alert_rule_id + last_triggered_at`
- `question_analytics.question_id + presented_at` (if filtering by question over time)

Files:
- `database/migrations/2025_03_01_000010_create_analytics_events_table.php`
- `database/migrations/2025_02_01_000020_create_prompt_runs_table.php`
- `database/migrations/2025_06_01_000010_create_email_events_table.php`
- `database/migrations/2025_09_01_000020_create_alert_tables.php`
- `database/migrations/2025_04_01_000010_create_domain_analytics_tables.php`

## Suggested Next Step
Pick a short list of safe changes to start with (e.g., add missing FKs + fix the `prompt_runs.visitor_id` nullability), then follow up with normalization work if analytics need it. If you want, I can produce a concrete migration plan and sequence once you confirm priorities.
