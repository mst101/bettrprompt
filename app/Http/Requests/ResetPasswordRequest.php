<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules;

class ResetPasswordRequest extends BaseFormRequest
{
    /**
     * Disable camelCase conversion for password reset (token and password_confirmation are sent as-is)
     */
    protected function shouldConvertCamelCase(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }
}
