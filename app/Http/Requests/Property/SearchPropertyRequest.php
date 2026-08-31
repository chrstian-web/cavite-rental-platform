<?php

namespace App\Http\Requests\Property;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchPropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'        => ['nullable', 'string', 'max:255'],
            'location_id'   => ['nullable', 'exists:locations,id'],
            'property_type' => ['nullable', 'string'],
            'min_price'     => ['nullable', 'numeric', 'min:0'],
            'max_price'     => ['nullable', 'numeric', 'gte:min_price'],
            'capacity'      => ['nullable', 'integer', 'min:1'],
            'amenities'     => ['nullable', 'array'],
            'amenities.*'   => ['exists:amenities,id'],
            'availability'  => ['nullable', 'string', 'in:all,available,occupied,pending,under_maintenance'],
        ];
    }
}
