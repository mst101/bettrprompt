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
        Schema::table('visitors', function (Blueprint $table) {
            // Track current utm values (updated on each visit)
            // Original utm_* columns preserve first-visit attribution
            $table->string('current_utm_source')->nullable()->after('utm_content');
            $table->string('current_utm_medium')->nullable()->after('current_utm_source');
            $table->string('current_utm_campaign')->nullable()->after('current_utm_medium');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn(['current_utm_source', 'current_utm_medium', 'current_utm_campaign']);
        });
    }
};
