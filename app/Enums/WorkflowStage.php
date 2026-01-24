<?php

namespace App\Enums;

/**
 * Workflow stage enumeration for the 3-stage prompt generation system
 *
 * Note: Keep in sync with resources/js/Constants/workflow.ts
 */
enum WorkflowStage: string
{
    // Workflow 0: Pre-analysis
    case PreAnalysisProcessing = '0_processing';
    case PreAnalysisCompleted = '0_completed';
    case PreAnalysisFailed = '0_failed';

    // Workflow 1: Analysis
    case AnalysisProcessing = '1_processing';
    case AnalysisCompleted = '1_completed';
    case AnalysisFailed = '1_failed';

    // Workflow 2: Generation
    case GenerationProcessing = '2_processing';
    case GenerationCompleted = '2_completed';
    case GenerationFailed = '2_failed';

    public function isProcessing(): bool
    {
        return match ($this) {
            self::PreAnalysisProcessing,
            self::AnalysisProcessing,
            self::GenerationProcessing => true,
            default => false,
        };
    }

    public function isCompleted(): bool
    {
        return match ($this) {
            self::PreAnalysisCompleted,
            self::AnalysisCompleted,
            self::GenerationCompleted => true,
            default => false,
        };
    }

    public function isFailed(): bool
    {
        return match ($this) {
            self::PreAnalysisFailed,
            self::AnalysisFailed,
            self::GenerationFailed => true,
            default => false,
        };
    }

    public function getWorkflowNumber(): int
    {
        return match ($this) {
            self::PreAnalysisProcessing,
            self::PreAnalysisCompleted,
            self::PreAnalysisFailed => 0,

            self::AnalysisProcessing,
            self::AnalysisCompleted,
            self::AnalysisFailed => 1,

            self::GenerationProcessing,
            self::GenerationCompleted,
            self::GenerationFailed => 2,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
