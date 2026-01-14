# Implementation Plan: Country Code URLs

## Overview

Convert URLs from full locale codes (`/en-GB/pricing`, `/en-US/pricing`) to lowercase country codes (`/gb/pricing`,
`/us/pricing`, `/mx/pricing`, `/sg/pricing`). Support all 247 country codes from the countries table, with automatic
fallback to USD currency and en-US language for unsupported combinations.

## Terminology & Naming

**Semantically correct naming throughout:**

- **Route parameter:** `{country}` (not "locale")
- **Config:** `supported_countries` (all 247 country codes from database)
- **Frontend composable:** `useCountryRoute()`
- **Middleware:** `SetCountry` (validates `{country}`, resolves locale/currency, caches preferences)
- **User/Visitor columns:** `country_code` (e.g., 'mx', 'sg', 'gb')

**Resolution flow:**

```
URL: /mx/pricing
  ↓
Route parameter {country}: 'mx'
  ↓
Check Redis cache for user/visitor preferences (language + currency)
  ↓
If cache miss: Query database + cache result for 1 hour
  ↓
Countries table lookup: mx → currency_id='USD', language_id='es-MX'
  ↓
app()->setLocale('es-MX')
  ↓
Display: Pricing in USD (fallback), content in Spanish
```

## Performance & Caching Strategy

**Keep existing session configuration:**

- `SESSION_DRIVER=database` - **Do not change** (needed for analytics/CDP tracking)
- `CACHE_STORE=redis` - **Already configured** (use for preference caching)

**Redis caching for preferences:**

- Cache user/visitor language preferences (1 hour TTL)
- Cache user/visitor currency preferences (1 hour TTL)
- Cache country defaults (indefinite, clear on country updates)
- Expected cache hit rate: >95% after warmup
- Performance: ~0.1ms per request with cache hit vs ~10ms without

**Remove session fallback:**

- `SetCountry` no longer relies on `session('locale')`; cookies and persisted preferences are the single source of truth
- Preference hierarchy: User DB → Visitor DB → Country default → Global fallback

**Cache invalidation:**

- When user/visitor updates language: Clear `{user|visitor}.{id}.language` cache
- When user/visitor updates currency: Clear `{user|visitor}.{id}.currency` cache
- Automatic on preference change endpoints

## Current Architecture

### Database Structure

- **countries** table: `id` (ISO code: currently 'GB', 'US', 'AU'), `language_id` (FK to languages)
- **languages** table: `id` (locale: 'en-GB', 'en-US', 'de-DE'), `active` (boolean)
- Existing mapping: GB→en-GB, US→en-US, AU→en-GB, etc.

### URL System

- Routes use the `{country}` parameter with lowercase ISO codes (e.g., `/gb/pricing`, `/mx/pricing`)
- Supported countries: all 247 entries from the `countries` table
- `SetCountry` middleware validates the code, resolves locale and currency (with Redis-backed caching), and sets `app()->setLocale()`
- Frontend uses the `useCountryRoute()` composable for link generation
- Silent fallback to USD pricing and `en-US` language when a country lacks pricing or translations

## Implementation Steps

### Phase 1: Database Updates

#### 1.1 Convert Countries Table IDs to Lowercase

**Files:**

- `database/seeders/csv/countries.csv`

**Actions:**

1. Update CSV file: Change all country `id` values from uppercase (GB, US) to lowercase (gb, us)
2. Create migration to update existing records:
   ```php
   // Migration: update_countries_to_lowercase_ids
   DB::statement("UPDATE countries SET id = LOWER(id)");
   // Also update foreign keys in other tables if any reference countries
   ```
3. Reseed countries table with lowercase IDs

**Why:** URLs will use lowercase country codes, so database should match

### Phase 2: Configuration Updates

#### 2.1 Update Config to Support All Countries

**File:** `config/app.php`

**Changes:**

```php
// Replace supported_locales with supported_countries
// Support all 247 countries from database
'supported_countries' => fn() => \App\Models\Country::pluck('id')->all(),

// Or for better performance (cache in config:cache):
'supported_countries' => ['gb', 'us', 'mx', 'sg', 'au', ...], // All 247 codes

// Default fallbacks for unsupported currencies/languages
'fallback_currency' => 'USD',
'fallback_locale' => 'en-US',
```

**Remove:**

- Old `supported_locales` array with locale codes

#### 2.2 Rename Language Files to Full Locale Codes

**Files:** `lang/*.json`

**Actions:**

1. Rename existing language files:
   ```bash
   mv lang/es.json lang/es-ES.json
   mv lang/de.json lang/de-DE.json
   mv lang/fr.json lang/fr-FR.json
   ```

2. Ensure en-GB and en-US exist:
   ```bash
   # If only en.json exists:
   cp lang/en.json lang/en-GB.json
   cp lang/en.json lang/en-US.json
   ```

3. Update any references to old filenames in code

**Why:** Language file names must match the `languages.id` values (es-ES, de-DE, en-GB) for Laravel's
`app()->setLocale()` to find them

### Phase 3: Middleware Updates

#### 3.1 Implement SetCountry Middleware

**File:** `app/Http/Middleware/SetCountry.php`

**Responsibilities:**

1. Validate the `{country}` route segment against the `countries` table and abort with a 404 for invalid codes.
2. Resolve the preferred language by checking the authenticated user's `language_code`, falling back to the visitor record, and finally the country default; every tier is cached via Redis.
3. Resolve the preferred currency using the same preference order, with a fallback to the country default and USD as a final fallback for unsupported pricing.
4. Set the resolved locale using `app()->setLocale()` and make the resolved currency available to shared props or downstream services.

Key helpers include `resolveLanguageCode()`, `resolveCurrencyCode()`, `getCountryDefaultLanguage()`, `getCountryDefaultCurrency()`, and the static `detectCountry()` helper that centralizes the user → visitor → geolocation → fallback flow.

#### 3.2 Caching & Detection Helpers

- Preference caches use keys like `user.{id}.language` / `.currency` and expire after 1 hour; country defaults are cached forever and can be invalidated with `country.{code}.{type}` keys when the admin updates the mapping.
- `detectCountry(Request $request)` encapsulates the lookup order and safely returns lowercase country codes; it's used whenever a country redirect is required (e.g., root redirect, `countryRoute()` fallback).
- `clearCachePattern()` remains available to flush Redis keys in production deployments or tests.

### Phase 4: Route Updates

#### 4.1 Update Web Routes

**File:** `routes/web.php`

**Changes:**

1. Change route parameter from `{locale}` to `{country}`:
   ```php
   // Before:
   Route::prefix('{locale}')
       ->middleware(['locale'])
       ->where(['locale' => implode('|', config('app.supported_locales'))])

   // After:
   Route::prefix('{country}')
       ->middleware(['country']) // Update middleware alias
       ->where(['country' => '[a-z]{2}']) // Validate: any 2 lowercase letters
       // Or validate against database: ->where(['country' => implode('|', config('app.supported_countries'))])
       ->group(function () {
           // All routes stay the same
       });
   ```

2. Update root redirect:
   ```php
   Route::get('/', function () {
       $country = SetCountry::detectCountry(request()); // Was detectLocale()
       return redirect("/{$country}");
   });
   ```

3. Update middleware alias registration:
   ```php
   // In bootstrap/app.php or app/Http/Kernel.php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->alias([
           'country' => \App\Http\Middleware\SetCountry::class, // Was 'locale'
       ]);
   })
   ```

**All route names stay the same** - but parameter changes from `locale` to `country`

### Phase 5: Frontend Updates

#### 5.1 Rename and Update useLocaleRoute Composable

**File:** `resources/js/Composables/useLocaleRoute.ts` → Rename to `useCountryRoute.ts`

**Changes:**

```typescript
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Composable for generating country-aware routes
 * Automatically injects the current country code into route parameters
 */
export function useCountryRoute() {
    const page = usePage();

    // Country code from URL (gb, mx, sg)
    const currentCountry = computed(() => (page.props.country as string) || 'gb');

    // Full locale for translations (en-GB, es-MX)
    const currentLocale = computed(() => (page.props.locale as string) || 'en-GB');

    /**
     * Generate a route URL with country parameter automatically injected
     */
    const countryRoute = (name: string, parameters?: Record<string, any>) => {
        return route(name, {
            country: currentCountry.value,
            ...(parameters || {}),
        });
    };

    return {
        currentCountry,
        currentLocale,
        countryRoute,
    };
}
```

#### 5.2 Update All Vue Components

**Files:** Search entire `resources/js/` directory

**Changes:**

1. **Import statement:**
   ```typescript
   // Before:
   import { useLocaleRoute } from '@/Composables/useLocaleRoute';
   const { localeRoute, currentLocale } = useLocaleRoute();

   // After:
   import { useCountryRoute } from '@/Composables/useCountryRoute';
   const { countryRoute, currentCountry, currentLocale } = useCountryRoute();
   ```

2. **Route calls:**
   ```typescript
   // Before:
   route('pricing', { locale: currentLocale.value })
   // Or
   localeRoute('pricing')

   // After:
   route('pricing', { country: currentCountry.value })
   // Or
   countryRoute('pricing')
   ```

3. **Hardcoded locale checks:**
   ```typescript
   // Before:
   if (currentLocale.value === 'en-GB')

   // After:
   if (currentCountry.value === 'gb')
   // Or if checking language:
   if (currentLocale.value === 'en-GB')
   ```

**Key files to update:**

- `resources/js/Pages/Pricing.vue`
- `resources/js/Layouts/AppLayout.vue`
- Any component using `useLocaleRoute()`
- Any component with hardcoded locale/country logic

#### 5.3 Update Country/Language Switcher

**Files:** Components for country or language selection

**Changes:**

- Update to use country codes: `['gb', 'us', 'mx', 'de']`
- Display country names/flags in UI
- When switching, redirect to new country URL: `/mx/pricing`

### Phase 6: Backend Controller Updates

#### 6.1 Update Controllers to Use {country} Parameter

**Files:**

- `app/Http/Controllers/VisitorController.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/SubscriptionController.php`
- All controllers that read route parameter or redirect

**Changes:**

1. Update route parameter access:
   ```php
   // Before:
   $locale = $request->route('locale');

   // After:
   $country = $request->route('country');
   ```

2. Update redirects:
   ```php
   // Before:
   return redirect()->route('pricing', ['locale' => $locale]);

   // After:
   return redirect()->route('pricing', ['country' => $country]);
   ```

3. Update validation rules:
   ```php
   // Before:
   'locale' => 'required|in:en-GB,en-US,de,fr,es'

   // After:
   'country' => 'required|string|size:2|exists:countries,id'
   ```

4. Update form requests that validate locale/country

#### 6.2 Add Country Code to User/Visitor Models

**Files:**

- `database/migrations/*_add_country_code_to_users_table.php` (new)
- `database/migrations/*_add_country_code_to_visitors_table.php` (new)
- `app/Models/User.php`
- `app/Models/Visitor.php`

**Actions:**

1. Create migrations:
   ```php
   Schema::table('users', function (Blueprint $table) {
       $table->string('country_code', 2)->nullable()->after('language_code');
       $table->foreign('country_code')->references('id')->on('countries')->nullOnDelete();
       $table->index('country_code');
   });

   Schema::table('visitors', function (Blueprint $table) {
       $table->string('country_code', 2)->nullable()->after('language_code');
       $table->foreign('country_code')->references('id')->on('countries')->nullOnDelete();
       $table->index('country_code');
   });
   ```

2. Add to `$fillable` arrays:
   ```php
   protected $fillable = [
       // ... existing fields
       'country_code',
       'language_code',
   ];
   ```

3. Update visitor/user creation logic to store country code from geolocation

**Note:** Keep `language_code` column (stores 'en-GB', 'es-MX', etc.) for translation preferences

### Phase 7: GeolocationService Updates

#### 7.1 Update Geolocation Service

**File:** `app/Services/GeolocationService.php`

**Changes:**

- Ensure all country codes returned are lowercase:
  ```php
  // In lookupIp() method:
  return LocationData::from([
      'country_code' => strtolower($record->country->isoCode), // 'gb' not 'GB'
      // ... other fields
  ]);
  ```
- Update any country-to-currency/language mapping if exists
- No major refactoring needed - mainly ensure lowercase output

### Phase 8: Testing Updates

#### 8.1 Update Backend Tests

**Files:**

- `tests/Feature/LocaleTest.php`
- `tests/Feature/LanguagePersistenceTest.php`
- `tests/Feature/CurrencyUpdateTest.php`
- `tests/Feature/SubscriptionTest.php`
- `tests/Feature/ProfileTest.php`

**Changes:**

1. Update all test URLs:
   ```php
   // Before: $this->get('/en-GB/pricing')
   // After: $this->get('/gb/pricing')
   ```

2. Update config expectations:
   ```php
   // Before: expect($locales)->toContain('en-GB');
   // After: expect($countries)->toContain('gb');

   // Before: config('app.supported_locales')
   // After: config('app.supported_countries')
   ```

3. Update route parameter values:
   ```php
   // Before: route('pricing', ['locale' => 'en-GB'])
   // After: route('pricing', ['country' => 'gb'])
   ```

4. Rename LocaleTest or update its name:
   ```php
   // tests/Feature/LocaleTest.php can stay named as is
   // But update test names and content to use 'country' terminology
   ```

5. Add test for country-to-language resolution:
   ```php
   test('SetCountry middleware resolves country code to full language code', function () {
       $this->get('/gb/pricing');

       // Middleware should have set app locale to en-GB
       expect(app()->getLocale())->toBe('en-GB');
   });

   test('unsupported language falls back to en-US', function () {
       // Add a country with unsupported language to database
       Country::create([
           'id' => 'xx',
           'currency_id' => 'USD',
           'language_id' => 'xx-XX', // Non-existent language
           'name' => 'Test Country'
       ]);

       $this->get('/xx/pricing');

       expect(app()->getLocale())->toBe('en-US'); // Fallback
   });
   ```

#### 8.2 Update Frontend Component Tests

**Files:** `tests-frontend/component/*.test.ts`

**Changes:**

- Update mocked Inertia props:
  ```typescript
  // Before:
  { locale: 'en-GB' }

  // After:
  {
    country: 'gb',
    locale: 'en-GB', // Full locale for translations
    currency: 'GBP'
  }
  ```
- Update route helper mocks to use `{country}` parameter
- No major structural changes to component tests

### Phase 9: Documentation Updates

#### 9.1 Update CLAUDE.md

**File:** `CLAUDE.md`

**Changes:**

- Document new country-code-based routing: `/gb/pricing`, `/mx/pricing`, `/sg/pricing`
- Explain that `{country}` parameter contains lowercase country code
- Note that country codes resolve to currencies and languages via `countries` table
- Update all URL examples throughout the file (change `/en-GB/` to `/gb/`)
- Document silent fallback behaviour for unsupported currencies/languages
- Update development command examples to use new URLs

**Example addition:**

```markdown
## URL Structure

All user-facing URLs use lowercase 2-letter country codes:

- `/gb/pricing` - United Kingdom (GBP, English)
- `/us/pricing` - United States (USD, English)
- `/mx/pricing` - Mexico (USD fallback, Spanish)
- `/sg/pricing` - Singapore (SGD, English)

The `{country}` route parameter resolves to:

- **Currency** via `countries.currency_id` (fallback: USD)
- **Language** via `countries.language_id` (fallback: en-US)

Users can override language preference in settings whilst staying on same country URL.
```

## Critical Files Summary

### Must Modify - Database & Seeding

1. `database/seeders/csv/countries.csv` - Convert all IDs to lowercase
2. Migration: Update existing country records to lowercase IDs
3. `lang/` directory - Rename JSON files to match full locales (es-ES.json, etc.)

### Must Modify - Configuration

4. `config/app.php` - Replace `supported_locales` with `supported_countries`, add fallback config

### Must Modify - Middleware

5. `app/Http/Middleware/SetCountry.php` - Handle `{country}` validation plus language/currency resolution and caching
6. `app/Http/Middleware/HandleInertiaRequests.php` - Update shared props (country, locale, currency)
7. Middleware alias registration - Change 'locale' to 'country'

### Must Modify - Routes

8. `routes/web.php` - Change `{locale}` to `{country}`, update middleware reference

### Must Modify - Frontend (Composable & Components)

9. `resources/js/Composables/useLocaleRoute.ts` - Rename to useCountryRoute.ts, update logic
10. All `.vue` files using `useLocaleRoute()` - Update imports and route calls
11. `resources/js/Pages/Pricing.vue` - Update route parameters
12. `resources/js/Layouts/AppLayout.vue` - Update route parameters

### Must Modify - Backend Controllers

13. `app/Http/Controllers/VisitorController.php` - Change route parameter to {country}
14. `app/Http/Controllers/ProfileController.php` - Change route parameter to {country}
15. `app/Http/Controllers/SubscriptionController.php` - Update redirects
16. All form requests - Update validation rules (locale → country)

### Must Create - Migrations

17. Migration: Add `country_code` column to `users` table
18. Migration: Add `country_code` column to `visitors` table

### Must Modify - Models

19. `app/Models/User.php` - Add `country_code` to $fillable
20. `app/Models/Visitor.php` - Add `country_code` to $fillable

### Must Modify - Services

21. `app/Services/GeolocationService.php` - Ensure lowercase country code returns

### Must Update - All Tests

22. `tests/Feature/LocaleTest.php` - Update URLs and assertions
23. `tests/Feature/LanguagePersistenceTest.php` - Update route parameters
24. `tests/Feature/CurrencyUpdateTest.php` - Update URLs
25. `tests/Feature/SubscriptionTest.php` - Update URLs
26. `tests/Feature/ProfileTest.php` - Update URLs
27. `tests-frontend/component/*.test.ts` - Update mocked props

### Must Update - Documentation

28. `CLAUDE.md` - Document new URL structure and country code system

## Migration Strategy

Since internationalisation hasn't gone live yet and you opted to **break old URLs**, migration is simple:

1. **Update database first**: Convert country IDs to lowercase, reseed
2. **Deploy all code changes** atomically
3. **No redirects needed** - old URLs can 404

## Edge Cases & Considerations

### System Terminology

- **URL** uses lowercase country code: `/gb/pricing`, `/mx/pricing`, `/sg/pricing`
- **Route `{country}` parameter** contains country code: `'gb'`, `'mx'`, `'sg'`
- **Config `supported_countries`** contains all 247 country codes from database
- **Internal `app()->setLocale()`** receives full language code: `'en-GB'`, `'es-MX'`, `'en-US'`
- **User/Visitor `country_code` column** stores country code: `'gb'`, `'mx'`
- **User/Visitor `language_code` column** stores full locale: `'en-GB'`, `'es-MX'`

### Supporting All 247 Countries

- **All countries from CSV are supported** via database lookup
- **Unsupported currencies** silently fall back to USD
- **Unsupported languages** (no translation file) silently fall back to en-US
- **No user-facing notices** for fallbacks - transparent experience

### Multi-Language Countries

- Each country has ONE default language (from `countries.language_id`)
- Users can override language preference in settings (updates `language_code`)
- Country code in URL stays the same, internal locale changes for translations
- Example: User in Switzerland (ch) can switch between de-CH, fr-CH, it-CH

### Fallback Behaviour Examples

| Country | Currency Mapping | Has Pricing? | Language Mapping | Has Translations? | Result                         |
|---------|------------------|--------------|------------------|-------------------|--------------------------------|
| gb      | GBP              | ✅ Yes        | en-GB            | ✅ Yes             | GBP, English ✅                 |
| mx      | MXN              | ❌ No         | es-MX            | ✅ Yes             | USD (fallback), Spanish ⚠️     |
| sg      | SGD              | ✅ Yes        | en-SG            | ❌ No              | SGD, en-US (fallback) ⚠️       |
| pk      | PKR              | ❌ No         | ur-PK            | ❌ No              | USD + en-US (both fallback) ⚠️ |

### API & Auth Routes

- `routes/api.php` has no country prefix - no changes needed
- `routes/auth.php` has no country prefix - no changes needed
- Test routes (`/test/*`) have no prefix - no changes

## Rollback Plan

If critical issues arise post-deployment:

1. **Revert routes:** Change `{country}` back to `{locale}` in `routes/web.php`
2. **Revert middleware:** Restore the pre-deployment `SetCountry` middleware behavior (or revert to the previous routing guard)
3. **Revert config:** Change `supported_countries` back to `supported_locales`
4. **Revert frontend:** Restore `useLocaleRoute.ts` composable
5. **Database changes:** Can stay - new columns/lowercase IDs won't break anything

**Note:** This is a significant change. Thorough testing recommended before deployment.

## Post-Deployment Checklist

### Core Functionality

- [ ] Test pages load for multiple countries: `/gb/pricing`, `/us/pricing`, `/mx/pricing`, `/sg/pricing`
- [ ] Verify country-to-locale resolution: `/gb/` → `app()->getLocale()` returns `'en-GB'`
- [ ] Verify country-to-currency resolution: `/gb/` → shows GBP pricing
- [ ] Test fallback behaviour: Visit `/pk/pricing` → shows USD + en-US (no errors)

### User Features

- [ ] Test visitor geolocation sets correct country code
- [ ] Test authenticated user preferences persist (country_code, language_code)
- [ ] Test language switching within a country (stay on /gb/, switch to de-DE in settings)
- [ ] Test currency switching (if separate from country selection)

### Navigation & Routing

- [ ] Test all major pages work with country prefix: home, pricing, profile, etc.
- [ ] Test route generation: `route('pricing', ['country' => 'mx'])` works
- [ ] Test frontend `countryRoute()` helper generates correct URLs
- [ ] Test redirects maintain country parameter

### Testing Suite

- [ ] Run full backend test suite: `./vendor/bin/sail test`
- [ ] Run frontend unit tests: `pnpm test:unit`
- [ ] Run E2E tests: `pnpm test:e2e` (if applicable)
- [ ] All tests should pass with new country-based URLs

### Monitoring

- [ ] Check Laravel logs for resolution errors or fallback warnings
- [ ] Verify Inertia shared props contain: `country`, `locale`, `currency`
- [ ] Monitor 404 errors (should be minimal since URLs never went live)
- [ ] Check database: country_code columns populated correctly

## Estimated Effort

**Total implementation time:**

- **Database & Config:** 2 hours (CSV updates, migrations, language file renames)
- **Middleware updates:** 3-4 hours (SetCountry logic, currency resolution, Inertia props)
- **Routes & Registration:** 1 hour (Update web.php, middleware aliases)
- **Frontend composable:** 1-2 hours (Rename, update logic, test)
- **Frontend components:** 2-3 hours (Find-replace imports, route calls across all .vue files)
- **Backend controllers:** 2-3 hours (Update {locale} → {country}, validation rules, redirects)
- **Services (Geolocation):** 1 hour (Ensure lowercase returns)
- **Backend tests:** 3-4 hours (Update URLs, assertions, add resolution tests)
- **Frontend tests:** 1-2 hours (Update mocked props)
- **Documentation:** 1 hour (CLAUDE.md updates)
- **Testing & QA:** 3-4 hours (Manual testing, edge cases)

**Total: 20-27 hours**

This is more substantial than initially estimated because we're:

- Properly renaming parameters ({locale} → {country})
- Updating all frontend components
- Supporting 247 countries with fallback logic
- Renaming language files
- Comprehensive testing across the full stack
