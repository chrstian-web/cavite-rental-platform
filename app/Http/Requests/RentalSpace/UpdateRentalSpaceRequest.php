<?php

namespace App\Http\Requests\RentalSpace;

class UpdateRentalSpaceRequest extends StoreRentalSpaceRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('property'));
    }
}
