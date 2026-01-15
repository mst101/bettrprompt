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
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropColumn([
                'browser',
                'os',
                'received_at',
                'created_at',
                'updated_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();
        });
    }
};
