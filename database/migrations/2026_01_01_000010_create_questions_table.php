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
            $table->string('priority', 10); // high, medium, low
            $table->string('task_category_code', 30)->nullable();
            $table->string('framework_code', 30)->nullable();
            $table->boolean('is_universal')->default(false);
            $table->boolean('is_conditional')->default(false);
            $table->text('condition_text')->nullable(); // e.g., "research task", "technical task"
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for common queries
            $table->index('priority');
            $table->index('is_active');
            $table->index('is_universal');
            $table->index('is_conditional');
            $table->index('task_category_code');
            $table->index('framework_code');
            $table->index(['task_category_code', 'framework_code']);

            $table->foreign('task_category_code')
                ->references('code')
                ->on('task_categories')
                ->restrictOnDelete();

            $table->foreign('framework_code')
                ->references('code')
                ->on('frameworks')
                ->restrictOnDelete();
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
