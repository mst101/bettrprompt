<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rules;

class RegisterRequest extends BaseFormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('messages.form.name_required'),
            'email.required' => __('messages.form.email_required'),
            'email.email' => __('messages.form.email_email'),
            'email.unique' => __('messages.form.email_unique'),
            'password.required' => __('messages.form.password_required'),
            'password.confirmed' => __('messages.form.password_confirmed'),
        ];
    }
}
