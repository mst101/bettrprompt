<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface WorkflowStageResource {
 *     readonly stage: number;
 *     readonly totalExecutions: number;
 *     readonly successful: number;
 *     readonly failed: number;
 *     readonly successRate: number;
 *     readonly avgDurationMs: number | null;
 *     readonly avgCostUsd: number | null;
 * }
 * ```
 */
class WorkflowStageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'stage' => $this['stage'] ?? $this->stage,
            'totalExecutions' => $this['totalExecutions'] ?? $this->totalExecutions,
            'successful' => $this['successful'] ?? $this->successful,
            'failed' => $this['failed'] ?? $this->failed,
            'successRate' => $this['successRate'] ?? $this->successRate,
            'avgDurationMs' => $this['avgDurationMs'] ?? $this->avgDurationMs ?? null,
            'avgCostUsd' => $this['avgCostUsd'] ?? $this->avgCostUsd ?? null,
        ];
    }
}
