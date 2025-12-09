<?php

namespace App\Http\Requests;

class UpdateQuickQueriesRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'answers' => 'required|array',
            'answers.*' => 'required|string',
        ];
    }
}
