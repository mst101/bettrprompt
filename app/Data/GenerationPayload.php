<?php

namespace App\Data;

/**
 * Data Transfer Object for generation workflow payload
 *
 * Encapsulates all parameters needed for the prompt generation workflow,
 * eliminating the need for 12+ method parameters.
 */
class GenerationPayload
{
    public function __construct(
        public array $taskClassification,
        public array $cognitiveRequirements,
        public array $selectedFramework,
        public string $personalityTier,
        public array $taskTraitAlignment,
        public string $originalTaskDescription,
        public array $questionAnswers,
        public ?string $personalityType = null,
        public ?array $traitPercentages = null,
        public ?array $userContext = null,
        public ?array $preAnalysisContext = null,
    ) {}
}
