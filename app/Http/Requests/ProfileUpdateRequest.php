<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'personality_type' => ['nullable', 'string', 'max:6', 'regex:/^[A-Z]{4}-[AT]$/'],
            'trait_percentages' => ['nullable', 'array'],
            'trait_percentages.mind' => ['nullable', 'integer', 'min:0', 'max:100'],
            'trait_percentages.energy' => ['nullable', 'integer', 'min:0', 'max:100'],
            'trait_percentages.nature' => ['nullable', 'integer', 'min:0', 'max:100'],
            'trait_percentages.tactics' => ['nullable', 'integer', 'min:0', 'max:100'],
            'trait_percentages.identity' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
