<?php

namespace App\Http\Requests;

class UpdateBudgetRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'budgetConsciousness' => ['nullable', 'in:free_only,free_first,mixed,premium_ok,enterprise'],
        ];
    }
}
