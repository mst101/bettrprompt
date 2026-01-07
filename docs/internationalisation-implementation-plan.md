# Internationalisation (i18n) Implementation Plan

## Executive Summary

This plan implements full multi-language support for BettrPrompt using **URL path prefixes** (`/en/`, `/fr/`, etc.), *
*vue-i18n** for frontend, and **Laravel's built-in translation system** for backend. Machine translation with human
review will be used for initial translations.

### Target Languages (18 total)

**Tier 1: Essential (English variants)**
| Code | Language | Direction | Notes |
|------|----------|-----------|-------|
| `en-US` | English (American) | LTR | **Default** - largest AI market |
| `en-GB` | English (British) | LTR | Source language, UK market |

**Tier 2: High Value (wealthy + high AI adoption)**
| Code | Language | Direction | Notes |
|------|----------|-----------|-------|
| `de` | German | LTR | Germany, Austria, Switzerland |
| `ja` | Japanese | LTR | High premium pricing acceptance |
| `ko` | Korean | LTR | Extremely tech-forward, affluent |
| `fr` | French | LTR | France, Canada, Belgium |

**Tier 3: Nordic (very wealthy, high tech adoption)**
| Code | Language | Direction | Notes |
|------|----------|-----------|-------|
| `sv` | Swedish | LTR | Sweden |
| `no` | Norwegian | LTR | Norway (highest GDP/capita) |
| `da` | Danish | LTR | Denmark |
| `fi` | Finnish | LTR | Finland |
| `nl` | Dutch | LTR | Netherlands, Belgium |

**Tier 4: Volume Markets**
| Code | Language | Direction | Notes |
|------|----------|-----------|-------|
| `es` | Spanish | LTR | Spain + Latin America |
| `pt` | Portuguese | LTR | Brazil, Portugal |
| `it` | Italian | LTR | Italy |
| `zh` | Chinese (Simplified) | LTR | Taiwan, Singapore (China has access issues) |

**Tier 5: RTL + Other**
| Code | Language | Direction | Notes |
|------|----------|-----------|-------|
| `ar` | Arabic | **RTL** | Gulf states (UAE, Saudi) - wealthy |
| `he` | Hebrew | **RTL** | Israel - high tech hub |

### Key Decisions

- ✅ **en-US as default** - largest AI market, highest willingness to pay
- ✅ **URL path prefix** (`/fr/prompt-builder`) - SEO friendly, shareable
- ✅ **Machine translation + review** - DeepL/Claude for initial pass
- ✅ **RTL support** - Arabic and Hebrew
- ✅ **Nordic languages** - high-value, low-competition localisation

---

## Current State Assessment

### What Needs Translating

| Category               | Count | Location                | Notes                        |
|------------------------|-------|-------------------------|------------------------------|
| Vue component strings  | ~600  | `resources/js/**/*.vue` | Hardcoded in templates       |
| Validation messages    | ~30   | Form requests           | Custom error messages        |
| Personality type names | 16    | `Constants/workflow.ts` | MBTI type labels             |
| Enum labels            | ~100  | Profile form options    | Job titles, industries, etc. |
| Legal pages            | 3     | Privacy, Terms, Cookies | Long-form content            |
| Email templates        | TBD   | `resources/views/mail/` | Password reset, etc.         |

### Key Files to Modify

**High-impact Vue components:**

- `resources/js/Layouts/AppLayout.vue` - Navigation, header
- `resources/js/Pages/Home.vue` - Landing page content
- `resources/js/Pages/Auth/*.vue` - Login, register modals
- `resources/js/Pages/Profile/**/*.vue` - 10+ profile forms
- `resources/js/Components/Features/PromptBuilder/**/*.vue` - Core feature

**Backend:**

- `routes/web.php` - Add locale prefix group
- `app/Http/Middleware/SetLocale.php` - New middleware
- `config/app.php` - Supported locales config

---

## Architecture

### URL Structure

```
Current:  /prompt-builder
New:      /en-US/prompt-builder
          /de/prompt-builder
          /ja/prompt-builder
          /ar/prompt-builder (RTL)
```

**Root URL behaviour:**

- `/` redirects to `/{detected_locale}/` based on:
    1. User preference (if logged in)
    2. Browser `Accept-Language` header
    3. Fallback to `en-US`

### Routing Decision: Same Slugs (Recommended)

**Two approaches considered:**

| Approach            | Example                                 | Complexity |
|---------------------|-----------------------------------------|------------|
| **Same slugs**      | `/de/about`, `/de/prompt-builder`       | Simple ✅   |
| **Localised slugs** | `/de/ueber-uns`, `/de/prompt-ersteller` | Complex ❌  |

**Decision: Use same slugs** for these reasons:

1. **Most pages are authenticated** - SEO irrelevant for app pages
2. **CJK languages don't use romanised slugs** - Japanese/Chinese would still be `/ja/about`
3. **Simpler routing** - No slug translation table needed
4. **Easier maintenance** - Add pages without updating 18 slug translations
5. **Minimal SEO difference** - Google handles locale prefixes well

**Future enhancement**: If SEO becomes critical, consider localised slugs only for public pages (`/`, `/pricing`,
`/features`).

### Translation File Structure

```
resources/
├── lang/
│   ├── en-US/                  # Source/default
│   │   ├── common.php          # Shared strings (nav, buttons)
│   │   ├── auth.php            # Authentication
│   │   ├── profile.php         # Profile forms
│   │   ├── prompt-builder.php  # Main feature
│   │   ├── home.php            # Landing page
│   │   ├── validation.php      # Validation messages
│   │   └── legal.php           # Privacy, terms, cookies
│   ├── en-GB/                  # British English variants
│   │   └── ... (spelling differences only)
│   ├── de/
│   │   └── ... (same structure)
│   └── ar/
│       └── ... (same structure)
├── js/
│   └── i18n/
│       ├── index.ts            # vue-i18n setup
│       ├── en-US.json          # Frontend strings (source)
│       ├── en-GB.json          # British variants
│       ├── de.json
│       └── ar.json
```

### Component Pattern

**Before:**

```vue

<template>
    <h1>Welcome to BettrPrompt</h1>
    <p>Create personality-calibrated AI prompts</p>
</template>
```

**After:**

```vue

<template>
    <h1>{{ t('home.hero.title') }}</h1>
    <p>{{ t('home.hero.subtitle') }}</p>
</template>

<script setup lang="ts">
    import { useI18n } from 'vue-i18n';

    const { t } = useI18n();
</script>
```

---

## RTL Support Strategy

### CSS Approach: Logical Properties

Use CSS logical properties instead of directional properties:

```css
/* Before (LTR only) */
.sidebar {
    margin-left: 1rem;
    padding-right: 2rem;
}

/* After (LTR + RTL) */
.sidebar {
    margin-inline-start: 1rem;
    padding-inline-end: 2rem;
}
```

**Property mapping:**
| Physical | Logical |
|----------|---------|
| `left/right` | `inline-start/inline-end` |
| `top/bottom` | `block-start/block-end` |
| `margin-left` | `margin-inline-start` |
| `padding-right` | `padding-inline-end` |
| `text-align: left` | `text-align: start` |

### HTML Direction

```html

<html lang="ar" dir="rtl">
```

Set via Inertia shared data:

```php
// HandleInertiaRequests.php
'locale' => app()->getLocale(),
'direction' => in_array(app()->getLocale(), ['ar', 'he', 'fa']) ? 'rtl' : 'ltr',
```

### Tailwind RTL Plugin

Install `tailwindcss-rtl` for directional utilities:

```html

<div class="ps-4 pe-2 ms-auto">  <!-- ps = padding-start, ms = margin-start -->
```

---

## Technical Implementation

### 1. Laravel Backend Setup

**config/app.php:**

```php
'locale' => 'en-US',
'fallback_locale' => 'en-US',
'supported_locales' => [
    // Tier 1: Essential
    'en-US', 'en-GB',
    // Tier 2: High Value
    'de', 'ja', 'ko', 'fr',
    // Tier 3: Nordic
    'sv', 'no', 'da', 'fi', 'nl',
    // Tier 4: Volume
    'es', 'pt', 'it', 'zh',
    // Tier 5: RTL
    'ar', 'he',
],
'rtl_locales' => ['ar', 'he'],
```

**SetLocale Middleware:**

```php
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->route('locale');

        if (!in_array($locale, config('app.supported_locales'))) {
            abort(404);
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
```

**routes/web.php:**

```php
Route::redirect('/', '/' . app()->getLocale());

Route::prefix('{locale}')
    ->middleware(['web', 'locale'])
    ->where(['locale' => implode('|', config('app.supported_locales'))])
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/prompt-builder', [PromptBuilderController::class, 'index']);
        // ... all other routes
    });
```

### 2. Vue Frontend Setup

**Install vue-i18n:**

```bash
pnpm add vue-i18n@9
```

**resources/js/i18n/index.ts:**

```typescript
import { createI18n } from 'vue-i18n';
import enUS from './en-US.json';

export const i18n = createI18n({
    legacy: false,
    locale: 'en-US',
    fallbackLocale: 'en-US',
    messages: { 'en-US': enUS },
});

// Lazy load other locales (including en-GB for British spellings)
export async function loadLocaleMessages(locale: string) {
    const messages = await import(`./locales/${locale}.json`);
    i18n.global.setLocaleMessage(locale, messages.default);
}
```

**app.ts integration:**

```typescript
import { i18n, loadLocaleMessages } from './i18n';

createInertiaApp({
    setup({ el, App, props, plugin }) {
        // Set locale from Inertia page props
        const locale = props.initialPage.props.locale as string;
        i18n.global.locale.value = locale;

        // Lazy load all locales except en-US (bundled by default)
        if (locale !== 'en-US') {
            loadLocaleMessages(locale);
        }

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .mount(el);
    },
});
```

### 3. Translation Key Naming Convention

```
{page}.{section}.{element}

Examples:
home.hero.title          = "Welcome to BettrPrompt"
home.hero.subtitle       = "Create personality-calibrated AI prompts"
home.features.title      = "Features"
auth.login.title         = "Sign in to your account"
auth.login.email_label   = "Email address"
profile.name.title       = "Personal Information"
profile.name.save_button = "Save changes"
common.nav.home          = "Home"
common.buttons.submit    = "Submit"
```

### 4. Inertia Link Component

Create locale-aware link helper:

```vue
<!-- resources/js/Components/LocaleLink.vue -->
<script setup lang="ts">
    import { Link } from '@inertiajs/vue3';
    import { usePage } from '@inertiajs/vue3';

    const props = defineProps<{ href: string }>();
    const page = usePage();
    const locale = page.props.locale as string;

    const localizedHref = computed(() => {
        if (props.href.startsWith('/') && !props.href.startsWith(`/${locale}`)) {
            return `/${locale}${props.href}`;
        }
        return props.href;
    });
</script>

<template>
    <Link :href="localizedHref">
    <slot />
    </Link>
</template>
```

### 5. Language Switcher Component

```vue
<!-- resources/js/Components/LanguageSwitcher.vue -->
<script setup lang="ts">
    import { router, usePage } from '@inertiajs/vue3';

    const page = usePage();
    const currentLocale = page.props.locale as string;
    const currentPath = computed(() => {
        const path = page.url;
        return path.replace(`/${currentLocale}`, '');
    });

    const locales = [
        // Tier 1: Essential
        { code: 'en-US', name: 'English (US)', flag: '🇺🇸' },
        { code: 'en-GB', name: 'English (UK)', flag: '🇬🇧' },
        // Tier 2: High Value
        { code: 'de', name: 'Deutsch', flag: '🇩🇪' },
        { code: 'ja', name: '日本語', flag: '🇯🇵' },
        { code: 'ko', name: '한국어', flag: '🇰🇷' },
        { code: 'fr', name: 'Français', flag: '🇫🇷' },
        // Tier 3: Nordic
        { code: 'sv', name: 'Svenska', flag: '🇸🇪' },
        { code: 'no', name: 'Norsk', flag: '🇳🇴' },
        { code: 'da', name: 'Dansk', flag: '🇩🇰' },
        { code: 'fi', name: 'Suomi', flag: '🇫🇮' },
        { code: 'nl', name: 'Nederlands', flag: '🇳🇱' },
        // Tier 4: Volume
        { code: 'es', name: 'Español', flag: '🇪🇸' },
        { code: 'pt', name: 'Português', flag: '🇧🇷' },
        { code: 'it', name: 'Italiano', flag: '🇮🇹' },
        { code: 'zh', name: '中文', flag: '🇨🇳' },
        // Tier 5: RTL
        { code: 'ar', name: 'العربية', flag: '🇸🇦' },
        { code: 'he', name: 'עברית', flag: '🇮🇱' },
    ];

    function switchLocale(locale: string) {
        router.visit(`/${locale}${currentPath.value}`);
    }
</script>
```

---

## Laravel Backend Translations

### Translation File Structure

```
resources/lang/
├── en-US/
│   ├── validation.php      # Form validation messages
│   ├── auth.php            # Authentication messages
│   ├── passwords.php       # Password reset messages
│   ├── pagination.php      # Pagination strings
│   └── messages.php        # Custom app messages
├── en-GB/
│   └── ... (same structure)
├── de/
│   └── ...
└── ar/
    └── ...
```

### Using Translations in Controllers/Services

**Laravel's `__()` helper:**

```php
// In controllers
return back()->with('success', __('messages.profile_updated'));

// In form requests
public function messages(): array
{
    return [
        'email.required' => __('validation.email_required'),
        'password.min' => __('validation.password_min', ['min' => 8]),
    ];
}

// In exceptions/error handling
throw new ValidationException(__('messages.invalid_input'));
```

### Validation Messages

**resources/lang/en-US/validation.php:**

```php
return [
    'required' => 'The :attribute field is required.',
    'email' => 'Please enter a valid email address.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],
    // Custom validation messages
    'task_description' => [
        'required' => 'Please describe your task.',
        'min' => 'Your task description must be at least :min characters.',
    ],
];
```

### Custom Application Messages

**resources/lang/en-US/messages.php:**

```php
return [
    // Success messages
    'profile_updated' => 'Your profile has been updated successfully.',
    'password_changed' => 'Your password has been changed.',
    'prompt_generated' => 'Your optimised prompt is ready.',

    // Error messages
    'workflow_failed' => 'Something went wrong. Please try again.',
    'rate_limited' => 'Too many requests. Please wait a moment.',

    // Email subjects
    'email' => [
        'password_reset_subject' => 'Reset Your Password',
        'welcome_subject' => 'Welcome to BettrPrompt',
    ],
];
```

### Email Templates

**resources/views/mail/password-reset.blade.php:**

```blade
@component('mail::message')
# {{ __('messages.email.password_reset_title') }}

{{ __('messages.email.password_reset_body') }}

@component('mail::button', ['url' => $url])
{{ __('messages.email.password_reset_button') }}
@endcomponent

{{ __('messages.email.password_reset_footer', ['minutes' => config('auth.passwords.users.expire')]) }}
@endcomponent
```

### API Error Responses

For API endpoints (n8n webhooks, etc.), ensure error messages are translated:

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $e)
{
    if ($request->expectsJson()) {
        return response()->json([
            'message' => __('messages.error.' . $e->getCode(), [], app()->getLocale()),
        ], $e->getCode());
    }
    // ...
}
```

### Inertia Shared Data

Pass translations to frontend via HandleInertiaRequests:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'locale' => app()->getLocale(),
        'direction' => in_array(app()->getLocale(), config('app.rtl_locales')) ? 'rtl' : 'ltr',
        'translations' => [
            // Pass any server-side translations needed by Vue
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ],
    ]);
}
```

### Flash Messages Pattern

Since Inertia apps show flash messages client-side, ensure they're translated:

```php
// In controller
return redirect()->back()->with('success', __('messages.profile_updated'));

// In Vue (via Inertia shared data)
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
```

---

## Translation Workflow

### Initial Translation Process

1. **Extract strings** - Use script to extract hardcoded strings from Vue files
2. **Create source JSON** - `en-US.json` with all keys
3. **Machine translate** - Use DeepL API or Claude to translate to target languages
4. **Human review** - Native speakers review and correct
5. **Import** - Load translations into JSON files

### Extraction Script

```typescript
// scripts/extract-translations.ts
// Scans Vue files for hardcoded strings, outputs en-US.json template
```

### Machine Translation Service

```php
// app/Services/TranslationService.php
class TranslationService
{
    public function translate(string $text, string $targetLocale): string
    {
        // Use DeepL API or Claude API
        return $this->deepl->translate($text, 'EN-US', $targetLocale);
    }

    public function translateBatch(array $strings, string $targetLocale): array
    {
        // Batch translation for efficiency
    }
}
```

---

## Database Content

### Personality Type Names

Move from `Constants/workflow.ts` to translation files:

```json
// en-US.json
{
    "personality": {
        "types": {
            "INTJ": "Architect",
            "INTP": "Logician",
            "ENTJ": "Commander"
        }
    }
}
```

### Enum Options (Profile Forms)

Move job titles, industries, etc. to translation files:

```json
{
    "profile": {
        "industries": {
            "technology": "Technology",
            "finance": "Finance & Banking",
            "healthcare": "Healthcare"
        }
    }
}
```

### n8n Workflow Outputs

**Challenge:** n8n generates text (questions, prompts) in the user's language.

**Solution:** Pass `locale` to n8n webhook, use in Claude prompts:

```javascript
// n8n workflow
const locale = webhookData.locale || 'en-US';
const systemPrompt = `Respond in ${locale} language. Use appropriate cultural conventions.`;
```

---

## SEO Considerations

### Hreflang Tags

```html

<link rel="alternate" hreflang="en-US" href="https://bettrprompt.com/en-US/prompt-builder" />
<link rel="alternate" hreflang="fr" href="https://bettrprompt.com/fr/prompt-builder" />
<link rel="alternate" hreflang="ar" href="https://bettrprompt.com/ar/prompt-builder" />
<link rel="alternate" hreflang="x-default" href="https://bettrprompt.com/en-US/prompt-builder" />
```

### Sitemap

Generate separate sitemaps per locale or include all locale URLs in main sitemap.

### Meta Tags

Translate title and description per page per locale.

---

## Implementation Phases

### Phase 0: Infrastructure (3-4 days)

1. Install vue-i18n and configure
2. Create SetLocale middleware
3. Update routes with locale prefix
4. Create language switcher component (with 18 languages)
5. Add RTL CSS logical properties where needed
6. Set up Tailwind RTL plugin
7. **Files**: `app.ts`, middleware, routes, Tailwind config

### Phase 1: English Variants + String Extraction (4-5 days)

1. Extract ALL strings from Vue components (~600 strings)
2. Create `en-US.json` as source file
3. Create `en-GB.json` with British spelling variants
4. Test language switching between EN variants
5. **Files**: ~35 Vue components, 2 JSON files

### Phase 2: High Value Languages (5-6 days)

1. Machine translate to DE, JA, KO, FR
2. Handle CJK font considerations (ja, ko)
3. Human review high-value translations
4. Test character display
5. **Files**: 4 JSON files

### Phase 3: Nordic Languages (3-4 days)

1. Machine translate to SV, NO, DA, FI, NL
2. Human review Nordic translations
3. **Files**: 5 JSON files

### Phase 4: Volume Markets (3-4 days)

1. Machine translate to ES, PT, IT, ZH
2. Handle CJK fonts (zh)
3. Human review
4. **Files**: 4 JSON files

### Phase 5: RTL Languages (4-5 days)

1. Audit and update CSS for logical properties
2. Translate to AR, HE
3. Test RTL layout thoroughly
4. Fix RTL-specific issues (mirrored icons, etc.)
5. **Files**: CSS updates, 2 JSON files

### Phase 6: Backend & n8n (2-3 days)

1. Translate validation messages (18 locales)
2. Update n8n workflows to accept locale
3. Translate email templates
4. **Files**: Laravel lang files, n8n workflows

### Phase 7: Legal & Content (3-4 days)

1. Translate Privacy Policy, Terms, Cookies
2. May require legal review per jurisdiction
3. **Files**: 3 Vue pages × 18 locales

**Total Estimated Effort: 27-35 days**

### Rollout Strategy

Deploy in tiers to manage risk:

1. **Week 1**: Infrastructure + en-US/en-GB
2. **Week 2**: DE, JA, KO, FR (high-value)
3. **Week 3**: Nordic (SV, NO, DA, FI, NL)
4. **Week 4**: Volume (ES, PT, IT, ZH)
5. **Week 5**: RTL (AR, HE) + backend + legal

---

## Testing Strategy

### Automated

- **Visual regression** - Screenshot comparison per locale
- **Unit tests** - Translation key existence
- **E2E tests** - Language switching, RTL layout

### Manual

- **Native speaker review** - Quality check translations
- **RTL testing** - Full user flow in Arabic
- **Character encoding** - CJK display, special characters

---

## Files Summary

| File                                           | Action | Purpose                           |
|------------------------------------------------|--------|-----------------------------------|
| `package.json`                                 | Modify | Add vue-i18n, tailwindcss-rtl     |
| `resources/js/app.ts`                          | Modify | Integrate vue-i18n                |
| `resources/js/i18n/index.ts`                   | Create | i18n configuration                |
| `resources/js/i18n/en-US.json`                 | Create | Source translations (default)     |
| `resources/js/i18n/en-GB.json`                 | Create | British English variants          |
| `resources/js/i18n/{locale}.json`              | Create | 16 additional locale files        |
| `resources/lang/{locale}/*.php`                | Create | Backend translations (18 locales) |
| `app/Http/Middleware/SetLocale.php`            | Create | Locale middleware                 |
| `routes/web.php`                               | Modify | Add locale prefix                 |
| `config/app.php`                               | Modify | Add supported_locales (18)        |
| `resources/js/Components/LanguageSwitcher.vue` | Create | Language selector UI              |
| `resources/js/Components/LocaleLink.vue`       | Create | Locale-aware links                |
| `tailwind.config.js`                           | Modify | Add RTL plugin                    |
| All Vue components (~35)                       | Modify | Replace hardcoded strings         |

**Total translation files: 18 JSON (frontend) + 18 PHP directories (backend) = 36 locale bundles**

---

## Maintenance Considerations

### Adding New Strings

1. Add to `en-US.json` with new key
2. Weblate auto-translates to all languages (marked "needs review")
3. Human review when convenient

### Adding New Language

1. Add to `config.supported_locales`
2. Create new JSON file
3. Weblate auto-translates from English
4. Human review

---

## Translation Management: Self-Hosted Weblate

**Scale**: 18 languages × ~600 strings = **~10,800 total translation strings**

**Recommended: Self-hosted Weblate** (no monthly fees)

### Why Weblate

| Feature                     | Weblate Support                     |
|-----------------------------|-------------------------------------|
| JSON files (vue-i18n)       | ✅ Native                            |
| PHP arrays (Laravel)        | ✅ Native                            |
| Git sync                    | ✅ Bidirectional with GitHub         |
| Machine translation         | ✅ DeepL, LibreTranslate, Claude API |
| Web UI for translators      | ✅ Professional quality              |
| Glossary/translation memory | ✅ Built-in                          |

### Weblate Setup

**Hosting requirements:**

- VPS with ~2GB RAM (e.g., Hetzner CX21 ~€4/month)
- Docker + Docker Compose
- PostgreSQL database
- Nginx reverse proxy

**Docker Compose deployment:**

```yaml
# docker-compose.weblate.yml
version: '3'
services:
    weblate:
        image: weblate/weblate:latest
        environment:
            WEBLATE_SITE_DOMAIN: translate.bettrprompt.com
            WEBLATE_ADMIN_EMAIL: admin@bettrprompt.com
            POSTGRES_HOST: db
            WEBLATE_MT_SERVICES: weblate.machinery.deepl.DeepLTranslation
        volumes:
            - weblate-data:/app/data
        ports:
            - "8080:8080"
    db:
        image: postgres:15
        volumes:
            - postgres-data:/var/lib/postgresql/data
```

**Git sync configuration:**

1. Connect Weblate to GitHub repo via SSH key
2. Configure component paths:
    - Frontend: `resources/js/i18n/*.json`
    - Backend: `resources/lang/*/*.php`
3. Enable push on translate (commits changes automatically)

### Weblate Workflow (Auto-Translate + Human Review)

```
Developer adds new string in en-US.json
        ↓
Push to GitHub
        ↓
Weblate pulls changes (webhook)
        ↓
AUTO-TRANSLATION RUNS (DeepL/Claude)
All 17 languages get machine translations
        ↓
Strings marked "Needs review" (fuzzy flag)
Machine translations are NOT final yet
        ↓
PRODUCTION: Can use fuzzy strings immediately
(or configure to wait for approval)
        ↓
HUMAN REVIEW (when convenient)
Translators see review queue in Weblate UI
Approve OR correct machine translations
        ↓
Approved translations pushed to GitHub
        ↓
Translation memory learns from corrections
(improves future MT suggestions)
```

**Configuration for this workflow:**

```python
# Weblate component settings
AUTO_TRANSLATE = True
AUTO_TRANSLATE_MODE = 'fuzzy'  # Mark as needing review
SUGGESTION_VOTING = False       # Direct approval
CHECK_GLOSSARY = True           # Enforce terminology
```

**Quality gates (optional):**

- Require human approval before pushing (slower but higher quality)
- Allow fuzzy strings in staging, require approval for production
- Set up review reminders for translators

### Machine Translation in Weblate

Configure free/cheap options:

- **LibreTranslate** (self-hosted, free)
- **DeepL Free** (500k chars/month free)
- **Claude API** (custom integration for high-quality suggestions)

### Alternative: File-based with Claude Script

If Weblate is overkill, simpler approach:

```bash
# scripts/translate.sh
# Uses Claude API to translate new strings
node scripts/translate-missing.js --source en-US --target de
```

This generates translations for review, committed directly to repo.
