<?php

namespace App\Http\Requests;

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
            'timezone' => ['nullable', 'string'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'language_code' => ['nullable', 'string', 'max:5'],
        ];
    }
}
