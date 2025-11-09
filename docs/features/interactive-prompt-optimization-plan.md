# Interactive Prompt Optimisation with Framework Selection

**Date**: 2025-11-04
**Status**: Approved - Ready for Implementation
**Complexity**: High
**Technology**: Laravel Reverb (WebSockets)

## Overview

This document outlines the plan to transform the current single-step prompt optimisation into an interactive multi-step process where:
1. The system determines the most suitable prompt framework
2. Users are asked framework-specific clarifying questions (3-5 questions)
3. Questions are presented one-at-a-time with skip functionality
4. Real-time updates via Laravel Reverb WebSockets
5. All Q&A data is stored and displayed in history

## Key Decisions (Approved)

✅ **Framework Guide**: Use concise summary matrix (~150 lines) instead of full guide (581 lines)
- Reduces token costs by ~75%
- Faster LLM processing
- Still provides sufficient context

✅ **Question Count**: 3-5 questions per flow
- Flexible based on task complexity
- Balance between depth and user fatigue

✅ **Skip Functionality**: Yes - users can skip any question
- Optional questions don't block progress
- Skipped questions marked in database and UI

✅ **Real-time Updates**: Laravel Reverb WebSockets
- Instant notifications when processing completes
- Better UX than polling
- Redis-backed for scalability

❌ **Progress Saving**: Not needed at this stage
- Simplified implementation
- Auto-save to localStorage as backup only

❌ **Framework Override**: Not needed at this stage
- System-selected framework only
- Can be added in future phase

❌ **Old Direct Flow**: Removed entirely
- New interactive flow only
- Simplifies codebase

✅ **Real-time Implementation**: Laravel Reverb WebSockets
- No polling required
- Private channels per prompt run
- Events: FrameworkSelected, PromptOptimizationCompleted

## Current State Analysis

### Existing n8n Workflow
- **Flow**: Webhook → Validate Input → Call LLM API → Format Response → Respond to Webhook
- **Input**: `personality_type`, `trait_percentages`, `task_description`, `prompt_run_id`
- **Output**: `optimized_prompt`, `model_used`, `tokens_used`
- **Limitation**: Single-pass, no interactivity

### Current Database Schema
```sql
prompt_runs:
  - id
  - user_id
  - personality_type
  - trait_percentages (json)
  - task_description
  - optimized_prompt
  - n8n_request_payload (json)
  - n8n_response_payload (json)
  - status
  - error_message
  - completed_at
  - timestamps
```

### Current User Flow
1. User visits `/prompt-optimizer`
2. User fills single form (personality type, optional traits, task description)
3. Submit triggers n8n webhook
4. User redirected to `/prompt-optimizer/{promptRun}` to see result
5. User can view history at `/prompt-optimizer-history`

## Proposed Architecture

### High-Level Flow

```
1. Initial Submission (existing)
   ↓
2. Framework Selection (NEW)
   ↓
3. Question Generation (NEW)
   ↓
4. Interactive Q&A Loop (NEW)
   ↓
5. Final Prompt Generation (enhanced)
```

## Detailed Design

### Phase 1: Framework Selection

#### New n8n Workflow: Framework Selector
**Webhook**: `/webhook/select-framework`

**Input**:
- `prompt_run_id`
- `personality_type`
- `trait_percentages`
- `task_description`

**Processing**:
1. Call LLM with system prompt that analyses the task
2. LLM determines best framework based on:
   - Task complexity
   - Output type needed
   - Personality type considerations
   - Guidance from `prompt_frameworks_guide.md`

**LLM Prompt Structure**:
```
System: You are an expert at selecting the most appropriate prompt engineering framework.

Given a personality type, task description, and available frameworks, determine:
1. Which framework is most suitable
2. Why this framework was chosen
3. 3-5 clarifying questions specific to this framework

Refer to the comprehensive framework guide to make your selection.

Output as JSON:
{
  "framework": "FRAMEWORK_NAME",
  "reasoning": "Explanation of why this framework was chosen",
  "questions": [
    {
      "id": 1,
      "question": "Question text",
      "framework_component": "Component this relates to (e.g., 'Reach' for RICE)",
      "guidance": "What kind of answer we're looking for"
    }
  ]
}
```

**Output**:
- `framework`: Selected framework name
- `reasoning`: Explanation text
- `questions`: Array of clarifying questions with metadata

#### Laravel API Endpoint
**Route**: `POST /api/prompt-optimizer/{promptRun}/select-framework`

**Controller Action**: `PromptOptimizerController@selectFramework`

**Responsibilities**:
1. Validate prompt run exists and belongs to user
2. Call n8n webhook for framework selection
3. Store framework and questions in database
4. Return framework info and first question

### Phase 2: Database Schema Changes

#### New Migration: Add Framework and Q&A Fields

```php
Schema::table('prompt_runs', function (Blueprint $table) {
    // Framework selection
    $table->string('selected_framework')->nullable()->after('task_description');
    $table->text('framework_reasoning')->nullable()->after('selected_framework');

    // Questions and answers
    $table->json('framework_questions')->nullable()->after('framework_reasoning');
    $table->json('clarifying_answers')->nullable()->after('framework_questions');

    // Updated workflow status
    $table->string('workflow_stage')->default('submitted')->after('status');
    // Stages: submitted, framework_selected, answering_questions, generating_prompt, completed
});
```

#### Updated PromptRun Model

```php
protected $fillable = [
    // ... existing fields
    'selected_framework',
    'framework_reasoning',
    'framework_questions',
    'clarifying_answers',
    'workflow_stage',
];

protected $casts = [
    // ... existing casts
    'framework_questions' => 'array',
    'clarifying_answers' => 'array',
];

// New methods
public function getCurrentQuestion(): ?array
public function hasAnsweredAllQuestions(): bool
public function getAnsweredQuestionsCount(): int
public function getTotalQuestionsCount(): int
```

### Phase 3: Interactive Q&A Interface

#### Frontend Changes

##### New Vue Component: `QuestionFlow.vue`
Location: `resources/js/Pages/PromptOptimizer/QuestionFlow.vue`

**Features**:
- Display framework selection result with reasoning
- Show progress indicator (Question 1 of 5)
- Present one question at a time
- Text area for user answer
- "Previous" and "Next" buttons
- Validation (minimum answer length)
- Auto-save answers to prevent data loss

**State Management**:
```typescript
interface QuestionFlowState {
    promptRun: PromptRun;
    currentQuestionIndex: number;
    answers: Record<number, string>;
    isSubmitting: boolean;
    framework: {
        name: string;
        reasoning: string;
    };
}
```

##### Updated `Index.vue`
- Keep existing initial form
- After submission, redirect to `/prompt-optimizer/{promptRun}/questions`

##### New Route
```php
Route::get('/prompt-optimizer/{promptRun}/questions', [
    PromptOptimizerController::class, 'questions'
])->name('prompt-optimizer.questions');
```

#### Backend API Endpoints

##### Answer Question
**Route**: `POST /api/prompt-optimizer/{promptRun}/answer-question`

**Payload**:
```json
{
    "question_id": 1,
    "answer": "User's answer text"
}
```

**Responsibilities**:
1. Validate answer (min length, not empty)
2. Store answer in `clarifying_answers` JSON column
3. Check if all questions answered
4. If complete, trigger final prompt generation
5. Return next question or completion status

##### Get Current State
**Route**: `GET /api/prompt-optimizer/{promptRun}/state`

**Response**:
```json
{
    "workflow_stage": "answering_questions",
    "framework": {
        "name": "RICE",
        "reasoning": "..."
    },
    "current_question": {
        "id": 2,
        "question": "...",
        "framework_component": "Impact",
        "guidance": "..."
    },
    "progress": {
        "answered": 1,
        "total": 5
    },
    "answers": {
        "1": "Previous answer"
    }
}
```

### Phase 4: Final Prompt Generation

#### Enhanced n8n Workflow: Generate Final Prompt
**Webhook**: `/webhook/generate-final-prompt`

**Input**:
- `prompt_run_id`
- `personality_type`
- `trait_percentages`
- `task_description`
- `selected_framework`
- `clarifying_answers` (array of Q&A pairs)

**Enhanced LLM Prompt**:
```
System: You are an expert at crafting AI prompts using the {framework} framework,
tailored to personality types.

Framework Selected: {framework}
Reasoning: {framework_reasoning}

Personality Type: {personality_type}
Trait Percentages: {formatted_traits}

Task Description: {task_description}

Clarifying Questions & Answers:
{formatted_qa_pairs}

Using the {framework} framework, create an optimised AI prompt that:
1. Incorporates all the clarifying information provided
2. Follows the structure of the {framework} framework
3. Is tailored to the {personality_type} personality type
4. Is clear, specific, and actionable

Return ONLY the optimised prompt text, without preamble or explanation.
```

#### Auto-trigger Flow
When last question is answered:
1. Laravel updates `workflow_stage` to `generating_prompt`
2. Laravel calls n8n `/webhook/generate-final-prompt`
3. n8n processes and returns optimised prompt
4. Laravel updates prompt_run with result
5. Laravel updates `workflow_stage` to `completed`
6. Frontend polls or uses websockets to detect completion
7. User redirected to result page

### Phase 5: History & Display Updates

#### Updated `Show.vue`
Display additional information:

```vue
<template>
  <div class="space-y-6">
    <!-- Existing: Optimised Prompt -->

    <!-- NEW: Framework Selection -->
    <section>
      <h3>Framework Used</h3>
      <p><strong>{{ promptRun.selected_framework }}</strong></p>
      <p class="text-sm">{{ promptRun.framework_reasoning }}</p>
    </section>

    <!-- NEW: Clarifying Questions & Answers -->
    <section>
      <h3>Clarifying Questions</h3>
      <div v-for="(qa, index) in questionsAndAnswers" :key="index">
        <div class="bg-gray-50 p-4 rounded-sm">
          <p class="font-medium">Q{{ index + 1 }}: {{ qa.question }}</p>
          <p class="text-sm text-gray-600">
            Framework Component: {{ qa.framework_component }}
          </p>
          <p class="mt-2">A: {{ qa.answer }}</p>
        </div>
      </div>
    </section>

    <!-- Existing: Input Information -->
  </div>
</template>
```

#### Updated `History.vue`
Add column showing framework used:

```vue
<td>{{ run.selected_framework || 'N/A' }}</td>
```

## Implementation Plan

### Step 0: Laravel Reverb Setup (PREREQUISITE)
**Duration**: 3-4 hours
**Status**: Required before all other steps

**Overview**: Laravel Reverb is NOT currently installed. This step sets up WebSocket infrastructure for real-time updates.

**Files to create/modify**:
- `compose.yaml` - Add Reverb service
- `.env` and `.env.example` - Add Reverb configuration variables
- `config/broadcasting.php` - Will be created by install command
- `routes/channels.php` - Will be created by install command
- `resources/js/bootstrap.ts` - Configure Laravel Echo
- `resources/js/types/global.d.ts` - Add Echo types
- `Caddyfile` - (Optional) Add WebSocket proxy
- `docs/laravel-reverb-setup.md` - Documentation

**Installation Tasks**:
1. Install Laravel Reverb package:
   ```bash
   composer require laravel/reverb
   php artisan reverb:install
   php artisan install:broadcasting
   ```

2. Install frontend packages:
   ```bash
   npm install laravel-echo pusher-js
   ```

3. Update `.env` with Reverb configuration:
   ```env
   BROADCAST_CONNECTION=reverb

   REVERB_APP_ID=your-app-id
   REVERB_APP_KEY=your-app-key
   REVERB_APP_SECRET=your-app-secret
   REVERB_HOST=localhost
   REVERB_PORT=8080
   REVERB_SCHEME=http

   VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
   VITE_REVERB_HOST="${REVERB_HOST}"
   VITE_REVERB_PORT="${REVERB_PORT}"
   VITE_REVERB_SCHEME="${REVERB_SCHEME}"
   ```

4. Add Reverb service to `compose.yaml`:
   ```yaml
   reverb:
       build:
           context: ./vendor/laravel/sail/runtimes/8.4
           dockerfile: Dockerfile
           args:
               WWWGROUP: '${WWWGROUP}'
       image: sail-8.4/app
       extra_hosts:
           - 'host.docker.internal:host-gateway'
       ports:
           - '${REVERB_PORT:-8080}:8080'
       environment:
           LARAVEL_SAIL: 1
           XDEBUG_MODE: '${SAIL_XDEBUG_MODE:-off}'
           XDEBUG_CONFIG: '${SAIL_XDEBUG_CONFIG:-client_host=host.docker.internal}'
       volumes:
           - '.:/var/www/html'
       networks:
           - sail
       depends_on:
           - pgsql
           - redis
       command: 'php artisan reverb:start --host=0.0.0.0 --port=8080 --hostname=localhost'
   ```

5. Configure Laravel Echo in `resources/js/bootstrap.ts`:
   ```typescript
   import Echo from 'laravel-echo';
   import Pusher from 'pusher-js';

   window.Pusher = Pusher;

   window.Echo = new Echo({
       broadcaster: 'reverb',
       key: import.meta.env.VITE_REVERB_APP_KEY,
       wsHost: import.meta.env.VITE_REVERB_HOST,
       wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
       wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
       forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
       enabledTransports: ['ws', 'wss'],
   });
   ```

6. Add TypeScript types in `resources/js/types/global.d.ts`:
   ```typescript
   import { AxiosInstance } from 'axios';
   import Echo from 'laravel-echo';

   declare global {
       interface Window {
           axios: AxiosInstance;
           Echo: Echo;
           Pusher: any;
       }
   }

   export {};
   ```

7. (Optional) Update `Caddyfile` for WebSocket proxy:
   ```caddyfile
   app.localhost {
       import app_common
       reverse_proxy laravel.test:80

       # WebSocket support for Reverb
       @websockets {
           header Connection *Upgrade*
           header Upgrade websocket
       }
       reverse_proxy @websockets laravel.test:8080
   }
   ```

**Testing**:
1. Start containers: `./vendor/bin/sail up -d`
2. Start Vite: `npm run dev`
3. Check Reverb is running: `./vendor/bin/sail ps` (should see reverb service)
4. Test WebSocket connection in browser console:
   ```javascript
   window.Echo.connector.pusher.connection.bind('connected', () => {
       console.log('✅ WebSocket connected!');
   });
   ```

**Success Criteria**:
- ✅ Reverb service running in Docker
- ✅ No connection errors in browser console
- ✅ Can connect to WebSocket endpoint
- ✅ `window.Echo` is defined and accessible

---

### Step 1: Database Migration
**Files to create/modify**:
- `database/migrations/YYYY_MM_DD_add_framework_fields_to_prompt_runs.php`

**Tasks**:
1. Create migration with new columns
2. Run migration
3. Update `PromptRun` model with new fillable fields and casts
4. Add helper methods to model

**Testing**:
- Create test prompt run with framework data
- Verify JSON fields store/retrieve correctly

---

### Step 2: Framework Selection n8n Workflow
**Files to create/modify**:
- `n8n/Framework Selector.json` (new workflow)
- `docs/n8n-framework-selector-setup.md` (new documentation)

**Tasks**:
1. Create new n8n workflow with webhook trigger
2. Add code node to build framework selection prompt
3. Include framework guide content in prompt context
4. Call LLM API (Anthropic Claude)
5. Parse LLM response as JSON
6. Return framework, reasoning, and questions
7. Test with various task types

**Testing**:
- Test with simple task (should select RTF or SPEAR)
- Test with complex analytical task (should select Chain of Thought)
- Test with prioritisation task (should select RICE)
- Test with creative task (should select SCAMPER or HMW)
- Verify JSON structure of response

---

### Step 3: Laravel Backend - Framework Selection
**Files to create/modify**:
- `app/Http/Controllers/PromptOptimizerController.php`
- `routes/web.php` or `routes/api.php`
- `app/Services/N8nClient.php` (add new method)

**Tasks**:
1. Add `selectFramework()` method to controller
2. Create route `POST /api/prompt-optimizer/{promptRun}/select-framework`
3. Implement N8nClient method to call framework selector webhook
4. Update prompt run with framework data
5. Return JSON response with first question

**Testing**:
- Unit test for controller method
- Test API endpoint with valid prompt run
- Test with invalid prompt run (should 404)
- Test n8n integration

---

### Step 4: Laravel Backend - Q&A Management
**Files to create/modify**:
- `app/Http/Controllers/PromptOptimizerController.php`
- `app/Http/Requests/AnswerQuestionRequest.php` (new)
- `routes/api.php`

**Tasks**:
1. Add `answerQuestion()` method to controller
2. Create validation request class
3. Create route `POST /api/prompt-optimizer/{promptRun}/answer-question`
4. Store answer in `clarifying_answers` JSON field
5. Check if all questions answered
6. If complete, trigger final prompt generation
7. Return next question or completion status

**Additional endpoints**:
- `GET /api/prompt-optimizer/{promptRun}/state` - Get current state
- `GET /prompt-optimizer/{promptRun}/questions` - Show questions page

**Testing**:
- Test answering questions in sequence
- Test answering out of order
- Test re-answering previous questions
- Test validation (empty answer, too short)
- Test completion detection

---

### Step 5: Frontend - Question Flow Interface
**Files to create/modify**:
- `resources/js/Pages/PromptOptimizer/QuestionFlow.vue` (new)
- `resources/js/Pages/PromptOptimizer/Index.vue` (modify redirect)
- `routes/web.php`

**Tasks**:
1. Create `QuestionFlow.vue` component
2. Implement state management for multi-step form
3. Add progress indicator
4. Implement question navigation (Next/Previous)
5. Add auto-save functionality
6. Implement answer submission
7. Add loading states and error handling
8. Redirect to result page when complete

**Component Features**:
- Framework display with reasoning
- Progress bar (Question X of Y)
- Question text with guidance
- Text area for answer
- Character count (optional)
- Navigation buttons
- Auto-save every 5 seconds
- "Skip" option for optional questions

**Testing**:
- Manual testing of user flow
- Test navigation between questions
- Test auto-save
- Test form validation
- Test completion and redirect

---

### Step 6: Enhanced Final Prompt Generation
**Files to create/modify**:
- `n8n/Prompt Optimiser.json` (modify existing)
- Update "Validate Input" node to handle new fields
- Update system prompt to incorporate Q&A

**Tasks**:
1. Modify webhook to accept new fields
2. Update validation node to include framework and Q&A
3. Enhance system prompt with framework context
4. Format Q&A pairs for inclusion in prompt
5. Test with various frameworks

**Enhanced prompt structure**:
```javascript
const systemPrompt = `You are an expert at crafting AI prompts using prompt engineering frameworks,
tailored to personality types based on the 16personalities.com framework.

You will be using the ${framework} framework for this prompt.

Framework Guide: ${frameworkComponentsDescription}

Given:
- Personality type and traits
- Task description
- Clarifying Q&A specific to the ${framework} framework

Create an optimised prompt that:
1. Follows the ${framework} framework structure
2. Incorporates insights from the Q&A
3. Matches the personality type's communication style
4. Is clear, specific, and actionable

Return ONLY the optimised prompt text.`;

const userMessage = `Personality Type: ${personalityType}${traitInfo}

Task Description:
${taskDescription}

Selected Framework: ${selectedFramework}
Why this framework: ${frameworkReasoning}

Clarifying Questions & Answers:
${formattedQA}

Create an optimised AI prompt using the ${selectedFramework} framework.`;
```

**Testing**:
- Test with RICE framework and prioritisation task
- Test with SMART framework and goal-setting task
- Test with BAB framework and marketing task
- Verify prompt quality improvement

---

### Step 7: Update History and Show Pages
**Files to create/modify**:
- `resources/js/Pages/PromptOptimizer/Show.vue`
- `resources/js/Pages/PromptOptimizer/History.vue`

**Tasks**:
1. Add framework display section to Show.vue
2. Add Q&A accordion/section to Show.vue
3. Add framework column to History.vue
4. Style new sections consistently
5. Handle old prompt runs without framework data

**Display Features for Show.vue**:
- Collapsible framework reasoning section
- Styled Q&A pairs with framework component labels
- Visual distinction between questions
- Responsive design

**Display Features for History.vue**:
- Framework name column
- Filter by framework (optional, future enhancement)
- Sort by date (existing)

**Testing**:
- Test with new prompt runs (with framework data)
- Test with old prompt runs (without framework data)
- Test responsive layout

---

### Step 8: Error Handling & Edge Cases
**Scenarios to handle**:

1. **LLM fails to return valid JSON**
   - Fallback: Use default framework (RTF) with generic questions
   - Log error for debugging

2. **User abandons Q&A mid-flow**
   - Save progress (auto-save handles this)
   - Allow resume from history page
   - Add "Resume" button in history

3. **n8n webhook timeout**
   - Implement retry logic
   - Show user-friendly error message
   - Allow manual retry

4. **User navigates away during generation**
   - Store workflow_stage = 'generating_prompt'
   - Continue background processing
   - Poll on history page to detect completion

5. **Framework guide context too large**
   - Extract only relevant framework details
   - Use summary of guide instead of full text
   - Optimise token usage

**Implementation**:
- Add try-catch blocks
- Implement fallback mechanisms
- Add user-facing error messages
- Create error logging

---

### Step 9: Testing & Quality Assurance

#### Unit Tests
**Files to create**:
- `tests/Unit/Models/PromptRunTest.php`
- `tests/Unit/Services/N8nClientTest.php`

**Coverage**:
- Test model helper methods
- Test n8n client framework selection
- Test answer storage logic

#### Feature Tests
**Files to create**:
- `tests/Feature/FrameworkSelectionTest.php`
- `tests/Feature/QuestionAnsweringTest.php`
- `tests/Feature/PromptGenerationTest.php`

**Coverage**:
- Test complete user flow from submission to result
- Test API endpoints
- Test data persistence

#### Manual Testing Checklist
- [ ] Submit new prompt optimisation request
- [ ] Verify framework is selected appropriately
- [ ] Answer all questions
- [ ] Verify final prompt is generated
- [ ] Check history shows all data
- [ ] Test with different personality types
- [ ] Test with different task types
- [ ] Test error scenarios
- [ ] Test on mobile devices

---

### Step 10: Documentation Updates

**Files to update**:
- `docs/QUICK_START.md` - Update user flow
- `docs/n8n-prompt-optimizer-setup.md` - Add framework selector setup
- `docs/architecture-changes.md` - Document new flow
- `README.md` - Update feature list

**New files to create**:
- `docs/n8n-framework-selector-setup.md` - Detailed n8n setup
- `docs/features/interactive-prompt-optimization.md` - Feature documentation
- `docs/api/prompt-optimizer-endpoints.md` - API reference

---

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     USER SUBMITS INITIAL FORM               │
│         (personality_type, trait_percentages, task)         │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              Laravel: PromptOptimizerController             │
│                  store() method (EXISTING)                  │
│                                                             │
│  - Create PromptRun (status: pending, stage: submitted)    │
│  - Redirect to /prompt-optimizer/{id}/questions            │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│          Laravel: PromptOptimizerController                 │
│                questions() method (NEW)                     │
│                                                             │
│  - Load PromptRun                                           │
│  - If framework not selected, call selectFramework()        │
│  - Return Inertia page with framework & first question      │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│             Laravel → n8n: Framework Selection              │
│           POST /webhook/select-framework                    │
│                                                             │
│  Input: personality_type, trait_percentages, task           │
│                                                             │
│  n8n Workflow:                                              │
│  1. Build system prompt with framework guide                │
│  2. Call LLM API (Anthropic Claude)                         │
│  3. Parse JSON response                                     │
│                                                             │
│  Output: {framework, reasoning, questions[]}                │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│               Laravel: Store Framework Data                 │
│                                                             │
│  - Update PromptRun:                                        │
│    - selected_framework                                     │
│    - framework_reasoning                                    │
│    - framework_questions                                    │
│    - workflow_stage: 'framework_selected'                   │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│         Frontend: QuestionFlow.vue - Question Loop          │
│                                                             │
│  For each question:                                         │
│  1. Display question with guidance                          │
│  2. User enters answer                                      │
│  3. Click "Next"                                            │
│  4. POST /api/prompt-optimizer/{id}/answer-question         │
│  5. Load next question                                      │
│                                                             │
│  Progress: Question X of Y                                  │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│        Laravel: PromptOptimizerController                   │
│             answerQuestion() method (NEW)                   │
│                                                             │
│  - Validate answer                                          │
│  - Store in clarifying_answers JSON field                   │
│  - Update workflow_stage: 'answering_questions'             │
│  - Check if all questions answered                          │
│  - If complete, trigger final generation                    │
│  - Return next question or completion status                │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│             When all questions answered...                  │
│                                                             │
│  - Update workflow_stage: 'generating_prompt'               │
│  - Call n8n final prompt generation                         │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│        Laravel → n8n: Final Prompt Generation               │
│       POST /webhook/generate-final-prompt                   │
│                                                             │
│  Input: All data + selected_framework + clarifying_answers  │
│                                                             │
│  n8n Workflow:                                              │
│  1. Build enhanced system prompt                            │
│  2. Format Q&A pairs                                        │
│  3. Call LLM API                                            │
│  4. Return optimised prompt                                 │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│           Laravel: Store Final Result                       │
│                                                             │
│  - Update PromptRun:                                        │
│    - optimized_prompt                                       │
│    - status: 'completed'                                    │
│    - workflow_stage: 'completed'                            │
│    - completed_at: now()                                    │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│      Frontend: Redirect to Show Page                        │
│      Display: Framework, Q&A, Optimised Prompt              │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Schema Example

After answering all questions, a complete `prompt_runs` record:

```json
{
  "id": 123,
  "user_id": 1,
  "personality_type": "INTJ-A",
  "trait_percentages": {
    "mind": 65,
    "energy": 72,
    "nature": 84,
    "tactics": 77,
    "identity": 88
  },
  "task_description": "I need to prioritise features for our MVP launch",

  "selected_framework": "RICE",
  "framework_reasoning": "RICE framework is ideal for this task because it provides a quantitative, data-driven approach to feature prioritisation. Given your INTJ personality type's preference for logical decision-making and systematic planning, RICE's structured scoring system aligns well with your analytical nature.",

  "framework_questions": [
    {
      "id": 1,
      "question": "How many users or customers will be affected by each feature you're considering?",
      "framework_component": "Reach",
      "guidance": "Provide estimates for the number of users who would benefit from or use each feature within a specific time period (e.g., per month or quarter)."
    },
    {
      "id": 2,
      "question": "What is the potential impact of each feature on user satisfaction, revenue, or business goals?",
      "framework_component": "Impact",
      "guidance": "Rate the impact on a scale (e.g., minimal, low, medium, high, massive) or use a numerical scale (0.25 to 3)."
    },
    {
      "id": 3,
      "question": "How confident are you in your estimates for reach and impact?",
      "framework_component": "Confidence",
      "guidance": "Express confidence as a percentage (e.g., 50%, 80%, 100%) based on available data and research."
    },
    {
      "id": 4,
      "question": "How much time and resources will each feature require to implement?",
      "framework_component": "Effort",
      "guidance": "Estimate in person-months, sprint points, or weeks of development time for your team."
    }
  ],

  "clarifying_answers": [
    {
      "question_id": 1,
      "answer": "Feature A would reach approximately 5,000 users per month. Feature B would reach about 2,000 users monthly. Feature C would impact around 8,000 users per month."
    },
    {
      "question_id": 2,
      "answer": "Feature A: High impact (2.5) - significantly improves core workflow. Feature B: Medium impact (1.5) - nice-to-have enhancement. Feature C: Massive impact (3.0) - addresses top customer complaint."
    },
    {
      "question_id": 3,
      "answer": "Feature A: 80% confident based on user research. Feature B: 50% confident, limited data. Feature C: 100% confident, validated by customer feedback and analytics."
    },
    {
      "question_id": 4,
      "answer": "Feature A: 2 person-months. Feature B: 1.5 person-months. Feature C: 3 person-months."
    }
  ],

  "optimized_prompt": "As a systematic, analytical thinker (INTJ), use the RICE prioritisation framework to evaluate your MVP features:\n\n**Calculate RICE Score** for each feature:\nRICE Score = (Reach × Impact × Confidence) / Effort\n\n**Feature A:**\n- Reach: 5,000 users/month\n- Impact: 2.5 (High)\n- Confidence: 80% (0.8)\n- Effort: 2 person-months\n- RICE Score: (5,000 × 2.5 × 0.8) / 2 = 5,000\n\n**Feature B:**\n- Reach: 2,000 users/month\n- Impact: 1.5 (Medium)\n- Confidence: 50% (0.5)\n- Effort: 1.5 person-months\n- RICE Score: (2,000 × 1.5 × 0.5) / 1.5 = 1,000\n\n**Feature C:**\n- Reach: 8,000 users/month\n- Impact: 3.0 (Massive)\n- Confidence: 100% (1.0)\n- Effort: 3 person-months\n- RICE Score: (8,000 × 3.0 × 1.0) / 3 = 8,000\n\n**Prioritisation Recommendation:**\n1. Feature C (RICE: 8,000) - Highest priority\n2. Feature A (RICE: 5,000) - Second priority\n3. Feature B (RICE: 1,000) - Lowest priority\n\n**Action Steps:**\n1. Begin development on Feature C immediately\n2. Schedule Feature A for next sprint\n3. De-prioritise Feature B unless resources become available\n4. Document assumptions and revisit scores quarterly\n5. Track actual metrics post-launch to validate estimates\n\nThis data-driven approach aligns with your preference for logical, systematic decision-making while ensuring maximum business impact.",

  "status": "completed",
  "workflow_stage": "completed",
  "completed_at": "2025-11-04 15:30:00",
  "created_at": "2025-11-04 15:15:00",
  "updated_at": "2025-11-04 15:30:00"
}
```

---

## Timeline Estimate

| Phase | Tasks | Estimated Time |
|-------|-------|----------------|
| **Phase 0**: Laravel Reverb Setup | Install, configure, test WebSockets | 3.5 hours |
| **Phase 1**: Database Migration | Schema changes, model updates | 2 hours |
| **Phase 1.5**: Framework Summary Matrix | Create concise guide (~150 lines) | 1 hour |
| **Phase 2**: n8n Framework Selector | New workflow, testing | 4 hours |
| **Phase 2.5**: Laravel Events | Create broadcast events, channels | 1.5 hours |
| **Phase 3**: Backend Framework Selection | API endpoint, n8n integration, events | 3 hours |
| **Phase 4**: Backend Q&A Management | API endpoints, skip support, events | 4 hours |
| **Phase 5**: Frontend Question Flow | Vue component, WebSocket listeners, skip button | 6 hours |
| **Phase 6**: Enhanced Prompt Generation | Update workflow, framework context | 3 hours |
| **Phase 7**: History & Show Updates | UI updates, skipped questions display | 2 hours |
| **Phase 8**: Error Handling | WebSocket disconnects, robustness | 2.5 hours |
| **Phase 9**: Testing | Unit, feature, manual, WebSocket testing | 4 hours |
| **Phase 10**: Documentation | Update docs, add Reverb guide | 2 hours |
| **Total** | | **38.5 hours** |

**Estimated completion**: 5 working days for solo developer

**Time Adjustments from Original Plan**:
- ✅ Added Phase 0 (Reverb): +3.5 hours
- ✅ Added Framework Matrix creation: +1 hour
- ✅ Added Laravel Events: +1.5 hours
- ✅ Added Skip functionality: Absorbed into existing phases
- ✅ Added WebSocket integration: Absorbed into existing phases
- ❌ Removed old flow maintenance: -4 hours saved
- ❌ Removed progress saving: -1.5 hours saved

**Net Change**: +5.5 hours (mainly due to WebSocket infrastructure)

---

## Risks & Mitigation

### Risk 1: LLM Framework Selection Inconsistency
**Impact**: Users get different frameworks for similar tasks
**Probability**: Medium
**Mitigation**:
- Provide very detailed framework guide to LLM
- Include examples in prompt
- Log all selections for review
- Add manual override option in future

### Risk 2: Token Costs Increase
**Impact**: Higher API costs due to multiple LLM calls
**Probability**: High
**Mitigation**:
- Use cheaper model for framework selection (Haiku)
- Use Sonnet only for final prompt generation
- Cache framework guide to reduce context size
- Monitor costs closely

### Risk 3: User Abandonment During Q&A
**Impact**: Incomplete prompt runs, user frustration
**Probability**: Medium
**Mitigation**:
- Implement auto-save
- Allow resume from history
- Keep question count reasonable (3-5 max)
- Add progress indicator
- Allow skipping optional questions

### Risk 4: Complex State Management
**Impact**: Bugs, data inconsistency
**Probability**: Medium
**Mitigation**:
- Use explicit `workflow_stage` enum
- Implement state validation
- Add comprehensive tests
- Use database transactions where appropriate

### Risk 5: n8n Workflow Complexity
**Impact**: Difficult to debug, maintain
**Probability**: Medium
**Mitigation**:
- Keep workflows modular
- Add detailed comments in code nodes
- Document thoroughly
- Create separate workflows instead of one complex workflow

---

## Future Enhancements

### Phase 2 Features (Post-MVP)
1. **Framework Override**: Allow users to manually select framework
2. **Question Editing**: Let users refine questions before answering
3. **Framework Templates**: Pre-saved Q&A for common scenarios
4. **Collaborative Prompts**: Multiple users answer questions
5. **Prompt Versioning**: Edit and regenerate based on feedback
6. **Analytics Dashboard**: Track which frameworks work best
7. **Export Options**: PDF, Markdown export of prompts
8. **Prompt Feedback**: "Was this helpful?" rating system

### Technical Improvements
1. **WebSocket Integration**: Real-time updates instead of polling
2. **Redis Caching**: Cache framework guide, user sessions
3. **Queue System**: Background processing for long-running tasks
4. **Rate Limiting**: Prevent API abuse
5. **Multi-language Support**: Internationalisation

---

## Success Metrics

### User Experience
- **Completion Rate**: >80% of users who start Q&A complete it
- **Time to Completion**: <5 minutes from start to final prompt
- **User Satisfaction**: >4.0/5.0 rating for prompt quality

### Technical Performance
- **API Response Time**: <3s for framework selection
- **Final Prompt Generation**: <10s end-to-end
- **Error Rate**: <1% of all requests

### Business Metrics
- **Feature Usage**: >50% of users use new interactive flow vs old direct flow
- **Prompt Quality**: User feedback indicates improvement over direct flow
- **Token Efficiency**: Cost per prompt <$0.10

---

## Questions for Review (ANSWERED)

1. **Framework Guide Inclusion**: ✅ Use concise summary matrix (~150 lines)
   - Decision: Summarise to save ~75% token costs
   - Claude already knows frameworks from training

2. **Question Count**: ✅ 3-5 questions per flow
   - Decision: Flexible based on task complexity
   - Good balance between depth and user experience

3. **Optional Questions**: ✅ Yes, all questions skippable
   - Decision: Add "Skip" button to every question
   - Track skipped questions in database

4. **Progress Saving**: ❌ Not at this stage
   - Decision: Keep it simple
   - Auto-save to localStorage as backup only

5. **Framework Override**: ❌ Not at this stage
   - Decision: System selection only
   - Can add in future phase if needed

6. **Old vs New Flow**: ❌ Remove old flow entirely
   - Decision: New interactive flow only
   - Simplifies codebase and maintenance

7. **Real-time Updates**: ✅ Laravel Reverb WebSockets
   - Decision: Best UX, instant updates
   - Redis-backed for scalability
   - Private channels per prompt run

---

## Approval & Next Steps

**Status**: ✅ APPROVED - Ready for Implementation

**Implementation Order**:
1. ✅ Phase 0: Laravel Reverb Setup (FIRST - prerequisite)
2. Phase 1: Database Migration
3. Phase 1.5: Create Framework Summary Matrix
4. Phase 2: n8n Framework Selector
5. Phase 2.5: Laravel Events for Broadcasting
6. Phase 3: Backend Framework Selection
7. Phase 4: Backend Q&A Management
8. Phase 5: Frontend Question Flow with WebSockets
9. Phase 6: Enhanced Prompt Generation
10. Phase 7: History & Show Updates
11. Phase 8: Error Handling
12. Phase 9: Testing
13. Phase 10: Documentation

**Start Date**: 2025-11-04
**Estimated Completion**: 5 working days (~38.5 hours)

---

## Appendix A: Example Framework Selection Prompts

### System Prompt for Framework Selection
```
You are an expert at selecting the most appropriate prompt engineering framework from a comprehensive list of 57 frameworks.

Your task is to analyse the user's task description and personality type, then select the SINGLE most appropriate framework and generate 3-5 clarifying questions.

**Available Framework Categories**:
1. Structured Clarity (CRISPE, RELIC, RTF)
2. Iterative Refinement (RACEF, Chain of Destiny)
3. Decision-Making (RICE, SMART, COAST)
4. Analytical (Chain of Thought, Tree of Thought, FOCUS)
5. Storytelling (BAB, CAR-PAR-STAR, Challenge-Solution-Benefit)
6. Specialised Purpose (BLOG, APE, TAG)
7. Creative (SCAMPER, HMW, Imagine, What If)
8. Educational (Bloom's Taxonomy, ELI5, TQA)
9. Communication (RACE, ERA, CARE)
10. Strategic Analysis (3Cs, 4S, GOPA)

**Selection Criteria**:
- Task complexity
- Output type needed
- Personality type considerations
- Framework strengths/weaknesses
- User expertise level

**Consider Personality Type**:
- **Analysts (INTJ, INTP, ENTJ, ENTP)**: Prefer logical, structured frameworks
- **Diplomats (INFJ, INFP, ENFJ, ENFP)**: Benefit from empathetic, values-based frameworks
- **Sentinels (ISTJ, ISFJ, ESTJ, ESFJ)**: Work well with clear, practical frameworks
- **Explorers (ISTP, ISFP, ESTP, ESFP)**: Respond to flexible, hands-on frameworks

You MUST respond with ONLY valid JSON in this exact format:
{
  "framework": "FRAMEWORK_NAME",
  "reasoning": "2-3 sentences explaining why this framework is optimal for this task and personality type",
  "questions": [
    {
      "id": 1,
      "question": "Specific question text",
      "framework_component": "Which component of the framework this relates to",
      "guidance": "What kind of information you're seeking"
    }
  ]
}

**Rules**:
- Select exactly ONE framework
- Generate 3-5 questions (no more, no less)
- Questions must be specific to the chosen framework
- Each question should elicit information needed for that framework's components
- Keep questions clear and concise
- Provide helpful guidance for each question
- NO preamble, NO explanation outside the JSON
- Respond ONLY with valid JSON
```

### Example User Message
```
Personality Type: INTJ-A
Trait Percentages:
  - Mind (Introversion/Extraversion): 65%
  - Energy (Intuitive/Observant): 72%
  - Nature (Thinking/Feeling): 84%
  - Tactics (Judging/Prospecting): 77%
  - Identity (Assertive/Turbulent): 88%

Task Description:
I need to write a blog post about the benefits of remote work for software developers. The audience is CTOs and engineering managers at mid-sized tech companies. The post should be persuasive and backed by data.

Select the most appropriate prompt engineering framework and generate clarifying questions.
```

### Expected Response
```json
{
  "framework": "PEE Framework",
  "reasoning": "The PEE (Point, Evidence, Explanation) framework is ideal for this persuasive blog post. Given your INTJ personality's strength in logical reasoning (84% Thinking) and systematic planning (77% Judging), this framework's structure of making claims, supporting with evidence, and explaining significance aligns perfectly. It ensures rigorous argumentation needed to persuade CTOs and engineering managers.",
  "questions": [
    {
      "id": 1,
      "question": "What are the 3-5 main points or benefits of remote work you want to emphasise in your blog post?",
      "framework_component": "Point",
      "guidance": "List specific, clear claims you want to make (e.g., 'Remote work increases developer productivity by 20%', 'Remote work improves work-life balance')"
    },
    {
      "id": 2,
      "question": "What data, studies, or evidence do you have to support each of your main points?",
      "framework_component": "Evidence",
      "guidance": "Provide specific statistics, research findings, case studies, or examples. Include sources if available (e.g., 'Stanford study showed...', 'GitLab's 2023 remote work report found...')"
    },
    {
      "id": 3,
      "question": "For each point, why does this matter specifically to CTOs and engineering managers?",
      "framework_component": "Explanation",
      "guidance": "Explain the significance and implications for the target audience. What business outcomes does this lead to? How does this affect their decision-making?"
    },
    {
      "id": 4,
      "question": "What counterarguments or concerns might CTOs have about remote work, and how would you address them?",
      "framework_component": "Evidence & Explanation",
      "guidance": "Anticipate objections (e.g., 'communication challenges', 'team cohesion') and provide evidence-based responses"
    }
  ]
}
```

---

## Appendix B: n8n Workflow Nodes Structure

### Framework Selector Workflow

```
1. [Webhook Trigger]
   - POST /webhook/select-framework
   - Receives: prompt_run_id, personality_type, trait_percentages, task_description

2. [Code: Prepare Framework Selection Prompt]
   - Load framework guide summary
   - Format personality data
   - Build system prompt
   - Build user message
   - Output: system_prompt, user_message

3. [HTTP Request: Call Anthropic Claude API]
   - Model: claude-3-5-haiku-20241022 (cheaper, faster)
   - Max tokens: 1500
   - Temperature: 0.3 (more deterministic)
   - Send system_prompt and user_message

4. [Code: Parse and Validate Response]
   - Extract JSON from response
   - Validate JSON structure
   - Ensure framework is valid
   - Ensure 3-5 questions present
   - Handle errors gracefully

5. [Respond to Webhook]
   - Return: {framework, reasoning, questions}
```

### Enhanced Final Prompt Generator Workflow

```
1. [Webhook Trigger]
   - POST /webhook/generate-final-prompt
   - Receives: All previous data + selected_framework + clarifying_answers

2. [Code: Prepare Enhanced Prompt]
   - Load framework-specific guidance
   - Format trait percentages
   - Format Q&A pairs with framework components
   - Build enhanced system prompt
   - Build comprehensive user message
   - Output: system_prompt, user_message

3. [HTTP Request: Call Anthropic Claude API]
   - Model: claude-3-5-sonnet-20241022 (better quality)
   - Max tokens: 2500
   - Temperature: 0.5 (balanced creativity)
   - Send enhanced prompts

4. [Code: Format Final Response]
   - Extract optimised prompt
   - Get token usage
   - Get model used
   - Format response

5. [Respond to Webhook]
   - Return: {optimized_prompt, model_used, tokens_used}
```

---

**End of Plan**
