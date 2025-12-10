<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL: We need to alter the enum type default value using raw SQL
        // since Laravel's enum().change() doesn't handle this properly
        if (Schema::hasColumn('visitors', 'ui_complexity')) {
            DB::statement("ALTER TABLE visitors ALTER COLUMN ui_complexity SET DEFAULT 'advanced'");
        }

        // Update existing visitor records that have 'simple' as their ui_complexity
        // to 'advanced' so they align with the new default
        DB::table('visitors')
            ->where('ui_complexity', 'simple')
            ->update(['ui_complexity' => 'advanced']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the default value back to 'simple'
        if (Schema::hasColumn('visitors', 'ui_complexity')) {
            DB::statement("ALTER TABLE visitors ALTER COLUMN ui_complexity SET DEFAULT 'simple'");
        }

        // Revert existing visitor records back to 'simple'
        DB::table('visitors')
            ->where('ui_complexity', 'advanced')
            ->update(['ui_complexity' => 'simple']);
    }
};
