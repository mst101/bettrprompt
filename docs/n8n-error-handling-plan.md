# n8n Error Handling Implementation Plan - Hybrid Approach

## User Decision: Hybrid Approach Selected

User chose the hybrid approach: Keep individual workflow error handling, add centralised logging and improved webhook
validation. This provides the best balance of simplicity and monitoring for a 3-workflow system.

## Current State Analysis

Based on exploration of the three n8n workflows (0, 1, and 2):

### Existing Error Handling (Solid Foundation)

- **N8nClient Service** (`app/Services/N8nClient.php`):
    - ✅ Circuit breaker (opens after 5 failures for 5 minutes)
    - ✅ Retry logic with exponential backoff (3 attempts)
    - ✅ Timeout handling (30 seconds)
    - ✅ Distinguishes client errors (4xx - no retry) from server errors (5xx - retry)
    - ✅ Redis-based failure tracking
- **Webhook Receiver** (`routes/api.php`, lines 15-167):
    - ✅ Secret verification (`X-N8N-SECRET` header)
    - ✅ Database transaction safety
    - ✅ Event broadcasting (AnalysisCompleted, PromptOptimizationCompleted, WorkflowFailed)
    - ⚠️ **Minimal validation** - only checks prompt_run_id, workflow_stage structure
- **Workflow 0**: Gracefully degrades on parsing errors (skips pre-analysis, continues to analysis)
- **Job Queue**: Laravel's built-in retry mechanism

### Gaps Identified (To Be Addressed)

1. **Limited error context**: workflow_stage shows failure (e.g. '1_failed'), but error_message field has limited
   details
2. **No error tracking fields**: No execution_id, retry_count, failed_node, error_type tracking
3. **No rate limiting detection**: Anthropic API 429 errors aren't specifically handled
4. **No admin alerting**: Circuit breaker opens silently, no notification when workflows repeatedly fail
5. **Webhook validation gaps**: error_message, error_type, failed_node, execution_id fields not validated

## Hybrid Approach: What We'll Implement

The hybrid approach keeps the best of both worlds:

### Keep (Already Working Well)

- ✅ Individual workflow error handling with try-catch blocks
- ✅ Graceful degradation (Workflow 0 skips on error)
- ✅ N8nClient circuit breaker and retry logic
- ✅ Structured error response format

### Add (Enhancements)

1. **Enhanced error context tracking** in PromptRun model
2. **Improved webhook validation** to catch malformed error payloads
3. **Centralized error logging** function in n8n workflows
4. **Rate limit detection** in N8nClient
5. **Admin alerting** for critical failures

### Don't Add (Too Complex for Current Needs)

- ❌ Global n8n error workflow (overhead not justified for 3 workflows)
- ❌ Complex retry logic within n8n (already have it in Laravel)
- ❌ Full monitoring dashboard (can add later when scaling)

## Implementation Plan

### Phase 1: Enhanced Error Context (Database & Model)

**Goal**: Add fields to track detailed error information

**1. Create Migration** - `database/migrations/XXXX_add_error_tracking_to_prompt_runs.php`

```php
Schema::table('prompt_runs', function (Blueprint $table) {
    $table->json('error_context')->nullable()->after('error_message');
    $table->integer('retry_count')->default(0)->after('error_context');
    $table->timestamp('last_error_at')->nullable()->after('retry_count');
});
```

**2. Update PromptRun Model** - `app/Models/PromptRun.php`

- Add to `$fillable`: `'error_context'`, `'retry_count'`, `'last_error_at'`
- Add to `$casts`: `'error_context' => 'array'`, `'last_error_at' => 'datetime'`

**Benefits**:

- Tracks which node failed, error type, execution ID
- Records retry attempts
- Enables debugging of recurring failures

---

### Phase 2: Improve Webhook Validation (Critical Security Fix)

**Goal**: Validate all error fields sent from n8n to prevent malformed data corruption

**Update API Webhook Receiver** - `routes/api.php` (lines 29-42)

**Current validation** (only basic fields):

```php
'prompt_run_id' => 'required|integer|exists:prompt_runs,id',
'workflow_stage' => 'nullable|string|in:0_processing,0_completed,...',
```

**Add validation for error fields**:

```php
'error_message' => 'nullable|string|max:1000',
'error_context' => 'nullable|array',
'error_context.error_type' => 'nullable|string|in:timeout,api_error,parsing_error,validation_error,rate_limit',
'error_context.failed_node' => 'nullable|string|max:255',
'error_context.execution_id' => 'nullable|string|max:255',
'error_context.timestamp' => 'nullable|date',
'retry_count' => 'nullable|integer|min:0|max:10',
```

**Update database write** (lines 83-89):

```php
$promptRun->update($request->only([
    'workflow_stage',
    'selected_framework',
    'framework_questions',
    'optimized_prompt',
    'error_message',
    'error_context',  // Add this
    'retry_count',    // Add this
]));

// Track error timestamp
if ($request->input('workflow_stage') && str_ends_with($request->input('workflow_stage'), '_failed')) {
    $promptRun->update(['last_error_at' => now()]);
}
```

**Benefits**:

- Prevents injection attacks via malformed error data
- Ensures data integrity
- Provides structured error information for debugging

---

### Phase 3: Add Centralised Error Logging to n8n Workflows

**Goal**: Add consistent error formatting function to each workflow

**For Each Workflow** (0, 1, 2), add a "Format Error" function node:

**Location**: Add after Anthropic API call node, triggered on error

**Function code**:

```javascript
// Format Error Details Function Node
const error = $input.item.error;
const nodeName = $input.item.nodeName || 'Unknown';
const executionId = $execution.id;

return {
    json: {
        success: false,
        error: {
            message: error?.message || 'Workflow execution failed',
            error_context: {
                error_type: error?.httpCode === 429 ? 'rate_limit' :
                    error?.httpCode ? 'api_error' : 'parsing_error',
                failed_node: nodeName,
                execution_id: executionId,
                timestamp: new Date().toISOString(),
                details: error?.description || null,
            }
        }
    }
};
```

**Implementation**:

- Workflow 0: Add after "Call Anthropic API" node
- Workflow 1: Add after "Call Anthropic API" node
- Workflow 2: Add after "Call Anthropic API" node

**Benefits**:

- Consistent error format across all workflows
- Rich context for debugging (execution ID, node name, timestamp)
- Detects rate limiting errors specifically

---

### Phase 4: Rate Limit Detection in N8nClient

**Goal**: Detect Anthropic API 429 errors and extend circuit breaker cooldown

**Update N8nClient** - `app/Services/N8nClient.php`

**Add after line 121** (in the HTTP error handling section):

```php
// Check for HTTP errors
if ($response->failed()) {
    $status = $response->status();

    // Special handling for rate limits (429)
    if ($status === 429) {
        Log::warning('N8n workflow hit Anthropic rate limit', [
            'path' => $path,
            'attempt' => $attempt + 1,
        ]);

        // Extend circuit breaker cooldown for rate limits
        Cache::put('n8n_circuit_breaker_open_until', now()->addMinutes(15));

        return [
            'success' => false,
            'error' => 'API rate limit reached. Please try again in a few minutes.',
            'error_context' => [
                'error_type' => 'rate_limit',
                'timestamp' => now()->toISOString(),
            ],
        ];
    }

    // Existing error handling...
}
```

**Benefits**:

- Prevents hammering Anthropic API when rate limited
- Extends circuit breaker cooldown to 15 minutes (vs 5 minutes for normal failures)
- Returns user-friendly error message

---

### Phase 5: Admin Alerting for Critical Failures (Optional)

**Goal**: Notify admin when circuit breaker opens or workflows fail repeatedly

**Create Notification** - `app/Notifications/WorkflowFailureAlert.php`

```php
class WorkflowFailureAlert extends Notification
{
    public function __construct(
        public string $type,  // 'circuit_breaker' | 'repeated_failures'
        public array $context
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'slack'];  // or just ['mail']
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Workflow Failure Alert')
            ->line($this->getMessage())
            ->line('Context: ' . json_encode($this->context));
    }

    private function getMessage(): string
    {
        return match($this->type) {
            'circuit_breaker' => 'N8n circuit breaker opened after 5 failures',
            'repeated_failures' => 'Workflow failed 3 times for same prompt run',
            default => 'Workflow error detected',
        };
    }
}
```

**Add to N8nClient** (after line 62, when circuit breaker opens):

```php
if ($failureCount >= $this->circuitBreakerThreshold) {
    Cache::put('n8n_circuit_breaker_open_until', now()->addSeconds($this->circuitBreakerTimeout));

    // Notify admin
    if (config('mail.admin_email')) {
        Notification::route('mail', config('mail.admin_email'))
            ->notify(new WorkflowFailureAlert('circuit_breaker', [
                'failure_count' => $failureCount,
                'cooldown_minutes' => $this->circuitBreakerTimeout / 60,
            ]));
    }

    return true;
}
```

**Add to webhook receiver** (after line 142, in catch block):

```php
catch (\Exception $e) {
    DB::rollBack();

    // Check if this prompt run has failed multiple times
    if ($promptRun->retry_count >= 2) {
        if (config('mail.admin_email')) {
            Notification::route('mail', config('mail.admin_email'))
                ->notify(new WorkflowFailureAlert('repeated_failures', [
                    'prompt_run_id' => $promptRun->id,
                    'retry_count' => $promptRun->retry_count,
                    'last_error' => $e->getMessage(),
                ]));
        }
    }

    throw $e;
}
```

**Configuration** - `.env`:

```
MAIL_ADMIN_EMAIL=admin@example.com
```

**Benefits**:

- Early warning when workflows are failing
- Prevents silent failures going unnoticed
- Enables proactive maintenance

## Critical Files to Modify

### Database & Model (Phase 1)

- `database/migrations/XXXX_add_error_tracking_to_prompt_runs.php` - New migration
- `app/Models/PromptRun.php` - Add error_context, retry_count, last_error_at fields

### API & Validation (Phase 2)

- `routes/api.php` (lines 29-42, 83-89) - Enhanced webhook validation
- `routes/api.php` (lines 116-118) - Track error timestamp on failures

### n8n Workflows (Phase 3)

- `n8n/workflow_0_pre_analysis.json` - Add "Format Error" function node
- `n8n/workflow_1_analysis.json` - Add "Format Error" function node
- `n8n/workflow_2_generation.json` - Add "Format Error" function node

### Laravel Services (Phase 4)

- `app/Services/N8nClient.php` (after line 121) - Add rate limit detection

### Optional Alerting (Phase 5)

- `app/Notifications/WorkflowFailureAlert.php` - New notification class
- `app/Services/N8nClient.php` (line 62) - Add admin notification
- `routes/api.php` (line 142) - Add retry count alerting
- `.env` - Add `MAIL_ADMIN_EMAIL` configuration

## Implementation Order

### Recommended: Start with Laravel First (Phases 1-2)

1. **Phase 1**: Database migration and model updates
    - Run migration
    - Update PromptRun model
    - Test with existing workflows (backward compatible)

2. **Phase 2**: Enhanced webhook validation
    - Add error field validation to webhook receiver
    - Update database write to include new fields
    - Test with existing workflows sending basic errors

### Then n8n Workflows (Phase 3)

3. **Add error formatting to workflows**
    - Start with Workflow 1 (most complex)
    - Then Workflow 2 (similar complexity)
    - Finally Workflow 0 (already has graceful degradation)
    - Test each workflow individually

### Then Advanced Features (Phases 4-5)

4. **Phase 4**: Rate limit detection in N8nClient
5. **Phase 5**: Admin alerting (optional, can be added later)

**Why this order?**

- Laravel changes are backward compatible (won't break existing workflows)
- Can test database changes before modifying n8n workflows
- N8nClient rate limit handling works immediately once deployed
- Alerting is optional and can be added when needed

## Testing Strategy

### Phase 1-2 Testing (Laravel Backend)

- **Migration test**: Run migration and rollback
- **Unit tests**: Test PromptRun model with new fields
- **Feature tests**: Test webhook receiver with error_context payloads
  ```php
  test('webhook accepts error context', function () {
      $response = $this->post('/api/n8n/webhook', [
          'prompt_run_id' => 1,
          'workflow_stage' => '1_failed',
          'error_message' => 'API call failed',
          'error_context' => [
              'error_type' => 'api_error',
              'failed_node' => 'Call Anthropic API',
              'execution_id' => 'abc123',
          ],
          'retry_count' => 1,
      ]);
      $response->assertOk();
  });
  ```

### Phase 3 Testing (n8n Workflows)

- **Manual testing**: Force Anthropic API failures (invalid API key, timeout)
- **Verify error format**: Check that error_context is properly structured
- **Check database**: Verify error details are stored in prompt_runs table

### Phase 4 Testing (Rate Limiting)

- **Unit test**: Mock 429 response from n8n, verify circuit breaker extension
- **Integration test**: Trigger actual rate limit scenario (if possible in staging)

### Phase 5 Testing (Alerting)

- **Manual test**: Force circuit breaker to open, verify email sent
- **Manual test**: Retry failed workflow 3 times, verify alert sent

## Success Metrics

After implementation, you should be able to:

- ✅ View detailed error context in database for failed workflows
- ✅ See which node failed and when in the error_context JSON field
- ✅ Track retry attempts via retry_count field
- ✅ Identify rate limit errors specifically (error_type: 'rate_limit')
- ✅ Circuit breaker extends cooldown to 15 minutes for rate limits
- ✅ Admin receives email when circuit breaker opens (if Phase 5 implemented)
- ✅ Admin receives email after 3 retries fail (if Phase 5 implemented)
- ✅ Users see helpful error messages without technical jargon

## Estimated Effort

- **Phase 1** (Database/Model): ~30 minutes
- **Phase 2** (Webhook validation): ~45 minutes
- **Phase 3** (n8n error nodes): ~1.5 hours (30 min per workflow)
- **Phase 4** (Rate limiting): ~30 minutes
- **Phase 5** (Alerting - optional): ~1 hour

**Total: ~4 hours** (3 hours without Phase 5)

## Future Enhancements (Not in This Plan)

These can be added later when scaling:

- Admin dashboard showing workflow health metrics
- Failed PromptRuns list with retry button in admin panel
- API usage tracking per hour/day in Redis
- Sentry integration for centralized error tracking
- Workflow execution time metrics
- Success/failure rate graphs

For now, the hybrid approach provides solid error handling without over-engineering.
