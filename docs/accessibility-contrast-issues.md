# Accessibility: Colour Contrast Issues

**Date Created:** 2025-12-13
**Last Updated:** 2025-12-13
**Status:** Needs Review

## Overview

This document identifies instances in the codebase where text colour combinations may not meet WCAG (Web Content Accessibility Guidelines) contrast ratio requirements. These issues can affect readability and accessibility for users with visual impairments or colour blindness.

### WCAG Contrast Requirements

- **Level AA (minimum):**
  - Normal text: 4.5:1 contrast ratio
  - Large text (18pt+/14pt+ bold): 3:1 contrast ratio
- **Level AAA (enhanced):**
  - Normal text: 7:1 contrast ratio
  - Large text: 4.5:1 contrast ratio

---

## Identified Issues

### 1. Gray Text on Indigo/Purple Backgrounds (Dark Mode)

**Severity:** High
**WCAG Level:** Likely fails AA for normal text

#### Card.vue (line 21)

**Location:** `resources/js/Components/Card.vue:21`

```vue
class="max-w-4xl overflow-hidden bg-white text-gray-600 shadow-lg ring-1 ring-gray-100 sm:rounded-lg dark:bg-indigo-50"
```

**Issue:**
- Light mode: `text-gray-600` on `bg-white` ✅ (likely sufficient contrast)
- Dark mode: `text-gray-600` on `bg-indigo-50` ❌ (insufficient contrast)

**Impact:** All card components throughout the application in dark mode

**Recommended Fix:**
```vue
class="max-w-4xl overflow-hidden bg-white text-gray-600 shadow-lg ring-1 ring-gray-100 sm:rounded-lg dark:bg-indigo-50 dark:text-indigo-900"
```

---

#### FeatureCard.vue (line 17)

**Location:** `resources/js/Components/FeatureCard.vue:17`

```vue
class="rounded-lg bg-white p-6 text-gray-600 shadow-lg ring-1 ring-gray-100 dark:bg-indigo-50"
```

**Issue:**
- Light mode: `text-gray-600` on `bg-white` ✅
- Dark mode: `text-gray-600` on `bg-indigo-50` ❌

**Impact:** Feature cards on home page

**Recommended Fix:**
```vue
class="rounded-lg bg-white p-6 text-gray-600 shadow-lg ring-1 ring-gray-100 dark:bg-indigo-50 dark:text-indigo-900"
```

---

#### ButtonVoiceInput.vue (line 59)

**Location:** `resources/js/Components/ButtonVoiceInput.vue:59`

```vue
: 'bg-white text-gray-700 hover:bg-gray-50 focus:ring-indigo-500 dark:bg-indigo-100 dark:hover:bg-indigo-200',
```

**Issue:**
- Light mode: `text-gray-700` on `bg-white` ✅
- Dark mode: `text-gray-700` on `bg-indigo-100` ❌

**Impact:** Voice input button when not active

**Recommended Fix:**
```vue
: 'bg-white text-gray-700 hover:bg-gray-50 focus:ring-indigo-500 dark:bg-indigo-100 dark:text-indigo-900 dark:hover:bg-indigo-200',
```

---

### 2. Gray Text on Light Coloured Backgrounds (All Modes)

**Severity:** Medium to High
**WCAG Level:** May fail AA for normal text

#### Home.vue - "For Analytical Minds" Section (lines 105-113)

**Location:** `resources/js/Pages/Home.vue:105-113`

```vue
<div class="rounded-lg bg-indigo-50 p-8">
    <h3 class="text-xl font-bold text-indigo-900">
        For Analytical Minds
    </h3>
    <p class="mt-4 text-gray-700">
        If you're a Thinking type who loves data, logic, and
        structure, you'll get prompts with clear frameworks,
        measurable outcomes, and systematic approaches.
    </p>
</div>
```

**Issue:**
- `text-gray-700` (#374151) on `bg-indigo-50` (#EEF2FF)
- Estimated contrast ratio: ~4.8:1 (marginal pass for AA, fails AAA)

**Impact:** Home page personality feature descriptions

**Recommended Fix:**
```vue
<p class="mt-4 text-indigo-900">
```

---

#### Home.vue - "For Creative Explorers" Section (lines 116-125)

**Location:** `resources/js/Pages/Home.vue:116-125`

```vue
<div class="rounded-lg bg-purple-50 p-8">
    <h3 class="text-xl font-bold text-purple-900">
        For Creative Explorers
    </h3>
    <p class="mt-4 text-gray-700">
        If you're an Intuitive type who thrives on
        possibilities and big-picture thinking, you'll get
        prompts that encourage exploration, connections, and
        innovative solutions.
    </p>
</div>
```

**Issue:**
- `text-gray-700` on `bg-purple-50`
- Similar contrast issue to indigo variant

**Recommended Fix:**
```vue
<p class="mt-4 text-purple-900">
```

---

#### Home.vue - "For Everyone in Between" Section (lines 128-138)

**Location:** `resources/js/Pages/Home.vue:128-138`

```vue
<div class="rounded-lg bg-indigo-50 p-8">
    <h3 class="text-xl font-bold text-indigo-900">
        For Everyone in Between
    </h3>
    <p class="mt-4 text-gray-700">
        With optional trait percentages, BettrPrompt calibrates
        prompts based on trait strength. A highly analytical
        INTJ (95% Thinking) gets different optimization than
        a balanced INTJ (55% Thinking) - because not all
        INTJs think alike.
    </p>
</div>
```

**Issue:**
- `text-gray-700` on `bg-indigo-50`

**Recommended Fix:**
```vue
<p class="mt-4 text-indigo-900">
```

---

## Other Potentially Affected Components

### Components Using Coloured Backgrounds (Currently Using Correct Contrast)

These components currently use appropriate colour pairings but should be monitored:

1. **Admin Dashboard** (`resources/js/Pages/Admin/Dashboard.vue`)
   - Lines 33, 48, 67, 84: Uses matching colour schemes (e.g., `bg-blue-100` with `text-blue-600`) ✅

2. **Admin Users/Tasks** (Index and Show pages)
   - Status badges use matching colour schemes ✅
   - Example: `bg-blue-100 text-blue-800`, `bg-green-100 text-green-800`

3. **Feedback ThankYou Page** (`resources/js/Pages/Feedback/ThankYou.vue`)
   - Line 146-163: Uses `bg-blue-50` with `text-blue-800` ✅

4. **Profile Location Form** (`resources/js/Pages/Profile/Partials/UpdateLocationForm.vue`)
   - Line 136-151: Uses `bg-blue-50` with `text-blue-900`, `text-blue-700`, `text-blue-600` ✅

5. **Workflow Docs Preview** (`resources/js/Pages/Workflow/Docs.vue`)
   - Line 307: Uses `prose` classes which should handle contrast automatically ✅

---

## Testing Methodology

To verify these issues, use one of the following tools:

### Browser DevTools
1. Open browser DevTools (F12)
2. Inspect the element
3. Look for the "Accessibility" or "Contrast" section
4. Check the contrast ratio reported

### Online Tools
- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
- [Colour Contrast Analyser](https://www.tpgi.com/color-contrast-checker/)

### Browser Extensions
- **Chrome/Edge:** WAVE Evaluation Tool, axe DevTools
- **Firefox:** Accessibility Inspector (built-in)

---

## Recommended Action Plan

### Priority 1: Critical Issues (Dark Mode)
1. ✅ Fix `Card.vue` - affects entire application
2. ✅ Fix `FeatureCard.vue` - affects home page
3. ✅ Fix `ButtonVoiceInput.vue` - affects prompt builder

### Priority 2: Medium Issues (Light Mode)
4. ✅ Fix all three sections in `Home.vue` - affects marketing/landing page

### Priority 3: Testing & Verification
5. ⬜ Run automated accessibility tests on all pages
6. ⬜ Manual testing with screen readers
7. ⬜ Test with actual users who have visual impairments

---

## Implementation Notes

### Pattern to Follow

When using coloured backgrounds, always use matching text colours from the same colour family:

**Good Examples:**
```vue
<!-- Indigo background with indigo text -->
<div class="bg-indigo-50 text-indigo-900">Content</div>

<!-- Purple background with purple text -->
<div class="bg-purple-50 text-purple-900">Content</div>

<!-- Blue background with blue text -->
<div class="bg-blue-50 text-blue-800">Content</div>
```

**Bad Examples:**
```vue
<!-- Gray text on coloured background -->
<div class="bg-indigo-50 text-gray-700">Content</div>

<!-- Mismatched colour families -->
<div class="bg-purple-50 text-blue-700">Content</div>
```

### Dark Mode Considerations

When adding dark mode variants, ensure text remains readable:

```vue
<!-- Light mode: white bg with gray text -->
<!-- Dark mode: indigo bg with indigo text -->
<div class="bg-white text-gray-600 dark:bg-indigo-50 dark:text-indigo-900">
  Content
</div>
```

---

## Additional Resources

- [WCAG 2.1 Success Criterion 1.4.3 (Contrast Minimum)](https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html)
- [Tailwind CSS Colour Palette](https://tailwindcss.com/docs/customizing-colors)
- [WebAIM: Contrast and Colour Accessibility](https://webaim.org/articles/contrast/)

---

## Change Log

| Date | Change | Author |
|------|--------|--------|
| 2025-12-13 | Initial audit completed | Claude Code |
