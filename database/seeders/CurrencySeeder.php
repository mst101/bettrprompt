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
     * only seeds currencies needed for whitelisted test countries:
     * GBP, USD, EUR, MXN
     * This optimises test seeding from 155 currencies to 4.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/currencies.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        $activeOnly = getenv('SEED_ACTIVE_ONLY') === 'true';
        $whitelistedCurrencies = ['GBP', 'USD', 'EUR', 'MXN'];
        $records = [];

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            $currencyId = $row[0];
            $isActive = (bool) $row[8];

            // Skip currencies not in whitelist if activeOnly mode is enabled
            if ($activeOnly && ! in_array($currencyId, $whitelistedCurrencies)) {
                continue;
            }

            $records[] = [
                'id' => $currencyId,
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
