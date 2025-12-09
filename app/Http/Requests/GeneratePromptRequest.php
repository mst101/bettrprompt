<?php

namespace App\Http\Requests;

class GeneratePromptRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_answers' => 'required|array',
        ];
    }
}
