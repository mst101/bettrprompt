<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskCategoryCognitiveRequirementSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $csvFile = database_path('seeders/csv/task_category_cognitive_requirements.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            DB::table('task_category_cognitive_requirements')->insertOrIgnore([
                'task_category_code' => $row[0],
                'cognitive_requirement_code' => $row[1],
                'requirement_level' => $row[2],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($handle);
    }
}
