<?php

namespace App\Http\Requests;

class UpdateProfessionalRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'job_title' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'experience_level' => ['nullable', 'in:entry,mid,senior,expert'],
            'company_size' => ['nullable', 'in:solo,small,medium,large,enterprise'],
        ];
    }
}
