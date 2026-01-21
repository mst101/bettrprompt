<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface ErrorAnalyticsResource {
 *     readonly errorCode: string;
 *     readonly count: number;
 *     readonly percentage: number;
 *     readonly message: string;
 * }
 * ```
 */
class ErrorAnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'errorCode' => $this['errorCode'] ?? $this['error_code'] ?? $this->error_code,
            'count' => $this['count'] ?? $this->count,
            'percentage' => $this['percentage'] ?? $this->percentage,
            'message' => $this['message'] ?? $this->message,
        ];
    }
}
