<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('UPDATE countries SET id = LOWER(id)');
        DB::statement('UPDATE users SET country_code = LOWER(country_code) WHERE country_code IS NOT NULL');
        DB::statement('UPDATE visitors SET country_code = LOWER(country_code) WHERE country_code IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('UPDATE countries SET id = UPPER(id)');
        DB::statement('UPDATE users SET country_code = UPPER(country_code) WHERE country_code IS NOT NULL');
        DB::statement('UPDATE visitors SET country_code = UPPER(country_code) WHERE country_code IS NOT NULL');
    }
};
