<?php

namespace App\Jobs;

use App\Models\FrameworkDailyStat;
use App\Models\FrameworkSelection;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BuildFrameworkDailyStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  Carbon|null  $date  Date to aggregate for (defaults to yesterday)
     */
    public function __construct(
        private ?Carbon $date = null,
    ) {
        $this->date = $this->date ?? now()->subDay()->startOfDay();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Building framework daily stats', [
                'date' => $this->date->toDateString(),
            ]);

            $dayStart = $this->date->clone()->startOfDay();
            $dayEnd = $this->date->clone()->endOfDay();

            // Get all framework selections for the day
            $selections = FrameworkSelection::whereBetween('selected_at', [$dayStart, $dayEnd])
                ->get();

            if ($selections->isEmpty()) {
                Log::info('No framework selections for date', ['date' => $this->date->toDateString()]);

                return;
            }

            // Group by framework and calculate stats
            $byRecommended = $selections->groupBy('recommended_framework');
            $byChosen = $selections->groupBy('chosen_framework');

            foreach ($byRecommended as $framework => $frameworkSelections) {
                $chosenCount = $selections->where('chosen_framework', $framework)->count();
                $acceptedCount = $frameworkSelections->where('accepted_recommendation', true)->count();

                // Get personality and task breakdowns
                $byPersonality = $frameworkSelections->groupBy('personality_type')
                    ->mapWithKeys(function ($group) {
                        return [
                            $group->first()->personality_type => [
                                'times_recommended' => $group->count(),
                                'acceptance_rate' => $group->where('accepted_recommendation', true)->count() / $group->count(),
                            ],
                        ];
                    });

                $byTask = $frameworkSelections->groupBy('task_category')
                    ->mapWithKeys(function ($group) {
                        return [
                            $group->first()->task_category => [
                                'times_recommended' => $group->count(),
                                'acceptance_rate' => $group->where('accepted_recommendation', true)->count() / $group->count(),
                            ],
                        ];
                    });

                $avgRating = $frameworkSelections->whereNotNull('prompt_rating')
                    ->avg('prompt_rating');

                $copiedCount = $frameworkSelections->where('prompt_copied', true)->count();
                $editedCount = $frameworkSelections->where('prompt_edited', true)->count();
                $totalChosen = $byChosen->get($framework, collect())->count();

                $stat = FrameworkDailyStat::updateOrCreate(
                    [
                        'date' => $this->date->toDateString(),
                        'framework' => $framework,
                    ],
                    [
                        'times_recommended' => $frameworkSelections->count(),
                        'times_chosen' => $chosenCount,
                        'times_accepted' => $acceptedCount,
                        'acceptance_rate' => $frameworkSelections->count() > 0
                            ? ($acceptedCount / $frameworkSelections->count())
                            : 0,
                        'avg_rating' => $avgRating,
                        'prompts_copied' => $copiedCount,
                        'prompts_edited' => $editedCount,
                        'copy_rate' => $totalChosen > 0 ? ($copiedCount / $totalChosen) : 0,
                        'by_personality_type' => $byPersonality,
                        'by_task_category' => $byTask,
                    ],
                );

                Log::info('Framework daily stat created/updated', [
                    'framework' => $framework,
                    'date' => $this->date->toDateString(),
                    'times_recommended' => $frameworkSelections->count(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to build framework daily stats', [
                'date' => $this->date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
