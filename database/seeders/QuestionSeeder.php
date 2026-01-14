<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * When SEED_ACTIVE_ONLY environment variable is set to 'true',
     * only seeds a whitelist of 7 core questions for faster test runs:
     * U1, U2, U3, COS1, COS2, D1, D2
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/questions.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        $activeOnly = getenv('SEED_ACTIVE_ONLY') === 'true';
        $whitelistedQuestions = ['U1', 'U2', 'U3', 'COS1', 'COS2', 'D1', 'D2'];

        $questionsToInsert = [];

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            $questionId = $row[0];

            // Skip questions not in whitelist if activeOnly mode is enabled
            if ($activeOnly && ! in_array($questionId, $whitelistedQuestions)) {
                continue;
            }

            $questionsToInsert[] = [
                'id' => $questionId,
                'question_text' => $row[1],
                'purpose' => $row[2],
                'priority' => $row[3],
                'task_category_code' => $row[4] ?: null,
                'framework_code' => $row[5] ?: null,
                'is_universal' => (bool) $row[6],
                'is_conditional' => (bool) $row[7],
                'condition_text' => $row[8] ?: null,
                'display_order' => (int) $row[9],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        // Insert all questions using upsert (update if exists, insert if not)
        if (! empty($questionsToInsert)) {
            DB::table('questions')->upsert(
                $questionsToInsert,
                ['id'],
                ['question_text', 'purpose', 'priority', 'task_category_code', 'framework_code', 'is_universal', 'is_conditional', 'condition_text', 'display_order', 'updated_at']
            );
        }
    }
}
