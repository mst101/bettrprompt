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
            $table->uuid('visitor_id')->nullable()->after('user_id');
            $table->foreign('visitor_id')
                ->references('id')
                ->on('visitors')
                ->onDelete('set null');

            $table->index('visitor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prompt_runs', function (Blueprint $table) {
            $table->dropForeign(['visitor_id']);
            $table->dropIndex(['visitor_id']);
            $table->dropColumn('visitor_id');
        });
    }
};
