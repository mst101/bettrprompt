# Data Retention & Compliance Plan

This document defines a detailed, implementable plan to manage database growth whilst complying with GDPR/CCPA requirements and maximising analytical capability.

## Table of Contents

- [Goals](#goals)
- [Legal & Compliance Framework](#legal--compliance-framework)
- [Alignment With Unified Analytics Architecture](#alignment-with-unified-analytics-architecture)
- [Current State](#current-state)
- [Part A — Visitor Identity & Tracking Improvements](#part-a--visitor-identity--tracking-improvements)
- [Part B — Table-Specific Retention Policies](#part-b--table-specific-retention-policies)
- [Part C — Backup & Disaster Recovery Strategy](#part-c--backup--disaster-recovery-strategy)
- [Part D — Privacy Policy Updates Required](#part-d--privacy-policy-updates-required)
- [Part E — Implementation Roadmap](#part-e--implementation-roadmap)
- [Part F — Monitoring & Observability](#part-f--monitoring--observability)

---

## Goals

1. **Compliance-first**: Meet GDPR (EU) and CCPA/CPRA (California) requirements for data retention, minimisation, and consumer rights
2. **Maximise analytical capability**: Retain data needed for year-over-year analysis, experiment attribution, and long-tail insights
3. **Control table growth**: Prevent unbounded growth in high-volume tables without sacrificing data quality
4. **Safety & reversibility**: Archive before deletion, maintain audit trails, support data recovery
5. **SEO-safe**: Ensure bot handling doesn't negatively impact search engine crawling or rankings

---

## Legal & Compliance Framework

### GDPR (General Data Protection Regulation) – EU Residents

**Core principles:**

- **Storage limitation** (Article 5(1)(e)): Personal data must not be kept longer than necessary for the purposes for which it was processed
- **Purpose limitation**: Data can only be retained for the specific, explicit purposes disclosed to users
- **Lawful basis documentation**: You must document and justify why you've set specific retention periods
- **No fixed periods**: GDPR doesn't mandate specific timeframes, but requires "necessity" be demonstrable

**Key requirements for BettrPrompt:**

1. **Document retention periods**: For each category of personal data (visitor tracking, analytics events, experiment data), document:
   - What data is collected
   - Why it's collected (business purpose)
   - How long it's kept (specific period or criteria)
   - Legal basis (legitimate interest, consent, etc.)

2. **Anonymisation exception**: Anonymised or properly pseudonymised data can be retained indefinitely for statistical purposes, provided it cannot be re-identified

3. **Data subject rights**: Must support:
   - Right to access (data export)
   - Right to erasure ("right to be forgotten")
   - Right to rectification

4. **Audit trail**: Maintain records of processing activities, including retention schedules

**Penalties**: Up to €20 million or 4% of global annual turnover (whichever is higher)

**Sources:**
- [GDPR Data Retention: Compliance guidelines](https://usercentrics.com/knowledge-hub/gdpr-data-retention/)
- [How to Write a GDPR Data Retention Policy](https://www.itgovernance.co.uk/blog/top-tips-for-data-retention-under-the-gdpr)
- [GDPR Compliance Guide 2026](https://secureprivacy.ai/blog/gdpr-compliance-2026)

### CCPA/CPRA (California Privacy Rights Act) – California Residents

**Core principles:**

- **Data minimisation**: Collect, process, and retain only the minimum necessary personal information required for the disclosed purpose
- **Data limitation**: Retain personal information only for as long as necessary to fulfil the disclosed purpose
- **Explicit disclosure**: Must disclose specific retention periods (e.g., "3 years") or clear methodology (e.g., "until account deletion plus 2 years for legal compliance")

**Key requirements for BettrPrompt:**

1. **Lookback period**: January 1, 2022 onwards
   - Consumers can request access to ALL personal information going back to Jan 1, 2022
   - This means you cannot delete data from before this date if it's covered by an access request
   - For BettrPrompt (launched after this date), this is automatically satisfied

2. **Privacy policy disclosure**: Must state retention periods for each category:
   - Visitor tracking data: [X months/years]
   - Analytics events: [X months/years]
   - Experiment data: [X months/years]
   - Can use criteria instead of fixed periods: "retained until user deletes account plus 90 days for fraud prevention"

3. **De-identification**: Properly de-identified data (cannot be re-identified) is no longer considered "personal information" and can be retained longer

4. **Consumer rights**: Must support:
   - Right to know (categories and specific pieces)
   - Right to delete
   - Right to correct
   - Right to opt out of sale/sharing (not applicable if you don't sell data)

**CPRA-specific additions (effective January 1, 2023, with updated regulations Jan 1, 2026):**

- Must minimise retention even within the disclosed purpose
- "Necessary" is narrowly construed – cannot keep data "just in case"
- Enhanced requirements for sensitive personal information (precise geolocation, biometrics, etc.)

**Sources:**
- [CPRA and Data Retention: 5 Steps](https://www.pwc.com/us/en/services/consulting/cybersecurity-risk-regulatory/library/cpra-data-retention-preparation.html)
- [CPRA Data Retention Requirements](https://secureprivacy.ai/blog/cpra-data-retention)
- [CCPA Requirements 2026: Complete Compliance Guide](https://secureprivacy.ai/blog/ccpa-requirements-2026-complete-compliance-guide)

### Recommended Retention Justifications (By Business Purpose)

| Data Category | Business Purpose | Justified Retention | Legal Basis (GDPR) |
|---------------|------------------|---------------------|-------------------|
| Visitor attribution (UTM, referrer) | Marketing attribution, ROI analysis | 25 months (YoY comparison) | Legitimate interest |
| Analytics events (raw) | Debugging, fraud detection, data quality | 6 months | Legitimate interest |
| Analytics sessions | Funnel analysis, retention metrics | 25 months (YoY comparison) | Legitimate interest |
| Experiment assignments | A/B test attribution | Experiment lifetime + 12 months | Legitimate interest |
| Experiment exposures/conversions | Statistical analysis, long-tail effects | Experiment lifetime + 12 months | Legitimate interest |
| Aggregate statistics | Business reporting, trend analysis | Indefinite (anonymised) | Not personal data |
| User accounts | Service delivery, authentication | Until account deletion + 90 days | Contract performance |
| Subscription/payment history | Billing, tax compliance, dispute resolution | 7 years (legal requirement) | Legal obligation |
| IP addresses | Fraud prevention, security | 30 days (then truncate/hash) | Legitimate interest |
| Workflow analytics | Product improvement, failure diagnosis | Indefinite (essential for service) | Legitimate interest |

---

## Alignment With Unified Analytics Architecture

This plan aligns with `docs/unified-analytics-experimentation-architecture.md`:

**Identity model:**
- `visitor_id` is server-owned, httpOnly cookie (essential, not gated by analytics consent)
- Analytics measurement (events, sessions, experiments) requires analytics consent
- No localStorage identity copies (this plan removes them)

**Retention approach:**
- Short retention for raw events (~6 months), following the architecture's 90-day recommendation with slight extension for conservative compliance
- Long retention for aggregates (indefinite)
- IP addresses: minimal retention (this plan adds truncation/nullification after 30 days)

**Session handling:**
- Sessions are continuously updated during active browsing (see `analytics_sessions` migration: `page_count`, `event_count` increment)
- Pruning uses `ended_at` for completed sessions, with special handling for abandoned sessions

---

## Current State

### What The Code Does Today

**Visitor tracking** (`app/Http/Middleware/TrackVisitor.php`):
- Runs on all web requests (registered in `bootstrap/app.php`)
- Creates new `visitors` row when no `visitor_id` cookie exists
- Updates `last_visit_at` and increments `visit_count` on every request for existing visitors
- Creates httpOnly, secure cookie with 2-year expiry

**Identity restoration** (`routes/api.php`):
- `POST /api/restore-visitor` endpoint exists
- Validates that `visitor_id` exists in `visitors` table
- Recreates cookie if localStorage backup exists
- **This pattern will be removed** (see Part A, Architecture Fix #1)

**Analytics tables** (migrations in `database/migrations/`):
- `analytics_events`: Raw event log with `event_id` (idempotent), `occurred_at`, `visitor_id`, `user_id`, `session_id`
- `analytics_sessions`: Session aggregates with `started_at`, `ended_at`, `page_count`, `event_count`
- Experiment tables: `experiments`, `experiment_variants`, `experiment_assignments`, `experiment_exposures`, `analytics_event_experiments`, `experiment_conversions`
- Funnel tables: `funnels`, `funnel_stages`, `funnel_progress`, `funnel_daily_stats`
- Domain analytics: `framework_selections`, `question_analytics`, `workflow_analytics`, plus `*_daily_stats` aggregates

**Current retention:**
- No automated cleanup commands exist
- No scheduled jobs for pruning
- Tables grow unbounded
- Archive tables don't exist yet

---

## Part A — Visitor Identity & Tracking Improvements

### Architecture Fix #1: Remove localStorage `visitor_id` Pattern

**Why:** The unified analytics architecture explicitly states: "Avoid copying `visitor_id` into localStorage" (security risk: XSS exposure; identity hygiene). The current code in `resources/js/app.ts` and `/api/restore-visitor` endpoint contradict this guidance.

**Critical concern:** If you implement retention policies whilst localStorage restore exists, deleted visitor IDs could be inadvertently resurrected, violating GDPR/CCPA erasure requirements.

**Implementation steps:**

1. **Frontend changes:**
   - Remove localStorage backup code from `resources/js/app.ts`:
     ```typescript
     // DELETE THIS:
     const visitorId = getCookie('visitor_id');
     if (visitorId) {
         localStorage.setItem('visitor_id_backup', visitorId);
     } else {
         const backupId = localStorage.getItem('visitor_id_backup');
         if (backupId) {
             fetch('/api/restore-visitor', { ... });
         }
     }
     ```
   - If frontend needs visitor ID, use Inertia shared props (server-provided, read-only)

2. **Backend changes:**
   - Remove or deprecate `POST /api/restore-visitor` endpoint in `routes/api.php`
   - If keeping for administrative purposes, restrict to authenticated staff only

3. **Documentation updates:**
   - Update `docs/visitor-tracking.md` to remove localStorage sections
   - Update any setup guides referencing localStorage pattern

**Acceptance criteria:**
- No client-side code reads or writes `visitor_id` to localStorage
- `visitor_id` remains accessible server-side via cookie
- Inertia props include safe visitor context when needed (e.g., for Fullstory identification)

---

### Architecture Fix #2: Remove `visit_count` Column Entirely

**Why:**
- `visit_count` increments on EVERY request (including assets, AJAX, form posts), making it an unreliable metric
- Causes unnecessary DB write churn (one update per request, per visitor)
- Not aligned with event-based analytics architecture
- Can be replaced with session-based metrics (`analytics_sessions` count) or event queries

**What currently uses `visit_count`:**
1. `Visitor` model's `isReturning()` helper method
2. Visitor retention Tier 1 rule (currently checks `visit_count >= 2`)
3. Docs reference it for A/B testing segmentation and CDP lead scoring
4. **Home page “returning visitor” UI** should derive from `visitors.visit_count` (not from a separate cookie).

**Replacement strategy:**

| Old Approach | New Approach |
|--------------|--------------|
| `visit_count > 1` (is returning?) | `first_visit_at != last_visit_at` OR count of `analytics_sessions > 1` |
| `visit_count >= 2` (Tier 1 retention) | Has 2+ analytics sessions OR multiple prompt runs |
| A/B segmentation by visit count | Segment by session count or "days since first visit" |
| CDP lead scoring using visit count | Use session count, event count, or engagement score |

**Implementation steps:**

1. **Migration to drop column:**
   ```php
   // database/migrations/YYYY_MM_DD_HHMMSS_remove_visit_count_from_visitors.php
   public function up(): void
   {
       Schema::table('visitors', function (Blueprint $table) {
           $table->dropColumn('visit_count');
       });
   }

   public function down(): void
   {
       Schema::table('visitors', function (Blueprint $table) {
           $table->integer('visit_count')->default(1);
       });
   }
   ```

2. **Update Visitor model:**
   - Remove `visit_count` from `$fillable` and `$casts`
   - Replace `isReturning()` method:
     ```php
     public function isReturning(): bool
     {
         // Simple approach: check if first and last visit differ by >1 hour
         if ($this->first_visit_at && $this->last_visit_at) {
             return $this->first_visit_at->diffInHours($this->last_visit_at) >= 1;
         }
         return false;
     }
     ```
   - Or use session-based approach:
     ```php
     public function isReturning(): bool
     {
         return $this->sessions()->count() > 1;
     }

     // Add relationship if not exists:
     public function sessions()
     {
         return $this->hasMany(AnalyticsSession::class, 'visitor_id');
     }
     ```

3. **Update TrackVisitor middleware:**
   - Remove `visit_count` increment from `updateVisitor()`:
     ```php
     protected function updateVisitor(string $visitorId): void
     {
         $visitor = Visitor::find($visitorId);

         $visitor?->update([
             'last_visit_at' => now(),
             // REMOVED: 'visit_count' => $visitor->visit_count + 1,
         ]);
     }
     ```
   - Remove `visit_count => 1` from `createVisitor()`

4. **Update resources, factories, tests:**
   - `VisitorResource`: Remove `visitCount` property
   - `VisitorFactory`: Remove `visit_count` generation
   - `VisitorTest`: Update `isReturning()` tests to reflect new logic
   - A/B testing and CDP docs: Replace `visit_count` references with session-based metrics

5. **Update retention rules** (see Part B1):
   - Tier 1 rule: Change `visit_count >= 2` to "has 2+ sessions OR multiple page views"

**Acceptance criteria:**
- `visit_count` column removed from database
- No code references `visit_count`
- `isReturning()` method works correctly using alternative logic
- All tests pass
- DB write load reduced (no per-request visitor updates unless `last_visit_at` changes significantly)

---

### Bot Detection (SEO-Safe Noise Reduction)

**Why:** Search engine crawlers and monitoring bots can create large volumes of low-value visitor rows. Skipping visitor persistence for known bots reduces growth without affecting SEO (bots can still access all content).

**Conservative approach:** Only skip visitor creation for well-known, legitimate crawlers. Do NOT use overly broad patterns that might catch edge cases (accessibility tools, corporate proxies, password managers).

**Implementation steps:**

1. **Add bot detection method to middleware:**
   ```php
   // In app/Http/Middleware/TrackVisitor.php

   /**
    * Determine if the request is from a known bot/crawler.
    * Uses a conservative allowlist of well-known search engines only.
    */
   protected function isKnownBot(Request $request): bool
   {
       $userAgent = strtolower($request->userAgent() ?? '');

       // Only well-known search engine crawlers
       $knownBots = [
           'googlebot',
           'bingbot',
           'duckduckbot',
           'baiduspider',
           'yandexbot',
           'slurp',       // Yahoo
           'applebot',    // Apple/Siri
       ];

       foreach ($knownBots as $bot) {
           if (str_contains($userAgent, $bot)) {
               return true;
           }
       }

       return false;
   }
   ```

2. **Modify middleware flow:**
   ```php
   public function handle(Request $request, Closure $next): Response
   {
       // Early exit for known bots - no tracking at all
       if ($this->isKnownBot($request)) {
           return $next($request);
       }

       $visitorId = $request->cookie('visitor_id');
       // ... existing visitor creation/update logic ...
   }
   ```

3. **Alternative: Track with `is_bot` flag:**
   - If you want to measure bot traffic separately, add `is_bot` boolean column to `visitors`
   - Create visitor records for bots but flag them
   - Exclude `is_bot = true` from analytics queries
   - This preserves debugging capability and traffic insights

**Acceptance criteria:**
- Requests from `Googlebot/2.1` (simulated User-Agent) render pages normally (200 status)
- No `visitors` rows created for bot requests
- No cookies set for bot requests
- Human traffic continues to be tracked normally

---

### Secure Cookie Configuration (Production vs Development)

**Current behaviour:** Cookie uses `secure=true` unconditionally (line 81 in TrackVisitor.php), which works in production (HTTPS) but can cause issues in local dev (HTTP).

**Problem:** If production is always HTTPS (with edge redirect), this isn't a growth driver. However, misconfigured proxy setups or local dev environments could cause repeated visitor creation if the cookie isn't persisted.

**Implementation steps:**

1. **Add config option:**
   ```php
   // config/services.php (or create config/tracking.php)

   'visitor' => [
       'cookie_secure' => env('VISITOR_COOKIE_SECURE', true),
       'cookie_lifetime' => 1051200, // 2 years in minutes
   ],
   ```

2. **Use config in middleware:**
   ```php
   // In TrackVisitor.php
   Cookie::queue(
       'visitor_id',
       $visitorId,
       config('services.visitor.cookie_lifetime', 1051200),
       '/',
       null,
       config('services.visitor.cookie_secure', true), // From config
       true, // httpOnly
       false,
       'lax' // sameSite
   );
   ```

3. **Update `.env.example`:**
   ```bash
   # Visitor tracking
   VISITOR_COOKIE_SECURE=true  # Set to false for local HTTP development
   ```

**Acceptance criteria:**
- Production: `visitor_id` cookie has `secure=true`
- Local dev (HTTP): Cookie persists across requests (if config set to false)
- No duplicate visitor rows created due to cookie loss

---

## Part B — Table-Specific Retention Policies

### B1) `visitors` Table — Tiered Retention Strategy

**Principle:** Separate high-value visitors (converted, engaged) from low-signal noise (single-visit, no attribution data).

#### Retention Tiers (Priority Order)

**Tier 0 — Never Delete (Permanent Retention)**

Keep indefinitely if ANY of these are true:
- `user_id IS NOT NULL` (visitor converted to registered user)
- `converted_at IS NOT NULL` (explicit conversion timestamp)
- `referred_by_user_id IS NOT NULL` (referral program attribution)
- Has prompt runs: `EXISTS (SELECT 1 FROM prompt_runs WHERE prompt_runs.visitor_id = visitors.id)`
- Has analytics sessions with conversion: `EXISTS (SELECT 1 FROM analytics_sessions WHERE analytics_sessions.visitor_id = visitors.id AND analytics_sessions.converted = true)`

**Rationale:** These visitors represent actual business value (revenue, product usage, attribution). Retention is justified by contract performance (users), legal obligation (referrals), and legitimate interest (conversion analysis).

---

**Tier 1 — Keep 25 Months (Marketing/YoY Analysis)**

Keep for **25 months** from `last_visit_at` if Tier 0 doesn't apply AND any of these are true:
- Any UTM field present: `utm_source IS NOT NULL OR utm_medium IS NOT NULL OR utm_campaign IS NOT NULL OR utm_term IS NOT NULL OR utm_content IS NOT NULL`
- `referrer IS NOT NULL` OR `landing_page IS NOT NULL`
- Has 2+ analytics sessions: `EXISTS (SELECT 1 FROM analytics_sessions WHERE analytics_sessions.visitor_id = visitors.id HAVING COUNT(*) >= 2)`
- Any preference/enrichment set:
  - `personality_type IS NOT NULL`
  - `location_detected_at IS NOT NULL`
  - `currency_code IS NOT NULL` OR `language_code IS NOT NULL`

**Prune when:**
- Tier 0 = false
- Tier 1 = true
- `last_visit_at < NOW() - INTERVAL '25 months'`

**Rationale:** Marketing attribution requires year-over-year comparison (12 months) plus 1 full year of prior data for baseline (24 months), rounded to 25 for buffer. Legitimate interest: marketing ROI analysis.

---

**Tier 2 — Keep 6 Months (Low-Signal Noise)**

Prune after **6 months** when ALL of these are true:
- Tier 0 = false
- Tier 1 = false (no attribution data, no enrichment)
- Has exactly 1 session OR 0 sessions: `(SELECT COUNT(*) FROM analytics_sessions WHERE analytics_sessions.visitor_id = visitors.id) <= 1`
- `last_visit_at < NOW() - INTERVAL '6 months'`

**Rationale:** Single-visit, no-data visitors provide minimal analytical value. 6-month retention allows for seasonal analysis (e.g., "did they return for holiday promo?") whilst controlling growth. Extended from original 90 days to be conservative and allow for longer attribution windows.

---

#### Archive-Then-Delete Workflow

**Purpose:** Ensure reversibility, support audits, maintain compliance trail.

**Archive table structure:**
```sql
-- database/migrations/YYYY_MM_DD_create_visitors_archive_table.php
CREATE TABLE visitors_archive (
    -- All columns from visitors table
    id UUID PRIMARY KEY,
    user_id BIGINT,
    utm_source VARCHAR,
    -- ... all other visitor columns ...

    -- Archive metadata
    archived_at TIMESTAMP NOT NULL,
    archive_tier VARCHAR(10) NOT NULL, -- 'tier_1' or 'tier_2'
    archive_reason TEXT -- Human-readable explanation
);

CREATE INDEX idx_visitors_archive_archived_at ON visitors_archive(archived_at);
CREATE INDEX idx_visitors_archive_user_id ON visitors_archive(user_id);
```

**Implementation: Artisan Command**

```php
// app/Console/Commands/CleanupOldVisitors.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupOldVisitors extends Command
{
    protected $signature = 'visitors:cleanup
                            {--dry-run : Show what would be deleted without deleting}
                            {--batch=5000 : Number of records to process per batch}
                            {--tier=all : Which tier to process: tier_1, tier_2, or all}';

    protected $description = 'Archive and delete stale visitor records based on retention tiers';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch');
        $tier = $this->option('tier');

        $this->info('Starting visitor cleanup...');
        $this->info('Dry run: ' . ($dryRun ? 'YES' : 'NO'));

        // Always process Tier 2 first (most restrictive), then Tier 1
        if ($tier === 'all' || $tier === 'tier_2') {
            $this->processTier2($dryRun, $batchSize);
        }

        if ($tier === 'all' || $tier === 'tier_1') {
            $this->processTier1($dryRun, $batchSize);
        }

        $this->info('Cleanup completed.');
        return 0;
    }

    protected function processTier2(bool $dryRun, int $batchSize): void
    {
        $this->info("\n--- Processing Tier 2 (Low-Signal Visitors) ---");

        $cutoffDate = Carbon::now()->subMonths(6);
        $this->info("Cutoff date: {$cutoffDate->toDateString()}");

        // Build query for Tier 2 eligible visitors
        $query = DB::table('visitors')
            ->select('visitors.id')
            ->where('visitors.last_visit_at', '<', $cutoffDate)
            // Exclude Tier 0
            ->whereNull('visitors.user_id')
            ->whereNull('visitors.converted_at')
            ->whereNull('visitors.referred_by_user_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('prompt_runs')
                  ->whereColumn('prompt_runs.visitor_id', 'visitors.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('analytics_sessions')
                  ->whereColumn('analytics_sessions.visitor_id', 'visitors.id')
                  ->where('analytics_sessions.converted', true);
            })
            // Exclude Tier 1 (no attribution data)
            ->whereNull('visitors.utm_source')
            ->whereNull('visitors.utm_medium')
            ->whereNull('visitors.utm_campaign')
            ->whereNull('visitors.utm_term')
            ->whereNull('visitors.utm_content')
            ->whereNull('visitors.referrer')
            ->whereNull('visitors.landing_page')
            ->whereNull('visitors.personality_type')
            ->whereNull('visitors.location_detected_at')
            ->whereNull('visitors.currency_code')
            ->whereNull('visitors.language_code')
            // Has 0-1 sessions
            ->where(function ($q) {
                $q->whereNotExists(function ($subQ) {
                    $subQ->select(DB::raw(1))
                         ->from('analytics_sessions')
                         ->whereColumn('analytics_sessions.visitor_id', 'visitors.id')
                         ->havingRaw('COUNT(*) >= 2');
                });
            });

        $count = $query->count();
        $this->info("Found {$count} Tier 2 visitors eligible for archival.");

        if ($dryRun) {
            $this->warn('DRY RUN: No changes made.');
            return;
        }

        if ($count === 0) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $processed = 0;

        while (true) {
            $visitorIds = (clone $query)
                ->limit($batchSize)
                ->pluck('id')
                ->toArray();

            if (empty($visitorIds)) {
                break;
            }

            DB::transaction(function () use ($visitorIds) {
                // Insert into archive
                DB::statement("
                    INSERT INTO visitors_archive (
                        id, user_id, utm_source, utm_medium, utm_campaign, utm_term, utm_content,
                        referrer, landing_page, user_agent, ip_address,
                        first_visit_at, last_visit_at, converted_at, referred_by_user_id,
                        personality_type, trait_percentages, ui_complexity, ui_step_number,
                        country_code, country_name, currency_code, language_code, location_detected_at,
                        created_at, updated_at,
                        archived_at, archive_tier, archive_reason
                    )
                    SELECT
                        id, user_id, utm_source, utm_medium, utm_campaign, utm_term, utm_content,
                        referrer, landing_page, user_agent, ip_address,
                        first_visit_at, last_visit_at, converted_at, referred_by_user_id,
                        personality_type, trait_percentages, ui_complexity, ui_step_number,
                        country_code, country_name, currency_code, language_code, location_detected_at,
                        created_at, updated_at,
                        NOW(), 'tier_2', 'Low-signal visitor: 1 or fewer sessions, no attribution data, inactive >6 months'
                    FROM visitors
                    WHERE id = ANY(?)
                ", ['{' . implode(',', $visitorIds) . '}']);

                // Delete from main table
                DB::table('visitors')->whereIn('id', $visitorIds)->delete();
            });

            $processed += count($visitorIds);
            $bar->advance(count($visitorIds));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Archived and deleted {$processed} Tier 2 visitors.");
    }

    protected function processTier1(bool $dryRun, int $batchSize): void
    {
        $this->info("\n--- Processing Tier 1 (Marketing Attribution Visitors) ---");

        $cutoffDate = Carbon::now()->subMonths(25);
        $this->info("Cutoff date: {$cutoffDate->toDateString()}");

        // Build query for Tier 1 eligible visitors
        // (Similar structure to Tier 2, but checks for Tier 1 criteria and 25-month cutoff)

        $query = DB::table('visitors')
            ->select('visitors.id')
            ->where('visitors.last_visit_at', '<', $cutoffDate)
            // Exclude Tier 0
            ->whereNull('visitors.user_id')
            ->whereNull('visitors.converted_at')
            ->whereNull('visitors.referred_by_user_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('prompt_runs')
                  ->whereColumn('prompt_runs.visitor_id', 'visitors.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('analytics_sessions')
                  ->whereColumn('analytics_sessions.visitor_id', 'visitors.id')
                  ->where('analytics_sessions.converted', true);
            })
            // Must have Tier 1 characteristics (at least one)
            ->where(function ($q) {
                $q->whereNotNull('visitors.utm_source')
                  ->orWhereNotNull('visitors.utm_medium')
                  ->orWhereNotNull('visitors.utm_campaign')
                  ->orWhereNotNull('visitors.utm_term')
                  ->orWhereNotNull('visitors.utm_content')
                  ->orWhereNotNull('visitors.referrer')
                  ->orWhereNotNull('visitors.landing_page')
                  ->orWhereNotNull('visitors.personality_type')
                  ->orWhereNotNull('visitors.location_detected_at')
                  ->orWhereNotNull('visitors.currency_code')
                  ->orWhereNotNull('visitors.language_code')
                  ->orWhereExists(function ($subQ) {
                      $subQ->select(DB::raw(1))
                           ->from('analytics_sessions')
                           ->whereColumn('analytics_sessions.visitor_id', 'visitors.id')
                           ->havingRaw('COUNT(*) >= 2');
                  });
            });

        $count = $query->count();
        $this->info("Found {$count} Tier 1 visitors eligible for archival.");

        if ($dryRun) {
            $this->warn('DRY RUN: No changes made.');
            return;
        }

        // Similar batching logic as Tier 2, with archive_tier = 'tier_1' and 25-month reason
        // ... (implementation follows same pattern as processTier2)
    }
}
```

**Scheduler registration:**
```php
// app/Console/Kernel.php (or routes/console.php in Laravel 11+)

$schedule->command('visitors:cleanup')
    ->monthlyOn(1, '02:30') // 1st of month, 2:30 AM
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        Log::info('Visitor cleanup completed successfully');
    })
    ->onFailure(function () {
        Log::error('Visitor cleanup failed');
    });
```

**Acceptance criteria:**
- `--dry-run` reports correct counts without deleting
- Archived visitors appear in `visitors_archive` with `archived_at`, `archive_tier`, `archive_reason`
- Original visitors removed from `visitors` table
- Tier 0 visitors never deleted (verified by checking user_id, converted_at, etc.)
- Tier 1 visitors archived after 25 months
- Tier 2 visitors archived after 6 months
- Command logs counts by tier
- `/api/restore-visitor` (if still exists) returns `restored=false` for archived visitors (graceful failure)

---

### B2) `analytics_events` — 6-Month Raw Event Retention

**Why:** Raw events are highest-volume table. Retention period balances debugging needs (recent data) with storage costs. 6 months allows for quarterly comparisons and seasonal analysis whilst remaining conservative.

**What to keep longer:** Aggregate tables (`experiment_conversions`, `funnel_daily_stats`, `*_daily_stats`) are retained indefinitely.

**Implementation:**

**Command: `analytics:prune-events`**

```php
// app/Console/Commands/PruneAnalyticsEvents.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PruneAnalyticsEvents extends Command
{
    protected $signature = 'analytics:prune-events
                            {--dry-run : Show counts without deleting}
                            {--before= : Delete events before this date (YYYY-MM-DD, defaults to 6 months ago)}
                            {--batch=10000 : Batch size for deletion}';

    protected $description = 'Delete analytics events older than retention period';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch');

        // Default: 6 months ago
        $beforeDate = $this->option('before')
            ? Carbon::parse($this->option('before'))
            : Carbon::now()->subMonths(6);

        $this->info("Pruning analytics_events before: {$beforeDate->toDateString()}");

        $query = DB::table('analytics_events')
            ->where('occurred_at', '<', $beforeDate);

        $count = $query->count();
        $this->info("Found {$count} events to prune.");

        if ($dryRun) {
            $this->warn('DRY RUN: No deletion performed.');

            // Show breakdown by event type
            $breakdown = DB::table('analytics_events')
                ->select('type', DB::raw('COUNT(*) as count'))
                ->where('occurred_at', '<', $beforeDate)
                ->groupBy('type')
                ->get();

            $this->table(['Type', 'Count'], $breakdown->map(fn($row) => [$row->type, $row->count]));

            return 0;
        }

        if ($count === 0) {
            $this->info('No events to prune.');
            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $deleted = 0;

        // Delete in batches (CASCADE will handle analytics_event_experiments)
        while (true) {
            $deletedBatch = DB::table('analytics_events')
                ->where('occurred_at', '<', $beforeDate)
                ->limit($batchSize)
                ->delete();

            if ($deletedBatch === 0) {
                break;
            }

            $deleted += $deletedBatch;
            $bar->advance($deletedBatch);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Deleted {$deleted} events.");

        // Log for observability
        \Log::info('Analytics events pruned', [
            'count' => $deleted,
            'before_date' => $beforeDate->toDateString(),
        ]);

        return 0;
    }
}
```

**Index requirement:** Ensure `analytics_events(occurred_at)` index exists (already present in migration).

**Scheduler:**
```php
$schedule->command('analytics:prune-events')
    ->monthlyOn(15, '03:00') // Mid-month, 3 AM
    ->withoutOverlapping()
    ->runInBackground();
```

**Acceptance criteria:**
- Dry run shows counts and breakdown by event type
- Events older than 6 months deleted
- Dependent rows in `analytics_event_experiments` cascade delete (verify FK exists)
- Aggregate tables (`experiment_conversions`, daily stats) unaffected
- Command logs deletion counts

---

### B3) `analytics_sessions` — 25-Month Session Retention

**Why:** Sessions are lower volume than events. 25-month retention enables year-over-year session analysis (e.g., "session length improved 15% vs last year").

**Session lifecycle:** Continuously updated during active browsing. `ended_at` set when session terminates (timeout, tab close, or explicit session end event).

**Implementation:**

**Command: `analytics:prune-sessions`**

```php
// app/Console/Commands/PruneAnalyticsSessions.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PruneAnalyticsSessions extends Command
{
    protected $signature = 'analytics:prune-sessions
                            {--dry-run}
                            {--before= : Delete sessions before this date (defaults to 25 months ago)}
                            {--batch=5000}';

    protected $description = 'Delete completed analytics sessions older than 25 months';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch');

        $beforeDate = $this->option('before')
            ? Carbon::parse($this->option('before'))
            : Carbon::now()->subMonths(25);

        $this->info("Pruning sessions ended before: {$beforeDate->toDateString()}");

        // Only prune completed sessions (ended_at is not null)
        $completedQuery = DB::table('analytics_sessions')
            ->whereNotNull('ended_at')
            ->where('ended_at', '<', $beforeDate);

        $completedCount = $completedQuery->count();
        $this->info("Found {$completedCount} completed sessions to prune.");

        // Also identify abandoned sessions (never ended, started >48 hours ago)
        $abandonedCutoff = Carbon::now()->subHours(48);
        $abandonedQuery = DB::table('analytics_sessions')
            ->whereNull('ended_at')
            ->where('started_at', '<', $abandonedCutoff);

        $abandonedCount = $abandonedQuery->count();
        $this->info("Found {$abandonedCount} abandoned sessions (never ended, started >48h ago).");

        if ($dryRun) {
            $this->warn('DRY RUN: No deletion performed.');
            return 0;
        }

        // Prune completed sessions
        if ($completedCount > 0) {
            $bar = $this->output->createProgressBar($completedCount);
            $deleted = 0;

            while (true) {
                $deletedBatch = (clone $completedQuery)->limit($batchSize)->delete();
                if ($deletedBatch === 0) break;

                $deleted += $deletedBatch;
                $bar->advance($deletedBatch);
            }

            $bar->finish();
            $this->newLine();
            $this->info("Deleted {$deleted} completed sessions.");
        }

        // Prune abandoned sessions (separate operation)
        if ($abandonedCount > 0) {
            $this->info("\nPruning abandoned sessions...");
            $abandonedDeleted = 0;

            while (true) {
                $deletedBatch = (clone $abandonedQuery)->limit($batchSize)->delete();
                if ($deletedBatch === 0) break;
                $abandonedDeleted += $deletedBatch;
            }

            $this->info("Deleted {$abandonedDeleted} abandoned sessions.");
        }

        return 0;
    }
}
```

**Scheduler:**
```php
$schedule->command('analytics:prune-sessions')
    ->monthlyOn(1, '03:30') // 1st of month, 3:30 AM
    ->withoutOverlapping();
```

**Acceptance criteria:**
- Sessions with `ended_at < 25 months ago` deleted
- Abandoned sessions (no `ended_at`, `started_at > 48h ago`) deleted
- Active sessions (recent `started_at`, no `ended_at`) retained

---

### B4) Experiment Tables — Lifecycle-Based Retention

**Why:** Experiments have defined start/end dates. Raw assignment/exposure data is only needed during active experiments and for post-analysis. After a grace period, archive or delete raw data whilst keeping aggregates indefinitely.

**Key consideration for long-tail experiments:** If experiments run for 6+ months, retention must account for full experiment duration plus attribution window.

**Recommended retention:** Keep raw data for experiments with `status IN ('completed', 'archived')` AND `ended_at < NOW() - (experiment.attribution_window_days + 365 days)`.

- 365 days post-experiment allows for full-year post-analysis ("did variant B show sustained impact 12 months later?")
- Accounts for max attribution window (e.g., 90 days for conversions)

**Tables affected:**
- `experiment_assignments` (one row per visitor per experiment)
- `experiment_exposures` (one row per exposure event)
- `analytics_event_experiments` (many-to-many: events ↔ experiments)

**Tables to keep indefinitely:**
- `experiments` (definitions)
- `experiment_variants` (variant configs)
- `experiment_conversions` (aggregates)

**Implementation:**

**Command: `experiments:prune-history`**

```php
// app/Console/Commands/PruneExperimentHistory.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PruneExperimentHistory extends Command
{
    protected $signature = 'experiments:prune-history
                            {--dry-run}
                            {--grace-days=365 : Days after experiment end to retain raw data}
                            {--batch=5000}';

    protected $description = 'Delete raw experiment data for ended experiments past retention period';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $graceDays = (int) $this->option('grace-days');
        $batchSize = (int) $this->option('batch');

        $this->info("Finding experiments ended >{$graceDays} days ago...");

        // Find experiments eligible for pruning
        $eligibleExperiments = DB::table('experiments')
            ->select('id', 'name', 'ended_at', 'attribution_window_days')
            ->whereIn('status', ['completed', 'archived'])
            ->whereNotNull('ended_at')
            ->where(function ($q) use ($graceDays) {
                // ended_at + attribution_window + grace < now
                $q->whereRaw("ended_at + INTERVAL '1 day' * COALESCE(attribution_window_days, 0) + INTERVAL '{$graceDays} days' < NOW()");
            })
            ->get();

        if ($eligibleExperiments->isEmpty()) {
            $this->info('No experiments eligible for pruning.');
            return 0;
        }

        $this->info("Found {$eligibleExperiments->count()} experiments eligible for pruning:");
        $this->table(
            ['ID', 'Name', 'Ended At', 'Attribution Window'],
            $eligibleExperiments->map(fn($e) => [
                $e->id,
                $e->name,
                $e->ended_at,
                $e->attribution_window_days ?? 0
            ])
        );

        if ($dryRun) {
            $assignmentCount = DB::table('experiment_assignments')
                ->whereIn('experiment_id', $eligibleExperiments->pluck('id'))
                ->count();
            $exposureCount = DB::table('experiment_exposures')
                ->whereIn('experiment_id', $eligibleExperiments->pluck('id'))
                ->count();
            $eventExperimentCount = DB::table('analytics_event_experiments')
                ->whereIn('experiment_id', $eligibleExperiments->pluck('id'))
                ->count();

            $this->info("\nWould delete:");
            $this->info("- {$assignmentCount} assignments");
            $this->info("- {$exposureCount} exposures");
            $this->info("- {$eventExperimentCount} event-experiment links");
            $this->warn('DRY RUN: No deletion performed.');
            return 0;
        }

        // Delete in batches
        $experimentIds = $eligibleExperiments->pluck('id')->toArray();

        $this->info("\nDeleting assignments...");
        $this->deleteBatched('experiment_assignments', 'experiment_id', $experimentIds, $batchSize);

        $this->info("Deleting exposures...");
        $this->deleteBatched('experiment_exposures', 'experiment_id', $experimentIds, $batchSize);

        $this->info("Deleting event-experiment links...");
        $this->deleteBatched('analytics_event_experiments', 'experiment_id', $experimentIds, $batchSize);

        $this->info('Experiment history pruning complete.');
        return 0;
    }

    protected function deleteBatched(string $table, string $column, array $ids, int $batchSize): void
    {
        $total = 0;
        while (true) {
            $deleted = DB::table($table)
                ->whereIn($column, $ids)
                ->limit($batchSize)
                ->delete();

            if ($deleted === 0) break;
            $total += $deleted;
        }
        $this->info("Deleted {$total} rows from {$table}.");
    }
}
```

**Scheduler:**
```php
$schedule->command('experiments:prune-history --grace-days=365')
    ->monthlyOn(1, '04:00')
    ->withoutOverlapping();
```

**Acceptance criteria:**
- Experiments with `status = 'running'` never pruned
- Ended experiments retain data for attribution_window + 365 days
- Aggregate table `experiment_conversions` unaffected
- Dry run shows accurate counts

---

### B5) Funnel Tables — Retain Aggregates, Prune Stale Progress

**Tables:**
- `funnel_progress` (per-visitor state per funnel) — PRUNE stale rows
- `funnel_daily_stats` (aggregates) — KEEP INDEFINITELY

**Why:** `funnel_progress` tracks individual visitor progression through funnels. Visitors who never complete or abandon at early stages create noise. Prune non-converted visitors after attribution window + buffer.

**Retention rule:** Delete `funnel_progress` rows where:
- `is_converted = false`
- `updated_at < NOW() - (MAX(funnels.attribution_window_days) + 180 days)`

180-day buffer (6 months) allows for very long conversion cycles whilst still controlling growth.

**Implementation:**

**Command: `funnels:prune-progress`**

```php
// app/Console/Commands/PruneFunnelProgress.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PruneFunnelProgress extends Command
{
    protected $signature = 'funnels:prune-progress
                            {--dry-run}
                            {--buffer-days=180 : Days beyond max attribution window to retain}
                            {--batch=5000}';

    protected $description = 'Delete non-converted funnel progress older than attribution window';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $bufferDays = (int) $this->option('buffer-days');
        $batchSize = (int) $this->option('batch');

        // Find max attribution window across all funnels
        $maxWindow = DB::table('funnels')->max('attribution_window_days') ?? 90;
        $this->info("Max funnel attribution window: {$maxWindow} days");

        $totalRetention = $maxWindow + $bufferDays;
        $cutoffDate = Carbon::now()->subDays($totalRetention);
        $this->info("Pruning non-converted progress updated before: {$cutoffDate->toDateString()}");

        $query = DB::table('funnel_progress')
            ->where('is_converted', false)
            ->where('updated_at', '<', $cutoffDate);

        $count = $query->count();
        $this->info("Found {$count} stale funnel progress rows.");

        if ($dryRun) {
            $this->warn('DRY RUN: No deletion performed.');

            // Show breakdown by funnel
            $breakdown = DB::table('funnel_progress')
                ->join('funnels', 'funnel_progress.funnel_id', '=', 'funnels.id')
                ->select('funnels.name', DB::raw('COUNT(*) as count'))
                ->where('funnel_progress.is_converted', false)
                ->where('funnel_progress.updated_at', '<', $cutoffDate)
                ->groupBy('funnels.name')
                ->get();

            $this->table(['Funnel', 'Stale Count'], $breakdown->map(fn($r) => [$r->name, $r->count]));
            return 0;
        }

        if ($count === 0) {
            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $deleted = 0;

        while (true) {
            $deletedBatch = (clone $query)->limit($batchSize)->delete();
            if ($deletedBatch === 0) break;

            $deleted += $deletedBatch;
            $bar->advance($deletedBatch);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Deleted {$deleted} stale funnel progress rows.");

        return 0;
    }
}
```

**Scheduler:**
```php
$schedule->command('funnels:prune-progress')
    ->monthlyOn(15, '04:00')
    ->withoutOverlapping();
```

**Acceptance criteria:**
- Converted progress (`is_converted = true`) never deleted
- Non-converted progress deleted after `max_attribution_window + 180 days`
- `funnel_daily_stats` untouched

---

### B6) Domain Analytics — Retain Indefinitely

**Tables:** `framework_selections`, `question_analytics`, `workflow_analytics`, plus corresponding `*_daily_stats` tables

**Decision:** **KEEP ALL DATA INDEFINITELY**

**Rationale:**
- Workflow failure diagnosis is essential for product quality ("why did workflow 1 fail for INTJ users in Q1 2024?")
- Framework selection patterns inform prompt generation improvements
- Question effectiveness analysis drives UX iteration
- Data volume expected to be manageable (tied to prompt runs, not raw page views)
- These tables represent core product analytics, not marketing noise

**No pruning command needed.**

**Index maintenance:** Ensure indexes exist on timestamp columns for efficient querying:
```sql
-- Verify indexes in migrations:
framework_selections(selected_at)
question_analytics(presented_at)
workflow_analytics(started_at)
```

---

### B7) IP Address Scrubbing (Privacy Enhancement)

**Why:** IP addresses are personal data under GDPR/CCPA. Retaining full IPs long-term increases compliance risk. After short window (fraud detection, abuse prevention), truncate or hash IPs.

**Recommendation:** After **30 days**, either:
- **Option A:** Truncate IP to /24 (e.g., `192.168.1.123` → `192.168.1.0`)
- **Option B:** Hash IP with salt (one-way, preserves uniqueness for abuse detection)
- **Option C:** Null out IP entirely (if no longer needed)

**Conservative approach:** Use Option A (truncation) to preserve geolocation capability at city level whilst protecting individual identity.

**Implementation:**

**Command: `visitors:scrub-ip-addresses`**

```php
// app/Console/Commands/ScrubVisitorIpAddresses.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScrubVisitorIpAddresses extends Command
{
    protected $signature = 'visitors:scrub-ip-addresses
                            {--dry-run}
                            {--age-days=30 : Age in days before scrubbing}
                            {--batch=5000}';

    protected $description = 'Truncate IP addresses for visitors older than 30 days';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $ageDays = (int) $this->option('age-days');
        $batchSize = (int) $this->option('batch');

        $cutoffDate = Carbon::now()->subDays($ageDays);
        $this->info("Scrubbing IPs for visitors created before: {$cutoffDate->toDateString()}");

        $query = DB::table('visitors')
            ->whereNotNull('ip_address')
            ->where('first_visit_at', '<', $cutoffDate)
            ->where('ip_address', 'NOT LIKE', '%.0'); // Skip already truncated

        $count = $query->count();
        $this->info("Found {$count} visitors with full IP addresses.");

        if ($dryRun) {
            $this->warn('DRY RUN: No updates performed.');
            return 0;
        }

        if ($count === 0) {
            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $updated = 0;

        // PostgreSQL-specific IP truncation
        while (true) {
            $affected = DB::update("
                UPDATE visitors
                SET ip_address = host(network(inet(ip_address) & inet('255.255.255.0')))
                WHERE id IN (
                    SELECT id FROM visitors
                    WHERE ip_address IS NOT NULL
                      AND first_visit_at < ?
                      AND ip_address NOT LIKE '%.0'
                    LIMIT ?
                )
            ", [$cutoffDate, $batchSize]);

            if ($affected === 0) break;

            $updated += $affected;
            $bar->advance($affected);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Truncated {$updated} IP addresses.");

        return 0;
    }
}
```

**Scheduler:**
```php
$schedule->command('visitors:scrub-ip-addresses')
    ->weekly()
    ->fridays()
    ->at('05:00')
    ->withoutOverlapping();
```

**Acceptance criteria:**
- IPs older than 30 days truncated to /24 (last octet = 0)
- Recent IPs (< 30 days) remain full for fraud detection
- User accounts unaffected (tied to visitors, but user table has no IPs)

---

## Part C — Backup & Disaster Recovery Strategy

### Archive Table Backup & Cold Storage

**Purpose:** Archived data must be recoverable for legal/compliance requests, audits, or operational needs.

**Strategy:**

1. **Archive tables are in production database**
   - `visitors_archive`
   - Future: `analytics_events_archive` (if needed)

2. **Regular backups include archive tables**
   - Standard PostgreSQL `pg_dump` or automated backup service (AWS RDS snapshots, etc.)
   - Retention: Follow database backup policy (e.g., daily for 30 days, weekly for 12 months)

3. **Optional: Export to cold storage (S3, data warehouse)**
   - For long-term retention beyond database backup windows
   - Export format: Parquet or CSV (compressed)
   - Schedule: Quarterly or after major pruning runs

**Implementation: S3 Export Command**

```php
// app/Console/Commands/ExportArchivedVisitors.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExportArchivedVisitors extends Command
{
    protected $signature = 'visitors:export-archive
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD)}
                            {--format=csv : Export format: csv or json}';

    protected $description = 'Export archived visitors to S3 for cold storage';

    public function handle()
    {
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::now()->subYear();
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now();
        $format = $this->option('format');

        $this->info("Exporting archived visitors from {$from->toDateString()} to {$to->toDateString()}...");

        $visitors = DB::table('visitors_archive')
            ->whereBetween('archived_at', [$from, $to])
            ->get();

        if ($visitors->isEmpty()) {
            $this->info('No archived visitors in date range.');
            return 0;
        }

        $this->info("Found {$visitors->count()} archived visitors.");

        // Generate filename
        $filename = sprintf(
            'archived-visitors-%s-to-%s.%s',
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
            $format
        );

        // Convert to desired format
        $content = $format === 'json'
            ? $visitors->toJson(JSON_PRETTY_PRINT)
            : $this->toCsv($visitors);

        // Upload to S3
        Storage::disk('s3')->put("archives/visitors/{$filename}", $content);

        $this->info("Exported to: s3://archives/visitors/{$filename}");
        $this->info('Size: ' . number_format(strlen($content) / 1024, 2) . ' KB');

        return 0;
    }

    protected function toCsv($collection): string
    {
        $csv = '';
        $headers = array_keys((array) $collection->first());
        $csv .= implode(',', $headers) . "\n";

        foreach ($collection as $row) {
            $csv .= implode(',', array_map(function ($value) {
                return '"' . str_replace('"', '""', $value ?? '') . '"';
            }, (array) $row)) . "\n";
        }

        return $csv;
    }
}
```

**Configure S3 disk** (if not already configured):
```php
// config/filesystems.php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
],
```

**Schedule quarterly exports:**
```php
$schedule->command('visitors:export-archive --from="3 months ago" --to=now')
    ->quarterly()
    ->withoutOverlapping();
```

---

### Data Recovery Procedures

**Scenario 1: Accidental deletion (recent)**

1. Restore from latest database backup (point-in-time recovery if available)
2. Run pruning commands with `--dry-run` first in future

**Scenario 2: Need archived visitor data (compliance request)**

1. Query `visitors_archive` table directly:
   ```sql
   SELECT * FROM visitors_archive WHERE user_id = ? OR id = ?;
   ```
2. If not in database (older than backup retention), restore from S3 export

**Scenario 3: Restore specific archived visitors to main table**

```php
// app/Console/Commands/RestoreArchivedVisitor.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreArchivedVisitor extends Command
{
    protected $signature = 'visitors:restore {visitor_id : UUID of archived visitor}';

    protected $description = 'Restore an archived visitor back to the main table';

    public function handle()
    {
        $visitorId = $this->argument('visitor_id');

        $archived = DB::table('visitors_archive')->where('id', $visitorId)->first();

        if (!$archived) {
            $this->error("Visitor {$visitorId} not found in archive.");
            return 1;
        }

        // Remove archive metadata columns
        $data = (array) $archived;
        unset($data['archived_at'], $data['archive_tier'], $data['archive_reason']);

        DB::table('visitors')->insert($data);

        $this->info("Restored visitor {$visitorId} to main table.");
        $this->info("Note: Archive entry still exists. Delete manually if needed.");

        return 0;
    }
}
```

---

### Backup Verification & Testing

**Quarterly checklist:**

1. Verify database backups exist and are restorable
2. Test restore of `visitors_archive` table from backup
3. Test export command to S3 and verify file integrity
4. Document recovery time objective (RTO) and recovery point objective (RPO)

**Recommended RTO/RPO:**
- Database backup RTO: 4 hours (time to restore production database)
- Archive data RTO: 24 hours (time to restore from S3 cold storage)
- RPO: 24 hours (max acceptable data loss)

---

## Part D — Privacy Policy Updates Required

To comply with GDPR/CCPA disclosure requirements, update your privacy policy (`resources/js/Pages/Legal/Privacy.vue` or wherever privacy policy lives) with the following information:

### Section: Data Retention Periods

**Add this table or equivalent text:**

| Data Category | What We Collect | Retention Period | Why We Keep It |
|---------------|-----------------|------------------|----------------|
| **Visitor tracking data** | IP address (truncated after 30 days), browser/device info, UTM parameters, referrer, landing page | 6 months for low-engagement visitors; 25 months for visitors with attribution data; indefinitely for converted users | Marketing attribution, fraud prevention, analytics |
| **Analytics events** | Page views, clicks, form submissions, prompt builder interactions | 6 months (raw events); indefinitely (aggregated statistics) | Product improvement, debugging, usage analytics |
| **Analytics sessions** | Session duration, entry/exit pages, bounce rate, conversion events | 25 months | Funnel analysis, user behaviour research |
| **A/B test assignments** | Which variant you saw, exposure timestamps | Duration of experiment + 12 months; indefinitely (aggregated results) | Statistical analysis, long-tail effect measurement |
| **User accounts** | Email, name, password (hashed), subscription tier, personality type | Until account deletion + 90 days for fraud prevention | Service delivery, authentication, personalisation |
| **Payment history** | Subscription details, invoices, payment method (tokenised) | 7 years | Tax compliance, dispute resolution, legal requirements |
| **Workflow analytics** | Prompt run details, question responses, framework selections, workflow failures | Indefinitely | Service improvement, debugging, quality assurance |

**Add this explanatory text:**

"We retain your data only as long as necessary for the purposes described in this policy. You can request deletion of your data at any time by contacting us at [privacy@bettrprompt.com]. Some data may be retained in anonymised or aggregated form for statistical purposes, which does not identify you personally."

**CCPA-specific addition:**

"California residents: You have the right to request details about the personal information we collect, how we use it, and how long we keep it. You can also request deletion of your personal information. To exercise these rights, contact us at [privacy@bettrprompt.com]. We will respond within 45 days."

---

## Part E — Implementation Roadmap

### Phase 1: Foundation (Week 1-2)

**Priority: Critical**

1. Remove localStorage `visitor_id` pattern
   - Update `resources/js/app.ts`
   - Remove/restrict `/api/restore-visitor` endpoint
   - Update docs

2. Remove `visit_count` column
   - Create migration to drop column
   - Update Visitor model, middleware, resources, tests
   - Run migration in staging first

3. Implement bot detection
   - Add `isKnownBot()` method to TrackVisitor middleware
   - Test with simulated bot User-Agents

4. Add indexes for retention queries
   - Migration: `visitors(last_visit_at)`
   - Migration: `visitors_archive` table creation

**Tests:**
- All existing tests pass
- Visitor tracking still works for humans
- Bots don't create visitor records

---

### Phase 2: Archive & Prune Commands (Week 3-4)

**Priority: High**

1. Implement `visitors:cleanup` command
   - Tier 0, 1, 2 logic
   - Archive-then-delete workflow
   - Dry-run mode
   - Progress bars and logging

2. Implement `analytics:prune-events` command
   - 6-month retention
   - Batch deletion
   - Event type breakdown

3. Implement `analytics:prune-sessions` command
   - 25-month retention for completed sessions
   - Abandoned session handling

4. Implement IP scrubbing command
   - 30-day truncation
   - PostgreSQL-specific SQL

**Tests:**
- Run all commands with `--dry-run` in staging
- Verify counts match expectations
- Check archive tables populated correctly
- Ensure Tier 0 visitors never deleted

---

### Phase 3: Experiment & Funnel Pruning (Week 5-6)

**Priority: Medium**

1. Implement `experiments:prune-history` command
   - Account for attribution window + 365 days
   - Verify aggregates unaffected

2. Implement `funnels:prune-progress` command
   - Max attribution window + 180-day buffer
   - Preserve converted progress

**Tests:**
- Dry run in staging
- Verify active experiments untouched
- Check funnel stats remain accurate

---

### Phase 4: Backup & Recovery (Week 7)

**Priority: Medium**

1. Implement S3 export command
   - CSV/JSON format support
   - Quarterly export schedule

2. Implement restore command
   - Single visitor restoration from archive

3. Document recovery procedures
   - RTO/RPO targets
   - Step-by-step restore instructions

**Tests:**
- Export sample archived data to S3
- Verify file integrity
- Test restore command

---

### Phase 5: Scheduler & Monitoring (Week 8)

**Priority: High**

1. Register all commands in scheduler
   - Monthly, weekly, quarterly cadence
   - Stagger times to avoid overlaps

2. Add observability logging
   - Log pruning counts, durations, errors
   - Set up alerts for failures

3. Update privacy policy
   - Add retention periods table
   - GDPR/CCPA-compliant language

**Tests:**
- Manually trigger scheduled commands
- Verify logging output
- Test alert notifications

---

### Phase 6: Production Deployment & Validation (Week 9-10)

**Priority: Critical**

1. **Staging validation:**
   - Run all commands with `--dry-run`
   - Review counts and verify logic
   - Load test with production-like data volumes

2. **Production deployment:**
   - Deploy code changes (migrations, commands, middleware)
   - Run `visitors:cleanup --dry-run` first
   - Monitor for errors

3. **First real pruning run:**
   - Start with Tier 2 only: `visitors:cleanup --tier=tier_2`
   - Monitor database size reduction
   - Check application functionality

4. **Enable full automation:**
   - Enable scheduler for all commands
   - Monitor for 1 month
   - Adjust retention periods if needed

---

## Part F — Monitoring & Observability

### Metrics to Track

**Table growth rates** (daily):
- `visitors` row count
- `analytics_events` row count
- `analytics_sessions` row count
- `experiment_exposures` row count
- `funnel_progress` row count
- `visitors_archive` row count

**Pruning effectiveness** (per run):
- Rows archived by tier
- Rows deleted
- Execution duration
- Errors/failures

**Data quality checks:**
- % of visitors with UTM data
- % of sessions with conversions
- Bot traffic ratio (if using `is_bot` flag)
- IP truncation coverage

### Logging Standards

All pruning commands should log:
```php
\Log::info('Command completed', [
    'command' => 'visitors:cleanup',
    'dry_run' => false,
    'tier_2_archived' => 1523,
    'tier_1_archived' => 342,
    'duration_seconds' => 45,
    'date' => now()->toDateString(),
]);
```

### Alerting Rules

Set up alerts for:
- Pruning command failures (exit code != 0)
- Unexpected row count drops (>20% decrease in main tables)
- Archive table growth exceeding expectations
- Scheduler job not running on schedule

---

## Summary

This plan provides:

✅ **GDPR/CCPA compliance** with documented retention periods and legal justifications
✅ **Conservative data retention** maximising analytical capability (25 months for YoY, indefinite for workflow analytics)
✅ **Table growth control** via tiered visitor retention, event pruning, and experiment lifecycle management
✅ **Reversibility & safety** through archive-then-delete workflow and S3 cold storage
✅ **SEO-safe bot handling** using conservative crawler detection
✅ **Clean architecture** removing localStorage pattern and `visit_count` inconsistencies
✅ **Disaster recovery** with backup, export, and restore procedures
✅ **Long-tail experiment support** with attribution window + 12-month retention

**Next steps:**
1. Review this plan with your team
2. Adjust retention periods if needed (all values are recommendations)
3. Begin Phase 1 implementation
4. Test thoroughly in staging before production deployment

---

**Document version:** 2.0
**Last updated:** 2026-01-13
**Maintained by:** Engineering Team
**Review cadence:** Quarterly (after each major pruning cycle)
