<?php

namespace App\Http\Requests\RentalSpace;

use Illuminate\Validation\Rule;

class UpdateRentalSpaceRequest extends StoreRentalSpaceRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('property'));
    }

    public function rules(): array
    {
        $rules = parent::rules();

        // Get the space ID from the route parameter
        $spaceId = $this->route('space')?->id ?? $this->route('space');

        $rules['space_number'] = [
            'required',
            'string',
            'max:50',
            Rule::unique('rental_spaces')
                ->where(function ($query) {
                    return $query->where('property_id', $this->route('property')?->id ?? $this->route('property'));
                })
                ->ignore($spaceId),
        ];

        return $rules;
    }
}
