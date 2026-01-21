<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface ApiUsageResource {
 *     readonly model: string;
 *     readonly inputTokens: number;
 *     readonly outputTokens: number;
 * }
 * ```
 */
class ApiUsageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'model' => $this['model'] ?? $this->model,
            'inputTokens' => $this['input_tokens'] ?? $this->input_tokens,
            'outputTokens' => $this['output_tokens'] ?? $this->output_tokens,
        ];
    }
}
