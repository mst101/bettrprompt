# Laravel API Specification

## Overview

This document specifies the Laravel API endpoints required to support the n8n prompt framework workflows.

---

## Environment Configuration

Add to your `.env` file:

```env
# n8n Configuration
N8N_BASE_URL=https://your-n8n-instance.com
N8N_WEBHOOK_SECRET=your-secret-key
```

Add to n8n environment variables:

```
LARAVEL_BASE_URL=https://your-laravel-app.com
```

---

## Part 1: Reference Document Endpoints

These endpoints serve the reference documents to n8n workflows.

### GET /api/reference/framework-taxonomy

**Purpose**: Returns the framework taxonomy reference document

**Response**:
```json
{
  "success": true,
  "content": "# Framework Taxonomy Reference Document\n\n## Purpose\n...[full markdown content]...",
  "last_updated": "2024-01-15T10:30:00Z"
}
```

**Laravel Implementation**:
```php
// routes/api.php
Route::prefix('reference')->group(function () {
    Route::get('framework-taxonomy', [ReferenceController::class, 'frameworkTaxonomy']);
    Route::get('personality-calibration', [ReferenceController::class, 'personalityCalibration']);
    Route::get('question-bank', [ReferenceController::class, 'questionBank']);
    Route::get('prompt-templates', [ReferenceController::class, 'promptTemplates']);
});
```

```php
// app/Http/Controllers/ReferenceController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ReferenceController extends Controller
{
    /**
     * Cache duration in seconds (1 hour)
     */
    private const CACHE_DURATION = 3600;

    public function frameworkTaxonomy(): JsonResponse
    {
        return $this->getReference('framework_taxonomy.md');
    }

    public function personalityCalibration(): JsonResponse
    {
        return $this->getReference('personality_calibration.md');
    }

    public function questionBank(): JsonResponse
    {
        return $this->getReference('question_bank.md');
    }

    public function promptTemplates(): JsonResponse
    {
        return $this->getReference('prompt_templates.md');
    }

    private function getReference(string $filename): JsonResponse
    {
        $cacheKey = "reference_doc_{$filename}";
        
        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filename) {
            $path = "reference_documents/{$filename}";
            
            if (!Storage::exists($path)) {
                return null;
            }
            
            return [
                'content' => Storage::get($path),
                'last_updated' => Storage::lastModified($path)
            ];
        });

        if ($data === null) {
            return response()->json([
                'success' => false,
                'error' => "Reference document not found: {$filename}"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'content' => $data['content'],
            'last_updated' => date('c', $data['last_updated'])
        ]);
    }
}
```

### GET /api/reference/personality-calibration

**Purpose**: Returns the personality calibration reference document

**Response**: Same structure as framework-taxonomy

### GET /api/reference/question-bank

**Purpose**: Returns the question bank reference document

**Response**: Same structure as framework-taxonomy

### GET /api/reference/prompt-templates

**Purpose**: Returns the prompt templates reference document

**Response**: Same structure as framework-taxonomy

---

## Part 2: Workflow Trigger Endpoints

These endpoints are called by your Laravel application to trigger n8n workflows.

### Service Class

```php
// app/Services/PromptFrameworkService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PromptFrameworkService
{
    private string $n8nBaseUrl;
    
    public function __construct()
    {
        $this->n8nBaseUrl = config('services.n8n.base_url');
    }

    /**
     * Workflow 1: Analyse task and generate clarifying questions
     */
    public function analyseTask(
        string $taskDescription,
        ?string $personalityType = null,
        ?array $traitPercentages = null
    ): array {
        $payload = [
            'task_description' => $taskDescription,
            'personality_type' => $personalityType,
            'trait_percentages' => $traitPercentages,
        ];

        try {
            $response = Http::timeout(60)
                ->post("{$this->n8nBaseUrl}/webhook/api/n8n/webhook/analysis", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Workflow 1 failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Analysis workflow failed'
            ];
        } catch (\Exception $e) {
            Log::error('Workflow 1 exception', ['message' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Workflow 2: Generate optimised prompt
     */
    public function generatePrompt(
        array $taskClassification,
        array $selectedFramework,
        array $alternativeFrameworks,
        string $personalityTier,
        array $personalityAdjustmentsPreview,
        string $originalTaskDescription,
        ?string $personalityType,
        ?array $traitPercentages,
        array $questionAnswers
    ): array {
        $payload = [
            'task_classification' => $taskClassification,
            'selected_framework' => $selectedFramework,
            'alternative_frameworks' => $alternativeFrameworks,
            'personality_tier' => $personalityTier,
            'personality_adjustments_preview' => $personalityAdjustmentsPreview,
            'original_task_description' => $originalTaskDescription,
            'personality_type' => $personalityType,
            'trait_percentages' => $traitPercentages,
            'question_answers' => $questionAnswers,
        ];

        try {
            $response = Http::timeout(90)
                ->post("{$this->n8nBaseUrl}/webhook/api/n8n/webhook/generate", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Workflow 2 failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Generation workflow failed'
            ];
        } catch (\Exception $e) {
            Log::error('Workflow 2 exception', ['message' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
```

### Controller for Vue/Inertia Frontend

```php
// app/Http/Controllers/PromptBuilderController.php
<?php

namespace App\Http\Controllers;

use App\Services\PromptFrameworkService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromptBuilderController extends Controller
{
    public function __construct(
        private PromptFrameworkService $promptService
    ) {}

    /**
     * Show the prompt builder page
     */
    public function index(): Response
    {
        return Inertia::render('PromptBuilder/Index');
    }

    /**
     * Step 1: Analyse task and get clarifying questions
     */
    public function analyse(Request $request)
    {
        $validated = $request->validate([
            'task_description' => 'required|string|min:10|max:5000',
            'personality_type' => 'nullable|string|regex:/^[EI][NS][TF][JP]-[AT]$/',
            'trait_percentages' => 'nullable|array',
            'trait_percentages.I_E' => 'nullable|integer|min:0|max:100',
            'trait_percentages.S_N' => 'nullable|integer|min:0|max:100',
            'trait_percentages.T_F' => 'nullable|integer|min:0|max:100',
            'trait_percentages.J_P' => 'nullable|integer|min:0|max:100',
            'trait_percentages.A_T' => 'nullable|integer|min:0|max:100',
        ]);

        $result = $this->promptService->analyseTask(
            $validated['task_description'],
            $validated['personality_type'] ?? null,
            $validated['trait_percentages'] ?? null
        );

        return response()->json($result);
    }

    /**
     * Step 2: Generate the optimised prompt
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'task_classification' => 'required|array',
            'selected_framework' => 'required|array',
            'alternative_frameworks' => 'array',
            'personality_tier' => 'required|string|in:full,partial,none',
            'personality_adjustments_preview' => 'array',
            'original_task_description' => 'required|string',
            'personality_type' => 'nullable|string',
            'trait_percentages' => 'nullable|array',
            'question_answers' => 'required|array',
        ]);

        $result = $this->promptService->generatePrompt(
            $validated['task_classification'],
            $validated['selected_framework'],
            $validated['alternative_frameworks'] ?? [],
            $validated['personality_tier'],
            $validated['personality_adjustments_preview'] ?? [],
            $validated['original_task_description'],
            $validated['personality_type'] ?? null,
            $validated['trait_percentages'] ?? null,
            $validated['question_answers']
        );

        return response()->json($result);
    }
}
```

### Routes

```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/prompt-builder', [PromptBuilderController::class, 'index'])
        ->name('prompt-builder');
    Route::post('/prompt-builder/analyse', [PromptBuilderController::class, 'analyse'])
        ->name('prompt-builder.analyse');
    Route::post('/prompt-builder/generate', [PromptBuilderController::class, 'generate'])
        ->name('prompt-builder.generate');
});
```

---

## Part 3: Request/Response Schemas

### Workflow 1: Analysis Request

**Endpoint**: `POST /prompt-builder/analyse`

**Request Body**:
```json
{
  "task_description": "I want to devise a marketing strategy for my SaaS product",
  "personality_type": "INTP-A",
  "trait_percentages": {
    "I_E": 65,
    "S_N": 64,
    "T_F": 84,
    "J_P": 57,
    "A_T": 84
  }
}
```

**Note**: `personality_type` and `trait_percentages` are both optional. If neither is provided, the system will proceed without personality-based adjustments.

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "task_classification": {
      "primary_category": "STRATEGY",
      "secondary_category": "CREATION_CONTENT",
      "complexity": "moderate",
      "classification_reasoning": "The task involves developing a comprehensive marketing strategy, which requires strategic planning with content creation elements."
    },
    "selected_framework": {
      "name": "COAST",
      "code": "COAST",
      "components": ["Challenge", "Objective", "Actions", "Strategy", "Tactics"],
      "rationale": "COAST provides a comprehensive structure for strategic planning, moving from problem identification through to tactical execution."
    },
    "alternative_frameworks": [
      {
        "name": "3Cs Model",
        "code": "3CS",
        "when_to_use_instead": "If competitive analysis is the primary focus"
      }
    ],
    "personality_tier": "full",
    "personality_adjustments_preview": [
      "High Thinking (84%): Logic-first framing, measurable criteria emphasis",
      "High Assertive (84%): Direct language, minimal hedging"
    ],
    "clarifying_questions": [
      {
        "id": "S1",
        "question": "What's the time horizon for this strategy?",
        "purpose": "Establish temporal scope for planning",
        "required": true
      },
      {
        "id": "S2",
        "question": "What resources are available (budget, team, tools)?",
        "purpose": "Understand constraints and capabilities",
        "required": true
      },
      {
        "id": "U2",
        "question": "What does success look like?",
        "purpose": "Define measurable success criteria",
        "required": true
      },
      {
        "id": "S7",
        "question": "Who are your main competitors?",
        "purpose": "Understand competitive landscape",
        "required": false
      },
      {
        "id": "S8",
        "question": "What's your unique advantage or differentiation?",
        "purpose": "Identify positioning opportunities",
        "required": false
      }
    ],
    "question_rationale": "Selected strategy-focused questions with emphasis on scope, resources, and competitive positioning. Question phrasing adjusted for High-T preference (direct, criteria-focused)."
  },
  "original_input": {
    "task_description": "I want to devise a marketing strategy for my SaaS product",
    "personality_type": "INTP-A",
    "trait_percentages": {
      "I_E": 65,
      "S_N": 64,
      "T_F": 84,
      "J_P": 57,
      "A_T": 84
    }
  },
  "error": null
}
```

### Workflow 2: Generation Request

**Endpoint**: `POST /prompt-builder/generate`

**Request Body**:
```json
{
  "task_classification": {
    "primary_category": "STRATEGY",
    "secondary_category": "CREATION_CONTENT",
    "complexity": "moderate",
    "classification_reasoning": "..."
  },
  "selected_framework": {
    "name": "COAST",
    "code": "COAST",
    "components": ["Challenge", "Objective", "Actions", "Strategy", "Tactics"],
    "rationale": "..."
  },
  "alternative_frameworks": [],
  "personality_tier": "full",
  "personality_adjustments_preview": [
    "High Thinking (84%): Logic-first framing",
    "High Assertive (84%): Direct language"
  ],
  "original_task_description": "I want to devise a marketing strategy for my SaaS product",
  "personality_type": "INTP-A",
  "trait_percentages": {
    "I_E": 65,
    "S_N": 64,
    "T_F": 84,
    "J_P": 57,
    "A_T": 84
  },
  "question_answers": [
    {
      "question_id": "S1",
      "question": "What's the time horizon for this strategy?",
      "answer": "12 months"
    },
    {
      "question_id": "S2",
      "question": "What resources are available?",
      "answer": "£2,000/month budget, 2-person founding team"
    },
    {
      "question_id": "U2",
      "question": "What does success look like?",
      "answer": "1,000 active users, £5k MRR"
    },
    {
      "question_id": "S7",
      "question": "Who are your main competitors?",
      "answer": "Mealime, Eat This Much, PlateJoy"
    },
    {
      "question_id": "S8",
      "question": "What's your unique advantage?",
      "answer": "AI-powered personalisation based on dietary preferences and health goals"
    }
  ]
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "optimised_prompt": "You are a strategic marketing consultant with expertise in B2B SaaS growth.\n\nCONTEXT:\nProduct: AI-powered meal planning application\nTarget: Health-conscious professionals seeking personalised nutrition\nTeam: 2-person founding team\nBudget: £2,000/month\nTimeline: 12-month strategy\nCompetitors: Mealime, Eat This Much, PlateJoy\nDifferentiator: AI-powered personalisation based on dietary preferences and health goals\n\n[CHALLENGE]\nIdentify the core marketing challenges for launching an AI meal planning app in a competitive market with limited initial budget and a small team.\n\n[OBJECTIVE]\nDefine specific, measurable marketing objectives for the 12-month period:\n- Primary goal: 1,000 active users\n- Revenue target: £5,000 MRR\n- Include quarterly milestones\n\n[ACTIONS]\nList the specific marketing actions required, organised by quarter. For each action, specify:\n- What exactly needs to be done\n- Resources required\n- Expected outcome\n- Success metrics\n\n[STRATEGY]\nOutline the overarching strategic approach, including:\n- Positioning relative to Mealime, Eat This Much, and PlateJoy\n- Key differentiators to emphasise (AI personalisation)\n- Primary vs. secondary channels\n- Phasing (awareness → consideration → conversion)\n\n[TACTICS]\nProvide specific tactical recommendations for each channel, including:\n- Content types and topics\n- Messaging frameworks\n- Tools or platforms to use\n- Budget allocation\n- Testing approaches\n\nCONSTRAINTS:\n- Budget: £2,000/month\n- Team: 2-person founding team with limited time\n- Timeline: First meaningful traction needed within 3 months\n\nOUTPUT FORMAT:\nStructure your response with clear sections for each COAST element. Use tables where they aid clarity. Provide specific, actionable recommendations with estimated costs and expected outcomes. Be direct and decisive in recommendations.",
    "metadata": {
      "framework_used": {
        "name": "COAST",
        "code": "COAST",
        "components": ["Challenge", "Objective", "Actions", "Strategy", "Tactics"],
        "explanation": "COAST was selected because this task requires comprehensive strategic planning that moves from problem identification through to tactical execution. The framework's structure ensures all aspects of a marketing strategy are covered systematically."
      },
      "personality_adjustments": [
        {
          "trait": "High Thinking (84%)",
          "adjustment": "Logic-first framing with emphasis on measurable criteria and metrics. Minimised emotional language in favour of data-driven structure."
        },
        {
          "trait": "High Assertive (84%)",
          "adjustment": "Direct, confident language throughout. Requested decisive recommendations without excessive hedging or caveats."
        },
        {
          "trait": "Moderate Intuitive (64%)",
          "adjustment": "Included strategic/big-picture framing while maintaining practical action orientation."
        },
        {
          "trait": "Borderline Perceiving (57%)",
          "adjustment": "Maintained structured output while allowing some flexibility in tactical recommendations."
        }
      ],
      "model_recommendations": [
        {
          "rank": 1,
          "model": "Claude Opus 4.5",
          "model_id": "claude-opus-4-5-20250514",
          "rationale": "Best for nuanced strategic reasoning, comprehensive analysis, and well-structured long-form output. Ideal for complex marketing strategy development."
        },
        {
          "rank": 2,
          "model": "GPT-4",
          "model_id": "gpt-4",
          "rationale": "Strong alternative with good strategic reasoning capabilities. May offer different perspectives on market positioning."
        },
        {
          "rank": 3,
          "model": "Claude Sonnet 4.5",
          "model_id": "claude-sonnet-4-5-20250514",
          "rationale": "Faster and more cost-effective option for iteration. Suitable if refining the strategy through multiple passes."
        }
      ],
      "iteration_suggestions": [
        "If output is too generic, add more specific details about your target customer persona",
        "If channel recommendations don't match your strengths, specify which channels you're comfortable with (e.g., 'strong at writing, weak at video')",
        "If timeline seems aggressive, adjust the user count targets or extend the timeline",
        "Consider adding information about your product's current development stage for more relevant tactics"
      ]
    }
  },
  "error": null
}
```

---

## Part 4: Configuration

### Config File

```php
// config/services.php
return [
    // ... other services
    
    'n8n' => [
        'base_url' => env('N8N_BASE_URL'),
        'webhook_secret' => env('N8N_WEBHOOK_SECRET'),
    ],
];
```

### Service Provider

```php
// app/Providers/AppServiceProvider.php
use App\Services\PromptFrameworkService;

public function register(): void
{
    $this->app->singleton(PromptFrameworkService::class, function ($app) {
        return new PromptFrameworkService();
    });
}
```

---

## Part 5: File Storage Structure

Store the reference documents in Laravel's storage:

```
storage/
└── app/
    └── reference_documents/
        ├── framework_taxonomy.md
        ├── personality_calibration.md
        ├── question_bank.md
        └── prompt_templates.md
```

### Artisan Command to Update Reference Docs

```php
// app/Console/Commands/UpdateReferenceDocuments.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class UpdateReferenceDocuments extends Command
{
    protected $signature = 'reference:clear-cache';
    protected $description = 'Clear cached reference documents';

    public function handle(): int
    {
        $documents = [
            'framework_taxonomy.md',
            'personality_calibration.md',
            'question_bank.md',
            'prompt_templates.md',
        ];

        foreach ($documents as $doc) {
            Cache::forget("reference_doc_{$doc}");
            $this->info("Cleared cache for: {$doc}");
        }

        $this->info('All reference document caches cleared.');
        return Command::SUCCESS;
    }
}
```

---

## Part 6: Error Handling

Both workflows should handle errors gracefully. The response envelope always includes:

```json
{
  "success": false,
  "data": null,
  "error": {
    "message": "Human-readable error message",
    "details": { }
  }
}
```

Common error scenarios:
- Reference document not found (404)
- n8n workflow timeout (504)
- Claude API error (502)
- Validation error (422)
- Internal server error (500)
