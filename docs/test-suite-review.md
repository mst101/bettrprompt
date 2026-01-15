# Pest Test Suite Critical Review

**Date:** January 2026
**Status:** Comprehensive analysis of 47 test files (~8,700 lines)

---

## Executive Summary

Your test suite shows strong foundational patterns but has **significant gaps in coverage** and **some areas of duplication**. Key findings:

| Category | Status | Details |
|----------|--------|---------|
| **Current Coverage** | ✅ Good | 47 test files, ~8,700 lines covering auth, n8n, databases |
| **Duplication** | ⚠️ Moderate | Language tests overlap, webhook auth duplicated, user setup repeated |
| **Legacy Tests** | ⚠️ Yes | `LanguagePersistenceSimpleTest.php` completely redundant |
| **Security** | ❌ Critical | `EncryptionService` & `RecoveryPhraseService` untested |
| **Core Logic** | ❌ Major | `PromptBuilderController` (1,230 lines) lacks tests |
| **Services** | ❌ High | 16/20 services untested (analytics, geolocation, etc.) |
| **Form Requests** | ❌ High | All 37 form requests lack dedicated tests |

---

## Test Quality Assessment

### ✅ Best Practices Observed

1. **Isolation & Cleanup**
   - Proper use of `RefreshDatabase` for Feature tests
   - `DatabaseTransactions` for Unit tests (faster rollback)
   - Tests don't depend on each other

2. **Organisation & Readability**
   - Pest `describe()` blocks organise tests logically
   - Clear, descriptive test names in most cases
   - Consistent use of `beforeEach()` for shared setup

3. **Comprehensive Testing**
   - Good edge case coverage (e.g., `CurrencyUpdateTest.php` has 70+ edge cases)
   - Validation testing well covered
   - Authorization checks present

4. **Helper Functions**
   - Custom helpers in `tests/Pest.php` reduce duplication
   - `webhookPost()`, `createSmartFramework()`, etc. are well-named
   - `PromptRunBuilder` fluent interface

5. **Assertions**
   - Proper use of Event, Queue, and Inertia assertions
   - Database assertions verify state changes
   - HTTP status codes explicitly checked

---

## Issues Found

### 1. Duplicate & Redundant Tests

#### 1.1 Completely Overlapping Test Files

**File:** `tests/Feature/LanguagePersistenceSimpleTest.php` (83 lines, 3 tests)

Duplicates functionality already in `tests/Feature/LanguagePersistenceTest.php` (222 lines, 12 tests):

| Test | Simple | Detailed |
|------|--------|----------|
| Authenticated user language persists | ❌ Yes | ✅ Yes |
| User can update language multiple times | ❌ Yes | ✅ Yes |
| Language preference uses cache | ❌ Yes | ✅ Yes |

**Recommendation:** Delete `LanguagePersistenceSimpleTest.php` entirely. The "detailed" version is more comprehensive.

```bash
# Command to remove
git rm tests/Feature/LanguagePersistenceSimpleTest.php
```

---

#### 1.2 Duplicated Setup Code

**Issue:** N8n webhook authentication duplicated across 3 files:

```php
// Repeated in:
// - tests/Feature/N8nWebhookTest.php:10-12
// - tests/Feature/N8nWorkflowIntegrationTest.php (same pattern)
// - tests/Feature/N8nEnhancedErrorHandlingTest.php (same pattern)

$this->validSecret = 'test-webhook-secret-123';
config(['services.n8n.webhook_secret' => $this->validSecret]);
```

**Solution:** Extract to `tests/Pest.php`:

```php
function setupN8nWebhookAuth(): string
{
    $secret = 'test-webhook-secret-123';
    config(['services.n8n.webhook_secret' => $secret]);
    return $secret;
}
```

Then in tests:

```php
beforeEach(function () {
    $this->validSecret = setupN8nWebhookAuth();
});
```

---

#### 1.3 Repeated User Factory Setup

**Issue:** Identical personality configuration in 7+ test files:

```php
// Found in: CreateTest.php, ShowTest.php, RetryTest.php,
// OptimiseTest.php, ChildOperationsTest.php, etc.

beforeEach(function () {
    $this->user = User::factory()->create([
        'personality_type' => 'INTJ-A',
        'trait_percentages' => [
            'mind' => 75,
            'energy' => 55,
            'nature' => 80,
            'tactics' => 70,
            'identity' => 65,
        ],
    ]);
});
```

**Solution:** Add to `database/factories/UserFactory.php`:

```php
public function withPersonality(string $type = 'INTJ-A'): static
{
    return $this->state(fn (array $attributes) => [
        'personality_type' => $type,
        'trait_percentages' => [
            'mind' => 75,
            'energy' => 55,
            'nature' => 80,
            'tactics' => 70,
            'identity' => 65,
        ],
    ]);
}
```

Usage:

```php
beforeEach(function () {
    $this->user = User::factory()->withPersonality()->create();
});
```

---

### 2. Tests That Don't Do What They Say

#### 2.1 Misleading Test Name

**File:** `tests/Feature/N8nWebhookTest.php:231`

```php
test('webhook is protected by rate limiting middleware', function () {
    // This test doesn't actually verify rate limiting
    // It just checks the endpoint handles rapid requests without crashing
    for ($i = 0; $i < 5; $i++) {
        $response = webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => '1_completed',
        ]);
        $response->assertStatus(200);
    }
});
```

**Issue:** Test name implies rate limiting behaviour is verified, but the test just checks the endpoint doesn't crash.

**Fix:** Rename and add comment:

```php
test('webhook handles rapid requests without error', function () {
    // Rate limiting is configured via middleware and tested in acceptance tests
    // This verifies the endpoint itself handles concurrent requests gracefully
    for ($i = 0; $i < 5; $i++) {
        $response = webhookPost([...]);
        $response->assertStatus(200);
    }
});
```

---

#### 2.2 Ineffective Timeout Test

**File:** `tests/Unit/N8nClientRetryLogicTest.php:171`

```php
test('respects 30 second timeout per request', function () {
    // This doesn't actually test timeout behaviour
    // It just makes a successful request
    Http::fake(['*' => Http::response(['success' => true], 200)]);
    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);
    expect($result['success'])->toBeTrue();
});
```

**Issue:** Doesn't verify timeout is actually being applied.

**Options:**
1. **Delete** if timeout is configuration (not logic)
2. **Rewrite** to test actual timeout behaviour using HTTP::sequence() or mocking

**Recommendation:** Delete this test. Timeouts are configuration; they're tested by integration tests that validate the HTTP client respects its config.

---

### 3. Naming Inconsistencies

**Issue:** Mix of naming styles makes test output inconsistent:

```php
// Present tense with article (less common)
test('user can create prompt run', function () { ... });

// Present tense without article (more common)
test('creates prompt run successfully', function () { ... });

// With action description
test('create prompt run validates task description required', function () { ... });
```

**Recommendation:** Standardise on **present tense without articles**:

| ❌ Avoid | ✅ Use |
|---------|--------|
| `user can create prompt run` | `creates prompt run successfully` |
| `validates task description is required` | `validates task description required` |
| `returns 403 for unauthorised users` | `returns forbidden for unauthorised users` |
| `should save personality traits` | `saves personality traits` |

**Files affected:** ~15-20 test files with inconsistent naming

---

### 4. Code Quality Issues

#### 4.1 PHPUnit vs Pest Style Mix

**File:** `tests/Unit/Services/WorkflowVariantServiceTest.php`

Uses old PHPUnit style:

```php
$this->assertIsArray($variants);
$this->assertArrayHasKey('single-pass', $variants);
```

While other Unit tests use modern Pest style:

```php
expect($variants)->toBeArray()
    ->toHaveKey('single-pass');
```

**Fix:** Convert to Pest style for consistency.

---

#### 4.2 Magic Numbers Without Explanation

**File:** Multiple test files

```php
// tests/Unit/N8nClientRetryLogicTest.php
Http::assertSentCount(3); // Why 3?

// tests/Feature/PromptBuilder/CreateTest.php
expect(PromptRun::where('visitor_id', $visitor->id)->count())->toBe(1); // Why checking exactly 1?
```

**Solution:** Use named constants or clear comments:

```php
const MAX_N8N_RETRIES = 3;
Http::assertSentCount(self::MAX_N8N_RETRIES); // Initial + 2 retries
```

---

#### 4.3 Database Seeding Performance

**File:** `tests/Feature/CurrencyUpdateTest.php:14-18`

```php
beforeEach(function () {
    (new CurrencySeeder)->run();
    (new PricesTableSeeder)->run();
});
```

Runs **2 seeders × 71 tests = 142 seeding operations**

**Impact:** Significant slowdown

**Solution:**

Option 1: Use `setUpBeforeClass()` (Laravel 8+):
```php
public static function setUpBeforeClass(): void
{
    parent::setUpBeforeClass();
    (new CurrencySeeder)->run();
    (new PricesTableSeeder)->run();
}
```

Option 2: Mock prices in tests that don't need them:
```php
test('currency validation', function () {
    // Only validation, not price lookup
    $this->mock(PriceProvider::class);
});
```

---

### 5. Missing Test Documentation

**File:** `tests/Feature/LanguagePersistenceTest.php:36-52`

```php
// Uses direct controller instantiation - why?
$request = \Illuminate\Http\Request::create('/gb/visitor/language', 'PATCH', [
    'language_code' => 'de-DE',
]);
$request->cookies->set('visitor_id', (string) $visitor->id);
$controller = new \App\Http\Controllers\VisitorController;
$response = $controller->updateLanguage($request);
```

**Issue:** No explanation why direct controller call is needed.

**Fix:** Add comment:

```php
// Direct controller call required because visitor routes don't have proper
// HTTP test helpers (patchJsonCountry assumes authenticated user)
$request = \Illuminate\Http\Request::create(...);
```

---

## Performance Issues

### Database Operations

**File:** `tests/Feature/LanguagePersistenceTest.php:116-136`

```php
test('user can update language multiple times', function () {
    $user = User::factory()->create(['language_code' => 'en-GB']);

    // First update
    $this->actingAs($user)->patchJsonCountry('/profile/language', ['language_code' => 'fr-FR']);
    $user->refresh(); // ← Database hit

    // Second update
    $this->actingAs($user)->patchJsonCountry('/profile/language', ['language_code' => 'de-DE']);
    $user->refresh(); // ← Database hit

    // Third update
    $this->actingAs($user)->patchJsonCountry('/profile/language', ['language_code' => 'es-ES']);
    $user->refresh(); // ← Database hit
});
```

**Optimised version:**

```php
test('user can update language multiple times', function () {
    $user = User::factory()->create(['language_code' => 'en-GB']);

    foreach (['fr-FR', 'de-DE', 'es-ES'] as $lang) {
        $this->actingAs($user)
            ->patchJsonCountry('/profile/language', ['language_code' => $lang]);

        expect($user->fresh()->language_code)->toBe($lang);
    }
});
```

---

## Critical Test Coverage Gaps

### 1. Security-Critical Services (URGENT ⚠️)

#### EncryptionService

**File:** `app/Services/EncryptionService.php`

**What's missing:** Zero tests

**Business logic:**
- AES-256-GCM encryption/decryption
- PBKDF2 key derivation from password
- DEK (Data Encryption Key) wrapping/unwrapping
- Recovery phrase encryption

**Why it matters:** Handles all user data encryption. Bugs here expose user data.

**Required tests:**

```php
// tests/Unit/Services/EncryptionServiceTest.php

test('encrypts and decrypts data correctly', function () {
    $service = new EncryptionService();
    $original = 'sensitive data';

    $encrypted = $service->encrypt($original);
    expect($encrypted)->not->toBe($original);

    $decrypted = $service->decrypt($encrypted);
    expect($decrypted)->toBe($original);
});

test('different passwords produce different ciphertexts', function () {
    $service = new EncryptionService();
    $data = 'test';

    $cipher1 = $service->encrypt($data, 'password1');
    $cipher2 = $service->encrypt($data, 'password2');

    expect($cipher1)->not->toBe($cipher2);
});

test('invalid password fails to decrypt', function () {
    $service = new EncryptionService();
    $encrypted = $service->encrypt('data', 'correct-password');

    expect(fn () => $service->decrypt($encrypted, 'wrong-password'))
        ->toThrow(DecryptionException::class);
});

test('tampered ciphertext fails authentication', function () {
    $service = new EncryptionService();
    $encrypted = $service->encrypt('data');
    $tampered = substr_replace($encrypted, 'X', 10, 1);

    expect(fn () => $service->decrypt($tampered))
        ->toThrow(DecryptionException::class);
});

test('DEK wrapping generates unique ciphertexts', function () {
    $service = new EncryptionService();
    $dek = random_bytes(32);

    $wrapped1 = $service->wrapDEK($dek);
    $wrapped2 = $service->wrapDEK($dek);

    // Each wrap should produce different ciphertext (different IV)
    expect($wrapped1)->not->toBe($wrapped2);

    // But both should unwrap to same DEK
    expect($service->unwrapDEK($wrapped1))->toBe($dek);
    expect($service->unwrapDEK($wrapped2))->toBe($dek);
});
```

---

#### RecoveryPhraseService

**File:** `app/Services/RecoveryPhraseService.php`

**What's missing:** Zero tests

**Business logic:**
- Generate 12-word recovery phrases
- Validate words against 400+ word list
- Handle normalisation (lowercase, trim, etc.)

**Required tests:**

```php
// tests/Unit/Services/RecoveryPhraseServiceTest.php

test('generates 12 unique words', function () {
    $service = new RecoveryPhraseService();
    $phrase = $service->generate();

    $words = explode(' ', $phrase);
    expect($words)->toHaveCount(12);
    expect(count(array_unique($words)))->toBe(12); // All unique
});

test('all generated words are from valid word list', function () {
    $service = new RecoveryPhraseService();

    for ($i = 0; $i < 10; $i++) {
        $phrase = $service->generate();
        $words = explode(' ', $phrase);

        foreach ($words as $word) {
            expect($service->isValidWord($word))->toBeTrue();
        }
    }
});

test('validates against invalid words', function () {
    $service = new RecoveryPhraseService();

    expect($service->isValidWord('xyz'))->toBeFalse();
    expect($service->isValidWord('notaword'))->toBeFalse();
});

test('handles word normalisation', function () {
    $service = new RecoveryPhraseService();

    // Should accept with normalisation
    expect($service->isValidWord('APPLE'))->toBeTrue();
    expect($service->isValidWord('  apple  '))->toBeTrue();
});

test('validates full recovery phrase', function () {
    $service = new RecoveryPhraseService();
    $phrase = $service->generate();

    expect($service->validatePhrase($phrase))->toBeTrue();
    expect($service->validatePhrase('invalid phrase with wrong words'))->toBeFalse();
});
```

---

### 2. Core Business Logic (HIGH PRIORITY)

#### PromptBuilderController

**File:** `app/Http/Controllers/PromptBuilderController.php` (1,230 lines!)

**What's missing:** No dedicated controller tests (only indirect tests through webhooks)

**Business logic:**
- 3-stage workflow orchestration (pre-analysis → analysis → generation)
- Question answering and navigation (next/previous)
- Child prompt creation from parent with modified answers
- Retry logic for failed workflows
- User/visitor access control

**Current tests:** Scattered across:
- `PromptBuilder/CreateTest.php` - Only creation, no full workflow
- `PromptBuilder/ShowTest.php` - Minimal
- `N8nWebhookTest.php` - Tests webhook updates, not controller logic

**Required additional tests:**

```php
// tests/Feature/PromptBuilder/WorkflowOrchestrationTest.php

describe('Complete 3-Stage Workflow', function () {
    test('completes full workflow from pre-analysis to generation', function () {
        $user = User::factory()->withPersonality()->create();

        // Stage 0: Pre-analysis
        $response = $this->actingAs($user)
            ->postCountry(route('prompt-builder.pre-analyse', [], false), [
                'task_description' => 'Create a strategic business plan',
            ]);

        $promptRun = PromptRun::where('user_id', $user->id)->first();
        expect($promptRun->workflow_stage)->toBe('0_processing');

        // Simulate webhook completing pre-analysis
        webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => '0_completed',
            'pre_analysis_questions' => [
                ['question' => 'What is the scope?'],
                ['question' => 'Who are stakeholders?'],
            ],
        ]);

        $promptRun->refresh();
        expect($promptRun->workflow_stage)->toBe('0_completed');

        // Stage 1: Answer framework questions
        $response = $this->actingAs($user)
            ->patchCountry(
                route('prompt-builder.answer', $promptRun, false),
                ['answer' => 'The scope is company-wide']
            );

        // ... continue to final stage
    });

    test('user cannot access other users workflows', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $promptRun = PromptRun::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->getCountry(route('prompt-builder.show', $promptRun, false));

        $response->assertForbidden();
    });
});

describe('Question Navigation', function () {
    test('advances to next question', function () { ... });
    test('goes back to previous question', function () { ... });
    test('skips optional questions', function () { ... });
});

describe('Retry Logic', function () {
    test('allows retrying failed pre-analysis', function () { ... });
    test('allows retrying failed analysis', function () { ... });
});
```

---

### 3. Analytics Services (MEDIUM PRIORITY)

Three services with zero tests:

#### WorkflowAnalyticsService

Records: workflow starts, completions, failures, timeouts, retries

**Required tests:** Start/completion/failure/timeout recording with proper metrics

#### QuestionAnalyticsService

Records: answer rates, skip rates, time-to-answer, rating correlations

**Required tests:** Rate calculations, correlation logic

#### FrameworkSelectionService

Records: framework acceptance rates, ratings, usage metrics

**Required tests:** Acceptance rate calculations, rating aggregation

---

### 4. Geolocation Service (MEDIUM PRIORITY)

**File:** `app/Services/GeolocationService.php`

**Business logic:**
- MaxMind IP lookup
- Coordinate anonymisation
- Private IP detection
- Development defaults
- Currency/language mapping

**Required tests:**
- Mock MaxMind, test IP → country mapping
- Private IPs (127.0.0.1, 192.168.x.x) return defaults
- Coordinates anonymised (precision reduced)
- Development IPs handled correctly

---

### 5. Form Request Validation (HIGH PRIORITY)

**Issue:** All 37 form requests have zero dedicated tests

**High-priority requests:**
1. `PromptBuilderAnalyseRequest` - Task description validation
2. `GeneratePromptRequest` - Framework and answers
3. `UpdateLocationRequest` - Location data + country/currency
4. `UpdatePersonalityTypeRequest` - Personality + trait percentages
5. `StoreFeedbackRequest` - Feedback data
6. `CreateChildFromAnswersRequest` - Child prompt creation

**Test pattern:**

```php
// tests/Feature/FormRequests/PromptBuilderAnalyseRequestTest.php

test('validates task_description is required', function () {
    $response = $this->actingAs(User::factory()->create())
        ->postCountry(route('prompt-builder.pre-analyse', [], false), []);

    $response->assertSessionHasErrors(['task_description']);
});

test('validates task_description minimum length is 10', function () {
    $response = $this->actingAs(User::factory()->create())
        ->postCountry(route('prompt-builder.pre-analyse', [], false), [
            'task_description' => 'short',
        ]);

    $response->assertSessionHasErrors(['task_description']);
});

test('accepts valid task_description', function () {
    $response = $this->actingAs(User::factory()->create())
        ->postCountry(route('prompt-builder.pre-analyse', [], false), [
            'task_description' => 'This is a long enough task description for testing',
        ]);

    $response->assertRedirect();
});

test('transforms camelCase request to snake_case in form request', function () {
    // If using camelCase in Vue but form request expects snake_case
    // Test the transformation
});
```

---

## Implementation Checklist

### Phase 1: Cleanup (1-2 days)

- [ ] Delete `tests/Feature/LanguagePersistenceSimpleTest.php`
- [ ] Extract n8n helper to `tests/Pest.php`
- [ ] Add UserFactory `withPersonality()` state
- [ ] Update 7+ PromptBuilder test files to use factory state
- [ ] Fix test name in `N8nWebhookTest.php:231`
- [ ] Delete ineffective timeout test from `N8nClientRetryLogicTest.php:171`

### Phase 2: Security Tests (3-4 days)

- [ ] Create `tests/Unit/Services/EncryptionServiceTest.php`
- [ ] Create `tests/Unit/Services/RecoveryPhraseServiceTest.php`

### Phase 3: Core Logic Tests (4-5 days)

- [ ] Create `tests/Feature/PromptBuilder/WorkflowOrchestrationTest.php`
- [ ] Create `tests/Feature/PromptBuilder/NavigationTest.php`
- [ ] Create analytics service tests (3 files)
- [ ] Create `tests/Unit/Services/GeolocationServiceTest.php`

### Phase 4: Validation Tests (3-4 days)

- [ ] Create form request tests (4-5 high-priority files)

### Phase 5: Quality Improvements (2-3 days)

- [ ] Convert `WorkflowVariantServiceTest` to Pest style
- [ ] Standardise test naming (15-20 files)
- [ ] Optimise `CurrencyUpdateTest.php` seeding
- [ ] Add documentation to complex test helpers
- [ ] Replace magic numbers with named constants

### Phase 6: Organisation (2 days)

- [ ] Reorganise Feature tests into subdirectories
- [ ] Create shared test traits
- [ ] Add test data builders

---

## Testing Guidelines Going Forward

### For New Features

1. **Always write tests first** (TDD)
2. **Controllers:**
   - Feature test for each endpoint
   - Test happy path + error paths
   - Test authorization
3. **Services:**
   - Unit tests for all public methods
   - Mock external dependencies
   - Test error conditions
4. **Form Requests:**
   - Test all validation rules
   - Test data transformations
   - Test edge cases
5. **Models:**
   - Test relationships (especially foreign keys)
   - Test scopes
   - Test business logic methods

### Naming Conventions

**Present tense, no articles:**

```php
test('creates user with valid data', function () { ... });
test('validates email is required', function () { ... });
test('returns forbidden for unauthorised users', function () { ... });
test('saves personality traits correctly', function () { ... });
```

### HTML Test Attributes

**Always use kebab-case:**

```html
<!-- ✓ CORRECT -->
<button data-testid="submit-button" id="user-form">Submit</button>

<!-- ✗ WRONG -->
<button data-testid="submitButton" id="userForm">Submit</button>
```

### Test Structure (Arrange-Act-Assert)

```php
test('updates user profile', function () {
    // ARRANGE
    $user = User::factory()->create(['name' => 'John']);

    // ACT
    $response = $this->actingAs($user)
        ->patchCountry(route('profile.update', [], false), [
            'name' => 'Jane',
        ]);

    // ASSERT
    $response->assertRedirect();
    expect($user->fresh()->name)->toBe('Jane');
});
```

### Performance Best Practices

- Use `RefreshDatabase` for Feature tests (full database reset)
- Use `DatabaseTransactions` for Unit tests (faster rollback)
- Only seed when necessary (avoid in Unit tests)
- Use factories over direct model creation
- Cache expensive setup (MaxMind databases, etc.)

---

## Summary Statistics

| Metric | Value |
|--------|-------|
| Total test files | 47 |
| Total test lines | ~8,700 |
| Feature tests | 35 |
| Unit tests | 12 |
| Services tested | 4/20 (20%) |
| Controllers tested | Partial (n8n focused) |
| Form requests tested | 0/37 (0%) |
| Models tested | 5/38 (13%) |

---

## Next Steps

1. **Review this document** with your team
2. **Prioritise Phase 1** (cleanup) - quick wins
3. **Start Phase 2** (security) - highest risk items
4. **Track progress** using this checklist
5. **Consider CI/CD integration** - run tests on every commit

For questions or implementation assistance, refer to the specific sections above or consult the code examples provided.
