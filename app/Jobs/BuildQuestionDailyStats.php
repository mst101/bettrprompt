<?php

namespace App\Jobs;

use App\Models\QuestionAnalytic;
use App\Models\QuestionDailyStat;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BuildQuestionDailyStats implements ShouldQueue
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
            Log::info('Building question daily stats', [
                'date' => $this->date->toDateString(),
            ]);

            $dayStart = $this->date->clone()->startOfDay();
            $dayEnd = $this->date->clone()->endOfDay();

            // Get all question analytics for the day
            $analytics = QuestionAnalytic::whereBetween('presented_at', [$dayStart, $dayEnd])
                ->get();

            if ($analytics->isEmpty()) {
                Log::info('No question analytics for date', ['date' => $this->date->toDateString()]);

                return;
            }

            // Group by question_id and calculate stats
            $byQuestion = $analytics->groupBy('question_id');

            foreach ($byQuestion as $questionId => $questionAnalytics) {
                $total = $questionAnalytics->count();
                $answered = $questionAnalytics->where('response_status', 'answered')->count();
                $skipped = $questionAnalytics->where('response_status', 'skipped')->count();
                $notShown = $questionAnalytics->where('response_status', 'not_shown')->count();

                // Calculate rates
                $shownCount = $total - $notShown;
                $answerRate = $shownCount > 0 ? ($answered / $shownCount) : 0;
                $skipRate = $shownCount > 0 ? ($skipped / $shownCount) : 0;

                // Calculate averages
                $avgResponseLength = $questionAnalytics->where('response_status', 'answered')
                    ->whereNotNull('response_length')
                    ->avg('response_length');

                $avgTimeToAnswer = $questionAnalytics->where('response_status', 'answered')
                    ->whereNotNull('time_to_answer_ms')
                    ->avg('time_to_answer_ms');

                // Rating correlations
                $ratingWhenAnswered = $questionAnalytics->where('response_status', 'answered')
                    ->whereNotNull('prompt_rating')
                    ->avg('prompt_rating');

                $ratingWhenSkipped = $questionAnalytics->where('response_status', 'skipped')
                    ->whereNotNull('prompt_rating')
                    ->avg('prompt_rating');

                // Copy rate correlation
                $copyRateWhenAnswered = $answered > 0
                    ? ($questionAnalytics->where('response_status', 'answered')
                        ->where('prompt_copied', true)
                        ->count() / $answered)
                    : 0;

                $copyRateWhenSkipped = $skipped > 0
                    ? ($questionAnalytics->where('response_status', 'skipped')
                        ->where('prompt_copied', true)
                        ->count() / $skipped)
                    : 0;

                // Personality variant breakdown
                $byPersonality = $questionAnalytics->groupBy('personality_variant')
                    ->mapWithKeys(function ($group) {
                        $count = $group->count();
                        $answered = $group->where('response_status', 'answered')->count();

                        return [
                            $group->first()->personality_variant => [
                                'times_shown' => $count,
                                'times_answered' => $answered,
                                'answer_rate' => $count > 0 ? ($answered / $count) : 0,
                            ],
                        ];
                    });

                $stat = QuestionDailyStat::updateOrCreate(
                    [
                        'date' => $this->date->toDateString(),
                        'question_id' => $questionId,
                    ],
                    [
                        'times_shown' => $total,
                        'times_answered' => $answered,
                        'times_skipped' => $skipped,
                        'answer_rate' => $answerRate,
                        'skip_rate' => $skipRate,
                        'avg_response_length' => $avgResponseLength,
                        'avg_time_to_answer_ms' => $avgTimeToAnswer,
                        'avg_prompt_rating_when_answered' => $ratingWhenAnswered,
                        'avg_prompt_rating_when_skipped' => $ratingWhenSkipped,
                        'copy_rate_when_answered' => $copyRateWhenAnswered,
                        'copy_rate_when_skipped' => $copyRateWhenSkipped,
                        'by_personality_variant' => $byPersonality,
                    ],
                );

                Log::info('Question daily stat created/updated', [
                    'question_id' => $questionId,
                    'date' => $this->date->toDateString(),
                    'times_shown' => $total,
                    'answer_rate' => round($answerRate * 100, 2).'%',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to build question daily stats', [
                'date' => $this->date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
