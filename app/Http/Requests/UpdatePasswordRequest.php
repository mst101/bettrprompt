<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }

    /**
     * Get custom attributes for validator errors (for better error messages).
     */
    public function attributes(): array
    {
        return [
            'current_password' => __('messages.form.current_password'),
            'password' => __('messages.form.password'),
            'password_confirmation' => __('messages.form.password_confirmation'),
        ];
    }
}
