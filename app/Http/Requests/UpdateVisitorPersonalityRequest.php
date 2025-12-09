<?php

namespace App\Http\Requests;

class UpdateVisitorPersonalityRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'personalityType' => ['required', 'string', 'max:255'],
            'traitPercentages' => ['nullable', 'array'],
            'traitPercentages.mind' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.energy' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.nature' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.tactics' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.identity' => ['nullable', 'integer', 'min:50', 'max:100'],
        ];
    }
}
