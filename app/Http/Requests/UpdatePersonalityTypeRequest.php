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
        parent::prepareForValidation();

        $traitPercentages = $this->traitPercentages;

        // Normalise numeric strings to integers and drop empty arrays to null
        if (is_array($traitPercentages)) {
            $traitPercentages = collect($traitPercentages)
                ->map(fn ($value) => is_numeric($value) ? (int) $value : $value)
                ->filter(fn ($value) => ! is_null($value))
                ->whenEmpty(fn () => null);

            $traitPercentages = $traitPercentages instanceof \Illuminate\Support\Collection
                ? $traitPercentages->all()
                : $traitPercentages;
        }

        // Convert camelCase to snake_case for database
        $this->merge([
            'personalityType' => $this->personalityType,
            'personality_type' => $this->personalityType,
            'traitPercentages' => $traitPercentages,
            'trait_percentages' => $traitPercentages,
        ]);
    }
}
