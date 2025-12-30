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
            'team_size' => ['nullable', 'in:solo,small,medium,large'],
            'team_role' => ['nullable', 'in:individual,lead,manager,director,executive'],
            'work_mode' => ['nullable', 'in:office,hybrid,remote,freelance'],
        ];
    }
}
