# Refactoring Analysis Summary

## Quick Start

A comprehensive refactoring analysis of the Vue/TypeScript frontend has been completed and saved to `REFACTORING_REPORT.md`.

**Analysis Scope:**
- 167 Vue components
- 2,513+ lines of component code
- 6 composables
- 4 Pinia stores
- 16 type definition files
- Entire resources/js directory structure

**Total Findings: 40+ actionable refactoring opportunities**

---

## Key Metrics

| Metric | Count |
|--------|-------|
| Code Duplication Issues | 8 |
| Component Structure Problems | 3 |
| Type Safety Issues | 5+ |
| Performance Concerns | 3+ |
| Accessibility Issues | 3+ |
| Styling Duplication | 31+ |
| Hardcoded Values | 5+ |
| Unused Patterns | 2 |

---

## Top 5 High-Impact, Low-Effort Refactoring Tasks

### 1. Extract Tailwind Constants (31+ occurrences)
**Effort:** 1-2 hours  
**Impact:** High  
**Details:**
- Consolidate repeated `focus:ring-2 focus:ring-indigo-500` patterns
- Create `constants/tailwind.ts` with button, focus, and layout utilities
- Replace hardcoded classes across 10+ components

**Files Affected:**
- ButtonVoiceInput.vue, CookieBanner.vue, CookieSettings.vue, Modal.vue, LoginModal.vue, FlashMessage.vue, and more

---

### 2. Create `useTextAppend` Composable (2 occurrences)
**Effort:** 30 minutes  
**Impact:** Medium (DRY, maintainability)  
**Details:**
- Extract identical transcription text appending logic
- Reuse in PromptOptimizer/Index.vue and QuestionAnsweringForm.vue

**Files Affected:**
- `/home/mark/repos/personality/resources/js/Pages/PromptOptimizer/Index.vue` (lines 25-31)
- `/home/mark/repos/personality/resources/js/Components/PromptOptimizer/QuestionAnsweringForm.vue` (lines 31-38)

---

### 3. Consolidate Button Components (7 files)
**Effort:** 2-3 hours  
**Impact:** High (consistency, bundle size)  
**Details:**
- Combine PrimaryButton, SecondaryButton, DangerButton, ButtonMode, ButtonClose, ButtonDarkMode
- Create unified `Button.vue` with variant prop
- Update 30+ usages across codebase

**Files to Create:**
- `Components/Button.vue` (unified button component)

**Files to Remove/Deprecate:**
- PrimaryButton.vue, SecondaryButton.vue, DangerButton.vue, ButtonMode.vue

---

### 4. Fix Type Safety Issues (5+ instances)
**Effort:** 1-2 hours  
**Impact:** High (maintainability, IDE support)  
**Details:**
- Replace `any` types in Checkbox.vue (value prop)
- Define EchoChannel types for useRealtimeUpdates
- Add proper event type definitions to Modal and other components

**Key Files:**
- `/home/mark/repos/personality/resources/js/Components/Checkbox.vue` (line 8)
- `/home/mark/repos/personality/resources/js/Composables/useRealtimeUpdates.ts`
- `/home/mark/repos/personality/resources/js/Components/Modal.vue` (line 17)

---

### 5. Extract Cookie Utilities (2 occurrences)
**Effort:** 30 minutes  
**Impact:** Low-Medium (code reuse, consistency)  
**Details:**
- Extract getCookie/setCookie logic from useCookieConsent
- Create `utils/cookies.ts`
- Reuse in useAudioRecording.ts

**Files Affected:**
- `/home/mark/repos/personality/resources/js/Composables/useCookieConsent.ts` (lines 18-40)
- `/home/mark/repos/personality/resources/js/Composables/useAudioRecording.ts` (lines 102-116)

---

## Medium-Impact Refactoring Tasks

### 6. Form Component Consolidation (8 files)
**Effort:** 4-6 hours  
**Impact:** Very High  
**Details:**
- Create shared `BaseFormInputProps` interface
- Consolidate FormInput, FormSelect, FormTextarea, FormCheckbox
- Reduce prop duplication across 8 components
- Estimated 150+ lines of code reduction

**Files Involved:**
- FormField.vue, FormInput.vue, FormSelect.vue, FormTextarea.vue, FormCheckbox.vue, FormCheckboxGroup.vue, FormToggle.vue, FormFieldWrapper.vue

---

### 7. Error Timeout Composable (3 occurrences)
**Effort:** 1 hour  
**Impact:** Medium (DRY, consistency)  
**Details:**
- Extract error timeout logic appearing in 3 places
- Create `useErrorTimeout.ts`
- Simplify useAudioRecording and FlashMessage

---

### 8. Authentication Modal Consolidation (4 files)
**Effort:** 2-3 hours  
**Impact:** Medium  
**Details:**
- Reduce duplication in LoginModal, RegisterModal, ForgotPasswordModal
- Consolidate around BaseAuthModal
- Eliminate similar form structure repetition

---

## Performance Optimizations

### 9. Memoize LikertScale Calculations
**File:** `/home/mark/repos/personality/resources/js/Components/LikertScale.vue`  
**Effort:** 30 minutes  
**Impact:** Small (minor render performance)  

Move color and size arrays to computed properties to prevent recreation on every render.

---

## Accessibility Improvements

### 10. Add ARIA Labels
**Effort:** 30 minutes  
**Impact:** Medium  
**Files:**
- ButtonVoiceInput.vue - add aria-label and aria-pressed
- LikertScale.vue - add keyboard navigation and aria-pressed
- Various buttons - standardise ARIA attributes

---

## Implementation Roadmap

### Week 1: Quick Wins
1. Extract Tailwind constants (1-2h)
2. Create useTextAppend composable (30min)
3. Extract cookie utilities (30min)
4. Add ARIA labels (30min)
5. Fix type safety (any types) (1-2h)

**Estimated: 4-6 hours**

### Week 2: Button & Form Work
1. Consolidate button components (2-3h)
2. Begin form component refactoring (2-3h)

**Estimated: 4-6 hours**

### Week 3: Complete Form & Polish
1. Complete form component refactoring (2-3h)
2. Error timeout composable (1h)
3. Performance optimisations (1h)

**Estimated: 4-5 hours**

### Week 4: Final Polish & Testing
1. Authentication modal consolidation (2-3h)
2. Comprehensive testing (2-3h)
3. Documentation updates (1h)

**Estimated: 5-7 hours**

**Total Estimated Effort: 17-24 hours**

---

## Files Modified (Estimated)

### New Files to Create
- `constants/tailwind.ts` (refactored Tailwind classes)
- `constants/messages.ts` (extracted hardcoded strings)
- `utils/cookies.ts` (extracted cookie utilities)
- `utils/debug.ts` (debug logger)
- `Composables/useTextAppend.ts`
- `Composables/useErrorTimeout.ts`
- `Components/Button.vue` (unified button component)
- `Components/SettingSection.vue` (reusable settings section)
- `Components/Transitions/FadeSlideUp.vue` (reusable transitions)
- `types/form.ts` (shared form interfaces)
- `types/echo.ts` (Echo client types)

### Files to Update
30+ component and composable files across the codebase

### Files to Deprecate
- PrimaryButton.vue
- SecondaryButton.vue
- DangerButton.vue
- ButtonMode.vue
- FormField.vue (may be consolidated)

---

## Code Quality Improvements

### Before
- 167 Vue components with significant duplication
- Inconsistent prop naming and types
- Use of `any` types in critical composables
- 31+ instances of repeated Tailwind classes
- 8+ form components with duplicated props
- 7 separate button component files
- Missing accessibility attributes

### After
- Reduced to ~150 focused components
- Consistent, type-safe prop interfaces
- Complete TypeScript coverage
- Centralised Tailwind class constants
- Form components with shared base props
- Unified button component system
- Full accessibility compliance

---

## Risk Assessment

### Low Risk Changes
- Tailwind constant extraction
- Cookie utility extraction
- useTextAppend composable
- ARIA label additions

### Medium Risk Changes
- Button component consolidation (30+ usages to update)
- useErrorTimeout consolidation
- Error handling changes

### Higher Risk Changes
- Form component refactoring (affects many components)
- Authentication modal changes (critical user flows)

**Mitigation:** Extensive test coverage, gradual rollout

---

## Related Documentation

See `REFACTORING_REPORT.md` for:
- Detailed code examples
- Line-by-line file references
- Complete improvement suggestions
- Priority justifications
- Impact assessments

---

## Questions?

Each section in REFACTORING_REPORT.md includes:
- Specific file paths and line numbers
- Current implementation code
- Suggested improvements with examples
- Priority and impact ratings
- Effort estimates

