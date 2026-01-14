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
        Schema::table('questions', function (Blueprint $table) {
            // Drop indexes first (required before dropping columns)
            $table->dropIndex(['category', 'framework']);
            $table->dropIndex(['category']);
            $table->dropIndex(['framework']);

            // Drop old denormalized columns
            $table->dropColumn(['category', 'framework', 'cognitive_requirements']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Restore old columns
            $table->string('category', 30)->nullable();
            $table->string('framework', 30)->nullable();
            $table->jsonb('cognitive_requirements')->nullable();

            // Restore indexes
            $table->index('category');
            $table->index('framework');
            $table->index(['category', 'framework']);
        });
    }
};
