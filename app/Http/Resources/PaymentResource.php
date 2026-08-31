<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->rental_contract_id,
            'property_name' => $this->whenLoaded('contract', fn () => $this->contract->property->name),
            'amount' => (float) $this->amount,
            'due_date' => $this->due_date->toDateString(),
            'payment_date' => $this->payment_date?->toDateString(),
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
        ];
    }
}
