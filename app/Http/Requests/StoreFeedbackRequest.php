<?php

namespace App\Http\Requests;

use Illuminate\Support\Str;

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
     * Don't convert camelCase to snake_case - we validate against camelCase fields.
     */
    protected function shouldConvertCamelCase(): bool
    {
        return false;
    }

    /**
     * Get the validated data, converting camelCase keys to snake_case for database storage.
     *
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        $validated = parent::validated();
        $converted = [];

        foreach ($validated as $key => $value) {
            $snakeKey = Str::snake($key);
            $converted[$snakeKey] = $value;
        }

        return $converted;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'experienceLevel' => ['required', 'integer', 'min:1', 'max:7'],
            'usefulness' => ['required', 'integer', 'min:1', 'max:7'],
            'usageIntent' => ['required', 'integer', 'min:1', 'max:7'],
            'suggestions' => ['nullable', 'string', 'max:5000'],
            'desiredFeatures' => ['required', 'array', 'min:1'],
            'desiredFeatures.*' => [
                'string', 'in:templates,compare,api-integration,collaboration,model-specific,document-upload,other',
            ],
            'desiredFeaturesOther' => ['required_if:desiredFeatures.*,other', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'experienceLevel.required' => 'Please select your experience level (Question 1)',
            'usefulness.required' => 'Please rate how useful the app was (Question 2)',
            'usageIntent.required' => 'Please indicate your likelihood to use the app again (Question 3)',
            'desiredFeatures.required' => 'Please select at least one feature you would like to see',
            'desiredFeatures.min' => 'Please select at least one feature you would like to see',
            'desiredFeaturesOther.required_if' => 'Please describe the feature you selected under "Other"',
        ];
    }
}
