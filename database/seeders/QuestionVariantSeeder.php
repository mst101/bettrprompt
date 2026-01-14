<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionVariantSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * When SEED_ACTIVE_ONLY environment variable is set to 'true',
     * only seeds variants for whitelisted questions: U2, U4, COS3, SRF1
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/question_variants.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        $activeOnly = getenv('SEED_ACTIVE_ONLY') === 'true';
        $whitelistedVariants = ['U2', 'U4'];

        $variantsToInsert = [];

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            $questionId = $row[0];

            // Skip variants not in whitelist if activeOnly mode is enabled
            if ($activeOnly && ! in_array($questionId, $whitelistedVariants)) {
                continue;
            }

            $variantsToInsert[] = [
                'question_id' => $questionId,
                'personality_pattern' => $row[1],
                'phrasing' => $row[2],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        // Insert all variants using upsert
        if (! empty($variantsToInsert)) {
            DB::table('question_variants')->upsert(
                $variantsToInsert,
                ['question_id', 'personality_pattern'],
                ['phrasing', 'updated_at']
            );
        }
    }
}
