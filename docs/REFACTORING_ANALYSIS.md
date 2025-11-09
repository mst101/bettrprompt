# Vue/TypeScript Codebase Analysis: Refactoring Opportunities

## Executive Summary

This analysis identifies significant opportunities to improve code reusability and maintainability through component
extraction and pattern consolidation. The codebase has good foundational components but shows clear duplication patterns
in forms, status displays, and layout patterns that could yield immediate benefits.

---

## 1. COMPONENT STRUCTURE ANALYSIS

### Existing Components (13 total)

Located in `/home/mark/repos/personality/resources/js/Components/`

**Core Form Components:**

- `TextInput.vue` - Text/email/password input wrapper
- `InputLabel.vue` - Form label wrapper
- `InputError.vue` - Error message display
- `Checkbox.vue` - Checkbox wrapper with v-model

**Button Components:**

- `PrimaryButton.vue` - Primary action button (dark gray)
- `SecondaryButton.vue` - Secondary action button (light gray)
- `DangerButton.vue` - Danger/destructive action button (red)

**Layout & Navigation Components:**

- `ApplicationLogo.vue` - Logo component
- `NavLink.vue` - Navigation link
- `ResponsiveNavLink.vue` - Responsive mobile navigation link
- `Dropdown.vue` - Dropdown menu wrapper
- `DropdownLink.vue` - Dropdown menu item
- `Modal.vue` - Modal dialog component

### Existing Pages (12 total)

**Authentication Pages** (6 pages):

- `Pages/Auth/Login.vue`
- `Pages/Auth/Register.vue`
- `Pages/Auth/ForgotPassword.vue`
- `Pages/Auth/ResetPassword.vue`
- `Pages/Auth/ConfirmPassword.vue`
- `Pages/Auth/VerifyEmail.vue`

**Profile Pages** (4 pages):

- `Pages/Profile/Edit.vue`
- `Pages/Profile/Partials/UpdateProfileInformationForm.vue`
- `Pages/Profile/Partials/UpdatePasswordForm.vue`
- `Pages/Profile/Partials/DeleteUserForm.vue`

**PromptOptimizer Pages** (3 pages):

- `Pages/PromptOptimizer/Index.vue` - Create new prompt
- `Pages/PromptOptimizer/Show.vue` - View/interact with prompt
- `Pages/PromptOptimizer/History.vue` - List all prompts

**Other Pages** (2 pages):

- `Pages/Home.vue` - Landing page
- `Pages/Dashboard.vue` - Post-login dashboard

---

## 2. CODE DUPLICATION & PATTERNS

### CRITICAL: Form Field Pattern (HIGH IMPACT)

This pattern appears **20+ times** across the codebase:

```vue

<div>
    <InputLabel for="fieldName" value="Field Label" />
    <TextInput
        id="fieldName"
        type="text"
        class="mt-1 block w-full"
        v-model="form.fieldName"
        required
        autofocus
        autocomplete="name"
    />
    <InputError class="mt-2" :message="form.errors.fieldName" />
</div>
```

**Files with duplication:**

- `Pages/Auth/Login.vue` (lines 39-53, 55-68) - 2 instances
- `Pages/Auth/Register.vue` (lines 30-44, 46-59, 61-75, 76-95) - 4 instances
- `Pages/Auth/ForgotPassword.vue` (lines 37-51) - 1 instance
- `Pages/Auth/ResetPassword.vue` (lines 35-49, 51-64, 66-85) - 3 instances
- `Pages/Auth/ConfirmPassword.vue` (lines 32-44) - 1 instance
- `Pages/Profile/Partials/UpdateProfileInformationForm.vue` (lines 37-51, 53-66) - 2 instances
- `Pages/Profile/Partials/UpdatePasswordForm.vue` (lines 50-66, 68-81, 83-101) - 3 instances
- `Pages/Profile/Partials/DeleteUserForm.vue` (lines 70-87) - 1 instance

**Estimated Impact:** 17 locations

### CRITICAL: Status Badge Pattern (HIGH IMPACT)

**Pattern 1 - Inline Status Styles:**
Appears in `Pages/PromptOptimizer/Show.vue` (lines 101-112) and `Pages/PromptOptimizer/History.vue` (lines 27-38):

```typescript
const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'processing':
            return 'bg-yellow-100 text-yellow-800';
        case 'failed':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};
```

**Template Usage:**

```vue
<span
    :class="getStatusBadgeClass(promptRun.status)"
    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase"
>
    {{ promptRun.status }}
</span>
```

**Estimated Impact:** 2+ locations (exact appearances: 4 in Show.vue, 1 in History.vue)

### MAJOR: Loading State / Spinner Pattern

Identical spinner SVGs appear in `Pages/PromptOptimizer/Show.vue`:

- Lines 350-369 - Submit answer spinner
- Lines 397-415 - Generating prompt spinner
- Lines 647-665 - Processing state spinner

**Pattern:**

```vue

<svg
    class="mr-3 h-5 w-5 animate-spin text-indigo-600"
    fill="none"
    viewBox="0 0 24 24"
>
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    <path class="opacity-75" fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
</svg>
```

**Estimated Impact:** 3 instances in Show.vue, potential for more pages

### MAJOR: Card/Section Wrapper Pattern (HIGH IMPACT)

**Pattern 1 - White card containers:**
Appears in multiple locations:

`Pages/Profile/Edit.vue` (lines 26-32, 34-36, 38-40):

```vue

<div class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8">
    <!-- Content -->
</div>
```

`Pages/PromptOptimizer/Show.vue` (lines 178-233, 236-267, 270-388, etc.):

```vue

<div class="mb-6 overflow-hidden bg-white shadow-xs sm:rounded-lg">
    <div class="p-6">
        <!-- Content -->
    </div>
</div>
```

**Variations:**

- With/without margin below (`mb-6`)
- With/without overflow hidden
- Different padding (p-4, p-6, p-8)
- Different border radius

**Estimated Impact:** 10+ locations

### MAJOR: Action Button Row Pattern

Appears in multiple Profile forms:

`Pages/Profile/Partials/UpdateProfileInformationForm.vue` (lines 89-105):

```vue

<div class="flex items-center gap-4">
    <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
    <Transition enter-active-class="..." leave-active-class="...">
        <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">
            Saved.
        </p>
    </Transition>
</div>
```

**Same pattern in:**

- `Pages/Profile/Partials/UpdatePasswordForm.vue` (lines 103-119)

**Estimated Impact:** 2+ locations (future: any form with save feedback)

### MAJOR: Section Header Pattern

Appears in Profile forms:

`Pages/Profile/Partials/UpdateProfileInformationForm.vue` (lines 22-31):

```vue

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Profile Information
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Update your account's profile information and email address.
        </p>
    </header>
    <!-- Form -->
</section>
```

**Same pattern in:**

- `Pages/Profile/Partials/UpdatePasswordForm.vue` (lines 39-47)
- `Pages/Profile/Partials/DeleteUserForm.vue` (lines 44-53)

**Estimated Impact:** 3 locations (future expandable)

### MODERATE: Collapsible/Accordion Pattern

`Pages/PromptOptimizer/Show.vue` (lines 23-50, 456-535):

**Expandable questions list:**

- State management with `expandedQuestions` ref (Set)
- Toggle function
- Toggle all function
- Conditional rendering with `v-show`

**Potential for:**

- Generic collapsible component
- Could be used for FAQ, settings sections, etc.

**Estimated Impact:** 1 major location, potential for reuse

### MODERATE: Empty State Pattern

`Pages/PromptOptimizer/History.vue` (lines 78-88):

```vue

<div v-if="promptRuns.data.length === 0" class="text-centre p-6 text-gray-500">
    <p>No prompt history yet.</p>
    <a :href="route('prompt-optimizer.index')" class="mt-2 text-indigo-600 hover:text-indigo-800">
        Create your first optimised prompt
    </a>
</div>
```

**Pattern characteristics:**

- Empty state message
- Call-to-action link
- Gray text styling

**Estimated Impact:** 1 location (common pattern for future tables/lists)

### MODERATE: Form Section Header Pattern (in Show.vue)

`Pages/PromptOptimizer/Show.vue` (lines 183-209, 244-265, 309-316, 444-452):

Multiple variations of form section headers with different layouts and content.

---

## 3. SPECIFIC REFACTORING OPPORTUNITIES

### Priority 1: HIGH IMPACT (Affects 15+ locations)

#### 1.1 FormField Component

**Current:** Duplicated 17+ times across auth and profile pages

**Proposed Component Name:** `FormField.vue`

**Props:**

```typescript
interface Props {
    id: string;
    label?: string;
    type?: string;
    value: any;
    error?: string;
    required?: boolean;
    autofocus?: boolean;
    autocomplete?: string;
    placeholder?: string;
    rows?: number;
    min?: number;
    max?: number;
    step?: string;
}
```

**Usage example:**

```vue

<FormField
    id="email"
    label="Email"
    type="email"
    v-model="form.email"
    :error="form.errors.email"
    required
    autofocus
/>
```

**Benefits:**

- Eliminates 17 duplicate patterns
- Consistent spacing/styling
- Single source of truth for form field layout
- Easier to add features (hints, icons, validation states)

**Files to refactor:**

- `Pages/Auth/Login.vue`
- `Pages/Auth/Register.vue`
- `Pages/Auth/ForgotPassword.vue`
- `Pages/Auth/ResetPassword.vue`
- `Pages/Auth/ConfirmPassword.vue`
- `Pages/Profile/Partials/UpdateProfileInformationForm.vue`
- `Pages/Profile/Partials/UpdatePasswordForm.vue`
- `Pages/Profile/Partials/DeleteUserForm.vue`

---

#### 1.2 StatusBadge Component

**Current:** Duplicated logic and styling in 2 files

**Proposed Component Name:** `StatusBadge.vue`

**Props:**

```typescript
interface Props {
    status: string;
    variant?: 'compact' | 'full';
}
```

**Template:**

```vue
<span
    :class="statusClasses"
    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase"
>
    {{ variant === 'full' ? statusLabel : status }}
</span>
```

**Composable helper:**

```typescript
// composables/useStatusBadge.ts
export function useStatusBadge() {
    const getStatusClass = (status: string) => {
        const classMap = {
            'completed': 'bg-green-100 text-green-800',
            'processing': 'bg-yellow-100 text-yellow-800',
            'failed': 'bg-red-100 text-red-800',
            'default': 'bg-gray-100 text-gray-800',
        };
        return classMap[status] || classMap.default;
    };

    const getStatusLabel = (status: string) => {
        const labelMap = {
            'completed': 'Completed',
            'processing': 'Processing',
            'failed': 'Failed',
            'default': status,
        };
        return labelMap[status] || status;
    };

    return { getStatusClass, getStatusLabel };
}
```

**Benefits:**

- Centralised status logic
- Consistent badge styling across application
- Easy to add new statuses
- Reusable across different pages

**Files to refactor:**

- `Pages/PromptOptimizer/Show.vue` (4 uses)
- `Pages/PromptOptimizer/History.vue` (1 use)

---

#### 1.3 Card Component

**Current:** Duplicated container styling in 10+ locations

**Proposed Component Name:** `Card.vue`

**Props:**

```typescript
interface Props {
    class?: string;
    padding?: 'sm' | 'md' | 'lg';
    shadow?: 'sm' | 'md' | 'lg';
    rounded?: 'md' | 'lg';
    overflow?: boolean;
    marginBottom?: boolean;
}
```

**Template:**

```vue

<div :class="cardClasses">
    <div :class="contentClasses">
        <slot />
    </div>
</div>
```

**Common variations:**

```vue
<!-- Simple card with padding -->
<Card>
    <p>Content here</p>
</Card>

<!-- Card with margin -->
<Card margin-bottom>
    <h3>Title</h3>
    <p>Content</p>
</Card>

<!-- Card with custom padding -->
<Card padding="lg" overflow>
    <table>...</table>
</Card>
```

**Benefits:**

- Eliminates hardcoded card styling
- Consistent spacing throughout application
- Easy to maintain responsive design
- Single source for card visual style

**Files to refactor:**

- `Pages/Profile/Edit.vue` (3 cards)
- `Pages/PromptOptimizer/Show.vue` (6+ cards)
- `Pages/PromptOptimizer/Index.vue` (1 card)
- `Pages/Dashboard.vue` (1 card)
- `Pages/Home.vue` (potential future use)

---

### Priority 2: HIGH IMPACT (Affects 5-10 locations)

#### 2.1 LoadingSpinner Component

**Current:** Duplicated SVG 3+ times in `Show.vue`

**Proposed Component Name:** `LoadingSpinner.vue`

**Props:**

```typescript
interface Props {
    size?: 'sm' | 'md' | 'lg';
    color?: 'indigo' | 'white' | 'gray';
    label?: string;
}
```

**Template:**

```vue

<div class="flex items-center" v-if="label">
    <svg :class="spinnerClasses" />
    <span :class="labelClasses">{{ label }}</span>
</div>
<svg v-else :class="spinnerClasses" />
```

**Usage:**

```vue
<!-- Inline spinner -->
<LoadingSpinner size="sm" color="indigo" />

<!-- With label -->
<LoadingSpinner label="Processing..." />
```

**Benefits:**

- Eliminates SVG duplication
- Consistent spinner styling
- Easy to add animations or variations
- Reusable across all pages

**Files to refactor:**

- `Pages/PromptOptimizer/Show.vue` (3 instances)

---

#### 2.2 FormSection Component

**Current:** Section with header pattern repeated 3+ times

**Proposed Component Name:** `FormSection.vue`

**Props:**

```typescript
interface Props {
    title: string;
    description?: string;
    spaceY?: string;
}
```

**Template:**

```vue

<section :class="containerClass">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ title }}</h2>
        <p v-if="description" class="mt-1 text-sm text-gray-600">
            {{ description }}
        </p>
    </header>
    <form :class="formClass">
        <slot />
    </form>
</section>
```

**Usage:**

```vue

<FormSection
    title="Profile Information"
    description="Update your account's profile information and email address."
>
    <!-- Form fields here -->
</FormSection>
```

**Benefits:**

- Consistent section styling
- Reduces boilerplate
- Easy to modify header styling centrally

**Files to refactor:**

- `Pages/Profile/Partials/UpdateProfileInformationForm.vue`
- `Pages/Profile/Partials/UpdatePasswordForm.vue`
- `Pages/Profile/Partials/DeleteUserForm.vue`

---

#### 2.3 FormActions Component

**Current:** Button group with save feedback pattern repeated 2+ times

**Proposed Component Name:** `FormActions.vue`

**Props:**

```typescript
interface Props {
    submitLabel?: string;
    isLoading?: boolean;
    showSuccessMessage?: boolean;
    successMessage?: string;
    secondaryAction?: () => void;
    secondaryLabel?: string;
}
```

**Template:**

```vue

<div class="flex items-center gap-4">
    <PrimaryButton :disabled="isLoading">
        {{ submitLabel }}
    </PrimaryButton>
    <Transition enter-active-class="..." leave-active-class="...">
        <p v-if="showSuccessMessage" class="text-sm text-gray-600">
            {{ successMessage }}
        </p>
    </Transition>
</div>
```

**Usage:**

```vue

<FormActions
    :is-loading="form.processing"
    :show-success-message="form.recentlySuccessful"
    success-message="Saved."
/>
```

**Benefits:**

- Eliminates duplicate transition logic
- Consistent success feedback
- Reusable in all forms

**Files to refactor:**

- `Pages/Profile/Partials/UpdateProfileInformationForm.vue`
- `Pages/Profile/Partials/UpdatePasswordForm.vue`

---

#### 2.4 SectionHeader Component

**Current:** Header pattern in form sections

**Proposed Component Name:** `SectionHeader.vue`

**Props:**

```typescript
interface Props {
    title: string;
    description?: string;
    size?: 'sm' | 'md' | 'lg';
}
```

**Standalone usage:**

```vue

<SectionHeader
    title="Clarifying Questions"
    description="Questions that were answered to refine your prompt"
/>
```

**Benefits:**

- Consistent styling across pages
- Easy to maintain header hierarchy

---

### Priority 3: MEDIUM IMPACT (Affects 3-5 locations)

#### 3.1 Collapsible/Accordion Component

**Current:** Manual state management in `Show.vue`

**Proposed Component Name:** `CollapsibleItem.vue` or `Accordion.vue`

**Props:**

```typescript
// For single item
interface Props {
    title: string;
    isOpen?: boolean;
    number?: number;
}

// For accordion wrapper
interface AccordionProps {
    allowMultiple?: boolean;
}
```

**Usage:**

```vue

<Accordion>
    <CollapsibleItem v-for="(question, idx) in questions" :key="idx">
        <template #title>{{ question }}</template>
        <template #content>{{ answers[idx] }}</template>
    </CollapsibleItem>
</Accordion>
```

**Benefits:**

- Reusable state management
- Potential for FAQ section
- Settings/preferences panels
- Better accessibility

**Files with potential use:**

- `Pages/PromptOptimizer/Show.vue` (refactor existing)
- Future: FAQ page, Settings

---

#### 3.2 EmptyState Component

**Current:** One instance in `History.vue`

**Proposed Component Name:** `EmptyState.vue`

**Props:**

```typescript
interface Props {
    title?: string;
    message: string;
    actionLabel?: string;
    actionHref?: string;
    icon?: string;
}
```

**Usage:**

```vue

<EmptyState
    message="No prompt history yet."
    actionLabel="Create your first optimised prompt"
    :actionHref="route('prompt-optimizer.index')"
/>
```

**Benefits:**

- Consistent empty state styling
- Reusable across future list/table pages
- Accessible design pattern

**Files with potential use:**

- `Pages/PromptOptimizer/History.vue` (refactor existing)
- Future: Other list pages, dashboard

---

#### 3.3 ProgressIndicator Component

**Current:** Hardcoded progress bar in `Show.vue` (lines 281-306)

**Proposed Component Name:** `ProgressBar.vue`

**Props:**

```typescript
interface Props {
    current: number;
    total: number;
    showLabel?: boolean;
    showPercentage?: boolean;
}
```

**Template:**

```vue

<div>
    <div class="mb-2 flex items-center justify-between" v-if="showLabel">
        <span class="text-sm font-medium text-gray-700">
            {{ current }} of {{ total }}
        </span>
        <span v-if="showPercentage" class="text-sm text-gray-500">
            {{ percentage }}% complete
        </span>
    </div>
    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
        <div
            class="h-full bg-indigo-600 transition-all duration-300"
            :style="{ width: `${percentage}%` }"
        ></div>
    </div>
</div>
```

**Benefits:**

- Reusable progress indicator
- Consistent styling
- Multi-step forms, wizard patterns
- Cleaner component code

**Files to refactor:**

- `Pages/PromptOptimizer/Show.vue` (progress bar in question answering)

---

### Priority 4: LOWER IMPACT but useful

#### 4.1 TextLink Component / Helper Classes

**Current:** Text links scattered with inline styling:

```vue

<Link
    :href="route('login')"
    class="rounded-md px-4 py-2 text-sm font-medium text-gray-700 transition hover:text-indigo-600"
>
Log in
</Link>
```

**Proposed:** Composable or utility classes to standardise link styling

**Benefits:**

- Consistent link appearance
- Easy to update hover/focus states globally

---

## 4. UTILITY & COMPOSABLE OPPORTUNITIES

### useFormStatus.ts

Extract form state patterns used across profile forms:

```typescript
export function useFormStatus() {
    const recentlySuccessful = ref(false);

    const handleSuccess = () => {
        recentlySuccessful.value = true;
        setTimeout(() => {
            recentlySuccessful.value = false;
        }, 2000);
    };

    return { recentlySuccessful, handleSuccess };
}
```

**Usage in forms:**

```typescript
const { recentlySuccessful, handleSuccess } = useFormStatus();
const form = useForm({ ... });
form.post(route, {
    onSuccess: handleSuccess
});
```

---

### useStatusBadge.ts

Centralise status badge logic:

```typescript
export function useStatusBadge() {
    const getStatusClass = (status: string) => {...
    };
    const getStatusLabel = (status: string) => {...
    };
    return { getStatusClass, getStatusLabel };
}
```

---

### useWorkflowStage.ts

Centralise workflow stage display logic from `Show.vue`:

```typescript
export function useWorkflowStage() {
    const getStageLabel = (stage: string) => {...
    };
    const getStageColor = (stage: string) => {...
    };
    return { getStageLabel, getStageColor };
}
```

---

## 5. SUMMARY TABLE

| Opportunity              | Component Name                          | Impact                          | Complexity | Effort  | ROI       |
|--------------------------|-----------------------------------------|---------------------------------|------------|---------|-----------|
| Form field pattern       | `FormField.vue`                         | Very High (17 locations)        | Medium     | 2-3 hrs | Excellent |
| Status badge duplication | `StatusBadge.vue` + `useStatusBadge.ts` | High (5+ uses)                  | Low        | 1-2 hrs | Excellent |
| Card/container styling   | `Card.vue`                              | High (10+ locations)            | Low        | 1-2 hrs | Excellent |
| Loading spinner SVG      | `LoadingSpinner.vue`                    | Medium (3 uses)                 | Low        | 30 min  | Good      |
| Form section headers     | `FormSection.vue`                       | Medium (3 uses)                 | Low        | 45 min  | Good      |
| Form actions & feedback  | `FormActions.vue`                       | Medium (2+ uses)                | Low        | 1 hr    | Good      |
| Section headers          | `SectionHeader.vue`                     | Low-Medium (3 uses)             | Low        | 30 min  | Good      |
| Collapsible items        | `Accordion.vue` / `CollapsibleItem.vue` | Medium (1 current + future)     | Medium     | 2 hrs   | Good      |
| Empty states             | `EmptyState.vue`                        | Low-Medium (1 current + future) | Low        | 45 min  | Good      |
| Progress bar             | `ProgressBar.vue`                       | Low-Medium (1 current + future) | Low        | 45 min  | Good      |
| Status logic             | `useStatusBadge.ts`                     | High (2 files)                  | Low        | 30 min  | Excellent |
| Form status              | `useFormStatus.ts`                      | Medium (2+ forms)               | Low        | 30 min  | Good      |
| Workflow stages          | `useWorkflowStage.ts`                   | Low-Medium (1 file)             | Low        | 30 min  | Good      |

---

## 6. REFACTORING ROADMAP

### Phase 1: High-Impact Foundation (4-6 hours)

Priority: Do first - immediately reduces code duplication

1. Extract `useStatusBadge.ts` composable
2. Create `FormField.vue` component
3. Create `Card.vue` component
4. Create `LoadingSpinner.vue` component

**Expected result:** 30-40% reduction in duplicate code

### Phase 2: Form Components (3-4 hours)

Priority: Improves form consistency

1. Create `FormSection.vue` component
2. Create `FormActions.vue` component
3. Create `SectionHeader.vue` component
4. Extract `useFormStatus.ts` composable

**Expected result:** All profile forms use consistent patterns

### Phase 3: Advanced Features (4-5 hours)

Priority: Enables future feature development

1. Create `Accordion.vue` / `CollapsibleItem.vue` components
2. Create `EmptyState.vue` component
3. Create `ProgressBar.vue` component
4. Extract `useWorkflowStage.ts` composable

**Expected result:** Better structure for future features (wizard patterns, tables, lists)

### Phase 4: Polish & Optimization (2 hours)

Priority: Final touches

1. Create link styling utilities
2. Add TypeScript types for all components
3. Create Storybook stories (optional)
4. Documentation

---

## 7. SPECIFIC CODE EXAMPLES FOR REFACTORING

### Example 1: FormField Component Implementation

**Current (17 instances like this):**

```vue

<div>
    <InputLabel for="email" value="Email" />
    <TextInput
        id="email"
        type="email"
        class="mt-1 block w-full"
        v-model="form.email"
        required
        autofocus
        autocomplete="username"
    />
    <InputError class="mt-2" :message="form.errors.email" />
</div>
```

**New:**

```vue

<FormField
    id="email"
    label="Email"
    type="email"
    v-model="form.email"
    :error="form.errors.email"
    required
    autofocus
    autocomplete="username"
/>
```

**Savings:** 12 lines → 8 lines per instance (saving ~68 lines across 17 instances)

---

### Example 2: Card Component Implementation

**Current (10+ instances like this):**

```vue
<!-- Profile page -->
<div class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8">
    <UpdateProfileInformationForm ... />
</div>

<!-- Show page -->
<div class="mb-6 overflow-hidden bg-white shadow-xs sm:rounded-lg">
    <div class="p-6">
        <!-- Content -->
    </div>
</div>
```

**New:**

```vue
<!-- Profile page -->
<Card padding="lg" shadow="sm">
    <UpdateProfileInformationForm ... />
</Card>

<!-- Show page -->
<Card margin-bottom overflow>
    <!-- Content -->
</Card>
```

**Benefit:** Consistent styling + easier to maintain responsive design

---

### Example 3: Status Badge Usage

**Current (Show.vue):**

```typescript
const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'processing':
            return 'bg-yellow-100 text-yellow-800';
        case 'failed':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getWorkflowStageLabel = (stage: string) => {
    switch (stage) {
        case 'submitted':
            return 'Submitted';
        case 'framework_selected':
            return 'Framework Selected';
        // ... 6 more cases
    }
};
```

**Repeated in History.vue:** Same logic duplicated

**New (composable):**

```typescript
import { useStatusBadge } from '@/composables/useStatusBadge';

const { getStatusClass, getStatusLabel } = useStatusBadge();
```

Used in:

```vue
<!-- Show.vue -->
<StatusBadge :status="promptRun.status" />

<!-- History.vue -->
<StatusBadge :status="promptRun.status" />
```

**Savings:** ~30 lines of logic removed, centralised in composable

---

## 8. ESTIMATED CODE REDUCTION

**Current state:** ~2,100 lines of Vue template + logic

**After refactoring:**

- Duplicate form field markup: -68 lines
- Duplicate card styling: -40 lines
- Duplicate status logic: -20 lines
- Duplicate spinner SVG: -60 lines
- Duplicate section headers: -15 lines
- Duplicate form actions: -15 lines

**Total estimated reduction:** ~220 lines (-10%)

**More importantly:**

- **Code reusability:** 200+ lines moved to reusable components
- **Maintainability:** Single source of truth for 13 patterns
- **Consistency:** Unified styling across entire application

---

## 9. ADDITIONAL OBSERVATIONS

### TypeScript Opportunities

1. Create type definitions for common patterns:
   ```typescript
   // types/form.ts
   export type FormStatus = 'completed' | 'processing' | 'failed' | 'pending';
   export type WorkflowStage = 'submitted' | 'framework_selected' | 'answering_questions' | ...;
   ```

2. Add generic form types:
   ```typescript
   export interface FormFieldProps {
       id: string;
       label?: string;
       type?: string;
       value: any;
       error?: string;
       // ...
   }
   ```

### Styling Patterns

1. Button styling is well-structured (Primary, Secondary, Danger)
2. Input styling is consistent
3. Spacing uses Tailwind consistently
4. Consider creating Tailwind utility layers for repeated class combinations

### Potential Future Patterns

1. **Data tables/grids** - `History.vue` has table markup
2. **Tabs/steppers** - PromptOptimizer workflow could benefit
3. **Tooltips/hints** - Form hints pattern
4. **Notifications** - Toast/notification system for feedback
5. **Pagination** - `History.vue` has custom pagination logic

---

## 10. RECOMMENDATIONS PRIORITY

### Must Do (Phase 1-2)

1. Extract `FormField.vue` - immediate high impact
2. Extract `Card.vue` - affects many pages
3. Extract status logic to composable - code reuse

### Should Do (Phase 2-3)

1. Extract form components (`FormSection`, `FormActions`)
2. Extract `LoadingSpinner.vue` - eliminates SVG duplication
3. Create `useStatusBadge.ts` and related composables

### Nice to Have (Phase 3)

1. Advanced components (Accordion, EmptyState, ProgressBar)
2. Link styling utilities
3. Additional composables for business logic

---

## CONCLUSION

This codebase has strong fundamentals with good use of existing components (TextInput, InputLabel, InputError, Buttons).
The main opportunities lie in:

1. **Form field extraction** (17 duplicate patterns) - CRITICAL
2. **Status/badge centralisation** (2 files with duplicate logic) - HIGH
3. **Container/card styling** (10+ locations) - HIGH
4. **Micro-components** (spinners, progress bars, collapsibles) - MEDIUM

Implementing Phase 1 and 2 (8-10 hours of work) would result in:

- **30-40% less duplicate code**
- **Faster feature development** (reusable building blocks)
- **Easier maintenance** (single source of truth)
- **Better consistency** (unified design patterns)

