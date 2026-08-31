<?php

namespace App\Http\Requests\MaintenanceRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTenant();
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'in:plumbing,electrical,internet,furniture,air_conditioning,cleaning,security,other'],
            'description' => ['required', 'string', 'max:2000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'max:5120'],
        ];
    }
}
