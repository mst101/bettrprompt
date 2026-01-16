<?php

namespace App\Services;

use App\Models\PromptRun;
use App\Models\QuestionAnalytic;
use Illuminate\Support\Facades\Log;

class QuestionAnalyticsService
{
    /**
     * Record a question presentation event
     *
     * Called when a question is shown to the user
     */
    public function recordPresentation(
        PromptRun $promptRun,
        string $visitorId,
        ?int $userId,
        string $questionId,
        string $questionCategory,
        ?string $personalityVariant = null,
        int $displayOrder = 0,
        bool $wasRequired = false,
    ): QuestionAnalytic {
        try {
            $analytic = QuestionAnalytic::create([
                'prompt_run_id' => $promptRun->id,
                'visitor_id' => $visitorId,
                'user_id' => $userId,
                'question_id' => $questionId,
                'question_category' => $questionCategory,
                'personality_variant' => $personalityVariant,
                'display_order' => $displayOrder,
                'was_required' => $wasRequired,
                'response_status' => 'not_shown',
                'presented_at' => now(),
            ]);

            Log::info('Question presentation recorded', [
                'prompt_run_id' => $promptRun->id,
                'question_id' => $questionId,
                'category' => $questionCategory,
            ]);

            return $analytic;
        } catch (\Exception $e) {
            Log::error('Failed to record question presentation', [
                'prompt_run_id' => $promptRun->id,
                'question_id' => $questionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Record a question response (answered)
     *
     * Called when a user answers a question
     */
    public function recordResponse(
        QuestionAnalytic $analytic,
        int $responseLength = 0,
        ?int $timeToAnswerMs = null,
    ): QuestionAnalytic {
        try {
            $analytic->update([
                'response_status' => 'answered',
                'response_length' => $responseLength,
                'time_to_answer_ms' => $timeToAnswerMs,
            ]);

            Log::info('Question response recorded', [
                'analytic_id' => $analytic->id,
                'question_id' => $analytic->question_id,
                'response_length' => $responseLength,
            ]);

            return $analytic->refresh();
        } catch (\Exception $e) {
            Log::error('Failed to record question response', [
                'analytic_id' => $analytic->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Record a question skip event
     *
     * Called when a user skips a question
     */
    public function recordSkip(QuestionAnalytic $analytic, ?int $timeBeforeSkipMs = null): QuestionAnalytic
    {
        try {
            $analytic->update([
                'response_status' => 'skipped',
                'time_to_answer_ms' => $timeBeforeSkipMs,
            ]);

            Log::info('Question skip recorded', [
                'analytic_id' => $analytic->id,
                'question_id' => $analytic->question_id,
            ]);

            return $analytic->refresh();
        } catch (\Exception $e) {
            Log::error('Failed to record question skip', [
                'analytic_id' => $analytic->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update question analytic with outcome metrics
     *
     * Called when the prompt is rated or interacted with
     */
    public function updateWithOutcome(
        QuestionAnalytic $analytic,
        ?int $promptRating = null,
        ?bool $promptCopied = null,
    ): QuestionAnalytic {
        try {
            $analytic->update([
                'prompt_rating' => $promptRating ?? $analytic->prompt_rating,
                'prompt_copied' => $promptCopied ?? $analytic->prompt_copied,
            ]);

            Log::info('Question analytic outcome updated', [
                'analytic_id' => $analytic->id,
                'question_id' => $analytic->question_id,
            ]);

            return $analytic->refresh();
        } catch (\Exception $e) {
            Log::error('Failed to update question analytic outcome', [
                'analytic_id' => $analytic->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate answer rate for a question
     */
    public function getAnswerRate(string $questionId): float
    {
        $total = QuestionAnalytic::where('question_id', $questionId)
            ->whereIn('response_status', ['answered', 'skipped'])
            ->count();

        if ($total === 0) {
            return 0;
        }

        $answered = QuestionAnalytic::where('question_id', $questionId)
            ->where('response_status', 'answered')
            ->count();

        return ($answered / $total) * 100;
    }

    /**
     * Calculate skip rate for a question
     */
    public function getSkipRate(string $questionId): float
    {
        $total = QuestionAnalytic::where('question_id', $questionId)
            ->whereIn('response_status', ['answered', 'skipped'])
            ->count();

        if ($total === 0) {
            return 0;
        }

        $skipped = QuestionAnalytic::where('question_id', $questionId)
            ->where('response_status', 'skipped')
            ->count();

        return ($skipped / $total) * 100;
    }

    /**
     * Get average time to answer for a question
     */
    public function getAverageTimeToAnswer(string $questionId): ?float
    {
        return QuestionAnalytic::where('question_id', $questionId)
            ->where('response_status', 'answered')
            ->whereNotNull('time_to_answer_ms')
            ->avg('time_to_answer_ms');
    }

    /**
     * Get average response length for a question
     */
    public function getAverageResponseLength(string $questionId): ?float
    {
        return QuestionAnalytic::where('question_id', $questionId)
            ->where('response_status', 'answered')
            ->whereNotNull('response_length')
            ->avg('response_length');
    }

    /**
     * Get correlation between answering this question and prompt rating
     */
    public function getAnswerRatingCorrelation(string $questionId): ?float
    {
        $answered = QuestionAnalytic::where('question_id', $questionId)
            ->where('response_status', 'answered')
            ->whereNotNull('prompt_rating')
            ->avg('prompt_rating');

        $skipped = QuestionAnalytic::where('question_id', $questionId)
            ->where('response_status', 'skipped')
            ->whereNotNull('prompt_rating')
            ->avg('prompt_rating');

        if ($answered === null || $skipped === null) {
            return null;
        }

        return $answered - $skipped;
    }

    /**
     * Get question effectiveness summary
     */
    public function getQuestionPerformance(string $questionId): array
    {
        return [
            'question_id' => $questionId,
            'times_shown' => QuestionAnalytic::where('question_id', $questionId)->count(),
            'answer_rate' => $this->getAnswerRate($questionId),
            'skip_rate' => $this->getSkipRate($questionId),
            'average_time_to_answer_ms' => $this->getAverageTimeToAnswer($questionId),
            'average_response_length' => $this->getAverageResponseLength($questionId),
            'answer_rating_correlation' => $this->getAnswerRatingCorrelation($questionId),
        ];
    }

    /**
     * Get least effective questions (high skip rate)
     */
    public function getLeastEffectiveQuestions(int $limit = 5): array
    {
        $questionIds = QuestionAnalytic::distinct('question_id')
            ->pluck('question_id')
            ->toArray();

        $performance = array_map(
            fn ($questionId) => $this->getQuestionPerformance($questionId),
            $questionIds,
        );

        // Sort by skip rate descending
        usort($performance, fn ($a, $b) => $b['skip_rate'] <=> $a['skip_rate']);

        return array_slice($performance, 0, $limit);
    }

    /**
     * Get most effective questions (high answer rate, positive rating correlation)
     */
    public function getMostEffectiveQuestions(int $limit = 5): array
    {
        $questionIds = QuestionAnalytic::distinct('question_id')
            ->pluck('question_id')
            ->toArray();

        $performance = array_map(
            fn ($questionId) => $this->getQuestionPerformance($questionId),
            $questionIds,
        );

        // Sort by combination of answer rate and rating correlation
        usort($performance, function ($a, $b) {
            $scoreA = ($a['answer_rate'] ?? 0) + max(0, $a['answer_rating_correlation'] ?? 0);
            $scoreB = ($b['answer_rate'] ?? 0) + max(0, $b['answer_rating_correlation'] ?? 0);

            return $scoreB <=> $scoreA;
        });

        return array_slice($performance, 0, $limit);
    }

    /**
     * Update question with user rating
     *
     * Called when a user rates an individual question
     */
    public function updateWithRating(
        PromptRun $promptRun,
        string $questionId,
        int $rating,
        ?string $explanation = null,
    ): QuestionAnalytic {
        try {
            $analytic = QuestionAnalytic::where('prompt_run_id', $promptRun->id)
                ->where('question_id', $questionId)
                ->latest()
                ->first();

            if (! $analytic) {
                $presentation = $this->resolveQuestionPresentation(
                    $promptRun,
                    $questionId,
                );

                $analytic = QuestionAnalytic::create([
                    'prompt_run_id' => $promptRun->id,
                    'visitor_id' => $promptRun->visitor_id,
                    'user_id' => $promptRun->user_id,
                    'question_id' => $presentation['question_id'],
                    'question_category' => $presentation['question_category'],
                    'personality_variant' => $presentation['personality_variant'],
                    'display_order' => $presentation['display_order'],
                    'was_required' => $presentation['was_required'],
                    'response_status' => 'not_shown',
                    'presented_at' => now(),
                ]);
            }

            $analytic->update([
                'user_rating' => $rating,
                'rating_explanation' => $explanation,
            ]);

            Log::info('Question rating recorded', [
                'prompt_run_id' => $promptRun->id,
                'question_id' => $questionId,
                'rating' => $rating,
            ]);

            return $analytic->refresh();
        } catch (\Exception $e) {
            Log::error('Failed to update question rating', [
                'prompt_run_id' => $promptRun->id,
                'question_id' => $questionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function resolveQuestionPresentation(
        PromptRun $promptRun,
        string $questionId,
    ): array {
        $questions = $promptRun->framework_questions ?? [];
        $matchedQuestion = null;
        $matchedIndex = null;

        foreach ($questions as $index => $question) {
            if (is_array($question) && ($question['id'] ?? null) === $questionId) {
                $matchedQuestion = $question;
                $matchedIndex = $index;
                break;
            }
        }

        if ($matchedQuestion === null && preg_match('/^Q(\d+)$/', $questionId, $matches)) {
            $index = (int) $matches[1];
            if (array_key_exists($index, $questions)) {
                $matchedQuestion = $questions[$index];
                $matchedIndex = $index;
            }
        }

        $questionCategory = 'framework';
        $personalityVariant = null;
        $wasRequired = true;

        if (is_array($matchedQuestion)) {
            $questionCategory = $matchedQuestion['category'] ?? $questionCategory;
            $personalityVariant = $matchedQuestion['personality_variant'] ?? null;
            if (array_key_exists('required', $matchedQuestion)) {
                $wasRequired = (bool) $matchedQuestion['required'];
            }
        }

        $displayOrder = $matchedIndex !== null ? $matchedIndex + 1 : 0;

        return [
            'question_id' => $questionId,
            'question_category' => $questionCategory,
            'personality_variant' => $personalityVariant,
            'display_order' => $displayOrder,
            'was_required' => $wasRequired,
        ];
    }
}
