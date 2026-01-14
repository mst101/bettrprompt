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
        Schema::create('task_category_cognitive_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('task_category_code');
            $table->string('cognitive_requirement_code');
            $table->enum('requirement_level', ['primary', 'secondary']);
            $table->timestamps();

            // Foreign keys
            $table->foreign('task_category_code')
                ->references('code')
                ->on('task_categories')
                ->cascadeOnDelete();
            $table->foreign('cognitive_requirement_code')
                ->references('code')
                ->on('cognitive_requirements')
                ->cascadeOnDelete();

            // Indexes & constraints
            $table->unique(['task_category_code', 'cognitive_requirement_code'], 'task_cog_req_unique');
            $table->index('task_category_code');
            $table->index('cognitive_requirement_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_category_cognitive_requirements');
    }
};
