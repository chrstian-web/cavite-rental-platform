<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property' => ['id' => $this->property->id, 'name' => $this->property->name],
            'rental_space' => ['id' => $this->rentalSpace->id, 'space_number' => $this->rentalSpace->space_number],
            'monthly_rent' => (float) $this->monthly_rent,
            'security_deposit' => (float) $this->security_deposit,
            'advance_payment' => (float) $this->advance_payment,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'status' => $this->status,
            'pdf_url' => route('tenant.contracts.pdf', $this->id),
        ];
    }
}
