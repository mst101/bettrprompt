# Testing Documentation

This directory contains all frontend and E2E tests for the AI Buddy application.

## Testing Stack

### Unit & Component Testing (Vitest)

- **Test Runner**: [Vitest](https://vitest.dev/) v4.0.8 - Fast, Vite-native test runner
- **Component Testing**: [@vue/test-utils](https://test-utils.vuejs.org/) v2.4.6
- **DOM Environment**: [happy-dom](https://github.com/capricorn86/happy-dom) v20.0.10
- **Coverage**: [@vitest/coverage-v8](https://vitest.dev/guide/coverage.html) with V8 provider

### E2E Testing (Playwright)

- **E2E Framework**: [Playwright](https://playwright.dev/) v1.56.1
- **Browsers**: Chromium (configurable for Firefox and WebKit)
- **Features**: Screenshots, videos, traces on failure

## Directory Structure

```
tests/
├── setup/
│   └── vitest.setup.ts        # Global test setup and mocks
├── helpers/
│   └── mount.ts               # Test helper utilities
├── unit/
│   └── *.test.ts              # Unit tests for composables
├── component/
│   └── *.test.ts              # Component tests
├── e2e/
│   ├── *.e2e.ts               # E2E tests
│   └── .gitignore             # Ignore test artifacts
└── README.md                  # This file
```

## Running Tests

### Unit & Component Tests

```bash
# Run all unit/component tests once
pnpm test:unit

# Run tests in watch mode (auto-rerun on changes)
pnpm test:watch

# Run tests with coverage report
pnpm test:coverage

# Open Vitest UI (visual test runner)
pnpm test:ui

# Run specific test file
pnpm test:unit tests/unit/useLocalStorage.test.ts
```

### E2E Tests

```bash
# Run all E2E tests
pnpm test:e2e

# Run E2E tests with UI mode (visual debugger)
pnpm test:e2e:ui

# Run E2E tests in headed mode (visible browser)
pnpm test:e2e:headed

# Run E2E tests in debug mode
pnpm test:e2e:debug

# Run specific E2E test file
pnpm test:e2e tests/e2e/home.e2e.ts
```

## Coverage Thresholds

The project enforces minimum coverage requirements:

- **Statements**: 70%
- **Branches**: 65%
- **Functions**: 65%
- **Lines**: 70%

Files excluded from coverage:
- Entry points (`app.ts`, `ssr.ts`)
- Type definitions (`*.d.ts`)
- Config files (`*.config.ts`)
- Icon components (`Icons/**`)

View coverage reports:
- **Terminal**: Run `pnpm test:coverage`
- **HTML Report**: Open `coverage/index.html` after running coverage

## Writing Tests

### Unit Tests (Composables)

Unit tests are for testing composables and utility functions in isolation.

**Example**: Testing a composable

```typescript
// tests/unit/useLocalStorage.test.ts
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useLocalStorage } from '@/Composables/useLocalStorage';
import { nextTick } from 'vue';

describe('useLocalStorage', () => {
    let localStorageMock: Record<string, string> = {};

    beforeEach(() => {
        localStorageMock = {};
        global.localStorage = {
            getItem: vi.fn((key: string) => localStorageMock[key] || null),
            setItem: vi.fn((key: string, value: string) => {
                localStorageMock[key] = value;
            }),
            // ... other methods
        };
    });

    it('should initialise with default value', () => {
        const value = useLocalStorage('test-key', 'default');
        expect(value.value).toBe('default');
    });

    it('should persist changes', async () => {
        const value = useLocalStorage('test-key', 'initial');
        value.value = 'updated';
        await nextTick();
        expect(localStorage.setItem).toHaveBeenCalled();
    });
});
```

### Component Tests

Component tests verify that Vue components render correctly and handle interactions.

**Example**: Testing a component

```typescript
// tests/component/PrimaryButton.test.ts
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import PrimaryButton from '@/Components/PrimaryButton.vue';

describe('PrimaryButton', () => {
    it('should render button element', () => {
        const wrapper = mount(PrimaryButton);
        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('should render slot content', () => {
        const wrapper = mount(PrimaryButton, {
            slots: { default: 'Click me' },
        });
        expect(wrapper.text()).toBe('Click me');
    });

    it('should emit click events', async () => {
        const wrapper = mount(PrimaryButton);
        await wrapper.find('button').trigger('click');
        expect(wrapper.emitted('click')).toBeTruthy();
    });
});
```

### Testing Inertia Components

Use the `mountWithInertia` helper for components that use Inertia.js features:

```typescript
import { mountWithInertia } from '../helpers/mount';
import MyComponent from '@/Components/MyComponent.vue';

it('should render with Inertia props', () => {
    const wrapper = mountWithInertia(MyComponent, {
        props: { title: 'Test' },
        pageProps: {
            auth: {
                user: { id: 1, name: 'Test User', email: 'test@example.com' },
            },
        },
    });
    expect(wrapper.text()).toContain('Test User');
});
```

### E2E Tests

E2E tests verify complete user journeys in a real browser environment.

**Example**: Testing a page

```typescript
// tests/e2e/home.e2e.ts
import { test, expect } from '@playwright/test';

test.describe('Home Page', () => {
    test('should load the home page', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveTitle(/Welcome to AI Buddy/);
    });

    test('should display main heading', async ({ page }) => {
        await page.goto('/');
        const heading = page.getByRole('heading', {
            name: /Optimise AI Prompts for/,
        });
        await expect(heading).toBeVisible();
    });
});
```

## Available Mocks

### Inertia.js

Automatically mocked in `tests/setup/vitest.setup.ts`:
- `usePage()` - Returns mock page props with auth.user
- `useForm()` - Returns mock form object with all methods
- `router` - Mock router with navigation methods
- `Link` and `Head` components

### Laravel Echo (WebSockets)

Mock Echo instance with channel management:
```typescript
const mockChannel = createMockEchoChannel();
mockChannel.listen('EventName', (data) => {
    // Handle event
});
```

### Browser APIs

- **MediaRecorder**: Mock for audio recording tests
- **SpeechRecognition**: Mock for voice input tests

### Helper Functions

Located in `tests/helpers/mount.ts`:

- `mountWithInertia()` - Mount component with Inertia context
- `createMockUser()` - Create mock user object
- `createMockPromptRun()` - Create mock PromptRun object
- `createMockMediaRecorder()` - Create mock MediaRecorder
- `createMockSpeechRecognition()` - Create mock SpeechRecognition
- `createMockEchoChannel()` - Create mock Laravel Echo channel
- `flushPromises()` - Wait for all promises to resolve

## Best Practices

### Unit & Component Tests

1. **Test behaviour, not implementation**: Test what the component/function does, not how it does it
2. **Use descriptive test names**: Use "should..." format for clarity
3. **Follow AAA pattern**: Arrange, Act, Assert
4. **Mock external dependencies**: Use mocks for API calls, localStorage, etc.
5. **Test edge cases**: Empty states, errors, loading states
6. **Keep tests isolated**: Each test should be independent

### E2E Tests

1. **Test critical user journeys**: Focus on important flows
2. **Use meaningful selectors**: Follow the selector strategy below
3. **Wait for elements**: Use `waitForLoadState`, `waitForSelector` appropriately
4. **Handle flakiness**: Add appropriate waits and retries
5. **Test responsive design**: Include mobile viewport tests
6. **Clean up after tests**: Ensure tests don't affect each other

#### Selector Strategy (Hybrid Approach)

We use a hybrid approach for selecting elements in E2E tests, prioritising resilience and maintainability:

1. **Semantic Selectors (Preferred)**: Use Playwright's built-in semantic selectors for interactive and user-facing elements:
   - `getByRole('button', { name: /submit/i })` - For buttons, links, headings
   - `getByLabel(/your answer/i)` - For form fields with labels
   - `getByText(/welcome/i)` - For text content
   - `getByPlaceholder(/search/i)` - For inputs with placeholders

2. **Test IDs (Escape Hatch)**: Use `data-testid` attributes for decorative or complex elements where semantic selectors are insufficient:
   - `getByTestId('hero-gradient-text')` - For styled/decorative text
   - `getByTestId('status-badge')` - For status indicators
   - `getByTestId('progress-bar')` - For progress indicators
   - `getByTestId('copy-prompt-button')` - For complex interactive elements

3. **When to Use Test IDs**:
   - Element has no semantic meaning (decorative spans, divs)
   - Multiple similar elements exist and need disambiguation
   - Element is dynamically generated or has unstable text
   - CSS classes are likely to change (Tailwind utility classes)

4. **When NOT to Use Test IDs**:
   - Element has a clear semantic role (button, link, heading)
   - Element has a visible label or accessible name
   - Text content is stable and unique

**Example**:
```typescript
// ✅ Good: Semantic selector for button
const submitButton = page.getByRole('button', { name: /submit answer/i });

// ✅ Good: Test ID for decorative element
const gradientText = page.getByTestId('hero-gradient-text');

// ❌ Bad: CSS class selector (fragile)
const statusBadge = page.locator('[class*="badge"]');

// ❌ Bad: Test ID for semantic element
const heading = page.getByTestId('main-heading'); // Use getByRole('heading') instead
```

## Debugging Tests

### Unit & Component Tests

```bash
# Run tests in UI mode for visual debugging
pnpm test:ui

# Run specific test file in watch mode
pnpm test:watch tests/unit/useLocalStorage.test.ts

# Add console.log or debugger statements in your tests
```

### E2E Tests

```bash
# Debug mode (opens browser DevTools)
pnpm test:e2e:debug

# Headed mode (see browser actions)
pnpm test:e2e:headed

# UI mode (time-travel debugging)
pnpm test:e2e:ui

# Generate trace for failed tests
# Traces are saved in tests/e2e/results/
```

## CI/CD Integration

Tests run automatically in CI environments:

- **Vitest**: Runs with coverage reporting
- **Playwright**:
  - Runs in headless mode
  - Retries failed tests twice
  - Generates HTML reports
  - Saves traces, videos, and screenshots on failure

## Troubleshooting

### Vitest Issues

**Problem**: Tests fail with "Cannot find module '@/Components/...'"
- **Solution**: Check that `vitest.config.ts` has correct path aliases matching `tsconfig.json`

**Problem**: Mocks not working
- **Solution**: Ensure `tests/setup/vitest.setup.ts` is loaded in `vitest.config.ts` setupFiles

### Playwright Issues

**Problem**: E2E tests timeout
- **Solution**: Increase timeout in `playwright.config.ts` or use `test.setTimeout(60000)` in specific tests

**Problem**: Browser not found
- **Solution**: Run `pnpm exec playwright install chromium`

**Problem**: Laravel server not starting
- **Solution**: Check that `php artisan serve` works independently and port 8000 is available

## Further Reading

- [Vitest Documentation](https://vitest.dev/)
- [Vue Test Utils Documentation](https://test-utils.vuejs.org/)
- [Playwright Documentation](https://playwright.dev/)
- [Testing Inertia.js Apps](https://inertiajs.com/testing)
