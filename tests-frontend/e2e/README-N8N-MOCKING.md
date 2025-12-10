# n8n Mocking Architecture for E2E Tests

## Overview

The e2e tests need to simulate n8n workflow execution without running a real n8n instance. This document explains how the mocking system works.

## Architecture

### Two-Way HTTP Communication

The system involves two directions of HTTP communication:

1. **Backend → n8n** (simulated by MockN8nController):
   - Backend calls: `POST https://app.localhost/webhook/api/n8n/webhook/{stage}`
   - Example: `/webhook/api/n8n/webhook/pre-analysis`
   - Intercepted by: Playwright route handler in N8nMockService
   - Handler: MockN8nController in routes/web.php

2. **n8n → Backend** (the actual webhook update):
   - n8n calls: `POST https://app.localhost/api/n8n/webhook`
   - Header: `X-N8N-SECRET: {webhook_secret}`
   - Body: Contains workflow_stage, selected_framework, error_message, etc.
   - Handler: Webhook receiver in routes/api.php
   - Result: Updates PromptRun in database, broadcasts events

### The Complete Flow

```
1. User submits form
   ↓
2. Backend creates PromptRun with workflow_stage = '0_processing'
   ↓
3. Backend (ProcessPreAnalysis job) calls n8n
   POST /webhook/api/n8n/webhook/pre-analysis
   ↓
4. N8nMockService intercepts this call (Playwright route handler)
   ↓
5. N8nMockService generates mock response based on scenario (success/error/etc)
   ↓
6. N8nMockService calls the webhook endpoint:
   POST /api/n8n/webhook (with X-N8N-SECRET header)
   ↓
7. Webhook handler in api.php receives the update
   ↓
8. PromptRun is updated in database
   ↓
9. WorkflowFailed or completion event is broadcast (if applicable)
   ↓
10. Frontend polling or WebSocket receives update
   ↓
11. Frontend reloads the page to show new state
```

## Key Components

### N8nMockService (tests-frontend/e2e/mocks/n8n-mock-service.ts)

Playwright-level HTTP route interceptor that:
- Intercepts requests to `**/api/n8n/webhook` (the backend→n8n calls)
- Generates mock n8n responses based on configured scenario
- **Crucially**: Calls the webhook endpoint (`POST /api/n8n/webhook`) to update the database
- Returns the mock response to the backend

### Mock Scenarios

Configured via:
```typescript
await n8nMock.enableMocking({
    scenario: 'rate-limit',  // or 'success', 'validation-error', 'api-error', 'timeout'
    responseDelay: 100,
});
```

Scenarios defined in `n8n-responses.ts`:
- **success**: Normal workflow completion
- **rate-limit**: LLM API rate limit error
- **validation-error**: Invalid input error
- **api-error**: External service unavailable
- **timeout**: Workflow timeout after 60s

### Error Response Format

When a workflow fails, the response includes:
```typescript
{
    prompt_run_id: number,
    workflow_stage: '0_failed',  // or '1_failed', '2_failed'
    status: 'failed',
    error_message: 'User-friendly error message'
}
```

### Webhook Secret

The webhook endpoint verifies the request with `X-N8N-SECRET` header:
- Configured in: `.env.e2e` as `N8N_WEBHOOK_SECRET`
- Must match: `config('services.n8n.webhook_secret')` in the backend
- Used by: N8nMockService when calling the webhook endpoint

## Important Details

### Timing

1. N8nMockService calls webhook BEFORE returning response to the backend
2. This ensures database is updated BEFORE frontend re-renders
3. Frontend can use polling to detect updates (polls every 1 second by default)

### Page-Level vs Database-Level Updates

- **Backend→n8n calls** (MockN8nController): Intercepted at page level by Playwright
- **n8n→Backend calls** (webhook): Simulated by N8nMockService making real HTTP POST
- This means the database IS updated in tests, just like in production

### Event Broadcasting

After webhook updates, events are broadcast:
- `PreAnalysisCompleted`: When 0_completed
- `AnalysisCompleted`: When 1_completed
- `PromptOptimizationCompleted`: When 2_completed
- `WorkflowFailed`: When any stage ends with _failed

Frontend listens for these via `useRealtimeUpdates` composable and reloads the page.

## Testing Considerations

### For Error Scenarios

When testing error handling:
1. Enable mocking with specific error scenario
2. Submit form to create PromptRun
3. Wait for page to navigate to show page
4. N8nMockService intercepts the n8n call
5. N8nMockService calls webhook to update database
6. Frontend polling detects the update
7. Frontend reloads and displays error message

### For Success Scenarios

Same flow, but:
- Response includes framework, questions, or optimized_prompt
- Page automatically transitions between tabs based on state

## Debugging

If tests fail to show expected state:

1. Check if N8nMockService webhook call succeeded:
   - Look for "Webhook endpoint returned non-OK status" in console
   - Verify N8N_WEBHOOK_SECRET matches configuration

2. Check if database was updated:
   - Verify workflow_stage changed in PromptRun
   - Verify error_message is populated

3. Check if frontend reloaded:
   - Verify polling is active (shouldPollForUpdates = true)
   - Check browser network tab for reload request

4. Check if error is displayed:
   - Verify hasWorkflowFailed computed property works
   - Verify error message template is in the DOM
   - Verify v-if condition for error display

## Related Files

- `tests-frontend/e2e/mocks/n8n-mock-service.ts` - Main mock service
- `tests-frontend/e2e/mocks/n8n-responses.ts` - Response generators
- `tests-frontend/e2e/helpers/fixtures.ts` - Test fixtures with automatic mocking
- `app/Http/Controllers/MockN8nController.php` - Backend handler for mock responses
- `routes/api.php` - Webhook receiver endpoint
- `resources/js/Composables/useRealtimeUpdates.ts` - Frontend polling/WebSocket
- `app/Events/WorkflowFailed.php` - Event broadcast on failure
- `.env.e2e` - Test environment configuration
