<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnalyticsEventsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Analytics events from all users (authenticated and guest) are allowed
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
            'events' => 'required|array|min:1',
            'events.*.event_id' => 'required|uuid',
            'events.*.name' => 'required|string|max:100',
            'events.*.occurred_at_ms' => 'required|integer|min:0',
            'events.*.page_path' => 'nullable|string|max:2048',
            'events.*.referrer' => 'nullable|string|max:2048',
            'events.*.properties' => 'nullable|array',
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
            'events.required' => 'At least one event is required',
            'events.min' => 'At least one event must be provided',
            'events.*.event_id.required' => 'Each event must have a unique event_id',
            'events.*.event_id.uuid' => 'Event ID must be a valid UUID',
            'events.*.name.required' => 'Event name is required',
            'events.*.name.max' => 'Event name must not exceed 100 characters',
            'events.*.occurred_at_ms.required' => 'Event timestamp is required',
            'events.*.occurred_at_ms.integer' => 'Event timestamp must be a valid number',
        ];
    }
}
