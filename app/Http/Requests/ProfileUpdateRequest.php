<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'personalityType' => ['nullable', 'string', 'max:6', 'regex:/^[A-Z]{4}-[AT]$/'],
            'traitPercentages' => ['nullable', 'array'],
            'traitPercentages.mind' => ['nullable', 'integer', 'min:0', 'max:100'],
            'traitPercentages.energy' => ['nullable', 'integer', 'min:0', 'max:100'],
            'traitPercentages.nature' => ['nullable', 'integer', 'min:0', 'max:100'],
            'traitPercentages.tactics' => ['nullable', 'integer', 'min:0', 'max:100'],
            'traitPercentages.identity' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert camelCase to snake_case for database
        $this->merge([
            'personality_type' => $this->personalityType,
            'trait_percentages' => $this->traitPercentages,
        ]);
    }
}
