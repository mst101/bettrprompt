// Collect all data from the workflow
const webhookData = $('Webhook Trigger').first().json.body || {};
const promptTemplatesDoc = $('Fetch Prompt Templates').first().json;
const personalityDoc = $('Fetch Personality Calibration').first().json;

// Extract the analysis data from Workflow 1
const analysisData = webhookData.analysis_data || {};
const questionAnswers = webhookData.question_answers || [];

// Build the system prompt
const systemPrompt = `You are an expert prompt engineering assistant. Your task is to construct an optimised, ready-to-use prompt based on the framework selected in the analysis phase, applying Task-Trait Alignment adjustments (amplification and counterbalancing) as specified.

You have access to two reference documents:

## PROMPT TEMPLATES
${promptTemplatesDoc.content || 'Not available'}

## PERSONALITY CALIBRATION
${personalityDoc.content || 'Not available'}

---

## YOUR TASK

1. **Construct the prompt** using the selected framework template
2. **Apply AMPLIFICATION** for aligned traits:
   - Use language and structure that leverages the user's natural strengths
   - Format output to match their preferences
3. **Apply COUNTERBALANCING** for opposed traits:
   - Inject the specific requirements identified in the analysis
   - Add explicit instructions the user might naturally skip
   - Include counterbalance criteria in quality checks
4. **Incorporate all user answers** to clarifying questions
5. **Generate model recommendations** considering counterbalance complexity
6. **Provide iteration suggestions** for refinement

## CRITICAL: COUNTERBALANCE INJECTION

When the analysis specifies counterbalancing, you MUST:
- Add a dedicated "IMPORTANT REQUIREMENTS" section if significant counterbalancing is needed
- Insert the specific injection phrases into relevant sections of the prompt
- Add counterbalance items to any quality criteria or checklists
- Make injections EXPLICIT - they should be impossible to overlook

## OUTPUT FORMAT

You MUST respond with valid JSON only (no markdown code blocks, no extra text). Use this exact structure:

{
  "optimised_prompt": "The complete prompt text, ready for copy/paste. This should be a fully self-contained prompt that includes all context, requirements, and instructions. It should be usable immediately without any additional information.",

  "metadata": {
    "framework_used": {
      "name": "Framework Name",
      "code": "FRAMEWORK_CODE",
      "components": ["Component1", "Component2"],
      "explanation": "How the framework was applied"
    },

    "task_trait_alignment": {
      "amplified": [
        {
          "trait": "High N (64%)",
          "requirement_aligned": "VISION",
          "how_applied": "Description of how this was leveraged in the prompt"
        }
      ],
      "counterbalanced": [
        {
          "trait": "High T (84%)",
          "requirement_opposed": "EMPATHY",
          "reason": "Why counterbalancing was needed",
          "injections_added": [
            "Specific text that was added to the prompt"
          ]
        }
      ],
      "neutral": [
        {
          "trait": "High I (65%)",
          "reason": "Why no adjustment was made"
        }
      ]
    },

    "personality_adjustments_summary": [
      "AMPLIFIED: Brief description",
      "COUNTERBALANCED: Brief description"
    ],

    "model_recommendations": [
      {
        "rank": 1,
        "model": "Model Name",
        "model_id": "model-id-string",
        "rationale": "Why this model is recommended, considering counterbalance complexity"
      },
      {
        "rank": 2,
        "model": "Alternative Model",
        "model_id": "model-id-string",
        "rationale": "Why this is a good alternative"
      },
      {
        "rank": 3,
        "model": "Budget Option",
        "model_id": "model-id-string",
        "rationale": "Cost-effective option if applicable"
      }
    ],

    "iteration_suggestions": [
      "Suggestion for how to refine the prompt if needed",
      "Another potential adjustment"
    ]
  }
}`;

// Build the user message with all the context
let userMessage = `## ANALYSIS FROM WORKFLOW 1\n\n`;
userMessage += `**Task Classification:**\n${JSON.stringify(analysisData.task_classification, null, 2)}\n\n`;
userMessage += `**Cognitive Requirements:**\n${JSON.stringify(analysisData.cognitive_requirements, null, 2)}\n\n`;
userMessage += `**Selected Framework:**\n${JSON.stringify(analysisData.selected_framework, null, 2)}\n\n`;
userMessage += `**Alternative Frameworks:**\n${JSON.stringify(analysisData.alternative_frameworks, null, 2)}\n\n`;
userMessage += `**Personality Tier:** ${analysisData.personality_tier || 'none'}\n\n`;

if (analysisData.task_trait_alignment) {
    userMessage += `**Task-Trait Alignment Analysis:**\n${JSON.stringify(analysisData.task_trait_alignment, null, 2)}\n\n`;
    userMessage += `IMPORTANT: Apply the amplifications and counterbalancing as specified above. For each counterbalanced trait, ensure the injection text is explicitly included in the generated prompt.\n\n`;
}

userMessage += `---\n\n## ORIGINAL TASK\n\n${webhookData.original_task_description || 'No task description provided'}\n\n`;

if (webhookData.personality_type) {
    userMessage += `## PERSONALITY DATA\n\n`;
    userMessage += `Type: ${webhookData.personality_type}\n`;
    if (webhookData.trait_percentages) {
        userMessage += `Percentages: ${JSON.stringify(webhookData.trait_percentages)}\n`;
    }
    userMessage += `\n`;
}

userMessage += `## USER'S ANSWERS TO CLARIFYING QUESTIONS\n\n`;
if (questionAnswers && questionAnswers.length > 0) {
    questionAnswers.forEach((qa, index) => {
        userMessage += `**Q${index + 1}: ${qa.question}**\n`;
        userMessage += `A: ${qa.answer}\n\n`;
    });
} else {
    userMessage += `No question answers provided.\n\n`;
}

userMessage += `---\n\n`;
userMessage += `Now construct the optimised prompt. Remember to:\n`;
userMessage += `1. Use the ${analysisData.selected_framework?.name || 'selected'} framework structure\n`;
userMessage += `2. Incorporate all user answers naturally into the prompt\n`;
userMessage += `3. Apply AMPLIFICATION for aligned traits (leverage strengths)\n`;
userMessage += `4. Apply COUNTERBALANCING for opposed traits (inject explicit requirements)\n`;
userMessage += `5. Make the prompt self-contained and immediately usable\n`;
userMessage += `6. Recommend models considering the complexity of counterbalancing needed`;

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
                original_task_description:
                    webhookData.original_task_description,
                personality_type: webhookData.personality_type || null,
                trait_percentages: webhookData.trait_percentages || null,
                analysis_data: analysisData,
                question_answers: questionAnswers,
            },
        },
    },
];
