<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetMonthlyPromptCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prompts:reset-monthly-counts';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Reset monthly prompt counts for all free tier users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = User::query()
            ->where('subscription_tier', 'free')
            ->where(function ($query) {
                // Reset if new month (last reset was in previous month or null)
                $query->whereNull('prompt_count_reset_at')
                    ->orWhereRaw('EXTRACT(MONTH FROM prompt_count_reset_at) != EXTRACT(MONTH FROM NOW())')
                    ->orWhereRaw('EXTRACT(YEAR FROM prompt_count_reset_at) != EXTRACT(YEAR FROM NOW())');
            })
            ->update([
                'monthly_prompt_count' => 0,
                'prompt_count_reset_at' => now(),
            ]);

        $this->info("Reset prompt counts for {$count} free tier users.");

        return self::SUCCESS;
    }
}
