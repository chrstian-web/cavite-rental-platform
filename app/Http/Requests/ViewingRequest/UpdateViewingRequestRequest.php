<?php

namespace App\Http\Requests\ViewingRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateViewingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('viewing')->property);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:confirmed,rescheduled,completed,cancelled'],
            'preferred_date' => ['required_if:status,rescheduled', 'nullable', 'date'],
            'preferred_time' => ['required_if:status,rescheduled', 'nullable', 'date_format:H:i'],
            'reschedule_reason' => ['required_if:status,rescheduled', 'nullable', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reschedule_reason.required_if' => 'Please tell the renter why the viewing is being rescheduled.',
            'reschedule_reason.min' => 'The reason is too short. Give the renter a short explanation.',
        ];
    }
}
