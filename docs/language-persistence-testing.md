# Language Persistence Implementation - Testing Guide

## Overview

The language persistence feature allows users to explicitly choose their language via the LanguageSwitcher, and this choice is persisted to the database. The language preference is then passed to n8n workflows for localized Claude responses.

## What Was Implemented

### 1. Backend Endpoints

**For Authenticated Users:**
- **Route:** `PATCH /profile/language`
- **Controller:** `ProfileController@updateLanguage`
- **Payload:** `{ "language_code": "fr" }`
- **Response:** `{ "success": true }`
- **Sets:** `users.language_code` and `users.language_manually_set = true`

**For Visitors:**
- **Route:** `PATCH /visitor/language`
- **Controller:** `VisitorController@updateLanguage`
- **Payload:** `{ "language_code": "de" }`
- **Response:** `{ "success": true }`
- **Sets:** `visitors.language_code`

### 2. Frontend Component

**Modified:** `resources/js/Components/Common/LanguageSwitcher.vue`
- Now calls the appropriate API endpoint when language is changed
- Auto-detects whether user is authenticated or visitor
- Gracefully handles API errors
- Persists language choice before navigating to new locale

### 3. Data Flow

```
User clicks language in LanguageSwitcher
    ↓
Component calls /profile/language or /visitor/language API
    ↓
Backend updates language_code in database
    ↓
Component updates frontend i18n
    ↓
User navigates to /new-locale/prompt-builder
    ↓
SetLocale middleware sets application locale
    ↓
PromptBuilderController triggers n8n workflow
    ↓
n8n receives user_context.location.language in payload
    ↓
n8n Claude nodes generate response in specified language
```

## Testing Steps

### Test 1: Visitor Language Persistence

**Setup:**
1. Open browser in private/incognito mode
2. Navigate to `https://app.localhost/en-GB/prompt-builder`
3. Open browser DevTools → Network tab

**Test Steps:**
1. Click the language switcher dropdown
2. Click "Français" (French)
3. In Network tab, look for `PATCH /visitor/language` request
   - Should see: `{ "language_code": "fr" }`
   - Should get: `{ "success": true }`
4. Verify page navigates to `/fr/prompt-builder`
5. **Database verification:**
   - In terminal, run:
     ```bash
     ./vendor/bin/sail artisan tinker
     >>> Visitor::latest()->first()->language_code
     => "fr"
     ```

**Expected Result:** `language_code` should be `"fr"`

### Test 2: User Language Persistence (Authenticated)

**Setup:**
1. Log in to your account
2. Navigate to `https://app.localhost/en-GB/prompt-builder`
3. Open browser DevTools → Network tab

**Test Steps:**
1. Click the language switcher dropdown
2. Click "Deutsch" (German)
3. In Network tab, look for `PATCH /profile/language` request
   - Should see: `{ "language_code": "de" }`
   - Should get: `{ "success": true }`
4. Verify page navigates to `/de/prompt-builder`
5. **Database verification:**
   - In terminal, run:
     ```bash
     ./vendor/bin/sail artisan tinker
     >>> User::find(1)->language_code
     => "de"
     >>> User::find(1)->language_manually_set
     => true
     ```

**Expected Result:** Both `language_code` should be `"de"` and `language_manually_set` should be `true`

### Test 3: Language Passed to n8n Workflows

**Setup:**
1. Log in to your account
2. Use the language switcher to set language to French (`/fr/prompt-builder`)
3. Open browser DevTools → Network tab (keep open)

**Test Steps:**
1. Submit a prompt with task description
2. In Network tab, find the `POST /prompt-builder/analyse` request
3. In the request payload, look for the webhook data
4. Verify `user_context.location.language` contains the French locale code

**Expected Result:**
```json
{
  "user_context": {
    "location": {
      "language": "fr"
    },
    ...
  }
}
```

### Test 4: Session Persistence (Optional)

**Setup:**
1. Log in with a user
2. Set language to Spanish via language switcher
3. Refresh the page

**Test Steps:**
1. After refresh, check if language defaults to Spanish
2. Check the locale from the URL

**Expected Result:**
- URL should remain at `/es/...`
- Language switcher should show Spanish as selected
- (Note: The URL-based routing takes precedence, so if you manually change the URL to `/en-GB/`, the SetLocale middleware will detect this. The language_code in the database becomes the fallback preference for future sessions.)

## Debugging

### If API call fails:

1. **Check browser console:**
   - Look for error message from axios
   - Check CORS headers if it's a cross-origin issue

2. **Check server logs:**
   ```bash
   ./vendor/bin/sail logs laravel
   ```

3. **Verify routes are registered:**
   ```bash
   ./vendor/bin/sail artisan route:list | grep language
   ```

### If language_code isn't updating:

1. **Check the database directly:**
   ```bash
   ./vendor/bin/sail artisan tinker
   >>> User::find(1)->where('language_code', 'fr')->get()
   ```

2. **Check if validation is failing:**
   - Look at `config/app.php` for supported locales
   - Verify the language code sent matches exactly

### If n8n doesn't receive the language:

1. **Check n8n webhook logs:**
   - Go to n8n dashboard → Execution history
   - Look at the incoming webhook data

2. **Verify User/Visitor context:**
   - In tinker, verify the user has the language_code set
   - Check the PromptRun and how it retrieves user context

## n8n Integration

Once you've verified language persistence works, update the n8n workflows:

See `docs/n8n-language-setup.md` for detailed instructions on:
- Adding language instructions to Claude system prompts
- Testing the language-specific responses
- Debugging if responses still come in English

## Test Commands

```bash
# Run all tests
./vendor/bin/sail test

# Run specific test
./vendor/bin/sail test tests/Feature/LanguagePersistenceTest.php

# Interactive tinker
./vendor/bin/sail artisan tinker

# Check routes
./vendor/bin/sail artisan route:list | grep language
```

## Success Criteria

✅ Visitor can change language and language_code updates in database
✅ Authenticated user can change language and language_code updates in database
✅ LanguageSwitcher calls correct API endpoint based on user type
✅ API returns success response
✅ Page navigates to correct locale URL
✅ n8n receives language code in webhook payload
✅ (After n8n setup) Responses come back in chosen language

## Files Modified

- `app/Http/Controllers/ProfileController.php` - Added `updateLanguage()` method
- `app/Http/Controllers/VisitorController.php` - Added `updateLanguage()` method
- `routes/web.php` - Added language routes
- `resources/js/Components/Common/LanguageSwitcher.vue` - Updated to persist choice

## Related Documentation

- `docs/i18n-language-persistence-plan.md` - Architecture and design decisions
- `docs/n8n-language-setup.md` - n8n workflow configuration
