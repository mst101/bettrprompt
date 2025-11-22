# Framework Selector Phase 2: User Profile Enhancement

## Overview

Phase 2 builds upon Phase 1's prompt engineering improvements by introducing persistent user profile data that can be leveraged during question generation. This allows the system to automatically skip questions where the answer is already known, reducing friction and improving the user experience.

## Problem Statement

Currently, the Framework Selector must ask about contextual information (location, age bracket, experience level) for every prompt optimisation, even if the user has already provided this information previously. This leads to:

- Repetitive questions that frustrate users
- Longer time-to-value for returning users
- Missed opportunities to personalise prompts based on known user context

## Proposed Solution

Extend the user profile beyond personality type to include demographic and contextual information that can be referenced during prompt optimisation.

## Database Schema Changes

### New Columns for `users` Table

```sql
-- Migration: add_profile_fields_to_users_table
ALTER TABLE users ADD COLUMN country_code VARCHAR(2) NULL;
ALTER TABLE users ADD COLUMN timezone VARCHAR(50) NULL;
ALTER TABLE users ADD COLUMN currency_code VARCHAR(3) NULL;
ALTER TABLE users ADD COLUMN age_bracket VARCHAR(20) NULL;
ALTER TABLE users ADD COLUMN occupation_category VARCHAR(50) NULL;
ALTER TABLE users ADD COLUMN education_level VARCHAR(50) NULL;
ALTER TABLE users ADD COLUMN family_lifecycle VARCHAR(50) NULL;
ALTER TABLE users ADD COLUMN professional_experience VARCHAR(20) NULL;

-- Add indexes for common queries
CREATE INDEX idx_users_country_code ON users(country_code);
CREATE INDEX idx_users_occupation_category ON users(occupation_category);
```

### Field Definitions

| Field | Type | Description | Example Values |
|-------|------|-------------|----------------|
| `country_code` | VARCHAR(2) | ISO 3166-1 alpha-2 country code | GB, US, AU, CA |
| `timezone` | VARCHAR(50) | IANA timezone identifier | Europe/London, America/New_York |
| `currency_code` | VARCHAR(3) | ISO 4217 currency code | GBP, USD, EUR, AUD |
| `age_bracket` | VARCHAR(20) | Age range | 18-24, 25-34, 35-44, 45-54, 55-64, 65+ |
| `occupation_category` | VARCHAR(50) | Broad occupation type | Technology, Healthcare, Education, Finance, Creative, Trades, Retail, Student, Retired |
| `education_level` | VARCHAR(50) | Highest education level | Secondary, Undergraduate, Postgraduate, Doctorate, Vocational |
| `family_lifecycle` | VARCHAR(50) | Family status | Single, Couple, Young Family, Established Family, Empty Nester |
| `professional_experience` | VARCHAR(20) | Years of experience | 0-2, 3-5, 6-10, 11-15, 16+ |

### Corresponding Changes for `visitors` Table

For consistency with guest users, add the same fields to the `visitors` table:

```sql
-- Migration: add_profile_fields_to_visitors_table
ALTER TABLE visitors ADD COLUMN country_code VARCHAR(2) NULL;
ALTER TABLE visitors ADD COLUMN timezone VARCHAR(50) NULL;
ALTER TABLE visitors ADD COLUMN currency_code VARCHAR(3) NULL;
ALTER TABLE visitors ADD COLUMN age_bracket VARCHAR(20) NULL;
ALTER TABLE visitors ADD COLUMN occupation_category VARCHAR(50) NULL;
ALTER TABLE visitors ADD COLUMN education_level VARCHAR(50) NULL;
ALTER TABLE visitors ADD COLUMN family_lifecycle VARCHAR(50) NULL;
ALTER TABLE visitors ADD COLUMN professional_experience VARCHAR(20) NULL;
```

## IP-Based Location Inference

For new users/visitors, automatically infer location data from their IP address to provide sensible defaults.

### Implementation Approach

1. **IP Lookup Service**: Use MaxMind GeoLite2 (free tier) or ip-api.com
2. **Middleware**: Enhance `TrackVisitor` middleware to detect and store location on first visit
3. **Privacy**: Make this opt-out with clear privacy notice

### Code Example

```php
// app/Services/GeoLocationService.php
class GeoLocationService
{
    public function getLocationFromIp(string $ip): array
    {
        // Using ip-api.com (free, no auth required)
        $response = Http::get("http://ip-api.com/json/{$ip}");

        if ($response->successful()) {
            $data = $response->json();
            return [
                'country_code' => $data['countryCode'],
                'timezone' => $data['timezone'],
                'currency_code' => $this->getCurrencyFromCountry($data['countryCode']),
            ];
        }

        return [];
    }

    private function getCurrencyFromCountry(string $countryCode): string
    {
        // Map country codes to currencies
        $map = [
            'GB' => 'GBP',
            'US' => 'USD',
            'EU' => 'EUR',
            'AU' => 'AUD',
            // ... etc
        ];

        return $map[$countryCode] ?? 'USD';
    }
}

// In TrackVisitor middleware
public function handle(Request $request, Closure $next): Response
{
    // ... existing visitor tracking code ...

    if ($visitor && !$visitor->country_code) {
        $geoData = app(GeoLocationService::class)->getLocationFromIp($request->ip());
        $visitor->update($geoData);
    }

    return $next($request);
}
```

## Progressive Profiling UI

Rather than overwhelming users with a long profile form, collect information progressively over time.

### Strategy 1: Profile Completion Prompt

After first prompt optimisation, show a profile completion card:

```vue
<!-- components/ProfileCompletionPrompt.vue -->
<template>
  <Card v-if="!isProfileComplete" class="border-blue-200 bg-blue-50">
    <div class="flex items-start">
      <DynamicIcon name="information-circle" class="mr-3 h-6 w-6 text-blue-600" />
      <div class="flex-1">
        <h3 class="font-semibold text-gray-900">
          Complete Your Profile for Better Prompts
        </h3>
        <p class="mt-1 text-sm text-gray-700">
          Help us ask fewer questions next time by completing your profile.
        </p>
        <ButtonSecondary @click="showProfileModal = true" class="mt-3">
          Complete Profile (1 min)
        </ButtonSecondary>
      </div>
      <button @click="dismissPrompt" class="text-gray-400 hover:text-gray-600">
        <DynamicIcon name="x" class="h-5 w-5" />
      </button>
    </div>
  </Card>
</template>
```

### Strategy 2: Just-In-Time Questions

When a clarifying question is asked that matches a profile field, offer to save the answer:

```vue
<!-- In the question answering interface -->
<div v-if="canSaveToProfile(question)" class="mt-2">
  <label class="flex items-center text-sm text-gray-600">
    <input type="checkbox" v-model="saveToProfile" class="mr-2" />
    Remember this answer for future prompts
  </label>
</div>
```

### Strategy 3: Profile Settings Page

Allow users to view and edit their profile at any time:

```
Route: /profile/settings
Components:
- PersonalitySection (existing personality type + trait percentages)
- LocationSection (country, timezone, currency)
- DemographicSection (age bracket, education, family lifecycle)
- ProfessionalSection (occupation category, experience level)
```

## Context-Aware Question Generation

Modify the Framework Selector workflow to leverage known profile data.

### Updated System Prompt (Addition)

```javascript
// In Build LLM Prompt node (n8n/Framework Selector.json)

// Add user profile context to the prompt
let profileContext = '';
if (inputData.user_profile) {
  const profile = inputData.user_profile;
  profileContext = `\n\nKnown User Profile Information:
- Location: ${profile.country_code || 'Unknown'} (${profile.timezone || 'Unknown'})
- Age Bracket: ${profile.age_bracket || 'Unknown'}
- Occupation: ${profile.occupation_category || 'Unknown'}
- Experience Level: ${profile.professional_experience || 'Unknown'}
- Education: ${profile.education_level || 'Unknown'}
- Family: ${profile.family_lifecycle || 'Unknown'}

IMPORTANT: Do NOT ask questions about information already provided in the user profile above.
For example, if country_code is GB, do NOT ask "Which country are you in?"
Instead, use this information to make your questions more specific and relevant.`;
}

systemPrompt = systemPrompt + profileContext;
```

### Backend Changes

```php
// app/Http/Controllers/PromptOptimizerController.php

private function buildFrameworkSelectorPayload(PromptRun $promptRun): array
{
    $payload = [
        'prompt_run_id' => $promptRun->id,
        'task_description' => $promptRun->task_description,
        'framework_matrix' => $this->getFrameworkMatrix(),
        'has_personality' => (bool) $promptRun->personality_type,
        'personality_type' => $promptRun->personality_type,
        // ... existing fields ...
    ];

    // Add user profile data if available
    $user = auth()->user();
    $visitor = $promptRun->visitor_id ? Visitor::find($promptRun->visitor_id) : null;

    $profile = [
        'country_code' => $user?->country_code ?? $visitor?->country_code,
        'timezone' => $user?->timezone ?? $visitor?->timezone,
        'age_bracket' => $user?->age_bracket ?? $visitor?->age_bracket,
        'occupation_category' => $user?->occupation_category ?? $visitor?->occupation_category,
        'professional_experience' => $user?->professional_experience ?? $visitor?->professional_experience,
        'education_level' => $user?->education_level ?? $visitor?->education_level,
        'family_lifecycle' => $user?->family_lifecycle ?? $visitor?->family_lifecycle,
    ];

    // Only include profile if at least one field is populated
    if (array_filter($profile)) {
        $payload['user_profile'] = $profile;
    }

    return $payload;
}
```

## Question Deduplication Logic

Prevent asking questions that have been answered in previous iterations.

### Approach 1: Store Answered Questions

Add a JSON column to `prompt_runs`:

```sql
ALTER TABLE prompt_runs ADD COLUMN answered_context JSON NULL;
```

Example structure:
```json
{
  "budget": "£25,000-£30,000",
  "location": "United Kingdom",
  "timeline": "3-6 months",
  "experience_level": "Intermediate",
  "target_audience": "Small business owners"
}
```

### Approach 2: Intelligent Question Filtering

When building the Framework Selector payload, include previously answered context:

```php
// Include answers from parent and previous iterations
$answeredContext = [];

if ($promptRun->parent_id) {
    $parentRun = PromptRun::find($promptRun->parent_id);
    $answeredContext = array_merge(
        $answeredContext,
        $parentRun->answered_context ?? [],
        $parentRun->clarifying_answers ?? []
    );
}

$payload['answered_context'] = $answeredContext;
```

Update the system prompt to reference this:

```javascript
if (inputData.answered_context && Object.keys(inputData.answered_context).length > 0) {
  profileContext += `\n\nPreviously Answered Questions:
${Object.entries(inputData.answered_context).map(([key, value]) =>
  `- ${key}: ${value}`
).join('\n')}

Do NOT ask about topics already covered in previously answered questions.`;
}
```

## Task-Type Detection for Specialised Prompts

In Phase 1, we manually categorise task types. In Phase 2, we can add automatic task type detection.

### Lightweight Classifier

Add a Claude API call before Framework Selection:

```javascript
// New n8n node: "Classify Task Type"
{
  "model": "claude-3-5-haiku-20241022",
  "max_tokens": 100,
  "system": "Classify the task into ONE category: purchase, planning, learning, creative, problem-solving, decision-making, or other. Respond with ONLY the category name.",
  "messages": [{
    "role": "user",
    "content": `Task: ${taskDescription}`
  }]
}
```

Pass this to Framework Selector as `task_type` to make the task type analysis step even more targeted.

## Privacy and Compliance Considerations

### GDPR Compliance

- **Consent**: Collect explicit consent for storing demographic data
- **Data Minimisation**: Only collect what's necessary
- **Right to Erasure**: Allow users to delete profile data
- **Transparency**: Clear privacy notice explaining how data is used

### Implementation

```php
// Add to User model
public function clearProfileData(): void
{
    $this->update([
        'country_code' => null,
        'timezone' => null,
        'currency_code' => null,
        'age_bracket' => null,
        'occupation_category' => null,
        'education_level' => null,
        'family_lifecycle' => null,
        'professional_experience' => null,
    ]);
}
```

Add a "Clear Profile Data" button in profile settings.

### Privacy Notice Example

> **How We Use Your Profile Information**
>
> We use your profile information (location, age bracket, occupation, etc.) to:
> - Ask fewer repetitive questions during prompt optimisation
> - Provide more relevant clarifying questions
> - Personalise framework selection based on your context
>
> This information is:
> - Stored securely and never shared with third parties
> - Optional – you can use the service without providing it
> - Deletable at any time from your profile settings

## Migration Path

### Phase 2.1: Database & Infrastructure
1. Add new columns to `users` and `visitors` tables
2. Implement GeoLocationService and IP lookup
3. Update TrackVisitor middleware

### Phase 2.2: Progressive Profiling UI
1. Create profile settings page
2. Add profile completion prompt component
3. Implement "save to profile" checkboxes in question interface

### Phase 2.3: Integration with Framework Selector
1. Update PromptOptimizerController to include profile data in payload
2. Modify n8n Framework Selector workflow to use profile context
3. Add answered context tracking and deduplication

### Phase 2.4: Testing & Refinement
1. Test question quality with/without profile data
2. A/B test progressive profiling strategies
3. Monitor profile completion rates
4. Refine based on user feedback

## Success Metrics

- **Profile Completion Rate**: % of users who complete ≥50% of profile fields
- **Question Reduction**: Average number of questions asked (with profile vs. without)
- **Time to Optimised Prompt**: Median time from task submission to final prompt
- **User Satisfaction**: NPS score for users with completed profiles vs. incomplete
- **Iteration Rate**: % of users who iterate on prompts (should increase if friction is reduced)

## Resource Estimates

### Development Time
- Database migrations: 2 hours
- GeoLocationService implementation: 4 hours
- Profile settings UI: 8 hours
- Progressive profiling components: 6 hours
- Framework Selector integration: 8 hours
- Testing & refinement: 12 hours
- **Total**: ~40 hours (1 week)

### Infrastructure Costs
- IP geolocation API: Free tier (ip-api.com) supports 45 req/min
- Additional database storage: Negligible
- No additional LLM costs (same number of API calls, just better prompts)

## Risks and Mitigations

### Risk 1: Privacy Concerns
**Mitigation**: Clear opt-in consent, transparent privacy notice, easy data deletion

### Risk 2: Profile Abandonment
**Mitigation**: Make all fields optional, use progressive profiling (not upfront forms)

### Risk 3: Inaccurate IP Geolocation
**Mitigation**: Always allow manual override, treat as suggestion not requirement

### Risk 4: Over-Optimisation
**Mitigation**: Monitor question quality – ensure we're not sacrificing quality for speed

## Alternative Approaches Considered

### Approach A: Infer from Answers (No Profile Storage)
Parse answers to extract demographic info (e.g., "I live in London" → country_code = GB).

**Pros**: No explicit data collection, privacy-friendly
**Cons**: Unreliable, doesn't help with first-time questions, complex NLP required

### Approach B: Browser Fingerprinting
Use timezone, language settings to infer location without IP lookup.

**Pros**: More privacy-friendly than IP lookup
**Cons**: Less accurate, can't infer occupation/age/etc.

### Approach C: Full Profile Onboarding
Require complete profile during registration.

**Pros**: Maximum context from day one
**Cons**: High friction, likely to reduce conversion rates

**Selected Approach**: Progressive profiling (Approach from this document) balances privacy, UX, and effectiveness.

## Conclusion

Phase 2 represents a significant enhancement to the Framework Selector's intelligence by leveraging persistent user profile data. By implementing progressive profiling and context-aware question generation, we can reduce friction for returning users whilst maintaining high-quality prompt optimisation for first-time users.

The phased rollout approach allows us to validate assumptions at each step and adjust based on real-world usage patterns before committing to the full implementation.
