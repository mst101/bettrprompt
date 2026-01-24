<?php

namespace App\Filters;

use App\Enums\WorkflowStage;

/**
 * Reusable filter closures for prompt run collections.
 * Static properties storing closures allow the JIT compiler to optimise
 * filters better than runtime-created closures.
 */
class PromptRunFilters
{
    public static \Closure $completed;

    public static \Closure $failed;

    public static \Closure $processing;

    public static function init(): void
    {
        self::$completed = fn ($run) => $run['workflow_stage'] === WorkflowStage::GenerationCompleted->value;

        self::$failed = fn ($run) => str_contains($run['workflow_stage'], 'failed');

        self::$processing = fn ($run) => str_contains($run['workflow_stage'], 'processing');
    }
}

// Initialise filters on class load
PromptRunFilters::init();
