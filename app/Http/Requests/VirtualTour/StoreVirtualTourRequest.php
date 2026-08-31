<?php

namespace App\Http\Requests\VirtualTour;

use Illuminate\Foundation\Http\FormRequest;

class StoreVirtualTourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('property'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
