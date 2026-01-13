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
        Schema::create('claude_models', function (Blueprint $table) {
            $table->string('id')->primary(); // API ID like 'claude-opus-4-20250514'
            $table->string('name'); // Display name like 'Claude Opus 4'
            $table->enum('tier', ['haiku', 'sonnet', 'opus']); // Model tier
            $table->integer('version'); // Version number (3, 4, 4.5, etc)
            $table->decimal('input_cost_per_mtok', 10, 4); // Input price per million tokens
            $table->decimal('output_cost_per_mtok', 10, 4); // Output price per million tokens
            $table->date('release_date')->nullable();
            $table->boolean('active')->default(true);
            $table->text('positioning')->nullable(); // Marketing positioning text
            $table->integer('context_window_input')->nullable(); // Input context window in tokens
            $table->integer('context_window_output')->nullable(); // Output context window in tokens
            $table->timestamps();

            // Indexes for common queries
            $table->index('tier');
            $table->index('active');
            $table->index('release_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claude_models');
    }
};
