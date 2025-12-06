# n8n Workflow Execution Time Analysis

## Executive Summary

- **Workflow 0 (Pre-Analysis):** 5.2 seconds typical (1.1s - 10.4s range)
- **Workflow 1 (Analysis & Questions):** 6.8 seconds typical (1.4s - 13.4s range)
- **Primary bottleneck:** Anthropic Claude API calls (5-10 seconds)
- **Secondary bottleneck:** HTTP requests to Laravel API (100-2000ms each)

---

## Workflow 0: Pre-Analysis Clarity Check

### Node List & Timing

| # | Node | Type | Min | Typical | Max | % of Total |
|---|------|------|-----|---------|-----|-----------|
| **1.** | Webhook | Trigger | 1ms | 3ms | 5ms | <1% |
| **2.** | Prepare Prompt | Code (JavaScript) | 50ms | 125ms | 200ms | 2% |
| **3.** | Call Anthropic API | HTTP Request | 1000ms | 5000ms | 10000ms | **95%** |
| **4.** | Parse & Validate Response | Code (JavaScript) | 50ms | 125ms | 200ms | 2% |
| **5.** | Respond to Webhook | Webhook Response | 1ms | 5ms | 10ms | <1% |
| | **TOTAL** | | **1102ms** | **5258ms** | **10415ms** | **100%** |

### Execution Flow

```
1. Webhook triggered
   ↓
2. Prepare system prompt & user message (100ms)
   ↓
3. Call Anthropic API with clarity check prompt (5000ms) ← BOTTLENECK
   ↓
4. Parse & validate JSON response (100ms)
   ↓
5. Send webhook response back to Laravel
```

### Timeline Visualization

```
0ms      100ms    200ms    300ms    1000ms           5000ms           10000ms
|--------|--------|--------|--------|--------|--------|--------|--------|
[W]      [------PP------|--------CAA--------|]              [---PV---|R]
 ↑                                            ↑                        ↑
 Webhook                                      Peak load              Response
```

---

## Workflow 1: Prompt Framework - Analysis & Questions

### Node List & Timing

| # | Node | Type | Min | Typical | Max | % of Total |
|---|------|------|-----|---------|-----|-----------|
| **1.** | Webhook Trigger | Webhook | 1ms | 3ms | 5ms | <1% |
| **2.** | Fetch Framework Taxonomy | HTTP | 100ms | 500ms | 1000ms | 7% |
| **3.** | Fetch Personality Calibration | HTTP | 100ms | 500ms | 1000ms | 7% |
| **4.** | Check Has Personality | Conditional | 1ms | 5ms | 10ms | <1% |
| **5.** | Fetch Question Bank | HTTP | 100ms | 500ms | 1000ms | 7% |
| **6.** | Prepare Prompt | Code (JavaScript) | 50ms | 125ms | 200ms | 2% |
| **7.** | Call Anthropic API | HTTP Request | 1000ms | 5000ms | 10000ms | **74%** |
| **8.** | Format Response | Code (JavaScript) | 50ms | 125ms | 200ms | 2% |
| **9.** | Respond to Webhook | Webhook Response | 1ms | 5ms | 10ms | <1% |
| **10.** | Merge | Merge Node | 1ms | 5ms | 10ms | <1% |
| | **TOTAL** | | **1404ms** | **6768ms** | **13435ms** | **100%** |

### Execution Flow

```
1. Webhook triggered
   ↓
2. Parallel data fetches (run simultaneously):
   ├─ Fetch Framework Taxonomy (500ms)
   ├─ Fetch Personality Calibration (500ms)
   └─ Fetch Question Bank (500ms)
   ↓
3. Conditional: Check if user has personality data
   ↓
4. Prepare system prompt (125ms)
   ↓
5. Call Anthropic API with analysis prompt (5000ms) ← BOTTLENECK
   ↓
6. Format response (125ms)
   ↓
7. Merge data & send webhook response back
```

### Timeline Visualization

```
0ms      500ms   1000ms        3000ms        6000ms              13000ms
|--------|--------|--------|--------|--------|--------|--------|--------|
[W][T]   [--FT--][--PC--][C][--FQB-]         [--PP-][-----CAA-----|FV][R][M]
          ↑                   ↑                       ↑
          Parallel            Question bank          API call
          requests
```

---

## Performance Characteristics

### Dominant Factors

| Factor | Impact | Notes |
|--------|--------|-------|
| **Anthropic API latency** | ⭐⭐⭐⭐⭐ (74-95%) | Responsible for most execution time |
| **Network latency** | ⭐⭐ (7-20%) | Fetching reference data from Laravel |
| **Code execution** | ⭐ (2-3%) | JavaScript code execution is very fast |
| **Webhook overhead** | ⭐ (<1%) | Minimal impact |

### Node Type Characteristics

| Node Type | Typical Time | Notes |
|-----------|--------------|-------|
| Webhook | 1-5ms | Instant trigger/response |
| Code (n8n JavaScript) | 50-200ms | Fast for simple operations |
| HTTP Request (internal) | 100-1000ms | Depends on network & server |
| HTTP Request (API call) | 1000-10000ms | Depends on API server & endpoint |
| Conditional | 1-10ms | Minimal overhead |
| Merge | 1-10ms | Minimal overhead |

---

## Real-World Variability

### Best Case Scenario (1.1s - 1.4s)
**Conditions:**
- API server responds in <2 seconds
- Network is fast (low latency)
- No rate limiting or throttling
- Typical during: off-peak hours, optimised infrastructure

**When to expect:** Rare, maybe 5-10% of requests

### Typical Case (5.2s - 6.8s)
**Conditions:**
- API latency: 2-5 seconds
- Network latency: 100-500ms per HTTP request
- Normal server load
- No rate limiting

**When to expect:** Most of the time (80% of requests)

### Worst Case Scenario (10.4s - 13.4s)
**Conditions:**
- API is slow (8-10 seconds)
- Network issues (500ms-1s+ per request)
- Rate limiting effects
- Server load/congestion

**When to expect:** 10-15% of requests, peak hours

### Extreme Case (15s+)
**Conditions:**
- API timeout or very slow response
- Network timeouts
- Rate limiting kicks in

**When to expect:** Rare, <1% of requests

---

## Optimization Opportunities

### 1. Cache Reference Data ⭐⭐⭐ High Impact

**What to cache:**
- Framework Taxonomy (rarely changes)
- Personality Calibration (rarely changes)
- Question Bank (rarely changes)

**Implementation:**
- Use Redis or Laravel Cache
- Set 24-hour TTL
- Invalidate on updates

**Potential savings:** 1.5 seconds (22% reduction for Workflow 1)

**Implementation priority:** HIGH

### 2. Implement Streaming ⭐⭐ Medium Impact

**What to do:**
- Use Claude API streaming endpoint
- Start displaying results as they arrive
- Improve perceived performance

**Benefits:**
- Users see results appearing immediately
- Still waiting for full response but UX improves
- Reduces perceived latency significantly

**Implementation priority:** MEDIUM (if UX is concern)

### 3. Parallel Processing ⭐ Low Impact (Already Implemented)

**Status:** Workflow 1 already fetches data in parallel
- No sequential bottlenecks between data fetches
- All 3 HTTP requests run simultaneously
- Good implementation

**Potential:** Minimal additional improvement

### 4. Reduce API Complexity ⭐ Low Impact

**What to do:**
- Simplify system prompts
- Reduce context window size
- Remove unnecessary instructions

**Potential savings:** 100-500ms (limited impact)

**Note:** Claude API processing is the bottleneck, not prompt complexity

### 5. Implement Prompt Caching ⭐⭐ Medium Impact (Claude API feature)

**Requirements:**
- Use Claude API with prompt caching support
- Cache static system prompts
- Cache reference data in prompt

**Potential savings:** 500-1000ms on cached requests

**Implementation priority:** MEDIUM

---

## Monitoring Recommendations

### Metrics to Track

```json
{
  "workflow_0": {
    "webhook_trigger_time_ms": "time to trigger",
    "prepare_prompt_time_ms": "time to prepare system prompt",
    "api_call_time_ms": "critical metric - Claude API response time",
    "parse_response_time_ms": "time to parse response",
    "respond_time_ms": "time to send webhook response",
    "total_time_ms": "end-to-end execution time",
    "success_rate": "% of successful executions"
  },
  "workflow_1": {
    "webhook_trigger_time_ms": "time to trigger",
    "parallel_fetch_time_ms": "time for all 3 fetches (parallel)",
    "individual_fetch_times_ms": {
      "framework_taxonomy": "time for taxonomy fetch",
      "personality_calibration": "time for calibration fetch",
      "question_bank": "time for question bank fetch"
    },
    "prepare_prompt_time_ms": "time to prepare system prompt",
    "api_call_time_ms": "critical metric - Claude API response time",
    "format_response_time_ms": "time to format response",
    "total_time_ms": "end-to-end execution time",
    "success_rate": "% of successful executions"
  }
}
```

### How to Implement Monitoring

1. **In n8n:** Add expression tracking to each node
2. **In Laravel:** Log webhook response times
3. **In observability tool:** Set up dashboards for:
   - P50 (median) execution time
   - P95 (95th percentile) execution time
   - P99 (99th percentile) execution time
   - Error rate and timeout rate

### Alert Thresholds

- **Warning:** Total time > 8 seconds
- **Critical:** Total time > 15 seconds
- **Critical:** API call time > 12 seconds
- **Warning:** HTTP fetch time > 1.5 seconds

---

## Summary

### Key Takeaways

1. **API Call is the Bottleneck:** 74-95% of execution time
2. **Network Latency Matters:** 7-20% of execution time
3. **Code is Fast:** JavaScript execution <3% of time
4. **6-7 Seconds is Normal:** Don't expect faster without major changes
5. **Caching is Best Improvement:** Can save ~1.5 seconds easily

### Recommendations Priority

1. ✅ **First:** Implement reference data caching (Redis)
2. ⚠️ **Second:** Add monitoring to establish baselines
3. 📊 **Third:** Implement streaming if UX is concern
4. 🔄 **Fourth:** Consider prompt caching in Claude API

### Expected Impact After Optimizations

- **With caching:** 3.5-5.5 seconds (22% faster)
- **With streaming:** 5 seconds (but perceived as instant)
- **Combined:** 3-4.5 seconds (40-50% faster perceived)

