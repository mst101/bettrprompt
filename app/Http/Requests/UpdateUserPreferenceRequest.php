<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_display_mode' => ['sometimes', 'in:one-at-a-time,show-all'],
            'ui_complexity' => ['sometimes', 'in:simple,advanced'],
        ];
    }
}
