# Analytics Automated Testing Plan

## Executive Summary

This document outlines the automated test implementation strategy for the analytics tracking system in BettrPrompt. The analytics system tracks user interactions across the prompt builder journey: framework selection, question answering, and prompt rating.

**Test Coverage Areas:**
1. **Backend Unit Tests** - Services and business logic (Pest)
2. **Backend Feature Tests** - API controllers and request validation (Pest)
3. **Frontend E2E Tests** - User interactions and analytics events (Playwright)
4. **Integration Tests** - Complete user journeys with database verification

**Total Estimated Effort:** ~3,500 lines of test code across 15 new test files

---

## Testing Infrastructure Overview

### Backend Testing (Pest)
- **Test Framework:** Pest with Expectation API
- **Base Classes:** `TestCase` (feature) and `UnitTestCase` (unit)
- **Database:** `RefreshDatabase` for features, `DatabaseTransactions` for units
- **Factories:** User, Visitor, PromptRun with state helpers
- **Patterns:** describe/test blocks, expectation chaining, service mocking

### Frontend E2E Testing (Playwright)
- **Test Framework:** Playwright with TypeScript
- **Configuration:** Isolated test database (`bettrprompt_e2e`)
- **Fixtures:** `authenticatedPage`, page objects, helpers
- **Patterns:** Page Object Model, test isolation, n8n mocking
- **Test Endpoint:** `/test/*` routes for database setup with `X-Test-Auth` header

---

## Phase 1: Backend Unit Tests - Analytics Services

### 1.1: FrameworkAnalyticsService Tests

**New File:** `tests/Unit/Services/FrameworkAnalyticsServiceTest.php`

**Test Coverage:**

```php
describe('Framework Selection Recording', function () {
    test('records framework selection with all required fields');
    test('sets accepted_recommendation to true when frameworks match');
    test('sets accepted_recommendation to false when frameworks differ');
    test('stores recommendation_scores as JSON');
    test('logs selection event');
});

describe('Framework Switching', function () {
    test('updates chosen framework when user switches');
    test('preserves recommended framework after switch');
    test('updates accepted_recommendation when switched');
    test('logs framework switch event');
});

describe('Outcome Recording', function () {
    test('updates framework analytic with rating');
    test('updates framework analytic with rating explanation');
    test('updates framework analytic with copy status');
    test('updates framework analytic with edit percentage');
    test('handles null rating gracefully');
});

describe('Framework Metrics', function () {
    test('calculates acceptance rate correctly');
    test('handles zero recommendations gracefully');
    test('calculates average rating excluding nulls');
    test('calculates copy rate correctly');
    test('returns comprehensive performance summary');
    test('ranks frameworks by acceptance rate');
    test('limits top frameworks to specified count');
});
```

**Critical Test Cases:**
- Acceptance rate: 7 accepted / 10 total = 70%
- Copy rate with zeros: 0 copied / 5 total = 0%
- Average rating with nulls: Sum non-null / count non-null
- Framework switch history: Multiple rows per prompt run

**Estimated Lines:** ~300 lines

---

### 1.2: QuestionAnalyticsService Tests

**New File:** `tests/Unit/Services/QuestionAnalyticsServiceTest.php`

**Test Coverage:**

```php
describe('Question Presentation Recording', function () {
    test('records question presentation with display_order');
    test('sets response_status to not_shown initially');
    test('stores was_required flag correctly');
    test('stores personality_variant');
    test('stores question_category');
});

describe('Question Response Recording', function () {
    test('updates response status to answered');
    test('stores response_length correctly');
    test('stores time_to_answer_ms');
    test('stores display_mode (one-at-a-time or show-all)');
    test('preserves presentation timestamp');
});

describe('Question Skip Recording', function () {
    test('updates response status to skipped');
    test('stores time_before_skip if provided');
    test('works for both required and optional questions');
});

describe('Question Rating', function () {
    test('updates question analytic with user_rating (1-5)');
    test('updates question analytic with rating_explanation');
    test('overwrites previous rating if submitted again');
    test('handles null explanation gracefully');
});

describe('Question Metrics', function () {
    test('calculates answer rate correctly');
    test('calculates skip rate correctly');
    test('calculates average time to answer excluding skipped');
    test('calculates average response length excluding skipped');
    test('returns comprehensive question performance summary');
});
```

**Critical Test Cases:**
- Answer rate: 8 answered / 10 total = 80%
- Skip rate: 2 skipped / 10 total = 20%
- Time to answer: Sum of non-null time_to_answer_ms / count
- Rating correlation: Avg rating (answered) - Avg rating (skipped)

**Estimated Lines:** ~400 lines

---

### 1.3: PromptQualityService Tests

**New File:** `tests/Unit/Services/PromptQualityServiceTest.php`

**Test Coverage:**

```php
describe('Metrics Recording', function () {
    test('creates new prompt_quality_metrics record');
    test('updates existing record by prompt_run_id');
    test('stores user_rating and rating_explanation');
    test('stores was_copied and copy_count');
    test('stores was_edited and edit_percentage');
    test('filters null values from update array');
});

describe('Engagement Score Calculation', function () {
    test('calculates score from copy (30pts) + edit (20pts) + rating (50pts)');
    test('copy: was_copied=true gives 30 points');
    test('edit: was_edited=true gives 20 points');
    test('rating: (user_rating / 5) * 50');
    test('all three: 30 + 20 + 50 = 100 points');
    test('none: 0 points');
    test('caps score at 100');
});

describe('Quality Score Calculation', function () {
    test('calculates score from length + edits + answered + time');
    test('prompt_length: 500-2000 chars = full points');
    test('was_edited: penalty -10 points');
    test('questions_answered: (answered / total) * 30');
    test('time_spent: reasonable range gives points');
    test('caps score at 100');
    test('floors score at 0');
});

describe('Quality Percentiles', function () {
    test('calculates p10, p25, p50, p75, p90 correctly');
    test('handles empty dataset');
    test('returns correct percentiles for 100 records');
});
```

**Critical Test Cases:**
- Engagement score all components: 30 + 20 + (5/5)*50 = 100
- Quality score calculation with various inputs
- Percentile accuracy with sorted dataset
- Null handling in averages

**Estimated Lines:** ~350 lines

---

## Phase 2: Backend Feature Tests - API Controllers

### 2.1: PromptRatingController Tests

**New File:** `tests/Feature/Api/PromptRatingControllerTest.php`

**Test Coverage:**

```php
describe('Prompt Rating Authorisation', function () {
    test('allows prompt owner to rate their prompt');
    test('allows admin to rate any prompt');
    test('rejects unauthorised user from rating');
    test('requires authentication');
});

describe('Prompt Rating Validation', function () {
    test('validates rating is required');
    test('validates rating is integer between 1 and 5');
    test('rejects rating below 1');
    test('rejects rating above 5');
    test('rejects non-integer rating');
    test('validates explanation max length 1000 chars');
    test('accepts null explanation');
});

describe('Prompt Rating Persistence', function () {
    test('creates prompt_quality_metrics record if not exists');
    test('updates existing prompt_quality_metrics record');
    test('stores user_rating correctly');
    test('stores rating_explanation correctly');
    test('returns success message JSON response');
});
```

**HTTP Request Example:**
```php
$response = $this->actingAs($user)
    ->postJson("/api/prompt-runs/{$promptRun->id}/rate", [
        'rating' => 5,
        'explanation' => 'Excellent prompt, very helpful!',
    ]);

$response->assertOk();
$this->assertDatabaseHas('prompt_quality_metrics', [
    'prompt_run_id' => $promptRun->id,
    'user_rating' => 5,
]);
```

**Estimated Lines:** ~200 lines

---

### 2.2: QuestionRatingController Tests

**New File:** `tests/Feature/Api/QuestionRatingControllerTest.php`

**Test Coverage:**

```php
describe('Question Rating Authorisation', function () {
    test('allows prompt owner to rate question');
    test('allows admin to rate any question');
    test('rejects unauthorised user from rating');
    test('requires authentication');
});

describe('Question Rating Validation', function () {
    test('validates rating is required');
    test('validates rating is integer between 1 and 5');
    test('rejects rating below 1');
    test('rejects rating above 5');
    test('validates explanation max length 1000 chars');
    test('accepts null explanation');
});

describe('Question Rating Persistence', function () {
    test('updates question_analytics record with rating');
    test('updates question_analytics record with explanation');
    test('finds question by prompt_run_id and question_id');
    test('returns success message JSON response');
});
```

**HTTP Request Example:**
```php
$response = $this->actingAs($user)
    ->postJson("/api/prompt-runs/{$promptRun->id}/questions/U1/rate", [
        'rating' => 4,
        'explanation' => 'Good question, helped me clarify my task',
    ]);

$response->assertOk();
```

**Estimated Lines:** ~200 lines

---

### 2.3: UserPreferenceController Tests

**New File:** `tests/Feature/Api/UserPreferenceControllerTest.php`

**Test Coverage:**

```php
describe('User Preference Authorisation', function () {
    test('allows authenticated user to update their preferences');
    test('allows guest visitor to update preferences via cookie');
    test('updates user table for authenticated users');
    test('updates visitor table for guest visitors');
});

describe('User Preference Validation', function () {
    test('validates question_display_mode enum values');
    test('rejects invalid question_display_mode');
    test('validates ui_complexity enum values');
    test('allows partial updates (only one field)');
    test('allows both fields in single request');
});

describe('User Preference Persistence', function () {
    test('updates users.question_display_mode for authenticated user');
    test('updates visitors.question_display_mode for guest');
    test('returns success message JSON response');
});
```

**HTTP Request Example:**
```php
$response = $this->actingAs($user)
    ->patchJson('/api/user/preferences', [
        'question_display_mode' => 'show-all',
    ]);

$response->assertOk();
$this->assertDatabaseHas('users', [
    'id' => $user->id,
    'question_display_mode' => 'show-all',
]);
```

**Estimated Lines:** ~180 lines

---

## Phase 3: Frontend E2E Tests - Component Interactions

### 3.1: PromptRating Component Tests

**New File:** `tests-frontend/e2e/components/prompt-rating.e2e.ts`

**Test Coverage:**

```typescript
test.describe('PromptRating Component', () => {
    test('displays 5 stars with correct initial state');
    test('clicking star updates rating value');
    test('hovering star shows preview');
    test('shows explanation textarea when rating selected');
    test('hides explanation textarea when rating cleared');
    test('submit button triggers event with rating and explanation');
    test('readonly mode disables interactions');
    test('respects size prop (sm, md, lg)');
    test('validates explanation max length');
});
```

**Estimated Lines:** ~200 lines

---

### 3.2: Prompt Rating Integration Tests

**New File:** `tests-frontend/e2e/prompt-builder/prompt-rating.e2e.ts`

**Test Coverage:**

```typescript
test.describe('Prompt Rating in OptimisedPrompt', () => {
    test('displays rating UI after prompt generation');
    test('submits prompt rating via API');
    test('shows thank you message after rating');
    test('prevents double submission');
    test('fires prompt_rated analytics event');
});
```

**Example Test:**
```typescript
test('submits prompt rating via API', async ({ authenticatedPage }) => {
    const promptRunId = await setupAndNavigateToPromptRun(authenticatedPage, '2_completed');

    // Click 5th star
    const star5 = authenticatedPage.locator('[aria-label="Rate 5 stars"]');
    await star5.click();

    // Type explanation
    const explanationTextarea = authenticatedPage.locator('textarea[placeholder*="rating"]');
    await explanationTextarea.fill('Perfect prompt for my needs!');

    // Submit
    await authenticatedPage.getByRole('button', { name: /submit/i }).click();

    // Verify database record
    const rating = await authenticatedPage.evaluate(async (id) => {
        const response = await fetch(`/test/prompt-quality-metrics/${id}`, {
            headers: { 'X-Test-Auth': 'playwright-e2e-tests' }
        });
        return response.json();
    }, promptRunId);

    expect(rating.user_rating).toBe(5);
    expect(rating.rating_explanation).toContain('Perfect prompt');
});
```

**Estimated Lines:** ~250 lines

---

### 3.3: Question Rating Integration Tests

**New File:** `tests-frontend/e2e/prompt-builder/question-rating.e2e.ts`

**Test Coverage:**

```typescript
test.describe('Question Rating in ClarifyingQuestions', () => {
    test('displays rating UI after answering question in one-at-a-time mode');
    test('does not show rating UI in show-all mode');
    test('submits question rating via API');
    test('fires question_rated analytics event');
    test('allows rating multiple questions independently');
});
```

**Estimated Lines:** ~300 lines

---

### 3.4: Display Mode Preference Tests

**New File:** `tests-frontend/e2e/prompt-builder/display-mode-preference.e2e.ts`

**Test Coverage:**

```typescript
test.describe('Question Display Mode Preference', () => {
    test('saves preference when toggling display mode');
    test('loads saved preference on component mount');
    test('persists preference across sessions');
    test('saves preference for guest visitors via cookie');
});
```

**Estimated Lines:** ~200 lines

---

### 3.5: Framework Switching Analytics Tests

**New File:** `tests-frontend/e2e/prompt-builder/framework-switching.e2e.ts`

**Test Coverage:**

```typescript
test.describe('Framework Switching Analytics', () => {
    test('fires framework_switched analytics event');
    test('creates framework_analytics record on switch');
    test('allows cancelling framework switch');
});
```

**Estimated Lines:** ~180 lines

---

## Phase 4: Integration Tests - Complete User Journeys

### 4.1: End-to-End Rating Journey Test

**New File:** `tests-frontend/e2e/journeys/complete-rating-flow.e2e.ts`

**Test Coverage:**

```typescript
test.describe('Complete Rating Journey', () => {
    test('user completes full prompt journey and rates everything', async ({ authenticatedPage }) => {
        // 1. Start new prompt
        // 2. Navigate to Questions tab
        // 3. Answer all questions and rate each
        // 4. Navigate to Optimised Prompt tab
        // 5. Rate the final prompt
        // 6. Wait for all analytics to flush
        // 7. Verify complete data pipeline

        // Assert all questions rated
        expect(verification.questions.every((q: any) => q.user_rating !== null)).toBe(true);

        // Assert prompt rated
        expect(verification.prompt.user_rating).toBe(5);

        // Assert analytics events captured
        const questionRatedEvents = verification.events.filter((e: any) => e.name === 'question_rated');
        expect(questionRatedEvents.length).toBe(3);
    });
});
```

**Estimated Lines:** ~250 lines

---

## Phase 5: Test Helper & Utility Functions

### 5.1: Backend Test Helpers

**New File:** `tests/Helpers/AnalyticsTestHelpers.php`

**Functions:**
- `createPromptRunWithFramework()` - Create prompt run with framework analytics
- `createPromptRunWithQuestions()` - Create prompt run with question analytics
- `assertAnalyticsEventExists()` - Assert analytics event exists

**Estimated Lines:** ~100 lines

---

### 5.2: E2E Test Helpers

**New File:** `tests-frontend/e2e/helpers/analytics.ts`

**Functions:**
- `createTestUser()` - Create test user with specific preferences
- `getAnalyticsEvents()` - Fetch analytics events for prompt run
- `getQuestionAnalytics()` - Fetch question analytics for prompt run
- `getPromptQualityMetrics()` - Fetch prompt quality metrics
- `getFrameworkAnalytics()` - Fetch framework analytics for prompt run
- `waitForAnalyticsBatchFlush()` - Wait for analytics batch to flush

**Estimated Lines:** ~100 lines

---

## Phase 6: Test Endpoint Creation (Backend Support)

### 6.1: Test Endpoints for E2E Data Verification

**New File:** `app/Http/Controllers/Test/AnalyticsTestController.php`

**Endpoints:**
- `GET /test/analytics-events` - Get analytics events
- `GET /test/question-analytics/{promptRunId}` - Get question analytics
- `GET /test/prompt-quality-metrics/{promptRunId}` - Get prompt quality metrics
- `GET /test/framework-analytics/{promptRunId}` - Get framework analytics
- `GET /test/user-preferences/{userId}` - Get user preferences
- `GET /test/visitor-preferences/{visitorId}` - Get visitor preferences

**Security:**
- Only available in `e2e` environment
- Requires `X-Test-Auth: playwright-e2e-tests` header
- Returns 403 without proper authentication
- Returns 404 in non-testing environments

**Add routes:** `routes/api.php`

```php
if (config('app.env') === 'e2e') {
    Route::prefix('test')->group(function () {
        Route::get('/analytics-events', [AnalyticsTestController::class, 'getAnalyticsEvents']);
        Route::get('/question-analytics/{promptRunId}', [AnalyticsTestController::class, 'getQuestionAnalytics']);
        Route::get('/prompt-quality-metrics/{promptRunId}', [AnalyticsTestController::class, 'getPromptQualityMetrics']);
        Route::get('/framework-analytics/{promptRunId}', [AnalyticsTestController::class, 'getFrameworkAnalytics']);
        Route::get('/user-preferences/{userId}', [AnalyticsTestController::class, 'getUserPreferences']);
        Route::get('/visitor-preferences/{visitorId}', [AnalyticsTestController::class, 'getVisitorPreferences']);
    });
}
```

**Estimated Lines:** ~165 lines (controller + routes)

---

## Testing Execution Strategy

### Test Priorities

**Priority 1 (Critical - Must Pass):**
1. Rating API controllers (authorisation, validation, persistence)
2. Service calculation methods (acceptance rate, quality score)
3. Rating component functionality (star clicks, submission)
4. E2E rating submission flows

**Priority 2 (High - Should Pass):**
1. Display mode preference persistence
2. Question analytics recording
3. Framework analytics recording
4. E2E question answering with ratings

**Priority 3 (Medium - Nice to Have):**
1. Edge case handling (null values, zero records)
2. Percentile calculations
3. Complex metric aggregations
4. Multi-question rating flows

### Running Tests

**Backend Tests:**
```bash
# All unit tests
./vendor/bin/sail test tests/Unit/Services/

# All feature tests
./vendor/bin/sail test tests/Feature/Api/

# Specific test file
./vendor/bin/sail test tests/Unit/Services/FrameworkAnalyticsServiceTest.php

# Specific test case
./vendor/bin/sail test --filter "calculates acceptance rate correctly"

# With coverage
./vendor/bin/sail test --coverage
```

**Frontend E2E Tests:**
```bash
# All E2E tests
pnpm test:e2e

# Specific test file
pnpm test:e2e tests-frontend/e2e/prompt-builder/prompt-rating.e2e.ts

# Debug mode
pnpm test:e2e --debug

# Headed mode (visible browser)
pnpm test:e2e --headed

# Single test
pnpm test:e2e --grep "submits prompt rating via API"
```

---

## Success Criteria

### Backend Tests
- ✅ All service methods tested with edge cases
- ✅ All API controllers tested with authorisation & validation
- ✅ Database persistence verified for all rating operations
- ✅ Calculation accuracy verified with known datasets
- ✅ Null value handling tested comprehensively

### E2E Tests
- ✅ Rating components render and function correctly
- ✅ Rating submissions reach database successfully
- ✅ Analytics events fire with correct properties
- ✅ Display mode preferences persist across sessions
- ✅ Framework switching tracked accurately
- ✅ Complete user journeys work end-to-end

### Code Coverage Goals
- **Services:** 90%+ coverage
- **Controllers:** 85%+ coverage
- **E2E User Flows:** All critical paths covered

---

## Files to Create

### Backend Tests (Pest)
1. `tests/Unit/Services/FrameworkAnalyticsServiceTest.php` - ~300 lines
2. `tests/Unit/Services/QuestionAnalyticsServiceTest.php` - ~400 lines
3. `tests/Unit/Services/PromptQualityServiceTest.php` - ~350 lines
4. `tests/Feature/Api/PromptRatingControllerTest.php` - ~200 lines
5. `tests/Feature/Api/QuestionRatingControllerTest.php` - ~200 lines
6. `tests/Feature/Api/UserPreferenceControllerTest.php` - ~180 lines
7. `tests/Helpers/AnalyticsTestHelpers.php` - ~100 lines

### Frontend E2E Tests (Playwright)
1. `tests-frontend/e2e/components/prompt-rating.e2e.ts` - ~200 lines
2. `tests-frontend/e2e/prompt-builder/prompt-rating.e2e.ts` - ~250 lines
3. `tests-frontend/e2e/prompt-builder/question-rating.e2e.ts` - ~300 lines
4. `tests-frontend/e2e/prompt-builder/display-mode-preference.e2e.ts` - ~200 lines
5. `tests-frontend/e2e/prompt-builder/framework-switching.e2e.ts` - ~180 lines
6. `tests-frontend/e2e/journeys/complete-rating-flow.e2e.ts` - ~250 lines
7. `tests-frontend/e2e/helpers/analytics.ts` - ~100 lines

### Backend Test Support
1. `app/Http/Controllers/Test/AnalyticsTestController.php` - ~150 lines
2. Update `routes/api.php` with test routes - ~15 lines

**Total Estimated Lines:** ~3,500 lines of test code

---

## Implementation Order

1. **Week 1:** Backend Unit Tests (Phases 1)
   - Day 1-2: FrameworkAnalyticsService tests
   - Day 3-4: QuestionAnalyticsService tests
   - Day 5: PromptQualityService tests

2. **Week 2:** Backend Feature Tests & Test Support (Phases 2 & 6)
   - Day 1: Test endpoints controller
   - Day 2-3: API controller tests (all 3)
   - Day 4-5: Test helper functions

3. **Week 3:** Frontend E2E Tests (Phases 3 & 4)
   - Day 1: Component tests
   - Day 2-3: Integration tests
   - Day 4: Journey tests
   - Day 5: Helper functions & cleanup

4. **Week 4:** Polish & Documentation
   - Fix failing tests
   - Achieve coverage targets
   - Document test patterns
   - Update README

---

## Verification Checklist

After implementing all tests:

- [ ] All backend unit tests pass
- [ ] All backend feature tests pass
- [ ] All E2E tests pass
- [ ] Code coverage meets targets (run `pest --coverage`)
- [ ] No flaky tests (run suite 3 times, all should pass)
- [ ] Test execution time reasonable (<5 min backend, <10 min E2E)
- [ ] CI/CD integration configured (if applicable)
- [ ] Test documentation updated in README
- [ ] Test helper functions documented
- [ ] Test endpoints secured properly

---

## Related Documentation

- **Manual Testing Plan:** `docs/analytics-manual-testing.md`
- **Analytics Events Spec:** `docs/ANALYTICS-EVENTS.md`
- **E2E Test Setup:** `docs/E2E-TEST-SETUP.md`
- **Workflow Stages:** `docs/workflow_stages.md`
