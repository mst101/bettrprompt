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
        Schema::table('prompt_runs', function (Blueprint $table) {
            $table->json('error_context')->nullable()->after('error_message');
            $table->integer('retry_count')->default(0)->after('error_context');
            $table->timestamp('last_error_at')->nullable()->after('retry_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prompt_runs', function (Blueprint $table) {
            $table->dropColumn(['error_context', 'retry_count', 'last_error_at']);
        });
    }
};
