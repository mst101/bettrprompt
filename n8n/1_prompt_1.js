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
2. **Identify cognitive requirements** for the task (from the Task Cognitive Requirements section)
3. **Select the most appropriate framework** for the task
4. **Perform Task-Trait Alignment analysis** (if personality data provided):
   - Identify which traits ALIGN with task requirements → Mark for AMPLIFICATION
   - Identify which traits OPPOSE task requirements → Mark for COUNTERBALANCING
   - Identify which traits are UNRELATED → Mark as NEUTRAL
5. **Generate clarifying questions** tailored to the task and personality (if provided)
6. **Apply personality calibration** to question phrasing and quantity (if personality data provided)

## TASK-TRAIT ALIGNMENT RULES

- **AMPLIFY** traits that are assets: The prompt will leverage the user's natural strengths
- **COUNTERBALANCE** traits that create blind spots: The prompt will inject explicit requirements the user might skip
- **NEUTRAL** for traits unrelated to the task: No adjustment needed

When a trait is marked for counterbalancing, specify the INJECTION - the explicit requirement that will be added to the prompt to cover the user's blind spot.

## IMPORTANT RULES

- If NO personality data is provided, skip Task-Trait Alignment and use neutral defaults
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
    "classification_reasoning": "Brief explanation",
    "content_type": "For CREATION_CONTENT only, e.g. customer_email"
  },
  "cognitive_requirements": {
    "primary": ["REQUIREMENT_CODE", "REQUIREMENT_CODE"],
    "secondary": ["REQUIREMENT_CODE"],
    "reasoning": "Why these requirements apply to this task"
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
  "task_trait_alignment": {
    "amplified": [
      {
        "trait": "High T (84%)",
        "requirement_aligned": "OBJECTIVE",
        "reason": "Why this trait helps with this task"
      }
    ],
    "counterbalanced": [
      {
        "trait": "High T (84%)",
        "requirement_opposed": "EMPATHY",
        "reason": "Why this trait may create a blind spot",
        "injection": "Specific requirement to add to the prompt"
      }
    ],
    "neutral": [
      {
        "trait": "High I (65%)",
        "reason": "Why this trait is not relevant to the task"
      }
    ]
  },
  "personality_adjustments_preview": [
    "AMPLIFIED: Description of what will be leveraged",
    "COUNTERBALANCED: Description of what will be injected"
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
    'No personality data provided. Skip Task-Trait Alignment and use neutral defaults.';

if (hasPersonality) {
    personalityInfo = `Type: ${webhookData.personality_type}`;
    if (webhookData.trait_percentages) {
        // Extract trait letters from personality type and percentages
        // personality_type format: "INTP-A" where each letter corresponds to a trait
        const typeLetters = webhookData.personality_type.split('-');
        const mainType = typeLetters[0]; // e.g., "INTP"
        const identity = typeLetters[1]; // e.g., "A" or "T"
        const traits = webhookData.trait_percentages;
        const traitDescriptions = [];

        // Map each percentage to its corresponding letter from the personality type
        if (traits.mind !== undefined) {
            traitDescriptions.push(`${mainType[0]} (${traits.mind}%)`); // I or E
        }
        if (traits.energy !== undefined) {
            traitDescriptions.push(`${mainType[1]} (${traits.energy}%)`); // S or N
        }
        if (traits.nature !== undefined) {
            traitDescriptions.push(`${mainType[2]} (${traits.nature}%)`); // T or F
        }
        if (traits.tactics !== undefined) {
            traitDescriptions.push(`${mainType[3]} (${traits.tactics}%)`); // J or P
        }
        if (traits.identity !== undefined) {
            traitDescriptions.push(`${identity} (${traits.identity}%)`); // A or T
        }

        personalityInfo += `\nTrait Percentages: ${traitDescriptions.join(', ')}`;
        personalityInfo += `\n\nPerform full Task-Trait Alignment analysis:`;
        personalityInfo += `\n- Check each trait against the task\'s cognitive requirements`;
        personalityInfo += `\n- Mark aligned traits for AMPLIFICATION`;
        personalityInfo += `\n- Mark opposed traits for COUNTERBALANCING (include specific injections)`;
        personalityInfo += `\n- Mark unrelated traits as NEUTRAL`;
        personalityInfo += `\n- Use the exact trait format shown above (e.g., "I (65%)", "N (64%)", etc.)`;
        personalityInfo += `\n\nPerform full Task-Trait Alignment analysis:`;
        personalityInfo += `\n- Check each trait against the task's cognitive requirements`;
        personalityInfo += `\n- Mark aligned traits for AMPLIFICATION`;
        personalityInfo += `\n- Mark opposed traits for COUNTERBALANCING (include specific injections)`;
        personalityInfo += `\n- Mark unrelated traits as NEUTRAL`;
    } else {
        personalityInfo += `\nPercentages: Not provided (use 65% default for each trait)`;
        personalityInfo += `\nPerform Task-Trait Alignment with default percentages.`;
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
