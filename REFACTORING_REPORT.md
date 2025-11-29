# Vue/TypeScript Frontend Refactoring Report

## Executive Summary

Analysis of the `resources/js` directory (167 Vue components, ~2,513 lines in Components alone) has identified 40+
actionable refactoring opportunities across code duplication, component structure, type safety, and styling patterns.
This report prioritises high-impact changes that would improve maintainability, reduce bundle size, and enhance
developer experience.

---

## 1. CODE DUPLICATION & REPEATED PATTERNS

### 1.1 Repeated Tailwind Focus Ring Classes

**Priority: High**  
**Impact: Styling consolidation, reduced duplication**

**Current Pattern:**

```
19 occurrences of: focus:ring-2 focus:ring-indigo-500
12 occurrences of: focus:ring-offset-2 focus:outline-hidden
```

**Files Affected:**

- `/home/mark/repos/personality/resources/js/Components/ButtonVoiceInput.vue` (line 56)
- `/home/mark/repos/personality/resources/js/Components/CookieBanner.vue` (lines 67, 74, 81)
- `/home/mark/repos/personality/resources/js/Components/CookieSettings.vue` (lines 161, 168, 175)
- `/home/mark/repos/personality/resources/js/Components/Modal.vue`
- `/home/mark/repos/personality/resources/js/Components/LoginModal.vue` (line 102, 110)
- `/home/mark/repos/personality/resources/js/Components/FlashMessage.vue` (line 133)
- And 10+ more components

**Suggested Improvement:**

Create a constants file for reusable Tailwind class groups:

```typescript
// constants/tailwind.ts
export const TAILWIND_FOCUS_RING = 'focus:ring-2 focus:ring-indigo-500 focus:outline-hidden';
export const TAILWIND_FOCUS_RING_OFFSET = 'focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden';
export const TAILWIND_BTN_BASE = 'inline-flex items-center rounded-md px-4 py-2 text-sm font-medium transition';
export const TAILWIND_BTN_PRIMARY = `${TAILWIND_BTN_BASE} border-transparent bg-indigo-600 text-white hover:bg-indigo-700 ${TAILWIND_FOCUS_RING}`;
export const TAILWIND_BTN_SECONDARY = `${TAILWIND_BTN_BASE} border-gray-300 bg-white text-gray-700 shadow-xs hover:bg-gray-50 ${TAILWIND_FOCUS_RING}`;
```

Then use in components:

```vue

<button :class="TAILWIND_BTN_PRIMARY">Save</button>
```

---

### 1.2 Transcription Logic Duplication

**Priority: High**  
**Impact: DRY principle, easier maintenance**

**Current Implementation:**

**File 1:** `/home/mark/repos/personality/resources/js/Pages/PromptBuilder/Index.vue` (lines 25-31)

```typescript
const handleTranscription = (text: string) => {
    if (form.taskDescription && !form.taskDescription.endsWith(' ')) {
        form.taskDescription += ' ';
    }
    form.taskDescription += text;
};
```

**File 2:** `/home/mark/repos/personality/resources/js/Components/PromptBuilder/QuestionAnsweringForm.vue` (lines 31-38)

```typescript
const handleTranscription = (text: string) => {
    let newAnswer = props.answer;
    if (newAnswer && !newAnswer.endsWith(' ')) {
        newAnswer += ' ';
    }
    newAnswer += text;
    emit('update:answer', newAnswer);
};
```

**Suggested Improvement:**

Create a composable:

```typescript
// Composables/useTextAppend.ts
export function useTextAppend() {
    const appendText = (currentText: string, textToAppend: string): string => {
        if (currentText && !currentText.endsWith(' ')) {
            currentText += ' ';
        }
        return currentText + textToAppend;
    };

    return { appendText };
}
```

Then use in both components:

```typescript
const { appendText } = useTextAppend();
const handleTranscription = (text: string) => {
    form.taskDescription = appendText(form.taskDescription, text);
};
```

---

### 1.3 Cookie Setting Modal Pattern Repetition

**Priority: Medium**  
**Impact: Component reusability**

**Files:**

- `/home/mark/repos/personality/resources/js/Components/CookieSettings.vue` (lines 115-153)
- `/home/mark/repos/personality/resources/js/Components/FormCheckboxGroup.vue` (similar structure)

Both files implement nearly identical expandable/toggleable sections with FormToggle.

**Suggested Improvement:**

Extract into a reusable `SettingSection` component:

```vue
<!-- Components/SettingSection.vue -->
<script setup lang="ts">
    interface Props {
        title: string;
        description: string;
        toggleLabel?: string;
    }

    const props = withDefaults(defineProps<Props>(), {
        toggleLabel: '',
    });
    const emit = defineEmits<{
        (e: 'update:toggle', value: boolean): void;
    }>();
</script>

<template>
    <div class="rounded-lg border border-gray-200 p-4">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="font-medium text-gray-900">{{ title }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ description }}</p>
            </div>
            <slot />
        </div>
    </div>
</template>
```

Then in CookieSettings:

```vue

<SettingSection
    :title="COOKIE_CATEGORIES.functional.name"
    :description="COOKIE_CATEGORIES.functional.description"
>
    <FormToggle v-model="functionalEnabled" label="Functional cookies" />
</SettingSection>
```

---

## 2. COMPONENT STRUCTURE IMPROVEMENTS

### 2.1 Button Component Fragmentation

**Priority: High**  
**Impact: Maintenance, consistency, bundle size**

**Current State:**

- `PrimaryButton.vue` (7 lines)
- `SecondaryButton.vue` (20 lines)
- `DangerButton.vue` (7 lines)
- `ButtonMode.vue` (18 lines)
- `ButtonDarkMode.vue` (23 lines)
- `ButtonVoiceInput.vue` (112 lines - actually feature button)
- `ButtonClose.vue` (26 lines)

**Issues:**

1. Many single-purpose button components with minimal unique logic
2. Inconsistent styling approach (some have hardcoded classes, some don't)
3. `ButtonClose.vue` and `ButtonMode.vue` could be combined with `ButtonBase`

**Location:** `/home/mark/repos/personality/resources/js/Components/`

**Suggested Improvement:**

Create a unified `Button.vue` component:

```vue
<!-- Components/Button.vue -->
<script setup lang="ts">
    import type { ButtonType } from '@/types';

    type Variant = 'primary' | 'secondary' | 'danger' | 'ghost';

    interface Props {
        type?: ButtonType;
        variant?: Variant;
        disabled?: boolean;
        size?: 'sm' | 'md' | 'lg';
        fullWidth?: boolean;
        loading?: boolean;
    }

    const props = withDefaults(defineProps<Props>(), {
        type: 'button',
        variant: 'primary',
        disabled: false,
        size: 'md',
        fullWidth: false,
        loading: false,
    });

    const variantClasses = {
        primary: 'border-transparent bg-indigo-600 text-white hover:bg-indigo-700',
        secondary: 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
        danger: 'border-transparent bg-red-600 text-white hover:bg-red-500',
        ghost: 'border-transparent text-gray-600 hover:text-gray-800',
    };

    const sizeClasses = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2 text-sm',
        lg: 'px-6 py-3 text-base',
    };
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'inline-flex items-center rounded-md font-medium transition duration-150',
            variantClasses[variant],
            sizeClasses[size],
            fullWidth ? 'w-full justify-center' : '',
            disabled || loading ? 'opacity-50 cursor-not-allowed' : '',
            'focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden',
        ]"
    >
        <span>
            <slot />
        </span>
    </button>
</template>
```

Then replace all button variants:

```vue
<!-- Old -->
<PrimaryButton>Save</PrimaryButton>
<DangerButton>Delete</DangerButton>

<!-- New -->
<Button variant="primary">Save</Button>
<Button variant="danger">Delete</Button>
```

---

### 2.2 Form Component Consolidation

**Priority: High**  
**Impact: Reduced code duplication, consistent API**

**Current Form Components:**

- `FormField.vue` (76 lines) - wrapper for various input types
- `FormInput.vue` (93 lines) - text/number input
- `FormSelect.vue` (90 lines) - select dropdown
- `FormTextarea.vue` (67 lines) - textarea
- `FormCheckbox.vue` (82 lines) - checkbox
- `FormCheckboxGroup.vue` (147 lines) - group of checkboxes
- `FormToggle.vue` (48 lines) - toggle switch
- `FormFieldWrapper.vue` (37 lines) - shared wrapper logic

**Issues:**

1. `FormField.vue` is a wrapper that delegates to specific components but adds complexity
2. Inconsistent handling of errors, help text, labels
3. Multiple wrapper layers (`FormFieldWrapper`)
4. Props are partially repeated across components

**Location:** `/home/mark/repos/personality/resources/js/Components/Form*.vue`

**Example of Duplication:**

FormInput.vue (lines 1-49):

```typescript
interface Props {
    id: string;
    modelValue?: string | number | null;
    label: string;
    type?: string;
    error?: string;
    required?: boolean;
    placeholder?: string;
    disabled?: boolean;
    autofocus?: boolean;
    autocomplete?: string;
    customClass?: string;
    helpText?: string;
    min?: number | string;
    max?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    type: 'text',
    error: '',
    required: false,
    placeholder: '',
    disabled: false,
    autofocus: false,
    autocomplete: '',
    customClass: '',
    helpText: '',
});
```

FormSelect.vue (lines 1-36):

```typescript
interface Props {
    id: string;
    label: string;
    modelValue?: string | number;
    options: SelectOption[];
    error?: string;
    required?: boolean;
    disabled?: boolean;
    placeholder?: string;
    showPlaceholder?: boolean;
    customClass?: string;
    helpText?: string;
    autofocus?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    error: '',
    required: false,
    disabled: false,
    placeholder: 'Please select...',
    showPlaceholder: true,
    customClass: '',
    helpText: '',
    autofocus: false,
});
```

**Suggested Improvement:**

Create a base form input composition pattern:

```typescript
// types/form.ts
export interface BaseFormInputProps {
    id: string;
    label: string;
    error?: string;
    required?: boolean;
    helpText?: string;
    disabled?: boolean;
    autofocus?: boolean;
    customClass?: string;
}

export interface FormInputProps extends BaseFormInputProps {
    type?: string;
    modelValue?: string | number | null;
    placeholder?: string;
    autocomplete?: string;
    min?: number | string;
    max?: number | string;
}

export interface FormSelectProps extends BaseFormInputProps {
    modelValue?: string | number;
    options: SelectOption[];
    placeholder?: string;
    showPlaceholder?: boolean;
}
```

Then all form components can extend from a base:

```typescript
// Composables/useFormInput.ts
export function useFormInputBase(props: BaseFormInputProps) {
    return {
        isRequired: props.required,
        hasError: !!props.error,
        showHelp: !!props.helpText,
    };
}
```

---

### 2.3 Authentication Modal Consolidation

**Priority: Medium**  
**Impact: Reduced duplication, easier feature additions**

**Files:**

- `/home/mark/repos/personality/resources/js/Components/LoginModal.vue` (127 lines)
- `/home/mark/repos/personality/resources/js/Components/RegisterModal.vue` (117 lines)
- `/home/mark/repos/personality/resources/js/Components/ForgotPasswordModal.vue` (97 lines)
- `/home/mark/repos/personality/resources/js/Components/BaseAuthModal.vue` (66 lines)

**Issues:**

1. Each modal duplicates similar form structure
2. Repeated button styling and layout
3. Similar error handling across all three

**Suggested Improvement:**

Create a shared auth form component that handles common patterns:

```vue
<!-- Components/AuthForm.vue -->
<script setup lang="ts">
    interface Props {
        title: string;
        submitLabel: string;
        loading?: boolean;
        showGoogleSignIn?: boolean;
    }

    const emit = defineEmits(['submit', 'google-signin'];
</script>

<template>
    <div>
        <h2 class="text-lg font-medium text-gray-900">{{ title }}</h2>
        <form @submit.prevent="emit('submit')" class="mt-6 space-y-4">
            <slot name="fields" />
            <button type="submit" :disabled="loading">
                {{ submitLabel }}
            </button>
        </form>
    </div>
</template>
```

---

## 3. COMPOSABLES & STATE MANAGEMENT

### 3.1 Error Timeout Duplication

**Priority: Medium**  
**Impact: Code reuse, consistency**

**Current Pattern Found In:**

- `/home/mark/repos/personality/resources/js/Composables/useAudioRecording.ts` (lines 45-48, 84-87)
- `/home/mark/repos/personality/resources/js/Components/FlashMessage.vue` (lines 28-31)

**Code:**

```typescript
// useAudioRecording.ts - appears twice
setTimeout(() => {
    error.value = null;
}, 5000);
```

**Suggested Improvement:**

Create a composable:

```typescript
// Composables/useErrorTimeout.ts
import { ref } from 'vue';

export function useErrorTimeout(timeout: number = 5000) {
    const error = ref<string | null>(null);

    const setError = (message: string) => {
        error.value = message;
        setTimeout(() => {
            error.value = null;
        }, timeout);
    };

    const clearError = () => {
        error.value = null;
    };

    return {
        error,
        setError,
        clearError,
    };
}
```

Then simplify useAudioRecording:

```typescript
export function useAudioRecording() {
    const { error, setError } = useErrorTimeout(5000);

    // ... in catch blocks
catch
    (err: any)
    {
        setError('Microphone access denied. Please enable microphone permissions.');
    }
}
```

---

### 3.2 Cookie Management Duplication

**Priority: Low**  
**Impact: Type safety, consistency**

**Files:**

- `/home/mark/repos/personality/resources/js/Composables/useCookieConsent.ts` (getCookie/setCookie logic, lines 18-40)

**Issue:** Cookie reading/writing logic is embedded in the composable and duplicated in `useAudioRecording.ts` (lines
102-116)

**Suggested Improvement:**

Extract into a utility:

```typescript
// utils/cookies.ts
export function getCookie(name: string): string | null {
    const matches = document.cookie.match(
        new RegExp(
            '(?:^|; )' +
            name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') +
            '=([^;]*)',
        ),
    );
    return matches ? decodeURIComponent(matches[1]) : null;
}

export function setCookie(
    name: string,
    value: string,
    days: number = 365,
): void {
    const expires = new Date();
    expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = `${name}=${encodeURIComponent(value)};expires=${expires.toUTCString()};path=/;SameSite=Strict`;
}
```

---

## 4. TYPE SAFETY IMPROVEMENTS

### 4.1 Use of `any` Type

**Priority: High**  
**Impact: Type safety, IDE support, bug prevention**

**Found Instances:**

1. **`/home/mark/repos/personality/resources/js/Components/Checkbox.vue` (line 8)**

```typescript
value ? : any;
```

Should be:

```typescript
value ? : string | number | boolean;
```

2. **`/home/mark/repos/personality/resources/js/types/shared/common.ts` (line 16)**

```typescript
export interface JsonData {
    [key: string]: any;
}
```

Could be made more specific based on usage, or:

```typescript
export type JsonData = Record<string, unknown>;
```

3. **`/home/mark/repos/personality/resources/js/Composables/useRealtimeUpdates.ts` (lines 40, 74, 91)**

```typescript
let channel: any = null;
channel.listen(eventName, (data: any) => { ...
}
channel.error((error: any) => { ...
}
```

**Suggested Improvement:**

Define proper types for Echo channel:

```typescript
// types/echo.ts
export interface EchoChannel {
    listen(event: string, callback: (data: any) => void): EchoChannel;

    error(callback: (error: Error) => void): EchoChannel;

    // ... other methods
}

export interface EchoClient {
    channel(name: string): EchoChannel;

    leave(name: string): void;
}

// Composables/useRealtimeUpdates.ts
let channel: EchoChannel | null = null;

const setupEcho = () => {
    try {
        channel = window.Echo?.channel(channelName);
        // ...
    }
};
```

---

### 4.2 Missing Event Types in Emits

**Priority: Medium**  
**Impact: Type safety, easier component usage**

**Examples:**

**File:** `/home/mark/repos/personality/resources/js/Components/Modal.vue` (line 17)

```typescript
const emit = defineEmits(['close']);  // Untyped emit
```

Should be:

```typescript
const emit = defineEmits<{
    close: [];
}>();
```

**File:** `/home/mark/repos/personality/resources/js/Components/LoginModal.vue` (line 15)

```typescript
const emit = defineEmits(['close', 'switchToRegister', 'switchToForgotPassword']);
```

Should be:

```typescript
const emit = defineEmits<{
    close: [];
    switchToRegister: [];
    switchToForgotPassword: [];
}>();
```

---

### 4.3 Inconsistent Prop Validation

**Priority: Medium**  
**Impact: Consistency, runtime safety**

**Issue:** Some components use `withDefaults(defineProps<Props>())` and others don't:

- ✅ `FormInput.vue` - uses `withDefaults`
- ❌ `Modal.vue` - uses `withDefaults` on line 4
- ✅ `LoginModal.vue` - has `defineProps<Props>()` but no defaults

**Suggested:** Standardise on always using typed props with `withDefaults`:

```typescript
// Consistent pattern
const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    required: false,
    // ... all optional props with defaults
});
```

---

## 5. PERFORMANCE OPTIMIZATIONS

### 5.1 Missing Computed Properties

**Priority: Medium**  
**Impact: Render performance**

**File:** `/home/mark/repos/personality/resources/js/Components/LikertScale.vue` (lines 31-59)

**Issue:** Color and size calculations run on every render:

```typescript
const getCircleSize = (index: number) => {
    const sizes = ['h-12 w-12', 'h-10 w-10', ...];
    return sizes[index] || 'h-14 w-14';
};

const getCircleColor = (index: number, isSelected: boolean) => {
    const colors = [...];
    const color = colors[index] || colors[3];
    return isSelected ? color.bg : color.border;
};

// In template - called on every iteration
:

class

= "getCircleSize(index)"
:

class

= "getCircleColor(index, modelValue === value)"
```

**Suggested Improvement:**

Memoize these values:

```typescript
const circleConfig = computed(() => {
    return {
        sizes: ['h-12 w-12', 'h-10 w-10', 'h-8 w-8', 'h-6 w-6', 'h-8 w-8', 'h-10 w-10', 'h-12 w-12'],
        colors: [
            { border: 'border-teal-600', bg: 'bg-teal-600' },
            // ...
        ],
    };
});

const getCircleClasses = (index: number, isSelected: boolean) => {
    const size = circleConfig.value.sizes[index] || 'h-14 w-14';
    const color = circleConfig.value.colors[index]?.bg ||
        circleConfig.value.colors[3].bg;
    return [size, isSelected ? color.bg : color.border];
};
```

---

### 5.2 Repeated Object Creation

**Priority: Low**  
**Impact: Memory efficiency**

**File:** `/home/mark/repos/personality/resources/js/Components/FlashMessage.vue` (lines 56-88)

**Issue:** TypeConfig object is recreated on every render:

```typescript
const typeConfig = computed(() => {
    const configs = {
        success: { /* ... */ },
        warning: { /* ... */ },
        error: { /* ... */ },
    };
    return configs[props.type];
});
```

**Better approach:**

```typescript
const MESSAGE_TYPE_CONFIG = {
    success: { /* ... */ },
    warning: { /* ... */ },
    error: { /* ... */ },
} as const;

const typeConfig = computed(() => MESSAGE_TYPE_CONFIG[props.type]);
```

---

## 6. ACCESSIBILITY IMPROVEMENTS

### 6.1 Missing ARIA Labels

**Priority: Medium**  
**Impact: Screen reader support**

**File:** `/home/mark/repos/personality/resources/js/Components/ButtonClose.vue` (line 17)

```typescript
// Has aria-label ✅
aria - label = "Close"
```

**File:** `/home/mark/repos/personality/resources/js/Components/FormToggle.vue` (line 32-34)

```typescript
// Has aria-checked and aria-label ✅
role = "switch"
:
aria - checked = "modelValue"
:
aria - label = "label"
```

**File:** `/home/mark/repos/personality/resources/js/Components/ButtonVoiceInput.vue` (line 64-68)

```typescript
// Missing aria-label on button
:
title = "isActive ? 'Stop recording' : 'Click to record using your microphone'"
```

**Suggested Fix:**

```vue

<button
    type="button"
    :aria-label="isActive ? 'Stop recording' : 'Click to record using your microphone'"
    :aria-pressed="isActive"
>
```

---

### 6.2 Missing Keyboard Navigation

**Priority: Low**  
**Impact: Keyboard accessibility**

**File:** `/home/mark/repos/personality/resources/js/Components/LikertScale.vue` (line 72-89)

The circular buttons need keyboard navigation:

```vue

<button
    v-for="(value, index) in options"
    type="button"
    @click="selectOption(value)"
    @keydown.enter.space="selectOption(value)"
    :tabindex="modelValue === value ? 0 : -1"
    :aria-label="`Select option ${value}`"
    :aria-pressed="modelValue === value"
>
```

---

## 7. STYLING & TAILWIND PATTERNS

### 7.1 Magic Hardcoded Values

**Priority: Medium**  
**Impact: Maintainability, consistency**

**File:** `/home/mark/repos/personality/resources/js/Components/FormCheckboxGroup.vue` (line 56-58)

```typescript
setTimeout(() => {
    otherTextarea.value?.focus();
}, 250);  // Magic number - relates to transition duration
```

**Suggested Fix:**

```typescript
const TRANSITION_DURATION_MS = 250;

setTimeout(() => {
    otherTextarea.value?.focus();
}, TRANSITION_DURATION_MS);
```

**File:** `/home/mark/repos/personality/resources/js/Composables/useAudioRecording.ts` (line 45-48)

```typescript
setTimeout(() => {
    error.value = null;
}, 5000);  // Magic number
```

**Suggested Fix:**

```typescript
const ERROR_DISPLAY_DURATION_MS = 5000;

setTimeout(() => {
    error.value = null;
}, ERROR_DISPLAY_DURATION_MS);
```

---

### 7.2 Repeated Transition Classes

**Priority: Low**  
**Impact: Consistency**

**Pattern Found In Multiple Components:**

```vue

<Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
>
```

**Suggested Solution:**

Create transition components:

```vue
<!-- Components/Transitions/FadeSlideUp.vue -->
<script setup lang="ts">
    defineProps<{
        enterDuration?: number;
        leaveDuration?: number;
    }>();
</script>

<template>
    <Transition
        enter-active-class="transition ease-out"
        :enter-from-class="`opacity-0 translate-y-2`"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
        :style="{ '--duration-enter': `${enterDuration}ms`, '--duration-leave': `${leaveDuration}ms` }"
    >
        <slot />
    </Transition>
</template>

<style scoped>
    :deep([enter-active-class]) {
        duration: var(--duration-enter);
    }
</style>
```

---

## 8. CONFIGURATION & CONSTANTS

### 8.1 Hardcoded Strings

**Priority: Low**  
**Impact: Maintainability, i18n support**

**Files with Hardcoded Strings:**

1. `/home/mark/repos/personality/resources/js/Components/ButtonVoiceInput.vue` (lines 28-33)

```typescript
const buttonLabel = computed(() => {
    if (isProcessing.value) {
        return 'Transcribing...';  // Hardcoded
    }
    if (isActive.value) {
        return 'Listening...';  // Hardcoded
    }
    return 'Record';  // Hardcoded
});
```

2. `/home/mark/repos/personality/resources/js/Pages/PromptBuilder/Index.vue` (lines 39, 162)

```vue

<Head title="Prompt Optimiser" />
<!-- and -->
<span v-else>Optimise Prompt</span>
```

**Suggested Improvement:**

Create a translations/messages constants file:

```typescript
// constants/messages.ts
export const AUDIO_LABELS = {
    transcribing: 'Transcribing...',
    listening: 'Listening...',
    record: 'Record',
} as const;

export const PAGE_TITLES = {
    promptOptimiser: 'Prompt Optimiser',
} as const;
```

---

## 9. COMPONENT COMPOSITION PATTERNS

### 9.1 Prop Drilling in QuestionAnsweringForm

**Priority: Low**  
**Impact: Component complexity**

**File:** `/home/mark/repos/personality/resources/js/Components/PromptBuilder/QuestionAnsweringForm.vue` (lines 9-17)

```typescript
interface Props {
    question: string;
    answer: string;
    currentQuestionNumber: number;
    totalQuestions: number;
    isSubmitting: boolean;
    hasError?: boolean;
    errorMessage?: string;
    showAll?: boolean;
}
```

Many of these could be grouped:

```typescript
interface Props {
    question: string;
    answer: string;
    progress: {
        current: number;
        total: number;
    };
    submission: {
        isSubmitting: boolean;
        hasError?: boolean;
        errorMessage?: string;
    };
    showAll?: boolean;
}
```

Then update template:

```vue
<span>Question {{ progress.current }} of {{ progress.total }}</span>
<span v-if="submission.hasError">{{ submission.errorMessage }}</span>
```

---

## 10. TESTING & DEBUGGING

### 10.1 Console Logging in Composables

**Priority: Low**  
**Impact: Production bundle size, debugging**

**File:** `/home/mark/repos/personality/resources/js/Composables/useRealtimeUpdates.ts`

Has 10+ console.log/console.error statements (lines 45, 60, 76, 78, 82, 105, 109, 116, 126, 140).

**Suggested Improvement:**

Create a debug utility:

```typescript
// utils/debug.ts
export function createLogger(namespace: string) {
    const isDev = process.env.NODE_ENV === 'development';

    return {
        log: (...args: any[]) => {
            if (isDev) console.log(`[${namespace}]`, ...args);
        },
        warn: (...args: any[]) => {
            if (isDev) console.warn(`[${namespace}]`, ...args);
        },
        error: (...args: any[]) => {
            console.error(`[${namespace}]`, ...args);
        },
    };
}

// In composable
const logger = createLogger('useRealtimeUpdates');
logger.log('Connected to channel', channelName);
```

---

## SUMMARY TABLE

| Category                    | Count | Priority | Impact                     | Effort |
|-----------------------------|-------|----------|----------------------------|--------|
| Tailwind Class Duplication  | 31+   | High     | Styling consolidation      | Low    |
| Transcription Logic         | 2     | High     | DRY, Maintainability       | Low    |
| Button Components           | 7     | High     | Consolidation, Consistency | Medium |
| Form Components             | 8     | High     | Duplication reduction      | High   |
| Type Safety (`any` usage)   | 5+    | High     | Type safety                | Medium |
| Missing Computed Properties | 3+    | Medium   | Performance                | Low    |
| Hardcoded Strings           | 5+    | Low      | i18n, Maintainability      | Low    |
| Missing ARIA Labels         | 3+    | Medium   | Accessibility              | Low    |
| Repeated Transitions        | 4+    | Low      | Consistency                | Low    |
| Cookie Utilities            | 2     | Low      | Code reuse                 | Low    |

**Total Refactoring Opportunities: 40+**

---

## RECOMMENDED IMPLEMENTATION ORDER

### Phase 1 (Immediate - High Impact, Low Effort)

1. Extract Tailwind focus ring constants
2. Consolidate button components
3. Create text append composable
4. Extract cookie utilities

### Phase 2 (Short-term - High Impact, Medium Effort)

1. Refactor form components with shared props interface
2. Add proper TypeScript types for Echo/async operations
3. Extract hardcoded strings to constants

### Phase 3 (Medium-term - Medium Impact)

1. Implement unified Button component (possibly using composition)
2. Create reusable form section component
3. Add comprehensive type definitions for composables

### Phase 4 (Long-term - Polish)

1. Implement accessibility improvements
2. Optimize performance with computed properties
3. Add i18n support via message constants

