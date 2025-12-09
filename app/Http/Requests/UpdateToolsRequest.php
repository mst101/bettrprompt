<?php

namespace App\Http\Requests;

class UpdateToolsRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'preferredTools' => ['nullable', 'array'],
            'preferredTools.*' => ['string'],
            'primaryProgrammingLanguage' => ['nullable', 'string', 'max:50'],
        ];
    }
}
