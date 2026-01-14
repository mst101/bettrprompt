<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionCognitiveRequirementSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/question_cognitive_requirements.csv');

        if (! file_exists($csvFile)) {
            return;
        }

        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        $requirementsToInsert = [];

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            if (empty($row[0])) {
                continue;
            }

            $requirementsToInsert[] = [
                'question_id' => $row[0],
                'cognitive_requirement_code' => $row[1],
                'requirement_level' => $row[2] ?? 'primary',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        // Insert all relationships using insertOrIgnore to avoid duplicates
        if (! empty($requirementsToInsert)) {
            DB::table('question_cognitive_requirements')->insertOrIgnore($requirementsToInsert);
        }
    }
}
