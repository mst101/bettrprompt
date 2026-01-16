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
            $table->enum('question_display_mode', ['one-at-a-time', 'show-all'])
                ->default('one-at-a-time')
                ->after('ui_complexity');
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->enum('question_display_mode', ['one-at-a-time', 'show-all'])
                ->default('one-at-a-time')
                ->after('ui_complexity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('question_display_mode');
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn('question_display_mode');
        });
    }
};
