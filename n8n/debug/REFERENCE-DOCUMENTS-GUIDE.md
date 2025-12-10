# Reference Documents Guide

The debug system now automatically loads reference documents for the n8n workflow. Here's where they're stored and how to use them.

## Document Locations

The reference documents are located in: **`resources/reference_documents/`**

```
resources/reference_documents/
├── framework_taxonomy.md          ← Framework Taxonomy document
├── personality_calibration.md     ← Personality Calibration document
└── question_bank.md               ← Question Bank document
```

## What Each Document Contains

### 1. framework_taxonomy.md
**Purpose**: Defines all task categories, cognitive requirements, and how they relate.

**Used by**: The workflow JavaScript uses this to:
- Classify user tasks into primary categories (DECISION, STRATEGY, ANALYSIS, etc.)
- Identify cognitive requirements (EMPATHY, VISION, DETAIL, etc.)
- Map task types to appropriate requirements

**Content includes**:
- Task categories and their trigger words
- Cognitive requirements and their traits
- Task → Requirements mapping table

**File size**: ~25KB (compressed version)

### 2. personality_calibration.md
**Purpose**: Rules for adjusting prompts based on personality type (16personalities.com).

**Used by**: The workflow JavaScript uses this to:
- Apply personality adjustments to prompt style
- Determine Task-Trait Alignment (AMPLIFY, COUNTERBALANCE, NEUTRAL)
- Adjust question phrasing based on personality
- Handle missing personality data gracefully

**Content includes**:
- 16Personalities framework overview (I/E, S/N, T/F, J/P, A/T)
- Percentage interpretation (borderline vs. strong preference)
- Personality tiers (Full data, Partial data, No data)
- Task-specific personality adjustments

**File size**: ~20KB

### 3. question_bank.md
**Purpose**: Library of clarifying questions organized by task category.

**Used by**: The workflow JavaScript uses this to:
- Generate task-specific clarifying questions
- Adjust question phrasing based on personality
- Select appropriate question count based on complexity
- Avoid duplicate questions when pre-analysis context exists

**Content includes**:
- Universal questions (U1-U6) with personality variants
- Task-specific questions by category
- Question selection guidelines
- Personality-adjusted phrasing options

**File size**: ~15KB

## How the System Uses These Documents

### During Workflow Execution

1. The debug system loads all three reference documents when you click "Execute"
2. The documents are provided to the workflow JavaScript via the mock `$('Load Reference Documents').first().json` object
3. The JavaScript accesses them like this:

```javascript
const referenceData = $('Load Reference Documents').first().json;
const frameworkDoc = referenceData.framework_taxonomy_doc;
const questionDoc = referenceData.question_bank_doc;
const personalityDoc = referenceData.personality_calibration_doc;
```

### Reference Document Structure

Each document is wrapped in this structure:

```json
{
  "framework_taxonomy_doc": {
    "content": "# Framework Taxonomy\n\n..."
  },
  "personality_calibration_doc": {
    "content": "# Personality Calibration\n\n..."
  },
  "question_bank_doc": {
    "content": "# Question Bank\n\n..."
  }
}
```

The `content` field contains the full markdown document as a string.

## Current Status

✓ **Framework Taxonomy** - Located at `resources/reference_documents/framework_taxonomy.md`
✓ **Personality Calibration** - Located at `resources/reference_documents/personality_calibration.md`
✓ **Question Bank** - Located at `resources/reference_documents/question_bank.md`

All documents already exist in the project! The debug system is now configured to load them automatically.

## Troubleshooting

### If documents are not found

Check that the files exist:
```bash
ls -la resources/reference_documents/
```

Expected output:
```
framework_taxonomy.md
personality_calibration.md
question_bank.md
```

### If documents are empty

Open each file and verify it has content:
```bash
wc -l resources/reference_documents/framework_taxonomy.md
wc -l resources/reference_documents/personality_calibration.md
wc -l resources/reference_documents/question_bank.md
```

Each file should be at least 100+ lines.

### If workflow execution still fails

Check the error message in the debug interface. Common issues:
- Reference documents referenced but not found → Check file paths above
- Missing reference data → Verify documents are not empty
- JavaScript error → May indicate workflow code needs adjustment for mock environment

## For Production n8n Workflows

When running the real n8n workflow (not the debug system):

1. The actual n8n instance loads reference documents from its own internal storage or nodes
2. The documents are provided to the "Prepare Prompt" node via the "Load Reference Documents" node
3. You don't need to do anything special — n8n handles it automatically

The debug system mimics this behaviour by loading the same reference documents and providing them in the same structure.

## Next Steps

1. Visit `https://app.localhost/workflow/1` in your browser
2. You should see:
   - Left column: Workflow input from `n8n/workflow_1_input.json`
   - Middle column: JavaScript code from the "Prepare Prompt" node
   - Right column: Empty (until you execute)
3. Click **Execute** button
4. The workflow JavaScript will now have access to all three reference documents
5. Results should appear in the output column

## Questions?

If reference documents aren't loading correctly:
- Check file paths in `app/Http/Controllers/DebugN8nController.php` → `loadReferenceDocuments()` method
- Verify files exist and have content
- Check Laravel logs: `php artisan pail`
