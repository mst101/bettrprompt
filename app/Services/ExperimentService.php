<?php

namespace App\Services;

use App\Models\Experiment;
use App\Models\ExperimentAssignment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExperimentService
{
    /**
     * Cache key prefix for experiment assignments
     */
    private const ASSIGNMENT_CACHE_KEY = 'experiment_assignment:';

    /**
     * Cache TTL (request lifetime)
     */
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get or create an assignment for a visitor to an experiment
     *
     * @return array{variant_slug: string, variant_id: int, config: array|null}|null
     */
    public function getOrCreateAssignment(
        Experiment $experiment,
        string $visitorId,
        ?int $userId = null,
    ): ?array {
        // Check if assignment exists in cache
        $cacheKey = self::ASSIGNMENT_CACHE_KEY.$experiment->id.':'.$visitorId;
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        // Check database for existing assignment
        $assignment = ExperimentAssignment::where('experiment_id', $experiment->id)
            ->where('visitor_id', $visitorId)
            ->with('variant')
            ->first();

        if ($assignment) {
            $result = [
                'variant_slug' => $assignment->variant->slug,
                'variant_id' => $assignment->variant->id,
                'config' => $assignment->variant->config,
            ];

            Cache::put($cacheKey, $result, self::CACHE_TTL);

            return $result;
        }

        // Create new assignment if visitor should be included
        $assignment = $this->createAssignment($experiment, $visitorId, $userId);

        if (! $assignment) {
            return null;
        }

        $result = [
            'variant_slug' => $assignment->variant->slug,
            'variant_id' => $assignment->variant->id,
            'config' => $assignment->variant->config,
        ];

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Create a new assignment for a visitor
     */
    private function createAssignment(
        Experiment $experiment,
        string $visitorId,
        ?int $userId = null,
    ): ?ExperimentAssignment {
        // Check if visitor qualifies based on traffic percentage
        if (! $this->shouldIncludeVisitor($experiment, $visitorId)) {
            return null;
        }

        // Check targeting rules
        if (! $this->matchesTargetingRules($experiment, $visitorId)) {
            return null;
        }

        // Deterministically bucket visitor into variant
        $variant = $this->getBucketedVariant($experiment, $visitorId);

        if (! $variant) {
            return null;
        }

        // Create assignment
        $assignment = ExperimentAssignment::create([
            'experiment_id' => $experiment->id,
            'variant_id' => $variant->id,
            'visitor_id' => $visitorId,
            'user_id' => $userId,
            'assigned_at' => now(),
            'segment_snapshot' => $this->captureSegmentSnapshot($visitorId),
        ]);

        Log::info('Experiment assignment created', [
            'experiment_id' => $experiment->id,
            'visitor_id' => $visitorId,
            'variant_id' => $variant->id,
        ]);

        return $assignment;
    }

    /**
     * Determine if visitor should be included based on traffic percentage
     */
    private function shouldIncludeVisitor(Experiment $experiment, string $visitorId): bool
    {
        if ($experiment->traffic_percentage >= 100) {
            return true;
        }

        // Use consistent hash of visitor_id to determine allocation
        $hash = crc32($visitorId.$experiment->id);
        $normalized = ($hash % 100) + 1; // 1-100

        return $normalized <= $experiment->traffic_percentage;
    }

    /**
     * Check if visitor matches experiment targeting rules
     */
    private function matchesTargetingRules(Experiment $experiment, string $visitorId): bool
    {
        if (empty($experiment->targeting_rules)) {
            return true;
        }

        // For now, accept all (targeting rules evaluation is extension point)
        // In production, implement rule evaluation based on visitor attributes
        return true;
    }

    /**
     * Get the variant for this visitor (deterministic bucketing)
     */
    private function getBucketedVariant(Experiment $experiment, string $visitorId)
    {
        $variants = $experiment->variants()->get();

        if ($variants->isEmpty()) {
            Log::warning('Experiment has no variants', ['experiment_id' => $experiment->id]);

            return null;
        }

        // Calculate total weight
        $totalWeight = $variants->sum('weight');

        // Deterministic hash-based bucketing
        $hash = crc32($visitorId.$experiment->slug);
        $bucket = $hash % $totalWeight;

        // Find variant for this bucket
        $currentWeight = 0;
        foreach ($variants as $variant) {
            $currentWeight += $variant->weight;
            if ($bucket < $currentWeight) {
                return $variant;
            }
        }

        // Fallback to last variant (shouldn't reach here)
        return $variants->last();
    }

    /**
     * Capture targeting context at assignment time for audit trail
     */
    private function captureSegmentSnapshot(string $visitorId): array
    {
        // In production, capture relevant visitor attributes
        // For now, return empty snapshot
        return [];
    }

    /**
     * Clear cached assignment for a visitor
     */
    public function clearAssignmentCache(int $experimentId, string $visitorId): void
    {
        $cacheKey = self::ASSIGNMENT_CACHE_KEY.$experimentId.':'.$visitorId;
        Cache::forget($cacheKey);
    }

    /**
     * Get all active experiments a visitor is assigned to
     *
     * @return array<int, array{experiment_id: int, experiment_slug: string, variant_slug: string, variant_id: int, config: array|null}>
     */
    public function getActiveAssignments(string $visitorId): array
    {
        $experiments = Experiment::active()->get();
        $assignments = [];

        foreach ($experiments as $experiment) {
            $assignment = $this->getOrCreateAssignment($experiment, $visitorId);

            if ($assignment) {
                $assignments[] = [
                    'experiment_id' => $experiment->id,
                    'experiment_slug' => $experiment->slug,
                    'variant_slug' => $assignment['variant_slug'],
                    'variant_id' => $assignment['variant_id'],
                    'config' => $assignment['config'],
                ];
            }
        }

        return $assignments;
    }
}
