// Collect all data from the workflow
const webhookData = $('Webhook Trigger').first().json.body || {};
const frameworkDoc = $('Fetch Framework Taxonomy').first().json;
const personalityDoc = $('Fetch Personality Calibration').first().json;
const questionDoc = $('Fetch Question Bank').first().json;

// Check if a forced framework was provided
const forcedFrameworkCode = webhookData.forced_framework_code || null;
let frameworkInstructions = '';

if (forcedFrameworkCode) {
    frameworkInstructions = `
## FORCED FRAMEWORK SELECTION

**CRITICAL:** The user has selected a specific framework to use: **${forcedFrameworkCode}**

You MUST:
1. Use ${forcedFrameworkCode} as the selected_framework (look up its details from the Framework Taxonomy)
2. Generate clarifying questions that are SPECIFIC to ${forcedFrameworkCode}
3. Ensure the rationale explains why ${forcedFrameworkCode} is suitable for this task
4. Suggest alternative frameworks OTHER than ${forcedFrameworkCode}
5. Recalculate task_classification and cognitive_requirements considering ${forcedFrameworkCode}'s strengths
6. Update personality adjustments based on how ${forcedFrameworkCode} leverages personality traits

Do NOT select a different framework - you must use ${forcedFrameworkCode}.
`;
} else {
    frameworkInstructions = `
## FRAMEWORK SELECTION

3. **Select the most appropriate framework** for the task from the Framework Taxonomy
`;
}

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
${frameworkInstructions}
4. **Perform Task-Trait Alignment analysis** (if personality data provided):
   - Identify which traits ALIGN with task requirements → Mark for AMPLIFICATION
   - Identify which traits OPPOSE task requirements → Mark for COUNTERBALANCING
   - Identify which traits are UNRELATED → Mark as NEUTRAL
5. **Generate clarifying questions** tailored to the task${forcedFrameworkCode ? ' AND the forced framework (' + forcedFrameworkCode + ')' : ''} and personality (if provided)
6. **Apply personality calibration** to question phrasing and quantity (if personality data provided)

## TASK-TRAIT ALIGNMENT

Analyze user's actual traits (provided in personality data):
- **AMPLIFY** aligned traits (leverage strengths)
- **COUNTERBALANCE** opposing traits (inject requirements to cover blind spots)
- **NEUTRAL** for unrelated traits

## IMPORTANT RULES

- If NO personality data is provided, skip Task-Trait Alignment and use neutral defaults
- Always explain your classification reasoning
- Select questions that are essential for generating a high-quality prompt
- Sequence questions logically (context → goals → constraints → specifics)
- Respect the question quantity guidelines based on task complexity${forcedFrameworkCode ? '\n- Generate questions SPECIFIC to ' + forcedFrameworkCode + ' framework requirements' : ''}

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
    "NOTE": "ONLY include traits from the user's actual personality. DO NOT make up traits.",
    "amplified": [
      {
        "trait": "One of the user's ACTUAL traits from the list provided",
        "requirement_aligned": "OBJECTIVE",
        "reason": "Why THIS SPECIFIC trait helps with this task"
      }
    ],
    "counterbalanced": [
      {
        "trait": "Another of the user's ACTUAL traits from the list provided",
        "requirement_opposed": "EMPATHY",
        "reason": "Why THIS SPECIFIC trait may create a blind spot",
        "injection": "Specific requirement to add to the prompt"
      }
    ],
    "neutral": [
      {
        "trait": "Another of the user's ACTUAL traits from the list provided",
        "reason": "Why THIS SPECIFIC trait is not relevant to the task"
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

        personalityInfo += `\nTraits: ${traitDescriptions.join(', ')}`;
        personalityInfo += `\nAnalyze ONLY these traits (not hypothetical ideal traits).`;
        personalityInfo += `\nFor each: AMPLIFIED (helps task), COUNTERBALANCED (opposes task), or NEUTRAL.`;
    } else {
        personalityInfo += `\nPercentages: Not provided (use 65% default for each trait)`;
        personalityInfo += `\nPerform Task-Trait Alignment with default percentages.`;
    }
}

// Check if pre-analysis context is provided
const preAnalysisContext = webhookData.pre_analysis_context || null;
let preAnalysisSection = '';

if (preAnalysisContext && typeof preAnalysisContext === 'object') {
    preAnalysisSection =
        '\n\n**Pre-Analysis Context:**\nThe user has provided clarification on the following aspects:';
    for (const [questionId, answer] of Object.entries(preAnalysisContext)) {
        preAnalysisSection += `\n- ${questionId}: ${answer}`;
    }
    preAnalysisSection +=
        '\n\nUse this context to inform your task classification, framework selection, and question generation.';
}

let userMessage = `Analyse this task and generate clarifying questions:

**Task Description:**
${webhookData.task_description || 'No task provided'}

**Personality Data:**
${personalityInfo}${preAnalysisSection}`;

if (forcedFrameworkCode) {
    userMessage += `\n\n**FORCED FRAMEWORK:** ${forcedFrameworkCode}`;
    userMessage += `\nYou MUST use this framework as the selected_framework. Look it up in the Framework Taxonomy and generate questions specific to it.`;
}

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
                forced_framework_code: forcedFrameworkCode,
            },
        },
    },
];
