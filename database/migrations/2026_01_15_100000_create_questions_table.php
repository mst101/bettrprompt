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
        Schema::create('questions', function (Blueprint $table) {
            $table->string('id', 10)->primary(); // U1, D1, S1, COS3, etc.
            $table->text('question_text');
            $table->text('purpose');
            $table->jsonb('cognitive_requirements')->nullable();
            $table->string('priority', 10); // high, medium, low
            $table->string('category', 30); // universal, decision, strategy, co_star, etc.
            $table->string('framework', 30)->nullable(); // co_star, react, self_refine, etc.
            $table->boolean('is_universal')->default(false);
            $table->boolean('is_conditional')->default(false);
            $table->text('condition_text')->nullable(); // e.g., "research task", "technical task"
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for common queries
            $table->index('category');
            $table->index('framework');
            $table->index('priority');
            $table->index('is_active');
            $table->index('is_universal');
            $table->index('is_conditional');
            $table->index(['category', 'framework']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
