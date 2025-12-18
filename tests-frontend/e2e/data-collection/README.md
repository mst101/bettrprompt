# Framework Selection Data Collection Tests

These tests are designed for **data analysis purposes**, not for testing the system's functionality. They collect data
about which prompt frameworks are selected for each of the 16 MBTI personality types.

## Purpose

The framework-selection tests:

- Submit the same precise task to the system for each personality type
- Capture which framework is selected by the n8n workflow
- Record any pre-analysis clarifying questions
- Persist all data to a **persistent, separate database** for analysis

## Running the Data Collection Tests

To run these tests separately from the main e2e test suite:

```bash
# Run all framework-selection tests with data collection config
npx playwright test --config=playwright-data-collection.config.ts

# Run single personality type
npx playwright test --config=playwright-data-collection.config.ts --grep "INTJ-A"

# Run with verbose output
npx playwright test --config=playwright-data-collection.config.ts --headed
```

## Persistent Data Storage

Unlike main e2e tests which reset the database (`bettrprompt_e2e`) before each run, data collection tests:

- Use a separate **persistent database** (`bettrprompt_data_collection`)
- Do NOT reset the database between test runs
- Accumulate framework selection data over time
- Preserve all workflow responses and n8n data

All requests from data collection tests include the `X-Data-Collection-Test` header, which signals the Laravel
middleware to use the `bettrprompt_data_collection` database instead of the standard `bettrprompt_e2e` database.

## Collected Data

Each test creates a prompt run with:

- **Task Description**: Precise technical specification for a healthcare management system
- **Selected Framework**: The prompt framework chosen by the n8n analysis workflow
- **Pre-analysis Questions**: Any clarifying questions from the pre-analysis phase
- **Workflow Stage**: Current stage of processing (0_processing, 0_completed, 1_processing, 1_completed, 2_processing,
  2_completed, etc.)
- **API Usage**: Token counts and usage metrics from each n8n workflow phase

## Data Location

All prompt run data is stored in the `bettrprompt_data_collection` database in the `prompt_runs` table. Key columns for
analysis:

- `task_description` - The task submitted for analysis
- `selected_framework` - Framework selected by workflow (JSON)
- `framework_questions` - Clarifying questions for the framework (JSON array)
- `pre_analysis_questions` - Initial clarifying questions (JSON array)
- `workflow_stage` - Current processing stage (0_processing, 0_completed, 1_processing, 1_completed, 2_processing,
  2_completed, etc.)
- `created_at` - Timestamp of when the test ran
- `updated_at` - Timestamp of last update

## Workflow Processing

The n8n workflows process asynchronously:

1. Test submits task → Prompt run created with `workflow_stage='0_processing'` in `bettrprompt_data_collection`
2. Pre-analysis phase → Updates to `workflow_stage='0_completed'` with clarifying questions (~5-10 seconds)
3. Main analysis → Transitions to `workflow_stage='1_processing'` then `'1_completed'`, selects framework and generates
   questions (~15-30 seconds)
4. Prompt optimisation → Transitions to `workflow_stage='2_processing'` then `'2_completed'`, generates final prompt (~
   10-20 seconds)

The tests wait up to 90 seconds for workflow completion, but data is persisted immediately to the data collection
database regardless.

## Database Isolation

- **Main E2E Tests** (`npm run test:e2e`): Use `bettrprompt_e2e` database (reset before each run)
- **Data Collection Tests** (`npx playwright test --config=playwright-data-collection.config.ts`): Use
  `bettrprompt_data_collection` database (persistent, never reset)

This separation ensures:

- Main tests remain fast and isolated
- Data collection accumulates results for analysis
- No cross-contamination between test suites

## Querying Collected Data

To view collected framework selection data:

```bash
# Count total prompt runs collected
./vendor/bin/sail exec pgsql psql -U sail -d bettrprompt_data_collection -c \
  "SELECT COUNT(*) FROM prompt_runs;"

# View recent framework selections
./vendor/bin/sail exec pgsql psql -U sail -d bettrprompt_data_collection -c \
  "SELECT id, task_description, selected_framework, workflow_stage, created_at
   FROM prompt_runs ORDER BY created_at DESC LIMIT 10;"
```
