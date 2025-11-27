const body = $('Webhook Trigger').first().json.body || {};
const promptTemplates = $('Fetch Prompt Templates').first().json.content;
const personalityCalibration = $('Fetch Personality Calibration').first().json
    .content;

return [
    {
        json: {
            workflow1_data: {
                task_classification: body.task_classification,
                selected_framework: body.selected_framework,
                alternative_frameworks: body.alternative_frameworks || [],
                personality_tier: body.personality_tier,
                personality_adjustments_preview:
                    body.personality_adjustments_preview || [],
            },
            user_input: {
                task_description: body.original_task_description,
                personality_type: body.personality_type || null,
                trait_percentages: body.trait_percentages || null,
            },
            question_answers: body.question_answers,
            reference_docs: {
                prompt_templates: promptTemplates,
                personality_calibration: personalityCalibration,
            },
        },
    },
];
