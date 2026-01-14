<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FrameworkTaskCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $csvFile = database_path('seeders/csv/framework_task_categories.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            DB::table('framework_task_categories')->insertOrIgnore([
                'framework_code' => $row[0],
                'task_category_code' => $row[1],
                'suitability' => $row[2],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($handle);
    }
}
