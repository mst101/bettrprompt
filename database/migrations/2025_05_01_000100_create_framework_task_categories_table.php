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
        Schema::create('framework_task_categories', function (Blueprint $table) {
            $table->id();
            $table->string('framework_code');
            $table->string('task_category_code');
            $table->enum('suitability', ['primary', 'secondary']);
            $table->timestamps();

            // Foreign keys
            $table->foreign('framework_code')
                ->references('code')
                ->on('frameworks')
                ->cascadeOnDelete();
            $table->foreign('task_category_code')
                ->references('code')
                ->on('task_categories')
                ->cascadeOnDelete();

            // Indexes & constraints
            $table->unique(['framework_code', 'task_category_code'], 'framework_task_unique');
            $table->index('framework_code');
            $table->index('task_category_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('framework_task_categories');
    }
};
