<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FrameworkSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $csvFile = database_path('seeders/csv/frameworks.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            DB::table('frameworks')->insertOrIgnore([
                'code' => $row[0],
                'name' => $row[1],
                'category' => $row[2],
                'description' => $row[3],
                'complexity' => $row[4],
                'components' => $row[5], // JSON string from CSV
                'best_for' => $row[6] ?: null,
                'not_for' => $row[7] ?: null,
                'is_active' => (bool) $row[8],
                'display_order' => (int) $row[9],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($handle);
    }
}
