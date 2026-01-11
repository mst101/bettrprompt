<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * When SEED_ACTIVE_ONLY environment variable is set to 'true',
     * only seeds languages marked as active=1 in the CSV.
     * This optimises test seeding from 45 languages to 5.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/languages.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle);

        $activeOnly = getenv('SEED_ACTIVE_ONLY') === 'true';

        while ($row = fgetcsv($handle)) {
            $isActive = (bool) $row[2];

            // Skip inactive languages if activeOnly mode is enabled
            if ($activeOnly && ! $isActive) {
                continue;
            }

            DB::table('languages')->insertOrIgnore([
                'id' => $row[0],
                'name' => $row[1],
                'active' => $isActive,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($handle);
    }
}
