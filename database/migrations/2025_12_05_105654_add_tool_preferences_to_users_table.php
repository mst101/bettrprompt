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
            $table->json('preferred_tools')->nullable()->after('work_mode');
            $table->string('primary_programming_language')->nullable()->after('preferred_tools');
            $table->unsignedTinyInteger('profile_completion_percentage')->default(0)->after('primary_programming_language');
            $table->timestamp('profile_last_updated_at')->nullable()->after('profile_completion_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_tools',
                'primary_programming_language',
                'profile_completion_percentage',
                'profile_last_updated_at',
            ]);
        });
    }
};
