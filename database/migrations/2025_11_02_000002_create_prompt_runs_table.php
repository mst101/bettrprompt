<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prompt_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('visitor_id')->constrained('visitors')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('prompt_runs')->onDelete('cascade');
            $table->string('personality_type',
                6)->nullable(); // e.g., INTJ-A, ENFP-T (nullable for users without personality)
            $table->json('trait_percentages')->nullable(); // Store trait percentages
            $table->text('task_description');
            $table->json('pre_analysis_questions')->nullable();
            $table->json('pre_analysis_answers')->nullable();
            $table->json('pre_analysis_context')->nullable();
            $table->text('pre_analysis_reasoning')->nullable();
            $table->boolean('pre_analysis_skipped')->default(false);
            $table->json('framework_questions')->nullable();
            $table->json('clarifying_answers')->nullable();
            $table->integer('current_question_index')->default(0);
            $table->text('optimized_prompt')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('workflow_stage')->default('submitted');
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Prompt Builder specific fields (from workflow 1 analysis)
            $table->json('task_classification')->nullable(); // {primary_category, secondary_category, complexity, classification_reasoning}
            $table->json('cognitive_requirements')->nullable(); // {primary[], secondary[], reasoning}
            $table->json('selected_framework')->nullable(); // {name, code, components[], rationale}
            $table->json('alternative_frameworks')->nullable(); // [{name, code, when_to_use_instead}]
            $table->string('personality_tier')->nullable(); // full, partial, none
            $table->json('task_trait_alignment')->nullable(); // {amplified[], counterbalanced[], neutral[]}
            $table->json('personality_adjustments_preview')->nullable(); // string[]
            $table->text('question_rationale')->nullable(); // Why these questions were chosen

            // Prompt Builder specific fields (from workflow 2 generation)
            $table->json('framework_used')->nullable(); // {name, code, components[], explanation}
            $table->json('personality_adjustments_summary')->nullable(); // string[] of adjustment descriptions
            $table->json('model_recommendations')->nullable(); // [{rank, model, model_id, rationale}]
            $table->json('iteration_suggestions')->nullable(); // string[] of suggestions for improving the prompt

            // API usage tracking
            $table->json('analysis_api_usage')->nullable(); // {model, input_tokens, output_tokens} from workflow 1
            $table->json('generation_api_usage')->nullable(); // {model, input_tokens, output_tokens} from workflow 2

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompt_runs');
    }
};
