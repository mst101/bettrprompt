<?php

namespace App\Http\Requests;

class StoreFeedbackRequest extends BaseFormRequest
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
            'experience_level' => ['required', 'integer', 'min:1', 'max:7'],
            'usefulness' => ['required', 'integer', 'min:1', 'max:7'],
            'usage_intent' => ['required', 'integer', 'min:1', 'max:7'],
            'suggestions' => ['nullable', 'string', 'max:5000'],
            'desired_features' => ['required', 'array', 'min:1'],
            'desired_features.*' => [
                'string', 'in:templates,compare,api-integration,collaboration,model-specific,document-upload,other',
            ],
            'desired_features_other' => ['required_if:desired_features.*,other', 'nullable', 'string', 'max:500'],
        ];
    }
}
