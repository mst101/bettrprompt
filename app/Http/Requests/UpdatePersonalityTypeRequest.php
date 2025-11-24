<?php

namespace App\Http\Requests;

class UpdatePersonalityTypeRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'personalityType' => ['nullable', 'string', 'max:6', 'regex:/^[A-Z]{4}-[AT]$/'],
            'traitPercentages' => ['nullable', 'array'],
            'traitPercentages.mind' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.energy' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.nature' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.tactics' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.identity' => ['nullable', 'integer', 'min:50', 'max:100'],
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
