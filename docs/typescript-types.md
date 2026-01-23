# TypeScript Type Usage Guide

## PromptRun Type Decision Tree

### For Component Props

Use `PromptRunResource` for any component that receives prompt run data from the backend:

```typescript
interface Props {
  promptRun: PromptRunResource;
}
```

This is the primary type you'll use in 95% of cases. It's auto-generated from the PHP Resource class and includes full type information for all fields.

### For Analytics/Table Rows

Define a custom interface for display-specific data structures:

```typescript
// PromptRunsAnalytics.vue
interface PromptRunAnalyticsRow {
  id: string;
  personalityType: string;
  framework: string;
  createdBy: string;
  taskDescription: string;
  status: string;
  createdAt: string;
  completedAt: string | null;
  durationMs: number | null;
}
```

Include only the fields needed for display. This keeps components focused and easy to test.

### For Test Fixtures

Use `PromptRunTestFixture` when building test data:

```typescript
import { TestDataBuilder, PromptRunTestFixture } from '@fixtures/data-builder';

const fixture = new TestDataBuilder(page)
  .withPromptRun('2_completed')
  .withTask('Write a professional email')
  .createPromptRun();
```

This type defines the structure of test data before it's sent to the API.

### For Tab Visibility Logic

Use `PromptRunTabData` for composables that work with subsets of prompt run data:

```typescript
import { useTabVisibility, PromptRunTabData } from '@/Composables/features/useTabVisibility';

export function useTabVisibility(
    promptRun: PromptRunTabData,
    uiComplexity: string,
    isAdmin: boolean,
): TabVisibilityResult { ... }
```

This type represents the minimal set of fields needed for UI state calculations.

### For Raw Model Relationships

Use `PromptRun` only when working with raw Eloquent model instances:

```typescript
// This is rare - avoid if possible
interface Visitor {
  promptRuns: PromptRun[];
}
```

Use this type only when you're working directly with Eloquent relationships and haven't transformed to a Resource.

## Naming Convention

- `*Resource` — Auto-generated from Laravel Resources (e.g. `PromptRunResource`)
- `*Row` — Table/list display data (e.g. `PromptRunAnalyticsRow`)
- `*Fixture` — Test data builders (e.g. `PromptRunTestFixture`)
- `*TabData` — UI-specific subsets (e.g. `PromptRunTabData`)
- `*PageResource` — Lightweight variants for page loads (internal only)

## Importing Types

### Standard Imports

```typescript
// ✅ CORRECT - Import the Resource type you need
import type { PromptRunResource } from '@/Types';

// ✅ CORRECT - Import composable with its types
import { useTabVisibility, type PromptRunTabData } from '@/Composables/features/useTabVisibility';

// ✅ CORRECT - Import workflow constants
import type { WorkflowStage } from '@/Constants/workflow';
// or via the re-export:
import type { WorkflowStage } from '@/Types';
```

### Avoid

```typescript
// ❌ WRONG - Don't import PromptRunPageResource directly
import type { PromptRunPageResource } from '@/Types';

// ❌ WRONG - Don't import from wrong location
import type { WorkflowStage } from '@/Types/resources/PromptRunResource';
```

## Common Patterns

### Checking Workflow Stage

Always use the helper functions from `@/Constants/workflow`:

```typescript
import { isProcessingStage, isFailedStage } from '@/Constants/workflow';

if (isProcessingStage(promptRun.workflowStage)) {
  // Show loading spinner
}

if (isFailedStage(promptRun.workflowStage)) {
  // Show error message
}
```

### Accessing Nested Data

Type-safe access to nested structures:

```typescript
const framework = promptRun.selectedFramework as SelectedFramework | null;
const cognitiveReqs = promptRun.cognitiveRequirements as CognitiveRequirements | null;
```

### Creating Custom Subsets

When you need only certain fields, define a local interface:

```typescript
interface PromptRunSummary {
  id: number;
  personalityType: string | null;
  workflowStage: string;
  createdAt: string;
}

function summarisePromptRun(run: PromptRunResource): PromptRunSummary {
  return {
    id: run.id,
    personalityType: run.personalityType,
    workflowStage: run.workflowStage,
    createdAt: run.createdAt,
  };
}
```

## Auto-Generated Types

The following types are **auto-generated** from Laravel:

- `PromptRunResource` — From `app/Http/Resources/PromptRunResource.php`
- `PromptRunPageResource` — From the page resource variant
- `PromptRun` — From the Eloquent model

**Do not modify these directly.** To update them:

1. Modify the PHP docblock in the Resource class
2. Run: `php artisan bp:types:generate`
3. Commit the updated TypeScript files

## Verification

Run type checking to catch type errors:

```bash
pnpm type-check
```

This should pass with zero errors. If you see TypeScript errors:

1. Check that you're using the correct type for your use case
2. Verify imports are from the correct location
3. Look for any missing `null` checks for optional fields

## Migration Guide

If you find old code using incorrect types:

| Old Pattern | New Pattern |
|---|---|
| `PromptRunData` in tabs | `PromptRunTabData` |
| `PromptRunData` in analytics | `PromptRunAnalyticsRow` |
| `PromptRunData` in tests | `PromptRunTestFixture` |
| Import `WorkflowStage` from Resource | Import from `@/Constants/workflow` |

## Troubleshooting

### "Property 'X' does not exist on type"

You're probably using the wrong type. Check the decision tree above.

### "Cannot assign PromptRun to PromptRunResource"

You need to transform the Eloquent model to a Resource. Use the Laravel Resource class.

### "Module not found for type"

Check that the import path is correct:
- Resources: `@/Types/resources/`
- Models: `@/Types/models/`
- Constants: `@/Constants/`

## See Also

- [Workflow Stages Documentation](./workflow_stages.md)
- [Frontend Architecture](./frontend-architecture.md)
