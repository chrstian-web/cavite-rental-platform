<?php

namespace App\Http\Requests\MaintenanceRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('maintenanceRequest')->property);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:submitted,in_progress,resolved,closed'],
        ];
    }
}
