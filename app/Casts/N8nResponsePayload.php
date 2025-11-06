<?php

namespace App\Casts;

use App\ValueObjects\N8nErrorResponse;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class N8nResponsePayload implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return null;
        }

        // Check if this is an error response
        if (isset($decoded['success']) && $decoded['success'] === false) {
            return N8nErrorResponse::fromArray($decoded);
        }

        // For success responses, return as array
        return $decoded;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string|null
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof N8nErrorResponse) {
            return json_encode($value->toArray());
        }

        return json_encode($value);
    }
}
