<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $csvFile = database_path('seeders/csv/task_categories.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            DB::table('task_categories')->insertOrIgnore([
                'code' => $row[0],
                'name' => $row[1],
                'description' => $row[2],
                'triggers' => $row[3], // JSON string from CSV
                'is_active' => (bool) $row[4],
                'display_order' => (int) $row[5],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($handle);
    }
}
