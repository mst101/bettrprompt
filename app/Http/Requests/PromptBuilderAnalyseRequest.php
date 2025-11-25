<?php

namespace App\Http\Requests;

class PromptBuilderAnalyseRequest extends BaseFormRequest
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
            'task_description' => ['required', 'string', 'min:10', 'max:5000'],
            'personality_type' => ['nullable', 'string', 'regex:/^[EI][NS][TF][JP]-[AT]$/'],
            'trait_percentages' => ['nullable', 'array'],
            'trait_percentages.mind' => ['nullable', 'integer', 'min:50', 'max:100'],
            'trait_percentages.energy' => ['nullable', 'integer', 'min:50', 'max:100'],
            'trait_percentages.nature' => ['nullable', 'integer', 'min:50', 'max:100'],
            'trait_percentages.tactics' => ['nullable', 'integer', 'min:50', 'max:100'],
            'trait_percentages.identity' => ['nullable', 'integer', 'min:50', 'max:100'],
        ];
    }
}
