<?php

namespace App\Http\Requests;

class CreateChildWithFrameworkRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'framework_code' => 'required|string',
        ];
    }
}
