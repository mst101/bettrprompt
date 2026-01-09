# Plan: Persist Language Choice and Pass to n8n Workflows

## Problem
When a user visits `https://app.localhost/fr/prompt-builder` and submits a task in French, n8n responds in English because:
1. The LanguageSwitcher only changes the URL - it doesn't persist the choice to the database
2. `user_context.location.language` (sent to n8n) remains the geolocation-detected value
3. n8n has no way to know the user explicitly chose French

## Solution Overview (Simplified)
Update `language_code` on User/Visitor when language is explicitly changed via LanguageSwitcher. This field is already sent to n8n via `user_context.location.language`.

**Key insight:** User's explicit choice should override geolocation detection. One field, one source of truth.

## Current State
- **LanguageSwitcher:** Changes URL + frontend i18n only, NO database persistence
- **Users:** Have `language_code` field, updated only via Profile location endpoint
- **Visitors:** Have `language_code` field, but NO endpoint to update it
- **n8n:** Already receives `user_context.location.language` - just needs to be kept in sync

## Implementation Steps

### Step 1: Create language update endpoint for visitors
**File:** `app/Http/Controllers/VisitorController.php`

Add new method:
```php
public function updateLanguage(Request $request): JsonResponse
{
    $validated = $request->validate([
        'language_code' => ['required', 'string', 'max:10', Rule::in(config('app.supported_locales'))],
    ]);

    $visitor = Visitor::findByToken($request->cookie('visitor_id'));
    if ($visitor) {
        $visitor->update(['language_code' => $validated['language_code']]);
    }

    return response()->json(['success' => true]);
}
```

**File:** `routes/web.php`
```php
Route::patch('/visitor/language', [VisitorController::class, 'updateLanguage']);
```

### Step 2: Create language update endpoint for users
**File:** `app/Http/Controllers/ProfileController.php`

Add new method (or use existing location endpoint):
```php
public function updateLanguage(Request $request): JsonResponse
{
    $validated = $request->validate([
        'language_code' => ['required', 'string', 'max:10', Rule::in(config('app.supported_locales'))],
    ]);

    $request->user()->update([
        'language_code' => $validated['language_code'],
        'language_manually_set' => true,
    ]);

    return response()->json(['success' => true]);
}
```

**File:** `routes/web.php`
```php
Route::patch('/profile/language', [ProfileController::class, 'updateLanguage'])->middleware('auth');
```

### Step 3: Update LanguageSwitcher to persist choice
**File:** `resources/js/Components/Common/LanguageSwitcher.vue`

Modify `switchLocale()` to call the API:
```typescript
async function switchLocale(locale: LocaleInfo) {
    if (locale.code === currentLocale.value) {
        isOpen.value = false;
        return;
    }
    isOpen.value = false;

    // Persist language choice to database
    const endpoint = page.props.auth?.user
        ? '/profile/language'
        : '/visitor/language';

    await axios.patch(endpoint, { language_code: locale.code });

    // Update client-side i18n
    await setLocale(locale.code);

    // Navigate to new locale URL
    const newPath = `/${locale.code}${currentPath.value}`;
    router.visit(newPath, { preserveState: true, preserveScroll: true });
}
```

### Step 4: Update n8n workflows to use language field
**Action in n8n workflow editor:**
1. `user_context.location.language` is already in the payload
2. Add to Claude system prompts: "Respond in the user's language: {user_context.location.language}. All output including error messages must be in this language."

## Files to Modify

| File | Change |
|------|--------|
| `app/Http/Controllers/VisitorController.php` | Add `updateLanguage()` method |
| `app/Http/Controllers/ProfileController.php` | Add `updateLanguage()` method |
| `routes/web.php` | Add language update routes |
| `resources/js/Components/Common/LanguageSwitcher.vue` | Persist choice via API call |

## Testing
1. As visitor: Change language via switcher → verify `visitors.language_code` updates
2. As user: Change language via switcher → verify `users.language_code` updates
3. Submit task after language change → verify n8n receives correct `user_context.location.language`
4. (After n8n update) Verify responses come back in chosen language

## Benefits of This Approach
- ✅ No new database columns needed
- ✅ Uses existing `language_code` field
- ✅ User's explicit choice overrides geolocation
- ✅ n8n payload already has the field - no changes to job payloads
- ✅ Single source of truth for language preference
