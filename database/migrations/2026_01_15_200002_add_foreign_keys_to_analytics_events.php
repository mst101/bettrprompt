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
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->foreign('visitor_id')
                ->references('id')
                ->on('visitors')
                ->nullOnDelete();

            $table->foreign('session_id')
                ->references('id')
                ->on('analytics_sessions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropForeign(['visitor_id']);
            $table->dropForeign(['session_id']);
        });
    }
};
