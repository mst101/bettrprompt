<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromptRunRequest extends FormRequest
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
            'personality_type' => 'required|string|max:6',
            'trait_percentages' => 'nullable|array',
            'task_description' => 'required|string|min:10',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'personality_type.required' => 'Please select your personality type.',
            'personality_type.max' => 'The personality type format is invalid.',
            'task_description.required' => 'Please describe the task you want to accomplish.',
            'task_description.min' => 'The task description must be at least 10 characters.',
        ];
    }
}
