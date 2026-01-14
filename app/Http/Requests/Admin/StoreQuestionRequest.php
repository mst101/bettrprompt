<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorised to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'max:10', 'unique:questions,id'],
            'questionText' => ['required', 'string'],
            'purpose' => ['required', 'string'],
            'cognitiveRequirements' => ['nullable', 'array'],
            'cognitiveRequirements.*' => ['string'],
            'priority' => ['required', 'string', 'in:high,medium,low'],
            'category' => ['required', 'string'],
            'framework' => ['nullable', 'string'],
            'isUniversal' => ['boolean'],
            'isConditional' => ['boolean'],
            'conditionText' => ['nullable', 'string'],
            'displayOrder' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->id,
            'question_text' => $this->questionText,
            'purpose' => $this->purpose,
            'cognitive_requirements' => $this->cognitiveRequirements,
            'priority' => $this->priority,
            'category' => $this->category,
            'framework' => $this->framework,
            'is_universal' => $this->isUniversal ?? false,
            'is_conditional' => $this->isConditional ?? false,
            'condition_text' => $this->conditionText,
            'display_order' => $this->displayOrder ?? 0,
        ]);
    }
}
