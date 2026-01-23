/**
 * Type definitions for PromptRun data structures
 * These types ensure type safety when passing data to components
 */

import type {
    AmplifiedTraitResource,
    CounterbalancedTraitResource,
    NeutralTraitResource,
} from '@/Types/resources';

// Framework selection data
export interface SelectedFramework {
    name: string;
    code: string;
    components: string[];
    rationale: string;
}

export interface AlternativeFramework {
    name: string;
    code: string;
    when_to_use_instead: string;
}

// Task analysis data
export interface TaskClassification {
    primary_category: string;
    secondary_category: string | null;
    complexity: string;
    classification_reasoning: string;
}

export interface CognitiveRequirements {
    primary: string[];
    secondary: string[];
    reasoning: string;
}

// Personality data
export type PersonalityTier = 'full' | 'partial' | 'none';

/**
 * TaskTraitAlignment structure as returned by Laravel
 * Contains amplified, counterbalanced, and neutral trait resources
 */
export interface TaskTraitAlignment {
    amplified: AmplifiedTraitResource[];
    counterbalanced: CounterbalancedTraitResource[];
    neutral: NeutralTraitResource[];
}

// Recommendations data
export interface ModelRecommendation {
    model: string;
    reasoning: string;
}

export interface IterationSuggestion {
    suggestion: string;
}

// API usage data
export interface ApiUsage {
    model?: string;
    input_tokens?: number;
    output_tokens?: number;
    total_tokens?: number;
    cost?: number;
}
