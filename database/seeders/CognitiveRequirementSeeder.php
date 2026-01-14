<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CognitiveRequirementSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $csvFile = database_path('seeders/csv/cognitive_requirements.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            DB::table('cognitive_requirements')->insertOrIgnore([
                'code' => $row[0],
                'name' => $row[1],
                'description' => $row[2],
                'aligned_traits' => $row[3], // JSON string from CSV
                'opposed_traits' => $row[4], // JSON string from CSV
                'is_active' => (bool) $row[5],
                'display_order' => (int) $row[6],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($handle);
    }
}
