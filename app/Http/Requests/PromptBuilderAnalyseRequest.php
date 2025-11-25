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
            'trait_percentages.I_E' => ['nullable', 'integer', 'min:50', 'max:100'],
            'trait_percentages.S_N' => ['nullable', 'integer', 'min:50', 'max:100'],
            'trait_percentages.T_F' => ['nullable', 'integer', 'min:50', 'max:100'],
            'trait_percentages.J_P' => ['nullable', 'integer', 'min:50', 'max:100'],
            'trait_percentages.A_T' => ['nullable', 'integer', 'min:50', 'max:100'],
        ];
    }
}
