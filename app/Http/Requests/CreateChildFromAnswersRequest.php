<?php

namespace App\Http\Requests;

class CreateChildFromAnswersRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clarifying_answers' => 'required|array',
        ];
    }
}
