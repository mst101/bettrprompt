<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @see \App\Models\ClaudeModel
 *
 * TypeScript interface:
 * ```typescript
 * interface ClaudeModelResource {
 *     readonly id: string;
 *     readonly name: string;
 *     readonly tier: 'haiku' | 'sonnet' | 'opus';
 *     readonly version: number;
 *     readonly inputCostPerMtok: string;
 *     readonly outputCostPerMtok: string;
 *     readonly releaseDate: string | null;
 *     readonly active: boolean;
 *     readonly positioning: string | null;
 *     readonly contextWindowInput: number | null;
 *     readonly contextWindowOutput: number | null;
 * }
 * ```
 */
class ClaudeModelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tier' => $this->tier,
            'version' => $this->version,
            'inputCostPerMtok' => $this->input_cost_per_mtok,
            'outputCostPerMtok' => $this->output_cost_per_mtok,
            'releaseDate' => $this->release_date?->toDateString(),
            'active' => $this->active,
            'positioning' => $this->positioning,
            'contextWindowInput' => $this->context_window_input,
            'contextWindowOutput' => $this->context_window_output,
        ];
    }
}
