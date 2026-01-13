<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * When SEED_ACTIVE_ONLY environment variable is set to 'true',
     * only seeds currencies marked as active=1 in the CSV.
     * This optimises test seeding from 155 currencies to 3 (GBP, EUR, USD).
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/currencies.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        $activeOnly = getenv('SEED_ACTIVE_ONLY') === 'true';
        $records = [];

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            $isActive = (bool) $row[8];

            // Skip inactive currencies if activeOnly mode is enabled
            if ($activeOnly && ! $isActive) {
                continue;
            }

            $records[] = [
                'id' => $row[0],
                'symbol' => $row[1],
                'thousands_separator' => $row[2],
                'decimal_separator' => $row[3],
                'symbol_on_left' => (bool) $row[4],
                'space_between_amount_and_symbol' => (bool) $row[5],
                'rounding_coefficient' => (int) $row[6],
                'decimal_digits' => (int) $row[7],
                'active' => $isActive,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        DB::table('currencies')->upsert(
            $records,
            ['id'],
            [
                'symbol',
                'thousands_separator',
                'decimal_separator',
                'symbol_on_left',
                'space_between_amount_and_symbol',
                'rounding_coefficient',
                'decimal_digits',
                'active',
                'updated_at',
            ]
        );
    }
}
