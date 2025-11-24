# Fullstory Analytics Implementation Guide

## Overview

This document outlines how to effectively use Fullstory to understand user behaviour in the AI Buddy application, identify friction points, and optimise conversion rates.

---

## Application Context

**AI Buddy** is a Laravel + Inertia.js + Vue 3 application that creates personality-tailored AI prompts based on user tasks and their 16personalities.com personality type. The app uses n8n workflows to select frameworks (SMART, RICE, COAST, etc.) and generate optimised prompts.

---

## 1. Key User Journeys to Track

### Primary Conversion Funnel

1. **Landing page view** → New visitor sees hero section
2. **Register modal opened** → Clicks "Get Started for Free"
3. **User registered** → Completes registration (email or Google OAuth)
4. **First prompt submitted** → Enters task description and submits
5. **Framework selected** → n8n workflow selects optimal framework
6. **Questions started** → User begins answering clarifying questions
7. **Questions completed** → Answers all questions (or skips some)
8. **Prompt generated successfully** → n8n workflow completes optimisation
9. **Prompt copied to clipboard** → User copies result
10. **Second prompt created** → Retention metric

### Personality Type Funnel

1. **Clicked "Add personality type" link** → From dashboard info banner
2. **Viewed profile page** → Navigates to `/profile`
3. **Selected personality type** → Chooses from dropdown (e.g., INTJ-A)
4. **Saved personality type** → Submits form
5. **Created prompt with personality** → Uses new personality data

---

## 2. Critical Interaction Points

### A. Homepage CTAs

Track which calls-to-action perform best:
- "Get Started for Free" button (new visitors)
- "Welcome back! Log in to continue" button (returning visitors)
- "Try It Now" link (authenticated users)

### B. Authentication Modals

Monitor conversion and friction:
- **Login Modal**: Form submission, "Remember me" checkbox, "Forgot password?" link, Google OAuth button
- **Register Modal**: Form submission, Google OAuth button, password visibility toggles
- **OAuth Flow**: Google sign-in success/failure rates

### C. Prompt Creation Form (`/prompt-optimizer`)

Key interactions:
- Task description textarea focus and input
- Character count tracking (minimum 10 characters required)
- Voice input button clicks and success/failure rates
- Clear button usage
- Submit button clicks (track disabled vs enabled states)

### D. Question Answering Interface

The most complex workflow:
- **Progress through questions**: Track which question number users reach
- **Answer submission**: Monitor answer length, voice input usage
- **Skip button**: Track skip rate per question number
- **Go back button**: Understand editing patterns
- **Clear answer**: See if users frequently restart answers
- **Abandonment**: Which question has highest drop-off

### E. Optimised Prompt Interactions

Post-generation behaviour:
- **Tab switches**: Track usage of Prompt, Task, Framework, Questions, Related Runs tabs
- **Copy to clipboard**: Primary conversion action
- **Edit prompt**: Inline editing usage
- **Save changes**: After editing
- **Edit task**: Creates child prompt run
- **Edit answers**: Creates child prompt run with modified answers
- **Create new**: Start fresh prompt

### F. History Page (`/prompt-optimizer-history`)

Analytics tracking:
- Table column sorting clicks
- Pagination navigation
- Per-page setting changes
- Row clicks to view details
- Filter/search usage (if added)

### G. Profile Updates (`/profile`)

User engagement with settings:
- Personality type selection from dropdown
- Trait percentage slider adjustments
- Name/email updates
- Password changes
- Account deletion attempts (critical drop-off point)

### H. Feedback Form (`/feedback/create`)

User satisfaction metrics:
- Likert scale selections (3 rating scales)
- Suggestion textarea input
- Feature checkbox selections
- "Other" feature text input
- Form completion rate

---

## 3. High-Priority Events to Track

### Authentication Events

```javascript
FS.event('User Registered', {
  method: 'email' | 'google',
  hasPersonalityType: boolean
});

FS.event('User Logged In', {
  method: 'email' | 'google',
  remember: boolean
});

FS.event('OAuth Failed', {
  provider: 'google',
  error: string
});
```

### Prompt Creation Events

```javascript
FS.event('Prompt Submitted', {
  taskLength: number,
  hasPersonalityType: boolean,
  usedVoiceInput: boolean
});

FS.event('Voice Input Used', {
  context: 'task' | 'question',
  success: boolean,
  errorType?: string
});
```

### Question Flow Events

```javascript
FS.event('Question Answered', {
  questionNumber: number,
  totalQuestions: number,
  usedVoiceInput: boolean,
  answerLength: number
});

FS.event('Question Skipped', {
  questionNumber: number,
  totalQuestions: number
});

FS.event('Went Back to Previous Question', {
  fromQuestion: number,
  toQuestion: number
});

FS.event('Question Flow Abandoned', {
  completedQuestions: number,
  totalQuestions: number,
  abandonedAt: number
});
```

### Prompt Completion Events

```javascript
FS.event('Prompt Generated Successfully', {
  framework: string,
  processingTimeSeconds: number,
  questionCount: number,
  answeredCount: number,
  skippedCount: number,
  hasPersonalityType: boolean
});

FS.event('Prompt Generation Failed', {
  stage: 'framework_selection' | 'optimization',
  errorMessage: string,
  attemptNumber: number
});

FS.event('Prompt Retried', {
  attemptNumber: number,
  previousError: string
});
```

### Prompt Interaction Events

```javascript
FS.event('Prompt Copied to Clipboard', {
  framework: string,
  promptLength: number,
  timeOnPageSeconds: number
});

FS.event('Prompt Edited', {
  changeLength: number,
  originalLength: number
});

FS.event('Child Prompt Created', {
  editType: 'task' | 'answers',
  parentFramework: string
});

FS.event('Tab Switched', {
  from: string,
  to: string,
  promptStatus: string
});
```

### Profile Events

```javascript
FS.event('Personality Type Set', {
  type: string,
  hasTraitPercentages: boolean,
  isFirstTime: boolean
});

FS.event('Personality Type Updated', {
  from: string,
  to: string
});

FS.event('Personality Type Removed');
```

### Feedback Events

```javascript
FS.event('Feedback Submitted', {
  experienceLevel: number,
  usefulness: number,
  usageIntent: number, // Likelihood to use app again (not NPS)
  hasSuggestions: boolean,
  selectedFeatureCount: number
});
```

### Navigation Events

```javascript
FS.event('Viewed Prompt History', {
  promptCount: number
});

FS.event('Viewed Profile');

FS.event('Viewed Feedback Form');
```

---

## 4. User Identification

### Anonymous Users (Pre-registration)

```javascript
// When visitor_id tracking is implemented
const visitorId = getCookie('visitor_id');

if (visitorId && !auth.user) {
  FS.identify(visitorId, {
    isGuest: true,
    hasCreatedPrompt: boolean,
    deviceType: 'mobile' | 'tablet' | 'desktop'
  });
}
```

### Authenticated Users

```javascript
// resources/js/app.ts or layout component
if (auth.user) {
  FS.identify(auth.user.id.toString(), {
    email: auth.user.email,
    displayName: auth.user.name,
    personalityType: auth.user.personality_type,
    hasTraitPercentages: auth.user.trait_percentages !== null,
    registrationDate: auth.user.created_at,
    registrationMethod: auth.user.google_id ? 'google' : 'email',
    promptCount: auth.user.prompt_runs_count,
    hasCompletedFirstPrompt: boolean,
    hasProvidedFeedback: boolean,
    // If visitor tracking implemented:
    visitorId: visitorId // Links pre-registration behaviour
  });
}
```

---

## 5. Recommended Segments

Create these segments in Fullstory for targeted analysis:

### User Lifecycle Segments

1. **New Users** - Registered < 7 days ago
2. **Returning Users** - Visited 2+ times
3. **Power Users** - Created 5+ completed prompts
4. **Single Prompt Users** - Created 1 prompt, never returned
5. **Churned Users** - Last visit > 30 days ago

### Feature Usage Segments

6. **Users Without Personality Type** - Profile incomplete
7. **Voice Input Users** - Used voice at least once
8. **Question Flow Abandonors** - Started questions but didn't complete
9. **Error Encountered Users** - Experienced failed prompt generation
10. **Feedback Providers** - Submitted feedback form

### Technical Segments

11. **Mobile Users** - Device type = mobile
12. **Desktop Users** - Device type = desktop
13. **Google OAuth Users** - Registration method = google
14. **Email Registration Users** - Registration method = email

### Conversion Segments

15. **Registered but No Prompts** - Signed up but never submitted task
16. **Completed Prompt but Never Copied** - Generated result but didn't use it
17. **Multi-Prompt Creators** - Created 3+ prompts (engaged users)

---

## 6. Session Replay Focus Areas

Prioritise watching sessions for these scenarios:

### High-Impact Friction Points

1. **Failed Registrations**
   - Watch what validation errors appear
   - See if users struggle with password requirements
   - Check OAuth redirect issues

2. **Abandoned Question Flows**
   - Which question causes drop-off?
   - Do users rage-click disabled buttons?
   - Is progress indicator clear enough?

3. **Error Recovery Attempts**
   - How do users react to failed prompt generation?
   - Do they understand the retry button?
   - What do they do after seeing error messages?

4. **Voice Input Struggles**
   - Browser permission denial handling
   - Transcription quality issues
   - Mobile vs desktop differences

5. **Mobile Question Answering**
   - Textarea usability on mobile keyboards
   - Button hit targets
   - Modal form filling difficulty

### Feature Discovery

6. **Tab Navigation Patterns**
   - Do users find the tabs on prompt view?
   - Which tabs get most attention?
   - Excessive tab switching (confusion indicator)

7. **History Page Interactions**
   - Do users realise columns are sortable?
   - Pagination confusion on mobile
   - Per-page setting usage

8. **Personality Type Selection Process**
   - Do users know their type?
   - Decision time for dropdown selection
   - Confusion about -A/-T suffix

---

## 7. Heatmaps & Clickmaps

Use Fullstory's heatmaps to understand:

### Homepage
- **CTA button performance**: Register vs Login click rates
- **Feature card engagement**: Which features get most attention
- **Scroll depth**: How far users read before converting

### Prompt Form
- **Voice button visibility**: Is it discovered?
- **Clear button usage**: How often do users restart?
- **Manual input vs voice preference**

### Question Page
- **Button hierarchy**: Answer vs Skip vs Back usage
- **Progress bar glances**: Eye tracking on progress indicator
- **Voice input adoption per question**

### Optimised Prompt View
- **Tab click distribution**: Which information is most valuable
- **Copy button discovery**: Time to find copy action
- **Edit button visibility**: Inline editing usage

### Profile Page
- **Section engagement**: Which profile sections get edited
- **Personality dropdown interaction**: Scroll behaviour in long dropdown
- **Trait slider usage**: How many users set trait percentages

### History Table
- **Column click rates**: Which columns get sorted
- **Row click patterns**: Users clicking rows vs specific cells
- **Pagination vs per-page preference**

---

## 8. Specific Questions to Answer

### User Behaviour Questions

- Do users with personality types complete prompts more often than those without?
- What's the average time to complete the question flow?
- Which frameworks have the highest/lowest completion rates?
- Do mobile users struggle more with voice input than desktop users?
- How many questions do users typically skip vs answer?

### Conversion Optimisation Questions

- Where do users drop off in the registration flow?
- What percentage of new users set a personality type within first session?
- How many prompts does the average user create?
- What's the return rate after first prompt creation?
- At which question number do most users abandon the flow?

### Feature Usage Questions

- What's the voice input adoption rate?
- Which tabs on prompt view get most/least traffic?
- Do users prefer sorting history by date, status, or framework?
- How often do users create child prompts (edit task/answers)?
- What percentage of users ever visit the history page?

### Error Analysis Questions

- What's the failure rate for prompt generation by framework?
- Which question numbers correlate with highest abandonment?
- What's the voice transcription error rate?
- How often do n8n workflows timeout?
- Do users retry after errors or leave the site?

### Mobile vs Desktop Questions

- Completion rate differences by device type?
- Voice input success rate on mobile vs desktop?
- Question answering time differences?
- Modal form conversion rates on mobile vs desktop?
- Which device type has higher retry rates after errors?

### Personality Type Impact

- Do users with personality types create more prompts?
- Which personality types use the service most frequently?
- Do trait percentages correlate with completion rates?
- How long after registration do users add personality type?

---

## 9. Implementation Locations

### Base Configuration

**File**: `resources/views/app.blade.php`

Already configured with production-only loading:
```blade
@production
<!-- Fullstory Analytics -->
<script>
  window['_fs_host'] = 'fullstory.com';
  window['_fs_script'] = 'edge.fullstory.com/s/fs.js';
  window['_fs_org'] = 'o-2442T0-na1';
  window['_fs_namespace'] = 'FS';
  // ... Fullstory snippet
</script>
@endproduction
```

### Page Navigation Tracking

**File**: `resources/js/app.ts`

```typescript
import { router } from '@inertiajs/vue3';

// Track Inertia page navigation
router.on('navigate', (event) => {
  if (window.FS) {
    window.FS('trackEvent', {
      name: 'Page Viewed',
      properties: {
        path: event.detail.page.url,
        component: event.detail.page.component
      }
    });
  }
});
```

### Component-Level Events

**Example**: `resources/js/Pages/PromptOptimizer/Index.vue`

```typescript
const submit = () => {
  if (window.FS) {
    window.FS('trackEvent', {
      name: 'Prompt Submitted',
      properties: {
        taskLength: form.taskDescription.length,
        hasPersonalityType: !!user.value?.personality_type,
        usedVoiceInput: voiceWasUsed.value
      }
    });
  }

  form.post(route('prompt-optimizer.store'));
};
```

**Example**: `resources/js/Components/PromptOptimizer/QuestionAnsweringForm.vue`

```typescript
const emit = defineEmits<Emits>();

const handleSubmit = () => {
  if (window.FS) {
    window.FS('trackEvent', {
      name: 'Question Answered',
      properties: {
        questionNumber: props.currentQuestionNumber,
        totalQuestions: props.totalQuestions,
        usedVoiceInput: voiceUsed.value,
        answerLength: props.answer.length
      }
    });
  }

  emit('submit');
};
```

### Backend Integration (Optional)

**File**: `app/Http/Controllers/Auth/RegisteredUserController.php`

```php
use Illuminate\Support\Facades\Log;

public function store(Request $request)
{
    // ... create user ...

    // Log event for correlation with Fullstory
    Log::info('User registered', [
        'user_id' => $user->id,
        'method' => $request->has('google_id') ? 'google' : 'email',
        'fullstory_event' => 'User Registered'
    ]);

    // Could also use Fullstory server-side API here
}
```

---

## 10. TypeScript Declarations

**File**: `resources/js/types/global.d.ts`

Add Fullstory types:

```typescript
interface Window {
  FS?: {
    (method: 'trackEvent', eventData: {
      name: string;
      properties: Record<string, any>;
    }): void;

    identify(uid: string | number, customVars?: Record<string, any>): void;
    setUserVars(customVars: Record<string, any>): void;
    event(eventName: string, properties?: Record<string, any>): void;
    anonymize(): void;
    shutdown(): void;
    restart(): void;
  };
}
```

---

## Summary

Fullstory provides invaluable insights for AI Buddy's complex, multi-stage workflow. Focus tracking on:

1. **Multi-step prompt creation flow** (highest friction potential)
2. **Voice input feature** (innovative but potentially problematic)
3. **Real-time processing states** (user patience/abandonment)
4. **Mobile experience** (modals, tables, voice input)
5. **Personality type adoption** (core value proposition)

The application has clear conversion funnels, multiple potential drop-off points, and advanced features (voice input, child prompt runs, tab navigation) that require careful UX monitoring. Fullstory's session replay and event tracking will be essential for understanding user behaviour, identifying friction, and optimising conversion rates at each stage of the journey.
