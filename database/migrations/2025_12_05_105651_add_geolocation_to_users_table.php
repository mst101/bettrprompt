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
            $table->string('country_code', 2)->nullable()->after('ui_complexity');
            $table->string('country_name')->nullable()->after('country_code');
            $table->string('region')->nullable()->after('country_name');
            $table->string('city')->nullable()->after('region');
            $table->string('timezone')->nullable()->after('city');
            $table->string('currency_code', 3)->nullable()->after('timezone');
            $table->decimal('latitude', 10, 8)->nullable()->after('currency_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('language_code', 5)->nullable()->after('longitude');
            $table->timestamp('location_detected_at')->nullable()->after('language_code');
            $table->boolean('location_manually_set')->default(false)->after('location_detected_at');
            $table->boolean('language_manually_set')->default(false)->after('location_manually_set');

            // Indexes for common queries
            $table->index('country_code');
            $table->index('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['country_code']);
            $table->dropIndex(['timezone']);
            $table->dropColumn([
                'country_code',
                'country_name',
                'region',
                'city',
                'timezone',
                'currency_code',
                'latitude',
                'longitude',
                'language_code',
                'location_detected_at',
                'location_manually_set',
                'language_manually_set',
            ]);
        });
    }
};
