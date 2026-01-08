<?php

namespace App\Http\Requests;

class AnswerQuestionRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorise that the user owns the prompt run
        $promptRun = $this->route('promptRun');

        return $promptRun && $promptRun->user_id === auth()->id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_index' => 'required|integer|min:0',
            'answer' => 'nullable|string|max:1000',
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
            'answer.required' => 'Please provide an answer to the question.',
            'answer.max' => __('messages.form.answer_max'),
        ];
    }
}
