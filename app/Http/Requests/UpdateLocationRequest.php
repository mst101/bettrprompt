<?php

namespace App\Http\Requests;

use DateTimeZone;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country_code' => ['nullable', 'string', 'size:2'],
            'region' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'language_code' => ['nullable', 'string', 'max:5'],
        ];
    }
}
