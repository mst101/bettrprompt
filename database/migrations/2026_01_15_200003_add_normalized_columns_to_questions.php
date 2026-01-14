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
            // Add normalized FK columns
            $table->string('task_category_code', 30)->nullable()->after('priority');
            $table->string('framework_code', 30)->nullable()->after('task_category_code');

            // Add foreign keys - use restrictOnDelete to prevent accidental deletion
            $table->foreign('task_category_code')
                ->references('code')
                ->on('task_categories')
                ->restrictOnDelete();

            $table->foreign('framework_code')
                ->references('code')
                ->on('frameworks')
                ->restrictOnDelete();

            // Add indexes for query performance
            $table->index('task_category_code');
            $table->index('framework_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['task_category_code']);
            $table->dropForeign(['framework_code']);
            $table->dropIndex(['task_category_code']);
            $table->dropIndex(['framework_code']);
            $table->dropColumn(['task_category_code', 'framework_code']);
        });
    }
};
