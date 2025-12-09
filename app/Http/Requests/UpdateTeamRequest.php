<?php

namespace App\Http\Requests;

class UpdateTeamRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'teamSize' => ['nullable', 'in:solo,small,medium,large'],
            'teamRole' => ['nullable', 'in:individual,lead,manager,director,executive'],
            'workMode' => ['nullable', 'in:office,hybrid,remote,freelance'],
        ];
    }
}
