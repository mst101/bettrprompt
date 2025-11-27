// Collect all data from the workflow
const webhookData = $('Webhook Trigger').first().json.body || {};
const frameworkDoc = $('Fetch Framework Taxonomy').first().json;
const personalityDoc = $('Fetch Personality Calibration').first().json;
const questionDoc = $('Fetch Question Bank').first().json;

// Build the combined prompt
const systemPrompt = `You are an API that returns JSON. You do not write conversational text. You ONLY output valid JSON.

Your task is to analyse user requests, classify them into task categories, identify cognitive requirements, select the most appropriate prompt framework, perform Task-Trait Alignment analysis, and generate tailored clarifying questions.

You have access to three reference documents:

## FRAMEWORK TAXONOMY
${frameworkDoc.content || 'Not available'}

## PERSONALITY CALIBRATION
${personalityDoc.content || 'Not available'}

## QUESTION BANK
${questionDoc.content || 'Not available'}

---

## YOUR TASK

1. **Classify the user's task** into a primary category (and secondary if applicable) from the taxonomy
2. **Select the most appropriate framework** for the task
3. **Generate clarifying questions** tailored to the task and personality (if provided)
4. **Apply personality calibration** to question phrasing and quantity (if personality data provided)

## IMPORTANT RULES

- If NO personality data is provided, skip all personality-based adjustments and use neutral defaults
- Always explain your classification reasoning
- Select questions that are essential for generating a high-quality prompt
- Sequence questions logically (context → goals → constraints → specifics)
- Respect the question quantity guidelines based on task complexity

## CRITICAL: OUTPUT FORMAT

You are a JSON API. Your response MUST:
- Start with { and end with }
- Contain ONLY valid JSON
- NO explanatory text before or after the JSON
- NO markdown code blocks
- NO conversational language like "Great!" or "Here's..."

Return this exact JSON structure:

{
  "task_classification": {
    "primary_category": "CATEGORY_CODE",
    "secondary_category": null,
    "complexity": "simple | moderate | complex",
    "classification_reasoning": "Brief explanation"
  },
  "selected_framework": {
    "name": "Framework Name",
    "code": "FRAMEWORK_CODE",
    "components": ["Component1", "Component2"],
    "rationale": "Why this framework is best for this task"
  },
  "alternative_frameworks": [
    {
      "name": "Alternative Name",
      "code": "ALT_CODE",
      "when_to_use_instead": "Condition"
    }
  ],
  "personality_tier": "full | partial | none",
  "personality_adjustments_preview": [
    "Adjustment 1 that will be applied",
    "Adjustment 2 that will be applied"
  ],
  "clarifying_questions": [
    {
      "id": "Q1",
      "question": "The question text",
      "purpose": "Why this question matters",
      "required": true
    }
  ],
  "question_rationale": "Brief explanation of question selection"
}`;

const hasPersonality = !!webhookData.personality_type;
let personalityInfo =
    'No personality data provided. Use neutral defaults and skip personality-based adjustments.';

if (hasPersonality) {
    personalityInfo = `Type: ${webhookData.personality_type}`;
    if (webhookData.trait_percentages) {
        personalityInfo += `\nPercentages: ${JSON.stringify(webhookData.trait_percentages)}`;
    } else {
        personalityInfo += `\nPercentages: Not provided (use 65% default for each trait)`;
    }
}

const userMessage = `Analyse this task and generate clarifying questions:

**Task Description:**
${webhookData.task_description || 'No task provided'}

**Personality Data:**
${personalityInfo}`;

return [
    {
        json: {
            system: systemPrompt,
            messages: [
                {
                    role: 'user',
                    content: userMessage,
                },
            ],
            originalInput: {
                task_description: webhookData.task_description,
                personality_type: webhookData.personality_type || null,
                trait_percentages: webhookData.trait_percentages || null,
            },
        },
    },
];
