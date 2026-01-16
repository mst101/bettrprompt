<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Framework analytics: track recommended vs chosen frameworks
        Schema::create('framework_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_run_id')->constrained()->cascadeOnDelete();

            // Identity
            $table->uuid('visitor_id')->index();
            $table->foreignId('user_id')->nullable();

            // Framework recommendation
            $table->string('recommended_framework', 50)->index();
            $table->string('chosen_framework', 50)->index();
            $table->boolean('accepted_recommendation')->index();

            // Context
            $table->string('task_category', 50)->nullable();
            $table->string('personality_type', 10)->nullable();
            $table->json('recommendation_scores')->nullable();

            // Outcome
            $table->unsignedTinyInteger('prompt_rating')->nullable();
            $table->text('rating_explanation')->nullable();
            $table->boolean('prompt_copied')->nullable();
            $table->boolean('prompt_edited')->nullable();
            $table->decimal('edit_percentage', 5, 2)->nullable();

            $table->timestamp('selected_at')->index();
            $table->timestamps();

            $table->index(['recommended_framework', 'chosen_framework']);
            $table->index(['personality_type', 'task_category']);
        });

        // Question analytics: track question effectiveness by ID
        Schema::create('question_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_run_id')->constrained()->cascadeOnDelete();

            // Identity
            $table->uuid('visitor_id')->index();
            $table->foreignId('user_id')->nullable();

            // Question identification (from question_bank.md)
            $table->string('question_id', 10)->index(); // U1, D1, S1, etc.
            $table->string('question_category', 20); // universal, decision, strategy, etc.

            // Presentation context
            $table->string('personality_variant', 50)->nullable();
            $table->unsignedTinyInteger('display_order');
            $table->boolean('was_required');
            $table->enum('display_mode', ['one-at-a-time', 'show-all'])->nullable();

            // User response
            $table->enum('response_status', ['answered', 'skipped', 'not_shown'])->index();
            $table->unsignedSmallInteger('response_length')->nullable();
            $table->unsignedInteger('time_to_answer_ms')->nullable();

            // Outcome correlation
            $table->unsignedTinyInteger('prompt_rating')->nullable();
            $table->unsignedTinyInteger('user_rating')->nullable();
            $table->text('rating_explanation')->nullable();
            $table->boolean('prompt_copied')->nullable();

            $table->timestamp('presented_at')->index();
            $table->timestamps();

            $table->index(['question_id', 'response_status']);
            $table->index(['question_category', 'response_status']);
        });

        // Workflow analytics: track n8n workflow performance
        Schema::create('workflow_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_run_id')->constrained()->cascadeOnDelete();

            // Workflow identification
            $table->unsignedTinyInteger('workflow_stage')->index(); // 0, 1, 2
            $table->string('workflow_version', 20)->nullable();

            // Timing
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            // Status
            $table->enum('status', ['processing', 'completed', 'failed', 'timeout'])->index();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();

            // Cost tracking
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->decimal('cost_usd', 8, 6)->nullable();
            $table->string('model_used', 50)->nullable();

            // Retry tracking
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->boolean('was_retry')->default(false);

            $table->timestamps();

            $table->index(['workflow_stage', 'status']);
            $table->index(['status', 'started_at']);
        });

        // Prompt quality metrics: aggregated outcomes
        Schema::create('prompt_quality_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_run_id')->unique()->constrained()->cascadeOnDelete();

            // User engagement
            $table->unsignedTinyInteger('user_rating')->nullable();
            $table->text('rating_explanation')->nullable();
            $table->boolean('was_copied')->default(false);
            $table->unsignedSmallInteger('copy_count')->default(0);
            $table->boolean('was_edited')->default(false);
            $table->decimal('edit_percentage', 5, 2)->nullable();

            // Quality signals
            $table->unsignedSmallInteger('prompt_length')->nullable();
            $table->unsignedTinyInteger('questions_answered')->default(0);
            $table->unsignedTinyInteger('questions_skipped')->default(0);
            $table->unsignedInteger('time_to_complete_ms')->nullable();

            // Context
            $table->string('task_category', 50)->nullable();
            $table->string('framework_used', 50)->nullable();
            $table->string('personality_type', 10)->nullable();

            // Composite scores
            $table->decimal('engagement_score', 5, 2)->nullable();
            $table->decimal('quality_score', 5, 2)->nullable();

            $table->timestamps();

            $table->index(['user_rating']);
            $table->index(['task_category', 'framework_used']);
            $table->index(['personality_type']);
        });

        // Daily aggregation: framework statistics
        Schema::create('framework_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('framework', 50);

            // Usage
            $table->unsignedInteger('times_recommended')->default(0);
            $table->unsignedInteger('times_chosen')->default(0);
            $table->unsignedInteger('times_accepted')->default(0);
            $table->decimal('acceptance_rate', 5, 4)->nullable();

            // Quality
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->unsignedInteger('prompts_copied')->default(0);
            $table->unsignedInteger('prompts_edited')->default(0);
            $table->decimal('copy_rate', 5, 4)->nullable();

            // By personality (JSON for top patterns)
            $table->json('by_personality_type')->nullable();
            $table->json('by_task_category')->nullable();

            $table->timestamps();

            $table->unique(['date', 'framework']);
            $table->index(['framework', 'date']);
        });

        // Daily aggregation: question statistics
        Schema::create('question_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('question_id', 10);

            // Presentation
            $table->unsignedInteger('times_shown')->default(0);
            $table->unsignedInteger('times_answered')->default(0);
            $table->unsignedInteger('times_skipped')->default(0);
            $table->decimal('answer_rate', 5, 4)->nullable();
            $table->decimal('skip_rate', 5, 4)->nullable();

            // Response quality
            $table->decimal('avg_response_length', 8, 2)->nullable();
            $table->decimal('avg_time_to_answer_ms', 10, 2)->nullable();

            // Outcome correlation
            $table->decimal('avg_prompt_rating_when_answered', 3, 2)->nullable();
            $table->decimal('avg_prompt_rating_when_skipped', 3, 2)->nullable();
            $table->decimal('copy_rate_when_answered', 5, 4)->nullable();
            $table->decimal('copy_rate_when_skipped', 5, 4)->nullable();

            // Personality variants (JSON)
            $table->json('by_personality_variant')->nullable();

            $table->timestamps();

            $table->unique(['date', 'question_id']);
            $table->index(['question_id', 'date']);
        });

        // Daily aggregation: workflow performance
        Schema::create('workflow_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->unsignedTinyInteger('workflow_stage');

            // Execution counts
            $table->unsignedInteger('total_executions')->default(0);
            $table->unsignedInteger('successful_executions')->default(0);
            $table->unsignedInteger('failed_executions')->default(0);
            $table->decimal('success_rate', 5, 4)->nullable();

            // Timing
            $table->unsignedInteger('avg_duration_ms')->nullable();
            $table->unsignedInteger('min_duration_ms')->nullable();
            $table->unsignedInteger('max_duration_ms')->nullable();

            // Cost
            $table->unsignedInteger('total_input_tokens')->default(0);
            $table->unsignedInteger('total_output_tokens')->default(0);
            $table->decimal('total_cost_usd', 12, 6)->default(0);
            $table->decimal('avg_cost_per_execution', 10, 6)->nullable();

            // Retries
            $table->unsignedInteger('retries')->default(0);
            $table->decimal('retry_rate', 5, 4)->nullable();

            // Most common errors (JSON)
            $table->json('top_errors')->nullable();

            $table->timestamps();

            $table->unique(['date', 'workflow_stage']);
            $table->index(['workflow_stage', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_daily_stats');
        Schema::dropIfExists('question_daily_stats');
        Schema::dropIfExists('framework_daily_stats');
        Schema::dropIfExists('prompt_quality_metrics');
        Schema::dropIfExists('workflow_analytics');
        Schema::dropIfExists('question_analytics');
        Schema::dropIfExists('framework_analytics');
    }
};
