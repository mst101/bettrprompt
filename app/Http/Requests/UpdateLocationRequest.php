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
            'countryCode' => ['nullable', 'string', 'size:2'],
            'region' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string'],
            'currencyCode' => ['nullable', 'string', 'size:3'],
            'languageCode' => ['nullable', 'string', 'max:5'],
        ];
    }
}
