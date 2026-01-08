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
            // Privacy encryption fields
            $table->boolean('privacy_enabled')->default(false)->after('prompt_count_reset_at');
            $table->text('encrypted_dek')->nullable()->after('privacy_enabled');
            $table->text('recovery_dek')->nullable()->after('encrypted_dek');
            $table->timestamp('dek_created_at')->nullable()->after('recovery_dek');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'privacy_enabled',
                'encrypted_dek',
                'recovery_dek',
                'dek_created_at',
            ]);
        });
    }
};
