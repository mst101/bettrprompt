# Database Structure Review & Implementation Plan

## Overview

The schema (see `database/migrations/*.php`) is centred on four clusters:

1. **Identity & tracking** – `users`, `visitors` (`2025_01_01_000020_create_visitors_table.php`), and the analytics tables (`2025_02_01_000010_create_analytics_sessions_table.php`, `2025_03_01_000010_create_analytics_events_table.php`). `visitors.id` is a UUID linked to `prompt_runs.visitor_id`, `analytics_events.visitor_id`, and `analytics_sessions.visitor_id`.
2. **Prompt processing** – `prompt_runs` (`2025_02_01_000020_create_prompt_runs_table.php`) captures every workflow stage and references `visitors`, `users`, and itself for parent/child chains.
3. **Question bank & frameworks** – `questions`/`question_variants` (`2026_01_15_100000_create_questions_table.php`, `2026_01_15_100001_create_question_variants_table.php`) sit alongside auxiliary lookup tables `frameworks`, `task_categories`, `cognitive_requirements`, and their pivot tables from `2025_05_01` migrations.
4. **Commerce & givens** – subscription, pricing, funnel, alert, and email tables keep billing/notifications separate.

Together they support workflows, analytics, personalization, and experiment tracking.

## Current Issues

### 1. Missing Foreign Key Constraints in Analytics Tables ❌

**analytics_sessions:**
- `visitor_id` (UUID) - Only indexed, NO FK to `visitors.id` ❌
- `user_id` (bigint) - Has FK to `users.id` ✅

**analytics_events:**
- `visitor_id` (UUID) - Only indexed, NO FK to `visitors.id` ❌
- `session_id` (UUID) - Only indexed, NO FK to `analytics_sessions.id` ❌
- `user_id` (bigint) - Has FK to `users.id` ✅
- `prompt_run_id` (bigint) - Has FK to `prompt_runs.id` ✅

**Impact:** Without foreign key constraints, the database cannot enforce referential integrity. Orphaned analytics records can exist when visitors/sessions are deleted. No cascade/null delete protection.

### 2. Denormalized Questions Table ❌

**Current state:**
- `category` - String(30) storing literals like "decision", "co_star" (should be FK to `task_categories.code`)
- `framework` - String(30) storing literals like "co_star", "react" (should be FK to `frameworks.code`)
- `cognitive_requirements` - JSONB array storing `["STRUCTURE", "DETAIL"]` (should be normalized via pivot table)

**Existing normalized tables available:**
- `frameworks` (code PK, name, description, components, etc.) ✅
- `task_categories` (code PK, name, description, triggers) ✅
- `cognitive_requirements` (code PK, name, description, aligned_traits, opposed_traits) ✅

**Problems:**
- String literals allow typos, no validation
- No referential integrity constraints
- Can't use Eloquent relationships
- JSONB hinders analytics queries
- No ability to distinguish primary vs secondary requirements

## Implementation Plan

### Phase 1: Add Missing Foreign Keys to Analytics Tables

#### Migration 1: `database/migrations/2026_01_15_200001_add_visitor_fk_to_analytics_sessions.php`

Add `visitor_id` foreign key to `analytics_sessions`:

```php
$table->foreign('visitor_id')
    ->references('id')
    ->on('visitors')
    ->nullOnDelete();
```

**Rationale:** Use `nullOnDelete()` to preserve analytics history even if visitor is archived/deleted.

#### Migration 2: `database/migrations/2026_01_15_200002_add_foreign_keys_to_analytics_events.php`

Add `visitor_id` and `session_id` foreign keys to `analytics_events`:

```php
$table->foreign('visitor_id')
    ->references('id')
    ->on('visitors')
    ->nullOnDelete();

$table->foreign('session_id')
    ->references('id')
    ->on('analytics_sessions')
    ->nullOnDelete();
```

### Phase 2: Normalize Questions Table

#### Migration 3: `database/migrations/2026_01_15_200003_add_normalized_columns_to_questions.php`

Add normalized columns alongside old ones for safe migration:

```php
$table->string('task_category_code', 30)->nullable()->after('priority');
$table->string('framework_code', 30)->nullable()->after('task_category_code');

$table->foreign('task_category_code')
    ->references('code')
    ->on('task_categories')
    ->restrictOnDelete();

$table->foreign('framework_code')
    ->references('code')
    ->on('frameworks')
    ->restrictOnDelete();
```

#### Migration 4: `database/migrations/2026_01_15_200004_create_question_cognitive_requirements_table.php`

Create junction table for many-to-many relationship:

```php
Schema::create('question_cognitive_requirements', function (Blueprint $table) {
    $table->id();
    $table->string('question_id', 10);
    $table->string('cognitive_requirement_code', 30);
    $table->enum('requirement_level', ['primary', 'secondary'])->default('primary');
    $table->timestamps();

    $table->foreign('question_id')
        ->references('id')
        ->on('questions')
        ->cascadeOnDelete();

    $table->foreign('cognitive_requirement_code')
        ->references('code')
        ->on('cognitive_requirements')
        ->restrictOnDelete();

    $table->unique(['question_id', 'cognitive_requirement_code']);
});
```

#### Migration 5: `database/migrations/2026_01_15_200005_migrate_questions_denormalized_data.php`

Migrate data from old denormalized columns to new normalized structure:
- Map lowercase strings to uppercase codes (e.g., "decision" → "DECISION")
- Populate `question_cognitive_requirements` junction table from JSONB arrays

#### Migration 6: `database/migrations/2026_01_15_200006_remove_denormalized_questions_columns.php`

Remove old denormalized columns after successful data migration:
- Drop `category` column
- Drop `framework` column
- Drop `cognitive_requirements` column
- Drop associated indexes

### Phase 3: Update Models

**Question.php:**
- Add relationships: `taskCategory()`, `framework()`, `cognitiveRequirements()`
- Update scopes: `byCategory()`, `byFramework()`, `universal()`
- Update `$fillable` array

**Framework.php:**
- Add `questions()` HasMany relationship

**TaskCategory.php:**
- Add `questions()` HasMany relationship

**CognitiveRequirement.php:**
- Add `questions()` BelongsToMany relationship

### Phase 4: Update Seeders & CSV Files

**QuestionSeeder.php:**
- Update to read from new column names: `task_category_code`, `framework_code`
- Remove JSONB column reading

**Create QuestionCognitiveRequirementSeeder.php:**
- New seeder to populate junction table from CSV

**CSV Files:**
- `questions.csv` - Update headers and values (lowercase → uppercase)
- `question_cognitive_requirements.csv` - Create new file with junction data

### Phase 5: Update Application Code

Update any code that queries questions by category/framework:

```php
// Before:
Question::where('category', 'decision')->get();

// After:
Question::where('task_category_code', 'DECISION')->get();
Question::byCategory('DECISION')->get();
```

## Benefits After Implementation

### Analytics Tables ✅
- **Referential integrity enforced** - Database prevents orphaned records
- **Cascade/null delete** - Automatic cleanup without application logic
- **Data consistency** - Foreign key constraints guarantee valid relationships

### Questions Table ✅
- **Type safety** - FK constraints prevent invalid values
- **Eloquent relationships** - Enable eager loading and query building
- **Analytics-friendly** - Can join across relationships
- **No more JSON parsing** - Direct queries on cognitive requirements
- **Typo prevention** - FK constraints validate values
- **Flexible modeling** - Support level metadata on pivot table

### Query Examples After Normalization

```php
// Find all questions for a specific framework
Framework::find('CO_STAR')->questions;

// Get questions requiring empathy
CognitiveRequirement::find('EMPATHY')->questions;

// Count questions by category
TaskCategory::withCount('questions')->get();

// Eager load everything
Question::with(['taskCategory', 'framework', 'cognitiveRequirements'])->get();
```

## Migration Execution Order

Run in this sequence:

1. `2026_01_15_200001_add_visitor_fk_to_analytics_sessions.php`
2. `2026_01_15_200002_add_foreign_keys_to_analytics_events.php`
3. `2026_01_15_200003_add_normalized_columns_to_questions.php`
4. `2026_01_15_200004_create_question_cognitive_requirements_table.php`
5. `2026_01_15_200005_migrate_questions_denormalized_data.php`
6. `2026_01_15_200006_remove_denormalized_questions_columns.php`

## Testing

Create feature tests to verify:
- Foreign key constraints work (cascade/null delete)
- Questions relationships function correctly
- Data migration completed without loss
- Seeder populates data correctly

## Rollback Strategy

- Migrations 1-2 (analytics FKs) can rollback independently
- Migrations 3-6 (questions) should rollback together for data safety
- Migration 5 preserves data in down() method for recovery

## Status

- [x] Plan created
- [x] Review document updated
- [ ] Phase 1: Implement analytics FK migrations
- [ ] Phase 2: Implement questions normalization migrations
- [ ] Phase 3: Update models
- [ ] Phase 4: Update seeders and CSV files
- [ ] Phase 5: Update application code
- [ ] Testing and validation
