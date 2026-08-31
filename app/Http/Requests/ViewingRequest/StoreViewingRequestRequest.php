<?php

namespace App\Http\Requests\ViewingRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreViewingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTenant();
    }

    public function rules(): array
    {
        return [
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_time' => ['required', 'date_format:H:i'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
