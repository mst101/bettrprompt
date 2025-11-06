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
        Schema::table('users', function (Blueprint $table) {
            $table->string('personality_type', 6)->nullable()->after('email'); // e.g., INTJ-A, ENFP-T
            $table->json('trait_percentages')->nullable()->after('personality_type'); // Store trait percentages
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['personality_type', 'trait_percentages']);
        });
    }
};
