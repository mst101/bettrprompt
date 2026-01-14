<?php

declare(strict_types=1);

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
        Schema::create('question_cognitive_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('question_id', 10);
            $table->string('cognitive_requirement_code', 30);
            $table->enum('requirement_level', ['primary', 'secondary'])->default('primary');
            $table->timestamps();

            // Foreign keys
            $table->foreign('question_id')
                ->references('id')
                ->on('questions')
                ->cascadeOnDelete();

            $table->foreign('cognitive_requirement_code')
                ->references('code')
                ->on('cognitive_requirements')
                ->restrictOnDelete();

            // Unique constraint - one question can't have same requirement twice
            $table->unique(['question_id', 'cognitive_requirement_code']);

            // Indexes for query performance
            $table->index('question_id');
            $table->index('cognitive_requirement_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_cognitive_requirements');
    }
};
