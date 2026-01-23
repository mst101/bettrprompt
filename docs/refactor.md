# Frontend Refactoring Priorities Plan

## Executive Summary

Based on comprehensive analysis of 406 TypeScript/Vue files, the codebase demonstrates **strong engineering practices**
with modern Vue 3 patterns, excellent TypeScript coverage (8.5/10), and thoughtful component organization. However,
there are **critical performance and accessibility issues** in the most important user-facing components that should be
addressed.

**Key Findings:**

- **Critical**: 2 oversized components (1,719 and 1,135 lines) causing performance issues
- **High Impact**: ~400-500 lines of duplicated code can be eliminated via composables
- **Accessibility**: Modal component fails WCAG 2.1 Level AA standards
- **Bundle Size**: Heavy import chains without lazy loading on main page
- **Testing Gap**: 0 tests for most critical user-facing components

---

## Tier 1: Critical Issues (Fix Immediately)

### 1. Split Oversized Components ⚠️ CRITICAL

**Impact**: 🔴 MASSIVE - Performance, maintainability, developer experience

#### A. Workflow/Show.vue (1,719 lines)

**Location**: `resources/js/Pages/Workflow/Show.vue`

**Current Issues:**

- Single monolithic component handling entire workflow testing interface
- 13+ sections including modals, forms, panels, comparison views
- Heavy JSON parsing and transformation without memoization (lines 624-711)
- Slows down Vue's reactivity system

**Refactoring Plan:**

```
Workflow/Show.vue (300 lines) - orchestration only
├── WorkflowTestingForm.vue (form inputs)
├── WorkflowResultsPanel.vue (results display)
├── WorkflowComparisonModal.vue (compare old/new)
├── WorkflowPromptPreview.vue (prompt display)
└── composables/
    ├── useWorkflowTesting.ts (test execution logic)
    └── useWorkflowResults.ts (results parsing & formatting)
```

**Success Metrics:**

- Component under 500 lines
- Improve page load time by 40-60%
- Enable parallel development on sub-features

**Effort**: 2-3 days | **Priority**: CRITICAL

---

#### B. ClarifyingQuestions.vue (1,135 lines)

**Location**: `resources/js/Components/Features/PromptBuilder/ClarifyingQuestions/ClarifyingQuestions.vue`

**Current Issues:**

- Handles 3 view modes (answering/reviewing/editing/generating)
- Question navigation (one-at-a-time mode)
- Answer persistence and validation
- Question ratings with explanations
- Analytics tracking (8+ events)
- Display mode toggle

**Refactoring Plan:**

```
ClarifyingQuestions.vue (300 lines) - orchestration
├── BulkQuestions.vue (already exists)
├── QuestionRatingSection.vue (NEW - rating UI)
├── QuestionViewModeToggle.vue (NEW - display toggle)
└── composables/
    ├── useQuestionAnswering.ts (NEW - answer CRUD logic)
    └── useQuestionAnalytics.ts (NEW - tracking logic)
```

**Duplicate Logic to Extract:**

- Rating UI rendering (shared with BulkQuestions.vue)
- Thank you message auto-hide logic
- Rating save handlers

**Success Metrics:**

- Main component under 400 lines
- Shared rating composable used by 2+ components
- 30% reduction in code duplication

**Effort**: 2 days | **Priority**: HIGH

---

### 2. Implement Lazy Loading for Main Page ⚠️ HIGH IMPACT

**Impact**: 🔴 CRITICAL - 40-60% bundle size reduction for most-visited page

**Location**: `resources/js/Pages/PromptBuilder/Show.vue`

**Current Issue:**

- 30+ static imports load entire component tree upfront
- Components like `AlternativeFrameworks`, `PersonalityAdjustments`, `Recommendations` only shown conditionally
- Heavy initial bundle on most-visited page

**Before (lines 9-25):**

```typescript
import ApiUsage from '@/Components/Features/PromptBuilder/ApiUsage/ApiUsage.vue';
import ClarifyingQuestions from '@/Components/Features/PromptBuilder/ClarifyingQuestions/ClarifyingQuestions.vue';
import Recommendations from '@/Components/Features/PromptBuilder/Recommendations/Recommendations.vue';
// ... 15 more imports
```

**After - Lazy Load Conditional Components:**

```typescript
// Eager - always visible
import YourTask from '@/Components/Features/PromptBuilder/YourTask/YourTask.vue';
import Progress from '@/Components/Features/PromptBuilder/Progress/Progress.vue';

// Lazy - conditionally rendered
const ApiUsage = defineAsyncComponent(() =>
    import('@/Components/Features/PromptBuilder/ApiUsage/ApiUsage.vue')
);
const Recommendations = defineAsyncComponent(() =>
    import('@/Components/Features/PromptBuilder/Recommendations/Recommendations.vue')
);
const AlternativeFrameworks = defineAsyncComponent(() =>
    import('@/Components/Features/PromptBuilder/Framework/AlternativeFrameworks.vue')
);
const PersonalityAdjustments = defineAsyncComponent(() =>
    import('@/Components/Features/PromptBuilder/Personality/PersonalityAdjustments.vue')
);
```

**Components to Lazy Load (8 candidates):**

1. `ApiUsage` - Only shown in "Api Usage" tab
2. `Recommendations` - Only shown after prompt optimization
3. `AlternativeFrameworks` - Only shown if framework selected
4. `PersonalityAdjustments` - Only shown if personality tier enabled
5. `PreAnalysisQuestions` - Only shown in pre-analysis stage
6. `OptimisedPrompt` - Only shown after completion
7. `RelatedPromptRuns` - Only shown if related runs exist
8. `AnswersSummary` - Only shown after answers provided

**Success Metrics:**

- Initial bundle size reduced by 40-60%
- Faster Time to Interactive (TTI)
- Lighthouse performance score improvement

**Effort**: 4-6 hours | **Priority**: HIGH

---

### 3. Fix Modal Accessibility ⚠️ WCAG VIOLATION

**Impact**: 🔴 CRITICAL - Currently fails WCAG 2.1 Level AA

**Location**: `resources/js/Components/Base/Modal/Modal.vue`

**Current Issues:**

- ❌ No focus trap - users can tab outside modal
- ❌ No focus restoration when modal closes
- ❌ Missing `aria-modal="true"`
- ❌ No escape key handler (exists in parent but should be in component)

**Required Changes:**

```vue

<script setup lang="ts">
    import { onMounted, onUnmounted, ref, watch } from 'vue';

    const props = defineProps<{
        show: boolean;
        maxWidth?: string;
    }>();

    const emit = defineEmits<{
        (e: 'close'): void;
    }>();

    const dialog = ref<HTMLDialogElement>();
    const previouslyFocusedElement = ref<HTMLElement | null>(null);

    // Store focus before opening
    watch(() => props.show, (isOpen) => {
        if (isOpen) {
            previouslyFocusedElement.value = document.activeElement as HTMLElement;
            nextTick(() => {
                // Focus first focusable element in modal
                const firstFocusable = dialog.value?.querySelector(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                ) as HTMLElement;
                firstFocusable?.focus();
            });
        } else {
            // Restore focus on close
            previouslyFocusedElement.value?.focus();
        }
    });

    // Trap focus within modal
    const handleKeyDown = (event: KeyboardEvent) => {
        if (event.key === 'Tab') {
            const focusableElements = dialog.value?.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            if (!focusableElements || focusableElements.length === 0) return;

            const firstElement = focusableElements[0] as HTMLElement;
            const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement;

            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            } else if (!event.shiftKey && document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        }
    };
</script>

<template>
    <dialog
        ref="dialog"
        :open="show"
        role="dialog"
        aria-modal="true"
        @keydown="handleKeyDown"
        data-testid="modal-dialog"
    >
        <!-- Modal content -->
    </dialog>
</template>
```

**Testing Checklist:**

- [ ] Tab key cycles within modal
- [ ] Shift+Tab works in reverse
- [ ] Escape closes modal
- [ ] Focus returns to trigger element on close
- [ ] Screen reader announces modal properly

**Effort**: 3-4 hours | **Priority**: HIGH (legal/compliance)

---

### 4. Remove Console Statements from Production ⚠️

**Impact**: 🟡 MEDIUM - Performance, security, professionalism

**Locations**: 67 console statements across 33 files

**Critical Examples:**

- `PromptBuilder/Show.vue` lines 320-402: WebSocket debug logs
- `useRealtimeUpdates.ts` lines 74-155: Polling debug logs
- `ClarifyingQuestions.vue`: Multiple debug statements

**Solution**: Create logging utility with environment-aware output

```typescript
// Utils/logger.ts (NEW)
const isDevelopment = import.meta.env.DEV;

export const logger = {
    debug: (...args: unknown[]) => {
        if (isDevelopment) console.log('[DEBUG]', ...args);
    },
    info: (...args: unknown[]) => {
        if (isDevelopment) console.info('[INFO]', ...args);
    },
    warn: (...args: unknown[]) => {
        console.warn('[WARN]', ...args);
    },
    error: (...args: unknown[]) => {
        console.error('[ERROR]', ...args);
    },
};

// Replace all console.log with:
import { logger } from '@/Utils/logger';

logger.debug('WebSocket connection established');
```

**Success Metrics:**

- 0 direct console.* calls in production build
- Standardized logging interface
- Better debugging experience

**Effort**: 2-3 hours | **Priority**: MEDIUM

---

## Tier 2: High-Impact Improvements (This Sprint)

### 5. Extract Form Composables (Eliminate ~200 Lines of Duplication)

**Impact**: 🟢 HIGH - Code reuse, consistency, maintainability

#### A. useFormWithNotifications

**Duplicate Pattern Found:** 10 Profile form components repeat identical code

**Files Affected:**

- `UpdateProfileInformationForm.vue`
- `UpdateToolsForm.vue`
- `UpdateUiComplexityForm.vue`
- `UpdatePasswordForm.vue`
- `UpdatePersonalityTypeForm.vue`
- `UpdateWorkCategoryForm.vue`
- `UpdateWorkDomainForm.vue`
- `UpdateRoleForm.vue`
- `UpdateTeamInfoForm.vue`
- `UpdateLocationForm.vue`

**Duplicated Code (20 lines per file = 200 total):**

```typescript
const { success, error } = useNotification();
const form = useForm({ /* fields */ });

watch(() => form.recentlySuccessful, (value) => {
    if (value) {
        success(t('profile.xxx.notifications.updated'));
    }
});

watch(() => Object.keys(form.errors).length > 0, (hasErrors) => {
    if (hasErrors) {
        const errorMessage = Object.values(form.errors)[0];
        if (typeof errorMessage === 'string') {
            error(errorMessage);
        }
    }
});
```

**Create Composable:**

```typescript
// Composables/data/useFormWithNotifications.ts (NEW)
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useNotification } from '@/Composables/ui/useNotification';

export function useFormWithNotifications<T extends Record<string, unknown>>(
    initialData: T,
    messages?: {
        success?: string;
        error?: string;
    }
) {
    const { success, error } = useNotification();
    const form = useForm(initialData);

    watch(() => form.recentlySuccessful, (value) => {
        if (value && messages?.success) {
            success(messages.success);
        }
    });

    watch(() => Object.keys(form.errors).length > 0, (hasErrors) => {
        if (hasErrors) {
            const errorMessage = Object.values(form.errors)[0];
            if (typeof errorMessage === 'string') {
                error(messages?.error || errorMessage);
            }
        }
    });

    return form;
}
```

**Usage:**

```typescript
// Before (20 lines)
const { success, error } = useNotification();
const form = useForm({ name: '', email: '' });
watch(...);
watch(...);

// After (3 lines)
const form = useFormWithNotifications(
    { name: '', email: '' },
    { success: t('profile.profileInfo.notifications.updated') }
);
```

**Success Metrics:**

- Eliminate 200 lines of duplication
- Consistent error/success handling across all forms

**Effort**: 3-4 hours | **Priority**: HIGH

---

#### B. useTableSorting

**Duplicate Pattern Found:** Admin index pages repeat sorting logic

**Files Affected:**

- `Admin/Visitors/Index.vue`
- `Admin/Users/Index.vue`
- `PromptBuilder/History.vue`
- Other admin list pages

**Create Composable:**

```typescript
// Composables/data/useTableSorting.ts (NEW)
import { router } from '@inertiajs/vue3';

export function useTableSorting(
    currentSortBy: string,
    currentDirection: string,
    additionalParams: Record<string, unknown> = {}
) {
    const sortBy = (column: string) => {
        let newDirection = 'asc';
        if (currentSortBy === column && currentDirection === 'asc') {
            newDirection = 'desc';
        }

        router.get(
            window.location.pathname,
            {
                sort_by: column,
                sort_direction: newDirection,
                ...additionalParams,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    return { sortBy };
}
```

**Effort**: 2 hours | **Priority**: MEDIUM

---

### 6. Consolidate Date Formatting (Eliminate ~200 Lines)

**Impact**: 🟢 HIGH - Code consistency, maintainability

**Current State:**

- 17 files define inline `formatDate` functions
- Existing utility (`Utils/formatting/formatters.ts`) only used by 3 files
- Slight variations in formatting across files

**Files with Inline Formatters:**

- `Visitors/Index.vue`, `Visitors/Show.vue`
- `Settings/Privacy.vue`, `Settings/Subscription.vue`
- `Admin/Alerts.vue`
- 12 more files

**Enhanced Utility:**

```typescript
// Utils/formatting/formatters.ts (ENHANCE EXISTING)

// Add preset formatters
export function formatDateShort(dateString: string | Date): string {
    return formatDate(dateString, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

export function formatDateLong(dateString: string | Date): string {
    return formatDate(dateString, {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

export function formatDateTime(dateString: string | Date): string {
    return new Date(dateString).toLocaleString('en-GB');
}

export function formatDuration(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs}s`;
}
```

**Migration Plan:**

1. Add presets to existing utility (1 hour)
2. Replace inline formatters in 17 files (3 hours)
3. Update imports (1 hour)

**Success Metrics:**

- 0 inline date formatters
- Consistent date formatting across app

**Effort**: 4-5 hours | **Priority**: HIGH

---

### 7. Optimize Computed Properties in Show.vue

**Impact**: 🟢 MEDIUM - Performance, readability

**Location**: `resources/js/Pages/PromptBuilder/Show.vue`

**Current Issue:**

- `tabs` computed (lines 102-178) recalculates entire 80-line array on every dependency change
- 13+ computed properties, some interdependent

**Before:**

```typescript
const tabs = computed<Tab[]>(() => {
    const allTabs: Tab[] = [];
    // 80 lines of complex conditional logic mixing concerns
    if (props.promptRun.selectedFramework) { /* ... */
    }
    if (props.promptRun.personalityTier && props.uiComplexity === 'advanced') { /* ... */
    }
    // etc.
    return allTabs;
});
```

**After - Split into Focused Computeds:**

```typescript
const hasFramework = computed(() => !!props.promptRun.selectedFramework);
const hasPersonality = computed(() =>
    props.promptRun.personalityTier &&
    props.promptRun.personalityTier !== 'none' &&
    props.uiComplexity === 'advanced'
);
const showApiUsage = computed(() =>
    props.promptRun.uiComplexity === 'advanced' &&
    props.promptRun.frameworkData?.api_usage
);

const baseTab = computed<Tab>(() => ({
    id: 'your-task',
    label: t('promptBuilder.tabs.yourTask'),
    count: null,
}));

const frameworkTab = computed<Tab>(() => ({
    id: 'framework',
    label: t('promptBuilder.tabs.framework'),
    count: null,
}));

const tabs = computed<Tab[]>(() => {
    return [
        baseTab.value,
        ...(hasFramework.value ? [frameworkTab.value] : []),
        ...(hasPersonality.value ? [personalityTab.value] : []),
        ...(showApiUsage.value ? [apiTab.value] : []),
    ];
});
```

**Benefits:**

- Vue can cache individual conditions separately
- Easier to test and reason about
- Only recomputes what changed

**Effort**: 4 hours | **Priority**: MEDIUM

---

## Tier 3: Quality of Life (Next Sprint)

### 8. Consolidate Workflow Stage Composables

**Impact**: 🟡 MEDIUM - Code consistency

**Current Issue:** Two similar composables handling workflow stage colors

**Files:**

- `Composables/features/useWorkflowStageColor.ts` (28 lines)
- `Composables/ui/useStatusBadge.ts` (76 lines)

**Solution:**

- Keep `useStatusBadge` as primary implementation (more comprehensive)
- Deprecate `useWorkflowStageColor`
- Update 3 components using old composable

**Effort**: 2 hours | **Priority**: LOW

---

### 9. Fix usePersonalityPromptPreference to Use useLocalStorage

**Impact**: 🟡 LOW - Code consistency

**Current Issue:**

- `usePersonalityPromptPreference` (41 lines) re-implements localStorage logic
- `useLocalStorage` generic composable exists but isn't used

**Before (41 lines):**

```typescript
export const usePersonalityPromptPreference = () => {
    const getInitialValue = () => {
        if (typeof window === 'undefined') return false;
        const stored = localStorage.getItem(STORAGE_KEY);
        return stored === 'true';
    };
    const isDismissed = ref<boolean>(getInitialValue());
    // ... 30 more lines
};
```

**After (8 lines):**

```typescript
export const usePersonalityPromptPreference = () => {
    const isDismissed = useLocalStorage('personality-prompt-dismissed', false);
    const showPrompt = computed(() => !isDismissed.value);
    return {
        isDismissed: computed(() => isDismissed.value),
        showPrompt,
        dismissPrompt: () => {
            isDismissed.value = true;
        },
        resetPreference: () => {
            isDismissed.value = false;
        },
    };
};
```

**Effort**: 1 hour | **Priority**: LOW

---

### 10. Make Status Labels Translatable in Composables

**Impact**: 🟡 LOW - i18n completeness

**Current Issue:**

- `useStatusBadge.ts` returns hardcoded English strings
- Not translatable via i18n

**Lines with Hardcoded Strings:**

```typescript
// lines 20, 28, 44, 51, 61
return { label: 'Unknown', colorClass: '...' };
return { label: 'Completed', colorClass: '...' };
return { label: 'Awaiting Questions', colorClass: '...' };
```

**Solution - Return Translation Keys:**

```typescript
// Before
return {
    label: 'Unknown',
    colorClass: 'bg-indigo-100 text-indigo-900',
};

// After
return {
    labelKey: 'workflow.status.unknown',
    colorClass: 'bg-indigo-100 text-indigo-900',
};

// Component usage
const { labelKey, colorClass } = useStatusBadge(status);
const label = t(labelKey);
```

**Files Affected:** 5 components using `useStatusBadge`

**Effort**: 2 hours | **Priority**: LOW

---

## Tier 4: Testing & Accessibility (Continuous)

### 11. Add Component Tests for Critical UI

**Impact**: 🔴 CRITICAL - Confidence for refactoring

**Current State:**

- ✅ 37 frontend tests (excellent composable/utility coverage)
- ❌ 0 tests for most critical user-facing components
- Example: `useRealtimeUpdates.test.ts` (787 lines) - EXCELLENT comprehensive testing
- Team clearly knows how to write good tests!

**Priority Test Files to Create:**

#### A. PromptBuilder/Show.test.ts

**Location**: `tests-frontend/component/PromptBuilderShow.test.ts` (NEW)

**Test Coverage:**

```typescript
describe('PromptBuilder/Show.vue', () => {
    it('renders tabs based on workflow stage', () => { /* ... */
    });
    it('shows pre-analysis questions in stage 0', () => { /* ... */
    });
    it('shows clarifying questions in stage 1', () => { /* ... */
    });
    it('shows optimized prompt in stage 2_completed', () => { /* ... */
    });
    it('handles tab navigation', () => { /* ... */
    });
    it('lazy loads conditional components', () => { /* ... */
    });
    it('updates active tab on prop change', () => { /* ... */
    });
});
```

**Effort**: 1 day | **Priority**: HIGH

---

#### B. WorkflowCard.test.ts

**Location**: `tests-frontend/component/WorkflowCard.test.ts` (NEW)

**Test Coverage:**

```typescript
describe('WorkflowCard.vue', () => {
    it('renders workflow information correctly', () => { /* ... */
    });
    it('displays correct stage badge', () => { /* ... */
    });
    it('navigates to workflow on click', () => { /* ... */
    });
    it('shows loading state', () => { /* ... */
    });
});
```

**Effort**: 3 hours | **Priority**: MEDIUM

---

#### C. E2E: Prompt Creation Flow

**Location**: `tests-frontend/e2e/prompt-creation-flow.spec.ts` (NEW)

**Test Coverage:**

```typescript
test('complete prompt creation flow', async ({ page }) => {
    await page.goto('/gb/prompt-builder');

    // Fill task description
    await page.fill('[data-testid="task-input"]', 'Test task');
    await page.click('[data-testid="submit-task"]');

    // Pre-analysis questions
    await expect(page.locator('[data-testid="pre-analysis"]')).toBeVisible();

    // Answer clarifying questions
    await page.fill('[data-testid="question-answer-1"]', 'Test answer');
    await page.click('[data-testid="save-answer"]');

    // Wait for optimization
    await expect(page.locator('[data-testid="optimized-prompt"]')).toBeVisible({ timeout: 30000 });

    // Verify prompt displayed
    await expect(page.locator('[data-testid="prompt-content"]')).toContainText('Test task');
});
```

**Effort**: 1 day | **Priority**: HIGH

---

### 12. Add ARIA Attributes to Components

#### A. Dropdown Component

**Location**: `Components/Base/Dropdown.vue`

**Missing ARIA:**

```vue
<!-- Before -->
<button @click="toggle">
    <slot name="trigger" />
</button>

<!-- After -->
<button
    @click="toggle"
    role="button"
    :aria-expanded="open"
    aria-haspopup="true"
    :aria-controls="dropdownId"
>
    <slot name="trigger" />
</button>

<div
    v-show="open"
    :id="dropdownId"
    role="menu"
>
    <slot name="content" />
</div>
```

**Effort**: 1 hour | **Priority**: MEDIUM

---

#### B. Tabs Component - Arrow Key Navigation

**Location**: `Components/Base/Tabs.vue`

**Add Keyboard Support:**

```typescript
const handleKeyDown = (event: KeyboardEvent) => {
    if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;

    event.preventDefault();
    const currentIndex = props.tabs.findIndex(t => t.id === activeTab.value);

    switch (event.key) {
        case 'ArrowLeft':
            selectPreviousTab(currentIndex);
            break;
        case 'ArrowRight':
            selectNextTab(currentIndex);
            break;
        case 'Home':
            selectTab(props.tabs[0].id);
            break;
        case 'End':
            selectTab(props.tabs[props.tabs.length - 1].id);
            break;
    }
};
```

**Effort**: 2 hours | **Priority**: MEDIUM

---

## Tier 5: Performance Optimizations (Future)

### 13. Optimize Polling Fallback

**Impact**: 🟡 MEDIUM - Better UX for users without WebSocket

**Location**: `Composables/data/useRealtimeUpdates.ts`

**Current Issue:**

- Polls every 1-5 seconds when WebSockets fail
- Calls `router.reload()` causing full page reloads

**Optimization:**

```typescript
// Use partial reload instead of full page
router.reload({
    only: ['promptRun'],
    preserveScroll: true,
    preserveState: true,
});

// Increase polling interval for non-critical updates
const pollingInterval = isProcessingStage ? 2000 : 5000;
```

**Effort**: 3 hours | **Priority**: LOW

---

### 14. Reduce Barrel Export Usage

**Impact**: 🟡 LOW - Smaller bundles, faster builds

**Current Issue:**

- `@/Types` re-exports everything from 30+ type files
- Components import entire barrel even if only using 1 type
- Can cause bundling issues and increase bundle size

**Solution - Use Specific Imports:**

```typescript
// Before
import type { PromptRunResource, User, Pagination } from '@/Types';

// After
import type { PromptRunResource } from '@/Types/resources/PromptRunResource';
import type { User } from '@/Types/models/User';
import type { Pagination } from '@/Types/shared/pagination';
```

**Effort**: 1 week (touch 100+ files) | **Priority**: LOW

---

## Implementation Roadmap

### Week 1: Critical Issues

- [ ] Day 1-3: Split Workflow/Show.vue (Tier 1, Issue #1)
- [ ] Day 4: Implement lazy loading in PromptBuilder/Show.vue (Tier 1, Issue #2)
- [ ] Day 5: Fix Modal accessibility + remove console statements (Tier 1, Issues #3-4)

### Week 2: High-Impact Improvements

- [ ] Day 1: Create useFormWithNotifications composable (Tier 2, Issue #5A)
- [ ] Day 2: Split ClarifyingQuestions.vue (Tier 1, Issue #1B)
- [ ] Day 3-4: Consolidate date formatting (Tier 2, Issue #6)
- [ ] Day 5: Optimize computed properties in Show.vue (Tier 2, Issue #7)

### Week 3: Testing & Quality

- [ ] Day 1-2: Add PromptBuilder/Show.test.ts (Tier 4, Issue #11A)
- [ ] Day 3: Add E2E prompt creation test (Tier 4, Issue #11C)
- [ ] Day 4-5: Add ARIA attributes to Dropdown/Tabs (Tier 4, Issue #12)

### Week 4: Cleanup & Documentation

- [ ] Day 1: Create useTableSorting composable (Tier 2, Issue #5B)
- [ ] Day 2: Consolidate workflow stage composables (Tier 3, Issue #8)
- [ ] Day 3: Fix localStorage composable usage (Tier 3, Issue #9)
- [ ] Day 4-5: Documentation & knowledge sharing

---

## Success Metrics

### Performance

- [ ] Initial bundle size reduced by 40-60% for main page
- [ ] Workflow/Show.vue page load time improved by 50%+
- [ ] Lighthouse performance score > 90

### Code Quality

- [ ] Eliminate 400-500 lines of duplicated code
- [ ] All components under 500 lines
- [ ] 0 console statements in production build

### Accessibility

- [ ] Pass WCAG 2.1 Level AA compliance
- [ ] All interactive components keyboard-accessible
- [ ] Screen reader testing passes

### Testing

- [ ] 80%+ coverage for critical user-facing components
- [ ] E2E tests for 3 core user flows
- [ ] 0 regressions in existing functionality

### Developer Experience

- [ ] New developers can understand component structure in <10 minutes
- [ ] Consistent patterns across similar features
- [ ] Clear documentation for composables

---

## Risk Mitigation

### High-Risk Changes

1. **Splitting large components**: Risk of breaking existing functionality
    - **Mitigation**: Write tests BEFORE splitting
    - **Rollback plan**: Feature flag new components, keep old as fallback

2. **Lazy loading**: Risk of loading states causing UI jank
    - **Mitigation**: Add loading skeletons for async components
    - **Testing**: Test on slow 3G connection

3. **Modal accessibility changes**: Risk of breaking existing modal usage
    - **Mitigation**: Keep existing API, enhance internally
    - **Testing**: Manual keyboard/screen reader testing

### Medium-Risk Changes

4. **Form composable refactoring**: Slight API changes
    - **Mitigation**: Backwards-compatible, gradual rollout

5. **Date formatting consolidation**: Potential formatting inconsistencies
    - **Mitigation**: Unit tests for all date formats

---

## Files to Modify

### Critical (Week 1)

- `resources/js/Pages/Workflow/Show.vue` (split)
- `resources/js/Pages/PromptBuilder/Show.vue` (lazy loading)
- `resources/js/Components/Base/Modal/Modal.vue` (accessibility)
- 33 files with console statements

### High-Impact (Week 2)

- `resources/js/Components/Features/PromptBuilder/ClarifyingQuestions/ClarifyingQuestions.vue` (split)
- `resources/js/Composables/data/useFormWithNotifications.ts` (new)
- `resources/js/Utils/formatting/formatters.ts` (enhance)
- 10 Profile form components
- 17 files with inline date formatters

### New Files to Create

- `resources/js/Composables/data/useFormWithNotifications.ts`
- `resources/js/Composables/data/useTableSorting.ts`
- `resources/js/Utils/logger.ts`
- `tests-frontend/component/PromptBuilderShow.test.ts`
- `tests-frontend/component/WorkflowCard.test.ts`
- `tests-frontend/e2e/prompt-creation-flow.spec.ts`

---

## Conclusion

The codebase is **fundamentally well-structured** with strong TypeScript usage, modern Vue 3 patterns, and excellent
utility/composable organization. The refactoring priorities focus on:

1. **Performance**: Splitting oversized components and lazy loading (biggest wins)
2. **Accessibility**: WCAG compliance for legal/ethical requirements
3. **Maintainability**: Eliminating ~500 lines of duplication
4. **Testing**: Confidence for safe refactoring

**Recommended approach**: Start with Tier 1 critical issues (Week 1) to deliver immediate, measurable improvements, then
tackle high-impact code quality improvements (Week 2).
