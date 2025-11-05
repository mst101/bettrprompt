<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     * Automatically converts camelCase keys to snake_case.
     */
    protected function prepareForValidation(): void
    {
        if ($this->shouldConvertCamelCase()) {
            $this->merge($this->convertCamelCaseToSnakeCase($this->all()));
        }
    }

    /**
     * Determine if camelCase conversion should occur.
     * Override in child classes to disable conversion if needed.
     */
    protected function shouldConvertCamelCase(): bool
    {
        return true;
    }

    /**
     * Convert all camelCase keys to snake_case recursively.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function convertCamelCaseToSnakeCase(array $data): array
    {
        $converted = [];

        foreach ($data as $key => $value) {
            $snakeKey = Str::snake($key);
            $converted[$snakeKey] = is_array($value)
                ? $this->convertCamelCaseToSnakeCase($value)
                : $value;
        }

        return $converted;
    }
}
