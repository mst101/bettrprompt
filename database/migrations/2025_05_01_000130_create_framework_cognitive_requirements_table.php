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
        Schema::create('framework_cognitive_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('framework_code');
            $table->string('cognitive_requirement_code');
            $table->enum('support_level', ['primary', 'secondary']);
            $table->timestamps();

            // Foreign keys
            $table->foreign('framework_code')
                ->references('code')
                ->on('frameworks')
                ->cascadeOnDelete();
            $table->foreign('cognitive_requirement_code')
                ->references('code')
                ->on('cognitive_requirements')
                ->cascadeOnDelete();

            // Indexes & constraints
            $table->unique(['framework_code', 'cognitive_requirement_code'], 'framework_cog_req_unique');
            $table->index('framework_code');
            $table->index('cognitive_requirement_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('framework_cognitive_requirements');
    }
};
