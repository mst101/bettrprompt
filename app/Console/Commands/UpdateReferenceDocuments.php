<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class UpdateReferenceDocuments extends Command
{
    protected $signature = 'reference:clear-cache';

    protected $description = 'Clear cached reference documents';

    public function handle(): int
    {
        $documents = [
            'framework_taxonomy.md',
            'personality_calibration.md',
            'question_bank.md',
            'prompt_templates.md',
        ];

        foreach ($documents as $doc) {
            Cache::forget("reference_doc_{$doc}");
            $this->info("Cleared cache for: {$doc}");
        }

        $this->info('All reference document caches cleared.');

        return Command::SUCCESS;
    }
}
