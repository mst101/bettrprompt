# Country, Language, and Currency Switching

This document explains how the application handles country codes, language preferences, and currency selection across different user scenarios.

## Quick Summary

- **Language**: Global preference that follows users everywhere (e.g., if you select French, you see French on all country URLs)
- **Currency**: Region-specific and matches the country URL for accurate pricing (e.g., `/gb/pricing` shows GBP, `/de/pricing` shows EUR)
- **Country Code**: User's "home country" stored in database, only changes via explicit profile updates

## URL Structure

All user-facing URLs are prefixed with a 2-letter country code:

```
/gb/pricing        → United Kingdom
/de/pricing        → Germany
/us/pricing        → United States
/mx/profile        → Mexico
```

The country in the URL determines what regional content (pricing, currency, default language) to show, but **does not automatically update the user's stored country code**.

## Three Key Concepts

### 1. Language Preference (Global)

User's language preference is **global** and applies to all country URLs.

#### Where It's Stored
- **Database**: `users.language_code` (e.g., `'en-GB'`, `'de-DE'`, `'fr-FR'`)
- **Cache**: `bp:user.{id}.language` (1-hour TTL)
- **Visitor**: `bp:visitor.{id}.language` for non-authenticated users

#### How It Works

```
User preferences:
- country_code: 'gb' (home country)
- language_code: 'fr-FR' (speaks French)

User visits:
✓ /gb/pricing     → Shows FRENCH (user preference)
✓ /de/pricing     → Shows FRENCH (user preference)
✓ /us/pricing     → Shows FRENCH (user preference)
```

Even though the user visits Germany (`/de/`), they still see French because language is a **personal preference**, not tied to which country URL they're viewing.

#### How to Change Language

1. Click the flag icon in the LanguageSwitcher component
2. POST to `/{country}/profile/language` with `language_code`
3. Backend updates `users.language_code`
4. Cache key `bp:user.{id}.language` is invalidated
5. Frontend updates immediately without page reload

### 2. Currency (Region-Specific)

Currency is **region-specific** and determined by the country in the URL.

#### Where It's Stored
- **Database**: `users.currency_code` (e.g., `'USD'`, `'EUR'`, `'GBP'`)
- **Cache**: `bp:user.{id}.currency.{routeCountry}` (1-hour TTL, different key per country URL)
- **Fallback**: Country default currency from `countries` table

#### How It Works

```
User preferences:
- country_code: 'gb' (home country)
- currency_code: 'GBP' (set once in profile)

User visits:
✓ /gb/pricing     → Shows GBP (user preference, matches home country)
✓ /de/pricing     → Shows EUR (country default for Germany)
✓ /us/pricing     → Shows USD (country default for USA)
```

When the user's home country matches the route country, their currency preference is used. Otherwise, the country's default currency is shown.

#### How to Change Currency

1. Click currency button on Pricing page (e.g., "USD", "EUR", "GBP")
2. POST to `/{country}/currency/select` with `currency_code`
3. Backend updates `users.currency_code`
4. Cache keys `bp:user.{id}.currency.*` (all route variants) are invalidated
5. Page reloads with new currency prices

### 3. Country Code (Explicit Updates Only)

User's stored `country_code` represents their "home country" and only changes via explicit profile updates.

#### Where It's Stored
- **Database**: `users.country_code` (2-letter ISO code: `'gb'`, `'de'`, `'us'`, etc.)
- **Inertia Props**: Passed as `country` prop to all Vue components

#### How It Works

```
User authentication flow:
1. User signs up in /gb/signup
   → country_code set to 'gb' via geolocation

2. User visits /de/pricing
   → Shows German content, EUR prices
   → country_code stays 'gb' (NOT updated)

3. User visits /us/pricing
   → Shows USA content, USD prices
   → country_code stays 'gb' (NOT updated)

4. User goes to /gb/profile and changes country to 'de'
   → country_code updated to 'de' (only here!)
   → Now language/currency defaults use 'de' as home
```

**Navigation never auto-updates country code** — it's only updated when explicitly changed in Profile settings.

#### How to Change Country Code

1. Go to `/gb/profile` (or any country URL)
2. Update "Location & Language" settings, select new country
3. PATCH to `/{country}/profile/location` with `country_code`
4. Backend updates `users.country_code`
5. If country changed, browser redirects to new country URL (e.g., `/de/profile`)

## Decision Tree: What Language/Currency Does User See?

### Language Decision

```
IF user has manually set language_code
    ✓ Use user's language (global preference)
ELSE
    ✓ Use country default language
```

**Example:**
- User's `language_code = 'de-DE'` → Shows German everywhere
- User's `language_code = null` → Falls back to each country's default

### Currency Decision

```
IF user visits their home country URL
AND user has set currency_code
    ✓ Use user's currency preference
ELSE
    ✓ Use country default currency
```

**Example:**
- User from GB visiting `/gb/` with `currency_code = 'GBP'` → Shows GBP
- User from GB visiting `/de/` → Shows EUR (Germany's default)
- User from GB visiting `/us/` → Shows USD (USA's default)

## Caching Strategy

### Cache Keys Structure

| Type | Key Pattern | TTL | Scope |
|------|------------|-----|-------|
| User Language | `bp:user.{id}.language` | 1 hour | Global |
| User Currency | `bp:user.{id}.currency.{country}` | 1 hour | Per country |
| Visitor Language | `bp:visitor.{id}.language` | 1 hour | Global |
| Visitor Currency | `bp:visitor.{id}.currency.{country}` | 1 hour | Per country |
| Country Defaults | `bp:country.{code}.language` | Forever | One per country |
| Country Currency | `bp:country.{code}.currency` | Forever | One per country |

### Why This Structure?

**Language uses simple keys** (`user.1.language`) because:
- Language is global, doesn't change per country
- One cache entry per user
- Easy to invalidate (single key)

**Currency uses route-specific keys** (`user.1.currency.gb`, `user.1.currency.de`) because:
- Currency differs per country URL
- Separate cache for `/gb/pricing` vs `/de/pricing`
- Preserves country-specific preferences when browsing multiple regions

### Cache Invalidation

When user updates language or currency:

```php
// Language invalidation (simple)
Cache::forget("user.{$user->id}.language");

// Currency invalidation (all route variants)
SetCountry::clearCachePattern("user.{$user->id}.currency.*");
```

The `clearCachePattern()` method uses Redis to find and clear all matching keys at once.

## Common User Scenarios

### Scenario 1: User Selects Language via LanguageSwitcher

**URL:** `/gb/profile` → Click French flag

```
1. Frontend: POST /gb/profile/language { language_code: 'fr-FR' }
2. Backend: Update users.language_code = 'fr-FR'
3. Backend: Cache::forget("user.1.language")
4. Frontend: Updates i18n, stays on /gb/profile
5. Result: Language changes to French everywhere (not just /gb/)
```

**Next time user visits:**
- `/gb/pricing` → French ✓
- `/de/pricing` → French ✓
- `/us/pricing` → French ✓

### Scenario 2: User Selects Currency on Pricing Page

**URL:** `/gb/pricing` → Click "EUR" button

```
1. Frontend: POST /gb/currency/select { currency_code: 'EUR' }
2. Backend: Update users.currency_code = 'EUR'
3. Backend: SetCountry::clearCachePattern("user.1.currency.*")
4. Page reloads with new prices in EUR
```

**But when user visits different country:**
- `/gb/pricing` → Still shows EUR (user preference for their country)
- `/de/pricing` → Shows EUR (Germany's default)
- `/us/pricing` → Shows USD (USA's default, overrides user EUR preference)

### Scenario 3: User Changes Home Country in Profile

**URL:** `/gb/profile` → Change country to Germany

```
1. Frontend: PATCH /gb/profile/location { country_code: 'de', ... }
2. Backend: Update users.country_code = 'de'
3. Backend: Invalidate caches for language and currency
4. Frontend: Redirect to /de/profile
5. Result: Home country is now Germany
```

**Now when viewing different countries:**
- `/de/pricing` → German language, EUR currency (home country)
- `/gb/pricing` → German language (global preference), GBP currency (visiting GB)
- `/us/pricing` → German language (global preference), USD currency (visiting US)

### Scenario 4: Visitor (Non-Authenticated) Browsing

**No account logged in**

```
User visits /de/pricing (visitor)
1. SetCountry middleware detects no user, checks for visitor_id cookie
2. If visitor exists in DB, uses their language_code preference
3. Falls back to country default if no preference
4. Currency always uses country default for visitors
```

Visitors can change language and currency preferences, which get stored in the `visitors` table and cached with the same strategy as authenticated users.

## Database Columns

### Users Table

```php
$table->string('country_code', 2)->nullable();      // Home country (ISO code)
$table->string('language_code', 5)->nullable();     // Preferred language (e.g., 'en-GB')
$table->string('currency_code', 3)->nullable();     // Preferred currency (e.g., 'USD')
$table->boolean('language_manually_set')->default(false);  // True if user explicitly chose language
```

### Visitors Table

```php
$table->string('country_code', 2)->nullable();      // Detected country (ISO code)
$table->string('language_code', 5)->nullable();     // Preferred language
$table->string('currency_code', 3)->nullable();     // Preferred currency
// No manual_set flags; visitors are temporary
```

## How the Middleware Works

### SetCountry Middleware Flow

```
Request comes in: GET /de/pricing

1. Extract country code from URL: 'de'
2. Validate country exists in database
3. Call resolveLanguageCode('de', $request)
   a. If authenticated user exists:
      - Get user's language_code from cache (or DB)
      - Always use it (language is global!)
      - Cache it under "user.{id}.language"
   b. Else if visitor cookie exists:
      - Get visitor's language_code from cache (or DB)
      - Always use it (language is global!)
      - Cache it under "visitor.{id}.language"
   c. Else fallback to country default language
4. Call resolveCurrencyCode('de', $request)
   a. If authenticated user AND user.country_code == 'de':
      - Use user's currency_code preference
      - Cache it under "user.{id}.currency.de"
   b. Else:
      - Use country's default currency
      - Cache it under "country.de.currency"
5. Set app locale: app()->setLocale('de-DE')
6. Continue to next middleware
```

### Key Points

- **Language check doesn't compare route country** — user preference always applies
- **Currency check compares route country to user's home country** — only use preference when home matches
- **Separate caches for language (simple) vs currency (route-specific)**
- **Database queries happen inside cache callbacks** — hit cache on subsequent requests

## Adding New Languages

1. Add language record to `languages` table (e.g., `'it-IT'`, `'Italian'`)
2. Create translation files: `resources/lang/it-IT/` directory
3. Add to `config('app.supported_locales')`:
   ```php
   'supported_locales' => ['en-GB', 'en-US', 'de-DE', 'fr-FR', 'it-IT'],
   ```
4. Optional: Assign to countries via `countries.language_id` ForeignKey
5. LanguageSwitcher will automatically show new language if in supported list

Users can now select the new language, and it will apply globally.

## Adding New Countries

1. Add country record to `countries` table with:
   - `id` (2-letter ISO code, e.g., `'it'`)
   - `currency_id` (ForeignKey to currencies)
   - `language_id` (ForeignKey to languages)
   - Other fields: continent_id, uses_miles, etc.

2. Create pricing for all currencies used in that country:
   - Add `Price` records with matching `currency_code`

3. Routes automatically work:
   ```
   /it/pricing      → Shows Italian language, EUR currency
   /it/profile      → Shows Italian language, EUR currency
   /it/prompt-builder → Shows Italian language, EUR currency
   ```

## Debugging

### Check What Language User Should See

```php
// In Tinker or test
$user = User::find(1);
$user->language_code;  // 'fr-FR' (stored in DB)

// Check cache
Cache::get('user.1.language');  // 'fr-FR' (or null if not cached)
```

### Check What Currency on Route

```php
// What user sees on /gb/pricing
$user = User::find(1);  // country_code='gb', currency_code='USD'
// Result: USD (user preference, home country matches)

// What user sees on /de/pricing
// Result: EUR (country default, not home country)
```

### Clear All Caches

```bash
php artisan cache:clear
```

Or selectively:

```php
// Clear user's language cache
Cache::forget("user.1.language");

// Clear user's all currency caches
SetCountry::clearCachePattern("user.1.currency.*");
```

## Testing

Run the locale tests to verify behavior:

```bash
./vendor/bin/sail test tests/Feature/LocaleTest.php
```

Key test scenarios:
- Language preference applies globally across country URLs
- Currency is country-specific based on route
- Cache invalidation works correctly
- Visitors behave the same as users

## GeoIP Integration (MaxMind GeoLite2)

The application uses GeoIP lookups to provide **smart defaults** for new users/visitors. This is an **automatic detection** system, not a manual preference override.

### When GeoIP Lookups Occur

#### 1. Visitor Creation (First Visit)

**File**: `app/Http/Middleware/TrackVisitor.php`

When a new visitor arrives:

```
Priority 1: If URL has country code (e.g., /gb/), use that country's defaults
   → country_code = 'gb'
   → language_code = 'en-GB' (from countries table)
   → currency_code = 'GBP' (from countries table)

Priority 2: If no route country AND GeoIP enabled, lookup IP address
   → country_code = detected country
   → language_code = detected language (e.g., 'de-DE' for Germany)
   → currency_code = detected currency (e.g., 'EUR' for Germany)
```

**Config**: `config/geoip.php` → `features.lookup_on_visitor_creation = true`

**Why route country takes priority**: If user explicitly visits `/gb/pricing`, they want GB content, not their IP-detected location.

#### 2. User Registration

**File**: `app/Http/Controllers/Auth/RegisteredUserController.php`

When a user signs up:

```
1. Create user account
2. Migrate visitor data to user (if they were a visitor before)
3. IF user still doesn't have location data:
   → Perform GeoIP lookup
   → Set country_code, language_code, currency_code
   → Set location_manually_set = false
   → Set language_manually_set = false
```

**Config**: `config/geoip.php` → `features.lookup_on_registration = true`

**Why this works**: Most users visit as visitors first, so they already have location data. GeoIP only runs if they somehow registered without visiting first (e.g., direct API signup).

#### 3. OAuth Login

**File**: `app/Http/Controllers/Auth/OAuthController.php`

Same logic as registration — fallback GeoIP lookup if no location data exists.

#### 4. "Detect My Location" Button (Profile Page)

**File**: `app/Http/Controllers/ProfileController.php` → `detectLocation()`

User can manually trigger re-detection:

```
1. User clicks "Detect My Location" in Profile > Location & Language
2. Runs GeoIP lookup for current IP
3. Updates ALL location fields:
   → country_code, region, city, timezone
   → language_code (automatic detection)
   → currency_code
4. Sets location_manually_set = false
5. Invalidates language cache
```

**Important**: This is **not** the same as manually selecting a language via LanguageSwitcher:
- "Detect Location" → Automatic (sets `language_manually_set = false`)
- LanguageSwitcher → Manual choice (sets `language_manually_set = true`)

### GeoIP Data Sources

The GeoIP service provides:

- `countryCode` (2-letter ISO: 'de', 'gb', 'us')
- `countryName` (Display: 'Germany', 'United Kingdom')
- `region` (State/province)
- `city`
- `timezone` (e.g., 'Europe/Berlin')
- `latitude` / `longitude` (anonymised to ~1km accuracy)
- `currencyCode` (mapped from country: 'EUR', 'GBP', 'USD')
- `languageCode` (mapped from country: 'de-DE', 'en-GB', 'en-US')

**Currency/Language Mappings**: Stored in `GeolocationService` constants:
- `COUNTRY_CURRENCY_MAP` — Maps country codes to currencies
- `COUNTRY_LANGUAGE_MAP` — Maps country codes to BCP 47 locales

### Manual vs Automatic Language Selection

| Method | Sets `language_manually_set` | Behaviour |
|--------|------------------------------|-----------|
| LanguageSwitcher (flag dropdown) | `true` | User's explicit choice, always respected globally |
| GeoIP on visitor creation | N/A (visitors don't have this flag) | Initial default for visitors |
| GeoIP on registration | `false` | Smart default, can be overridden |
| "Detect My Location" button | `false` | Automatic re-detection, not a manual choice |

### Global Language Logic with GeoIP

Even GeoIP-detected languages are **global**:

```
User signs up from Germany:
→ GeoIP detects language_code = 'de-DE'
→ language_manually_set = false

User visits /gb/pricing:
→ Sees German (their GeoIP-detected language)

User visits /us/pricing:
→ Still sees German (language is global)
```

The key: **Automatic detection produces a global preference**, just like manual selection.

### Disabling GeoIP

To disable GeoIP lookups, update `.env`:

```env
GEOIP_ENABLED=false
```

Or selectively disable features in `config/geoip.php`:

```php
'features' => [
    'lookup_on_visitor_creation' => false,  // Disable on first visit
    'lookup_on_registration' => false,      // Disable on signup
],
```

When disabled:
- Visitors get route country defaults (or fallback country `'gb'`)
- Users get route country defaults until they manually set preferences

### Privacy & Caching

- **IP addresses are not stored** — Only the detected location data
- **Coordinates are anonymised** to ~1km accuracy (2 decimal places)
- **Lookups are cached** for 30 days per IP (config: `geoip.cache.ttl`)
- **Private IPs**: Rejected in production, but allowed in local development for Docker

### Testing GeoIP

In local development with Docker:

```env
GEOIP_ALLOW_PRIVATE_IPS=true
```

This allows testing with Docker's private network IPs. The service returns a default development location (GB) for private IPs.

## Related Files

- **Middleware**:
  - `app/Http/Middleware/SetCountry.php` — Main language/currency resolution logic
  - `app/Http/Middleware/TrackVisitor.php` — Creates visitors with GeoIP defaults
- **Controllers**:
  - `app/Http/Controllers/ProfileController.php` — User language/location updates
  - `app/Http/Controllers/VisitorController.php` — Visitor language/currency updates
  - `app/Http/Controllers/Auth/RegisteredUserController.php` — Registration with GeoIP
  - `app/Http/Controllers/Auth/OAuthController.php` — OAuth login with GeoIP
- **Services**: `app/Services/GeolocationService.php` — MaxMind GeoLite2 integration
- **Components**: `resources/js/Components/Common/LanguageSwitcher.vue` — UI for language switching
- **Inertia Middleware**: `app/Http/Middleware/HandleInertiaRequests.php` — Shares props to Vue
- **Config**: `config/geoip.php` — GeoIP settings and feature flags
- **Tests**: `tests/Feature/LocaleTest.php` — Automated test scenarios
