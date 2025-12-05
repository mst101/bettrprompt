# Implementation Plan: IP Geolocation & Expanded User Profiles

## Overview

Add IP-based geolocation using MaxMind GeoLite2 and expand user profiles with professional, team, budget, and tool preferences to provide better context for prompt optimization.

## User Decisions

- **Geolocation Service**: MaxMind GeoLite2 (free database, local lookups)
- **Lookup Timing**: Both Visitor creation AND User registration
- **Profile Fields**: Professional context, language preference, team/budget context, tool ecosystem
- **User Control**: Auto-detect with manual override in profile settings

## Architecture

### Data Flow
```
IP Address → GeolocationService → LocationData → Visitor Model
                                                      ↓
                                          (on registration)
                                                      ↓
                                                  User Model
                                                      ↓
                                          (on workflow trigger)
                                                      ↓
                                          PromptFrameworkService
                                                      ↓
                                              n8n Workflows
```

### Key Components

1. **GeolocationService**: Manages MaxMind database, performs IP lookups, handles caching
2. **LocationData DTO**: Structured data transfer object for location information
3. **TrackVisitor Middleware**: Modified to perform geolocation on visitor creation
4. **User/Visitor Models**: Extended with location and profile fields
5. **Profile Management**: New API endpoints and UI forms for managing extended profile
6. **Workflow Integration**: Pass user context to n8n workflows for optimization

## Implementation Phases

### Phase 1: Foundation & Geolocation Service

**Install Dependencies**
```bash
composer require geoip2/geoip2:~3.0
```

**Create Core Service**
- `/app/Services/GeolocationService.php` - Main service class
  - `lookupIp(string $ip): ?LocationData` - Performs IP lookup with caching
  - `downloadDatabase(): bool` - Downloads MaxMind database
  - `isDatabaseStale(): bool` - Checks if database needs update
  - Private IP detection and validation
  - Cache management (30-day TTL)
  - Error handling with graceful fallbacks

- `/app/DTOs/LocationData.php` - Data transfer object
  - Properties: countryCode, countryName, region, city, timezone, currencyCode, latitude, longitude, languageCode, detectedAt
  - `toArray()` and `fromArray()` methods

**Create Configuration**
- `/config/geoip.php` - Configuration file
  - MaxMind settings (license key, database path, update interval)
  - Cache settings (TTL, prefix)
  - Privacy settings (anonymise coordinates)
  - Country → currency mapping
  - Country → language mapping

**Create Console Commands**
- `/app/Console/Commands/UpdateGeoIpDatabase.php` - Download database command
  - `geoip:update {--force}` signature
  - Schedule weekly on Mondays at 2am
- `/app/Console/Commands/GeolocationAnalytics.php` - Analytics command
  - Show coverage rates, top countries, timezone distribution

**Environment Variables**
```env
MAXMIND_LICENSE_KEY=your_key
MAXMIND_ACCOUNT_ID=your_id
GEOIP_ENABLED=true
```

### Phase 2: Database Schema

**Migration 1: Add Geolocation to Visitors**
- `/database/migrations/YYYY_MM_DD_add_geolocation_to_visitors_table.php`
- Fields: country_code, country_name, region, city, timezone, currency_code, latitude, longitude, location_detected_at, language_code
- Indexes: country_code, timezone

**Migration 2: Add Geolocation to Users**
- `/database/migrations/YYYY_MM_DD_add_geolocation_to_users_table.php`
- Same location fields as visitors
- Additional: location_manually_set, language_manually_set (booleans)

**Migration 3: Add Professional Context to Users**
- `/database/migrations/YYYY_MM_DD_add_professional_context_to_users_table.php`
- Fields: job_title, industry, experience_level (enum: entry/mid/senior/expert), company_size (enum: solo/small/medium/large/enterprise)
- Indexes: industry, experience_level

**Migration 4: Add Team & Budget Context to Users**
- `/database/migrations/YYYY_MM_DD_add_team_budget_context_to_users_table.php`
- Fields: team_size (enum: solo/small/medium/large), team_role (enum: individual/lead/manager/director/executive), budget_consciousness (enum: free_only/free_first/mixed/premium_ok/enterprise)
- Indexes: team_role, budget_consciousness

**Migration 5: Add Tool Preferences to Users**
- `/database/migrations/YYYY_MM_DD_add_tool_preferences_to_users_table.php`
- Fields: preferred_tools (JSON), work_mode (enum: office/hybrid/remote/freelance), primary_programming_language
- Profile tracking: profile_completion_percentage, profile_last_updated_at

**Model Updates**

Update `/app/Models/Visitor.php`:
- Add all location fields to $fillable
- Add casts for location_detected_at, latitude, longitude
- Add methods: `hasLocationData()`, `getLocationSummary()`

Update `/app/Models/User.php`:
- Add all location, professional, team, budget, tool fields to $fillable
- Add appropriate casts
- Add methods:
  - `calculateProfileCompletion(): int` - Calculate percentage
  - `updateProfileCompletion(): void` - Update stored percentage
  - `getUserContext(): array` - Build context for workflows
  - `hasLocationData(): bool` - Check if location exists

### Phase 3: Visitor Tracking Enhancement

**Modify TrackVisitor Middleware**
- `/app/Http/Middleware/TrackVisitor.php`
- In `createVisitor()` method:
  - Call `GeolocationService::lookupIp($request->ip())`
  - Store location data in visitor record
  - Detect browser language from Accept-Language header
  - Use geolocation language if available, otherwise browser language
  - Handle errors gracefully (don't block visitor creation)

**Alternative: Background Job Approach**
- Create `/app/Jobs/UpdateVisitorLocation.php`
- Dispatch job after visitor creation (non-blocking)
- Job performs geolocation lookup and updates visitor record
- Use 'low-priority' queue

**Backfill Existing Visitors**
- `/app/Console/Commands/BackfillVisitorLocations.php`
- Command: `geoip:backfill-visitors {--limit=100} {--dry-run}`
- Find visitors with null country_code but valid ip_address
- Perform geolocation lookup and update

### Phase 4: User Registration Flow

**Update RegisteredUserController**
- `/app/Http/Controllers/Auth/RegisteredUserController.php`
- In `store()` method:
  - After user creation, check for visitor cookie
  - If visitor found, copy location data from visitor to user
  - Copy personality data, referrer, AND location
  - Set location_manually_set = false (auto-detected)
  - Calculate initial profile completion percentage
  - Mark visitor as converted

**Update OAuthController**
- `/app/Http/Controllers/Auth/OAuthController.php`
- Similar changes in `findOrCreateUser()` method
- Copy location from visitor when OAuth user registers

**Backfill Existing Users**
- `/app/Console/Commands/BackfillUserLocations.php`
- Command: `geoip:backfill-users {--limit=100} {--dry-run}`
- Strategy:
  1. Try to copy from most recent visitor record
  2. If no visitor, lookup from session IP
  3. Update profile completion

### Phase 5: Profile Management Backend

**Update ProfileController**
- `/app/Http/Controllers/ProfileController.php`

Add new methods:
- `updateLocation(Request $request): RedirectResponse`
  - Validate: country_code, timezone, currency_code, language_code
  - Set location_manually_set = true
  - Update profile completion

- `updateProfessional(Request $request): RedirectResponse`
  - Validate: job_title, industry, experience_level, company_size
  - Update profile completion

- `updateTeam(Request $request): RedirectResponse`
  - Validate: team_size, team_role, work_mode
  - Update profile completion

- `updateBudget(Request $request): RedirectResponse`
  - Validate: budget_consciousness
  - Update profile completion

- `updateTools(Request $request): RedirectResponse`
  - Validate: preferred_tools (array), primary_programming_language
  - Update profile completion

- `detectLocation(Request $request, GeolocationService): RedirectResponse`
  - Re-detect location from current IP
  - Update user record
  - Return success message with detected location

- `clearLocation(Request $request): RedirectResponse`
  - Clear all location fields
  - Set location_manually_set = false
  - Update profile completion

Update `edit()` method:
- Pass additional Inertia props: profileCompletion, locationData, professionalData, teamData, budgetData, toolsData

**Add Routes**
- `/routes/web.php`
- Add in auth middleware group:
  - `PATCH /profile/location` → updateLocation
  - `PATCH /profile/professional` → updateProfessional
  - `PATCH /profile/team` → updateTeam
  - `PATCH /profile/budget` → updateBudget
  - `PATCH /profile/tools` → updateTools
  - `POST /profile/location/detect` → detectLocation
  - `DELETE /profile/location` → clearLocation

### Phase 6: Profile Management Frontend

**Update Profile Edit Page**
- `/resources/js/Pages/Profile/Edit.vue`
- Add profile completion progress bar at top
- Add new form sections (after personality, before account info):
  1. Location Preferences
  2. Professional Context
  3. Team Context
  4. Budget Preferences
  5. Tool Preferences

**Create Form Components**

1. `/resources/js/Pages/Profile/Partials/UpdateLocationForm.vue`
   - Show auto-detected location (if available)
   - Dropdowns: country, timezone, currency, language
   - Button: "Detect from current location"
   - Button: "Clear location data"
   - Save button

2. `/resources/js/Pages/Profile/Partials/UpdateProfessionalForm.vue`
   - Text input: job_title
   - Text input/dropdown: industry
   - Dropdown: experience_level (Entry/Mid/Senior/Expert)
   - Dropdown: company_size (Solo/Small/Medium/Large/Enterprise)
   - Save button

3. `/resources/js/Pages/Profile/Partials/UpdateTeamForm.vue`
   - Dropdown: team_size (Solo/Small/Medium/Large)
   - Dropdown: team_role (Individual/Lead/Manager/Director/Executive)
   - Dropdown: work_mode (Office/Hybrid/Remote/Freelance)
   - Save button

4. `/resources/js/Pages/Profile/Partials/UpdateBudgetForm.vue`
   - Radio buttons: budget_consciousness
     - Free only
     - Prefer free, will pay if necessary
     - Mixed (free and paid)
     - Premium tools OK
     - Enterprise budget
   - Save button

5. `/resources/js/Pages/Profile/Partials/UpdateToolsForm.vue`
   - Multi-select/tags: preferred_tools (categories: OS, IDE, Cloud, AI, Design, PM)
   - Text input: primary_programming_language
   - Save button

**TypeScript Types**
- Create interfaces for LocationData, ProfessionalData, TeamData, BudgetData, ToolsData
- Update User type to include new fields

### Phase 7: Workflow Integration

**Update PromptFrameworkService**
- `/app/Services/PromptFrameworkService.php`

Add `buildUserContext()` method:
- Accepts raw user context array
- Returns filtered, structured context for workflows
- Structure:
  ```php
  [
    'location' => ['country', 'timezone', 'currency', 'language'],
    'professional' => ['role', 'industry', 'experience', 'company_size'],
    'team' => ['size', 'role', 'work_mode'],
    'preferences' => ['budget', 'tools', 'primary_language']
  ]
  ```

Update method signatures:
- `preAnalyseTask(..., ?array $userContext = null)`
- `analyseTask(..., ?array $userContext = null)`
- `generatePrompt(..., ?array $userContext = null)`

Add user_context to payload when calling n8n webhooks.

**Update Controllers**
- Wherever `PromptFrameworkService` methods are called:
  - Get user context via `$user->getUserContext()`
  - For guests, build minimal context from visitor location
  - Pass context to service methods

**Update n8n Workflows**

Modify `/n8n/workflow_0_pre_analysis.json`:
- Update "Prepare Prompt" node to receive user_context
- Add context string to system prompt:
  ```
  # User Context
  - Location: {country} ({timezone})
  - Language: {language}
  - Currency: {currency}
  - Professional: {role} in {industry} ({experience} level)
  - Team: {size} team, {role} ({work_mode})
  - Budget: {budget}
  ```
- Use context for better contextual questions

Similar updates for:
- `/n8n/workflow_1_analysis.json`
- `/n8n/workflow_2_generation.json`

### Phase 8: Testing & Validation

**Unit Tests**
- `/tests/Unit/GeolocationServiceTest.php`
  - Test IP validation
  - Test private IP detection
  - Test lookup returns null for invalid/private IPs
  - Test caching
  - Test database download

**Feature Tests**
- `/tests/Feature/ProfileLocationTest.php`
  - Test user can update location
  - Test location is copied from visitor on registration
  - Test OAuth registration copies location
  - Test detect location endpoint
  - Test clear location endpoint

- `/tests/Feature/ProfileProfessionalTest.php`
  - Test updating professional context
  - Test validation rules

- `/tests/Feature/WorkflowContextTest.php`
  - Test user context is passed to workflows
  - Test context structure is correct
  - Test workflow payload contains expected fields

**Integration Tests**
- Test end-to-end flow: visitor → registration → location copy → prompt workflow
- Verify profile completion updates correctly
- Test backfill commands work

### Phase 9: Privacy & Security

**Privacy Considerations**
- Add geolocation notice to privacy policy
- Implement coordinate anonymisation (round to 0.01 for ~1km accuracy)
- Provide clear opt-out mechanism (clear location button)
- Include location data in GDPR data export
- Include location data in account deletion

**Security Measures**
- Store MaxMind license key in environment variables only
- Rotate API key every 90 days
- Implement rate limiting on geolocation endpoints
- Validate all user inputs for profile updates
- Use proper authorization policies (users can only edit own profile)

**Monitoring**
- Track geolocation success rate
- Monitor database age
- Alert on consecutive failures (>10)
- Track profile completion rates
- Monitor cache hit rates

## Critical Files (Implementation Order)

1. `/app/Services/GeolocationService.php` - Core geolocation service
2. `/app/DTOs/LocationData.php` - Location data transfer object
3. `/config/geoip.php` - Configuration file
4. `/database/migrations/YYYY_MM_DD_add_geolocation_to_visitors_table.php` - Visitor schema
5. `/database/migrations/YYYY_MM_DD_add_geolocation_to_users_table.php` - User location schema
6. `/database/migrations/YYYY_MM_DD_add_professional_context_to_users_table.php` - Professional fields
7. `/database/migrations/YYYY_MM_DD_add_team_budget_context_to_users_table.php` - Team/budget fields
8. `/database/migrations/YYYY_MM_DD_add_tool_preferences_to_users_table.php` - Tool preferences
9. `/app/Models/User.php` - Model updates with new fields and methods
10. `/app/Models/Visitor.php` - Model updates for location fields
11. `/app/Http/Middleware/TrackVisitor.php` - Capture location on visitor creation
12. `/app/Http/Controllers/Auth/RegisteredUserController.php` - Copy location on registration
13. `/app/Http/Controllers/ProfileController.php` - Profile management endpoints
14. `/routes/web.php` - Add new routes
15. `/app/Services/PromptFrameworkService.php` - Pass context to workflows
16. `/resources/js/Pages/Profile/Edit.vue` - Main profile page
17. Form components (UpdateLocationForm, UpdateProfessionalForm, etc.)
18. `/n8n/workflow_0_pre_analysis.json` - Update to receive user context
19. `/n8n/workflow_1_analysis.json` - Update to use user context
20. `/n8n/workflow_2_generation.json` - Update to use user context

## Deployment Checklist

**Pre-deployment**
- [ ] Sign up for MaxMind GeoLite2 account
- [ ] Get license key and account ID
- [ ] Test migrations on staging
- [ ] Review privacy policy updates
- [ ] Backup production database

**Deployment**
- [ ] Deploy code to production
- [ ] Set environment variables
- [ ] Run migrations
- [ ] Download GeoIP database: `php artisan geoip:update`
- [ ] Build frontend assets
- [ ] Clear caches
- [ ] Restart queue workers

**Post-deployment**
- [ ] Monitor error logs
- [ ] Check geolocation success rate: `php artisan geoip:analytics`
- [ ] Run backfill for visitors (batched)
- [ ] Run backfill for users (batched)
- [ ] Verify new registrations copy location
- [ ] Test profile forms
- [ ] Verify workflow receives context

## Additional Useful Fields (Future Enhancement)

Consider adding in future iterations:
- Communication style preference (direct/detailed/visual/conversational)
- Learning style (visual/auditory/reading/kinesthetic)
- AI verbosity preference (concise/balanced/detailed)
- Typical working hours (JSON)
- Accessibility needs (JSON array)
- Output format preference (code_only/with_comments/with_explanation)

## Success Metrics

Track these metrics to measure success:
- Geolocation coverage rate (% of users/visitors with location)
- Profile completion rates
- Geolocation success rate (lookups successful / total attempts)
- Prompt quality improvement (subjective feedback)
- User engagement with profile features
- Context usage in workflows (how often is context non-empty)
