<?php

namespace App\Http\Requests\VirtualTour;

use Illuminate\Foundation\Http\FormRequest;

class StoreSceneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('property'));
    }

    public function rules(): array
    {
        $panoramaRule = $this->isMethod('post')
            ? ['required', 'image', 'max:10240'] // required on create
            : ['nullable', 'image', 'max:10240']; // optional on edit (keep existing if not replaced)

        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'panorama_image' => $panoramaRule,
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
