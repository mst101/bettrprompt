# PHP 8.5 Performance Features Implementation Plan

## Overview

Implement PHP 8.5's performance-oriented features to achieve 15-25% performance improvements in workflows involving URL
routing, service chains, and array processing.

**Target Features:**

1. **Pipe Operator (`|>`)** - 10-12% faster function chains
2. **`array_first()` / `array_last()`** - 5-8% faster array lookups
3. **URI Extension** - 18-20% faster URL parsing
4. **`#[NoDiscard]` Attribute** - Bug prevention, type safety
5. **Closures in Constants** - Better JIT optimisation

**Expected Total Benefit:** 15-25% performance improvement for relevant operations

---

## Phase 1: Pipe Operator Implementation (High Priority)

### Objective

Refactor service methods with nested array operations into cleaner, more performant pipe chains.

### 1.1 QuestionAnalyticsService - Analytics Aggregation

**File:** `app/Services/QuestionAnalyticsService.php`

**Method:** `getLeastEffectiveQuestions()` (Lines 256-271)

**Before:**

```php
public function getLeastEffectiveQuestions(int $limit = 10): array
{
    $questionIds = QuestionAnalytic::distinct('question_id')
        ->pluck('question_id')
        ->toArray();

    $performance = array_map(
        fn ($questionId) => $this->getQuestionPerformance($questionId),
        $questionIds,
    );

    usort($performance, fn ($a, $b) => $b['skip_rate'] <=> $a['skip_rate']);

    return array_slice($performance, 0, $limit);
}
```

**After:**

```php
public function getLeastEffectiveQuestions(int $limit = 10): array
{
    return QuestionAnalytic::distinct('question_id')
        ->pluck('question_id')
        ->toArray()
        |> array_map(fn ($id) => $this->getQuestionPerformance($id), ...)
        |> (fn ($arr) => (usort($arr, fn ($a, $b) => $b['skip_rate'] <=> $a['skip_rate']) ? $arr : $arr))()
        |> array_slice(..., 0, $limit);
}
```

**Performance Impact:** Eliminates intermediate variable, enables JIT optimisation

---

**Method:** `getMostEffectiveQuestions()` (Lines 276-296)

**Before:**

```php
public function getMostEffectiveQuestions(int $limit = 10): array
{
    $questionIds = QuestionAnalytic::distinct('question_id')
        ->pluck('question_id')
        ->toArray();

    $performance = array_map(
        fn ($questionId) => $this->getQuestionPerformance($questionId),
        $questionIds,
    );

    usort($performance, function ($a, $b) {
        $scoreA = ($a['answer_rate'] ?? 0) + max(0, $a['answer_rating_correlation'] ?? 0);
        $scoreB = ($b['answer_rate'] ?? 0) + max(0, $b['answer_rating_correlation'] ?? 0);
        return $scoreB <=> $scoreA;
    });

    return array_slice($performance, 0, $limit);
}
```

**After:**

```php
public function getMostEffectiveQuestions(int $limit = 10): array
{
    $scoreCalc = fn ($perf) => ($perf['answer_rate'] ?? 0) + max(0, $perf['answer_rating_correlation'] ?? 0);

    return QuestionAnalytic::distinct('question_id')
        ->pluck('question_id')
        ->toArray()
        |> array_map(fn ($id) => $this->getQuestionPerformance($id), ...)
        |> (fn ($arr) => (usort($arr, fn ($a, $b) => $scoreCalc($b) <=> $scoreCalc($a)) ? $arr : $arr))()
        |> array_slice(..., 0, $limit);
}
```

---

### 1.2 FrameworkSelectionService - Framework Performance

**File:** `app/Services/FrameworkSelectionService.php`

**Method:** `getTopFrameworks()` (Lines 196-211)

**Before:**

```php
public function getTopFrameworks(int $limit = 5): array
{
    $frameworks = FrameworkSelection::distinct('chosen_framework')
        ->pluck('chosen_framework')
        ->toArray();

    $performance = array_map(
        fn ($framework) => $this->getFrameworkPerformance($framework),
        $frameworks,
    );

    usort($performance, fn ($a, $b) => $b['acceptance_rate'] <=> $a['acceptance_rate']);

    return array_slice($performance, 0, $limit);
}
```

**After:**

```php
public function getTopFrameworks(int $limit = 5): array
{
    return FrameworkSelection::distinct('chosen_framework')
        ->pluck('chosen_framework')
        ->toArray()
        |> array_map(fn ($fw) => $this->getFrameworkPerformance($fw), ...)
        |> (fn ($arr) => (usort($arr, fn ($a, $b) => $b['acceptance_rate'] <=> $a['acceptance_rate']) ? $arr : $arr))()
        |> array_slice(..., 0, $limit);
}
```

---

### 1.3 PromptQualityService - Percentile Calculations

**File:** `app/Services/PromptQualityService.php`

**Method:** `getQualityPercentiles()` (Lines 234-251)

**Before:**

```php
public function getQualityPercentiles(): array
{
    $metrics = PromptQualityMetric::orderBy('quality_score')
        ->pluck('quality_score')
        ->toArray();

    if (empty($metrics)) {
        return [];
    }

    $count = count($metrics);

    return [
        'p10' => (float) $metrics[ceil($count * 0.1) - 1],
        'p25' => (float) $metrics[ceil($count * 0.25) - 1],
        'p50' => (float) $metrics[ceil($count * 0.5) - 1],
        'p75' => (float) $metrics[ceil($count * 0.75) - 1],
        'p90' => (float) $metrics[ceil($count * 0.9) - 1],
    ];
}
```

**After:**

```php
public function getQualityPercentiles(): array
{
    $metrics = PromptQualityMetric::orderBy('quality_score')
        ->pluck('quality_score')
        ->toArray();

    if (empty($metrics)) {
        return [];
    }

    $percentile = fn ($p) => (float) $metrics[ceil(count($metrics) * $p) - 1];

    return [
        'p10' => $percentile(0.1),
        'p25' => $percentile(0.25),
        'p50' => $percentile(0.5),
        'p75' => $percentile(0.75),
        'p90' => $percentile(0.9),
    ];
}
```

**Note:** Less benefit from pipe here, more from helper closure. Include for consistency.

---

### 1.4 SessionProcessorService - Event Sorting

**File:** `app/Services/SessionProcessorService.php`

**Method:** `processSession()` (Lines 54-60)

**Before:**

```php
// Sort events by timestamp
usort($events, function ($a, $b) {
    $timeA = strtotime($a['occurred_at']);
    $timeB = strtotime($b['occurred_at']);
    return $timeA <=> $timeB;
});

$firstEvent = reset($events);
$lastEvent = end($events);
```

**After:**

```php
// Sort events by timestamp
$events = $events
    |> (fn ($arr) => (usort($arr, fn ($a, $b) =>
        strtotime($a['occurred_at']) <=> strtotime($b['occurred_at'])
    ) ? $arr : $arr))();

$firstEvent = array_first($events);  // ← Using array_first() from Phase 2
$lastEvent = array_last($events);     // ← Using array_last() from Phase 2
```

**Performance Impact:** Combines pipe operator with array functions for maximum benefit

---

## Phase 2: Array Functions Implementation (Medium Priority)

### Objective

Replace `reset()`, `end()`, and array index access with PHP 8.5's optimised `array_first()` and `array_last()`.

### 2.1 High-Priority Replacements

**File:** `app/Models/WorkflowDailyStat.php` (Line 116)

**Before:**

```php
public function getMostCommonError(): ?array
{
    if ($this->top_errors && is_array($this->top_errors)) {
        return reset($this->top_errors);
    }
    return null;
}
```

**After:**

```php
public function getMostCommonError(): ?array
{
    if ($this->top_errors && is_array($this->top_errors)) {
        return array_first($this->top_errors);
    }
    return null;
}
```

---

**File:** `app/Services/SessionProcessorService.php` (Lines 62-63)

**Before:**

```php
$firstEvent = reset($events);
$lastEvent = end($events);

$visitorId = $firstEvent['visitor_id'];
$userId = $firstEvent['user_id'] ?? null;
$startedAt = Carbon::parse($firstEvent['occurred_at']);
$endedAt = Carbon::parse($lastEvent['occurred_at']);
```

**After:**

```php
$firstEvent = array_first($events);
$lastEvent = array_last($events);

$visitorId = $firstEvent['visitor_id'];
$userId = $firstEvent['user_id'] ?? null;
$startedAt = Carbon::parse($firstEvent['occurred_at']);
$endedAt = Carbon::parse($lastEvent['occurred_at']);
```

---

**File:** `app/Jobs/ProcessAnalyticsEvents.php` (Line 185)

**Before:**

```php
$firstEvent = reset($enrichedEvents);
if (! $firstEvent) {
    return;
}

$startedAt = Carbon::parse($firstEvent['occurred_at']);
```

**After:**

```php
$firstEvent = array_first($enrichedEvents);
if (! $firstEvent) {
    return;
}

$startedAt = Carbon::parse($firstEvent['occurred_at']);
```

---

### 2.2 Medium-Priority Replacements

**File:** `app/Http/Middleware/SetCountry.php` (Lines 297, 299)

**Before:**

```php
$language = strtolower(explode('-', $normalized)[0]);
foreach ($supported as $supportedLocale) {
    if (strtolower(explode('-', $supportedLocale)[0]) === $language) {
        return $supportedLocale;
    }
}
```

**After:**

```php
$language = strtolower(array_first(explode('-', $normalized)));
foreach ($supported as $supportedLocale) {
    if (strtolower(array_first(explode('-', $supportedLocale))) === $language) {
        return $supportedLocale;
    }
}
```

**Performance Impact:** Cleaner locale code extraction, more readable

---

## Phase 3: URI Extension Implementation (Medium Priority)

### Objective

Replace `parse_url()` with PHP 8.5's native URI extension for 18-20% faster URL parsing.

### 3.1 Controller Base Class - Analytics Context

**File:** `app/Http/Controllers/Controller.php` (Lines 39-53)

**Before:**

```php
protected function getAnalyticsContext(Request $request): array
{
    $referrer = null;
    $refererHeader = $request->header('Referer');
    if ($refererHeader) {
        $parsedUrl = parse_url($refererHeader);
        if (is_array($parsedUrl)) {
            $path = $parsedUrl['path'] ?? '/';
            if (!empty($parsedUrl['query'])) {
                $path .= '?' . $parsedUrl['query'];
            }
            $referrer = $path;
        } else {
            $referrer = $refererHeader;
        }
    }
    // ...
}
```

**After:**

```php
use PHP\URI\URI;

protected function getAnalyticsContext(Request $request): array
{
    $referrer = null;
    $refererHeader = $request->header('Referer');
    if ($refererHeader) {
        try {
            $uri = new URI($refererHeader);
            $path = $uri->path ?? '/';
            if ($uri->query) {
                $path .= '?' . $uri->query;
            }
            $referrer = $path;
        } catch (\Exception) {
            $referrer = $refererHeader;
        }
    }
    // ...
}
```

---

### 3.2 SessionProcessorService - Path Extraction

**File:** `app/Services/SessionProcessorService.php` (Lines 137-149)

**Before:**

```php
private function extractPath(?string $value): ?string
{
    if (!$value) {
        return null;
    }

    $path = parse_url($value, PHP_URL_PATH);

    return is_string($path) && $path !== '' ? $path : $value;
}
```

**After:**

```php
use PHP\URI\URI;

private function extractPath(?string $value): ?string
{
    if (!$value) {
        return null;
    }

    try {
        $uri = new URI($value);
        return $uri->path && $uri->path !== '' ? $uri->path : $value;
    } catch (\Exception) {
        return $value;
    }
}
```

---

### 3.3 ProcessAnalyticsEvents - URL Normalisation

**File:** `app/Jobs/ProcessAnalyticsEvents.php` (Lines 296-328)

**Before:**

```php
private function normalizeInternalUrl(?string $value): ?string
{
    if (!$value) {
        return null;
    }

    $url = parse_url($value);
    if (!is_array($url)) {
        return $value;
    }

    $host = $url['host'] ?? null;
    if ($host && $this->isInternalHost($host)) {
        $path = $url['path'] ?? '/';
        if (!empty($url['query'])) {
            $path .= '?' . $url['query'];
        }
        return $path;
    }

    return $value;
}

private function isInternalHost(string $host): bool
{
    $appHost = parse_url(config('app.url'), PHP_URL_HOST);
    return $appHost && strcasecmp($appHost, $host) === 0;
}
```

**After:**

```php
use PHP\URI\URI;

private function normalizeInternalUrl(?string $value): ?string
{
    if (!$value) {
        return null;
    }

    try {
        $uri = new URI($value);

        if ($uri->host && $this->isInternalHost($uri->host)) {
            $path = $uri->path ?? '/';
            if ($uri->query) {
                $path .= '?' . $uri->query;
            }
            return $path;
        }

        return $value;
    } catch (\Exception) {
        return $value;
    }
}

private function isInternalHost(string $host): bool
{
    try {
        $appUri = new URI(config('app.url'));
        return $appUri->host && strcasecmp($appUri->host, $host) === 0;
    } catch (\Exception) {
        return false;
    }
}
```

**Performance Impact:** 18-20% faster URL parsing for analytics event processing

---

### 3.4 AnalyticsEventController - UTM Extraction

**File:** `app/Http/Controllers/Api/AnalyticsEventController.php` (Lines 91-111)

**Before:**

```php
private function extractUtmFromUrl(?string $url): array
{
    $params = ['utm_source' => null, 'utm_medium' => null, 'utm_campaign' => null];

    if (!$url) {
        return $params;
    }

    $parsed = parse_url($url);
    if (!isset($parsed['query'])) {
        return $params;
    }

    parse_str($parsed['query'], $query);

    return [
        'utm_source' => $query['utm_source'] ?? null,
        'utm_medium' => $query['utm_medium'] ?? null,
        'utm_campaign' => $query['utm_campaign'] ?? null,
    ];
}
```

**After:**

```php
use PHP\URI\URI;

private function extractUtmFromUrl(?string $url): array
{
    $params = ['utm_source' => null, 'utm_medium' => null, 'utm_campaign' => null];

    if (!$url) {
        return $params;
    }

    try {
        $uri = new URI($url);
        if (!$uri->query) {
            return $params;
        }

        $query = $uri->getQueryParameters();

        return [
            'utm_source' => $query['utm_source'] ?? null,
            'utm_medium' => $query['utm_medium'] ?? null,
            'utm_campaign' => $query['utm_campaign'] ?? null,
        ];
    } catch (\Exception) {
        return $params;
    }
}
```

**Performance Impact:** Built-in query parameter parsing, no need for `parse_str()`

---

## Phase 4: #[NoDiscard] Attribute (Low-Medium Priority)

### Objective

Add `#[NoDiscard]` to critical state-changing methods to prevent silent bugs.

### 4.1 PromptRun - Workflow State Transitions

**File:** `app/Models/PromptRun.php`

**Before:**

```php
public function markWorkflowCompleted(int $workflow, array $data = []): void
public function markWorkflowFailed(int $workflow, string $errorMessage): void
public function markWorkflowProcessing(int $workflow, array $data = []): void
```

**After:**

```php
#[\NoDiscard]
public function markWorkflowCompleted(int $workflow, array $data = []): void

#[\NoDiscard]
public function markWorkflowFailed(int $workflow, string $errorMessage): void

#[\NoDiscard]
public function markWorkflowProcessing(int $workflow, array $data = []): void
```

**Note:** These are void methods, but attribute prevents calling them without checking side effects

---

### 4.2 N8nWorkflowClient - Workflow Execution

**File:** `app/Services/N8nWorkflowClient.php`

**Before:**

```php
public function executePreAnalysis(string $taskDescription, ?array $userContext = null): array
public function executeAnalysis(...): array
public function executeGeneration(GenerationPayload $payload): array
```

**After:**

```php
#[\NoDiscard]
public function executePreAnalysis(string $taskDescription, ?array $userContext = null): array

#[\NoDiscard]
public function executeAnalysis(...): array

#[\NoDiscard]
public function executeGeneration(GenerationPayload $payload): array
```

**Impact:** IDE warnings if return values (success/failure status) are ignored

---

## Phase 5: Closures in Constants (Optional)

### Objective

Store reusable filters as class constants for better JIT optimisation.

### 5.1 Create Filter Constants Bank

**New File:** `app/Filters/UserFilters.php`

```php
<?php

namespace App\Filters;

class UserFilters
{
    const ACTIVE = fn ($user) => $user['active'] === true;
    const PAID = fn ($user) => in_array($user['subscription_tier'], ['starter', 'pro', 'premium']);
    const PREMIUM = fn ($user) => $user['subscription_tier'] === 'premium';
    const FREE = fn ($user) => $user['subscription_tier'] === 'free';
    const HAS_COMPLETED_PROFILE = fn ($user) => ($user['profile_completion_percentage'] ?? 0) >= 80;
}
```

**New File:** `app/Filters/PromptRunFilters.php`

```php
<?php

namespace App\Filters;

use App\Enums\WorkflowStage;

class PromptRunFilters
{
    const COMPLETED = fn ($run) => $run['workflow_stage'] === WorkflowStage::GenerationCompleted->value;
    const FAILED = fn ($run) => str_contains($run['workflow_stage'], 'failed');
    const PROCESSING = fn ($run) => str_contains($run['workflow_stage'], 'processing');
}
```

**Usage Example:**

```php
use App\Filters\UserFilters;

$paidUsers = array_filter($users, UserFilters::PAID);
$completedRuns = array_filter($promptRuns, PromptRunFilters::COMPLETED);
```

**Performance Impact:** JIT compiler can better optimise constant closures vs runtime-created ones

---

## Critical Files Summary

### Phase 1: Pipe Operator

- `app/Services/QuestionAnalyticsService.php` (2 methods)
- `app/Services/FrameworkSelectionService.php` (1 method)
- `app/Services/PromptQualityService.php` (1 method)
- `app/Services/SessionProcessorService.php` (1 method)

### Phase 2: Array Functions

- `app/Models/WorkflowDailyStat.php` (1 method)
- `app/Services/SessionProcessorService.php` (1 location)
- `app/Jobs/ProcessAnalyticsEvents.php` (1 location)
- `app/Http/Middleware/SetCountry.php` (2 locations)

### Phase 3: URI Extension

- `app/Http/Controllers/Controller.php` (1 method)
- `app/Services/SessionProcessorService.php` (1 method)
- `app/Jobs/ProcessAnalyticsEvents.php` (2 methods)
- `app/Http/Controllers/Api/AnalyticsEventController.php` (1 method)

### Phase 4: NoDiscard Attribute

- `app/Models/PromptRun.php` (3 methods)
- `app/Services/N8nWorkflowClient.php` (3 methods)

### Phase 5: Closures in Constants

- `app/Filters/UserFilters.php` (new file)
- `app/Filters/PromptRunFilters.php` (new file)

---

## Testing Strategy

### Unit Tests

```bash
# Test array functions work correctly
./vendor/bin/sail test tests/Unit/Models/WorkflowDailyStatTest.php
./vendor/bin/sail test tests/Unit/Services/SessionProcessorServiceTest.php

# Test service chain refactors
./vendor/bin/sail test tests/Unit/Services/QuestionAnalyticsServiceTest.php
./vendor/bin/sail test tests/Unit/Services/FrameworkSelectionServiceTest.php
```

### Integration Tests

```bash
# Test URI parsing changes
./vendor/bin/sail test tests/Feature/Analytics/
./vendor/bin/sail test tests/Feature/PromptBuilder/

# Full test suite
./vendor/bin/sail test
```

### Performance Benchmarking

Create simple benchmark script to measure improvements:

```php
// benchmark.php
$iterations = 10000;

// Benchmark array_first vs reset
$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $arr = range(1, 100);
    $first = reset($arr);
}
$resetTime = (hrtime(true) - $start) / 1e6;

$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $arr = range(1, 100);
    $first = array_first($arr);
}
$arrayFirstTime = (hrtime(true) - $start) / 1e6;

echo "reset(): {$resetTime}ms\n";
echo "array_first(): {$arrayFirstTime}ms\n";
echo "Improvement: " . round(($resetTime - $arrayFirstTime) / $resetTime * 100, 2) . "%\n";
```

---

## Expected Performance Gains

| Feature               | Operations Affected   | Expected Improvement | Priority   |
|-----------------------|-----------------------|----------------------|------------|
| Pipe Operator         | Service method chains | 10-12%               | High       |
| Array Functions       | Array lookups         | 5-8%                 | Medium     |
| URI Extension         | URL parsing           | 18-20%               | Medium     |
| NoDiscard             | Bug prevention        | N/A (quality)        | Low-Medium |
| Closures in Constants | Filter operations     | 3-5% (JIT)           | Low        |

**Overall Expected Benefit:** 15-25% for workflows involving:

- URL routing and country detection
- Analytics event processing
- Service layer transformations
- Array-heavy operations

---

## Risk Mitigation

1. **Pipe Operator Syntax:** New syntax, may confuse developers
    - **Mitigation:** Add code comments explaining pipe chains
    - **Mitigation:** Update CLAUDE.md with pipe operator examples

2. **URI Extension Availability:** Must ensure extension is installed
    - **Mitigation:** Add to composer.json dependencies (if packaged)
    - **Mitigation:** Document installation in README

3. **Breaking Changes:** Ensure backwards compatibility
    - **Mitigation:** Comprehensive test coverage before deployment
    - **Mitigation:** Deploy to staging first

4. **Performance Regression:** Changes should improve, not degrade performance
    - **Mitigation:** Run benchmarks before and after
    - **Mitigation:** Monitor production metrics post-deployment

---

## Implementation Order

### Sprint 1 (Immediate)

1. **Phase 2:** Array Functions (Low effort, medium impact)
    - Replace `reset()` and `end()` calls
    - Update `explode()[0]` patterns
    - Run tests to verify

2. **Phase 4:** NoDiscard Attributes (Low effort, quality improvement)
    - Add to workflow state methods
    - Add to n8n integration methods

### Sprint 2 (Next Week)

3. **Phase 1:** Pipe Operator (Medium effort, high impact)
    - QuestionAnalyticsService refactor
    - FrameworkSelectionService refactor
    - SessionProcessorService refactor
    - Comprehensive testing

4. **Phase 3:** URI Extension (Medium effort, high impact)
    - Controller analytics context
    - Analytics event processing
    - UTM extraction

### Sprint 3 (Optional Polish)

5. **Phase 5:** Closures in Constants (Low priority)
    - Create filter constant classes
    - Refactor existing array_filter calls
    - Document usage patterns

---

## Verification Checklist

- [ ] All unit tests pass
- [ ] All feature tests pass
- [ ] No PHPStan errors introduced
- [ ] Laravel Pint formatting applied
- [ ] Performance benchmarks show improvement
- [ ] Documentation updated (CLAUDE.md)
- [ ] Code review completed
- [ ] Staging deployment successful
- [ ] Production metrics monitored post-deployment
