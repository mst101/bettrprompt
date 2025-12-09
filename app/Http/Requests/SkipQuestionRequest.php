<?php

namespace App\Http\Requests;

class SkipQuestionRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_index' => 'required|integer|min:0',
        ];
    }
}
